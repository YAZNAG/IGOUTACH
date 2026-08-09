<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferStatus;
use App\Domain\Warehouses\Models\Warehouse;

beforeEach(function (): void {
    foreach ([
        ['in', 1], ['out', -1], ['transfer_in', 1], ['transfer_out', -1], ['adjustment', 0],
    ] as [$code, $sign]) {
        MovementType::firstOrCreate(['code' => $code], ['name' => $code, 'sign' => $sign, 'affects_valuation' => $sign > 0]);
    }

    foreach ([['requested', 'Demandé'], ['refused', 'Refusé'], ['in_transit', 'En transit'], ['received', 'Reçu']] as [$c, $n]) {
        TransferStatus::firstOrCreate(['code' => $c], ['name' => $n]);
    }

    $this->source = Warehouse::factory()->create(['code' => 'SOURCE']);
    $this->mien = Warehouse::factory()->create(['code' => 'MIEN']);
});

function demandeProduit(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

it('enregistre une demande sans déplacer la moindre marchandise', function (): void {
    $user = grantUser(['transfer.request', 'stock.view'], ['warehouse_id' => $this->mien->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $this->actingAs($user)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 30]],
    ])->assertCreated()->assertJsonPath('data.status', 'requested');

    // Le stock source doit être intact : une demande n'est pas un transfert.
    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(100)
        ->and(app(StockReaderInterface::class)->quantityFor($this->mien->id, $product->id))->toBe(0);
});

it('refuse une demande vers le lieu d\'un autre', function (): void {
    $user = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $product = demandeProduit();

    // Se faire livrer chez quelqu'un d'autre n'a pas de sens et ouvrirait la
    // porte à des mouvements non désirés.
    $this->actingAs($user)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->mien->id,
        'to_warehouse_id' => $this->source->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 5]],
    ])->assertStatus(422)->assertJsonValidationErrors('to_warehouse_id');
});

it('déplace le stock seulement à l\'approbation', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $direction = grantUser(['transfer.approve', 'stock.view_global']);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 30]],
    ])->json('data.id');

    $this->actingAs($direction)->postJson("/api/v1/transfers/{$id}/approve")
        ->assertOk()->assertJsonPath('data.status', 'in_transit');

    // La marchandise a quitté la source ; elle arrive à réception.
    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(70);
});

it('interdit au demandeur d\'approuver sa propre demande', function (): void {
    $user = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 50, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($user)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10]],
    ])->json('data.id');

    $this->actingAs($user)->postJson("/api/v1/transfers/{$id}/approve")->assertForbidden();

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(50);
});

it('refuse une demande sans toucher au stock', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $direction = grantUser(['transfer.approve', 'stock.view_global']);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 50, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10]],
    ])->json('data.id');

    $this->actingAs($direction)->postJson("/api/v1/transfers/{$id}/refuse", ['reason' => 'Stock nécessaire sur place'])
        ->assertOk()->assertJsonPath('data.status', 'refused');

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(50)
        ->and(Transfer::withoutGlobalScopes()->find($id)->refusal_reason)->toBe('Stock nécessaire sur place');
});

it('n\'approuve pas deux fois la même demande', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $direction = grantUser(['transfer.approve', 'stock.view_global']);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 20]],
    ])->json('data.id');

    $this->actingAs($direction)->postJson("/api/v1/transfers/{$id}/approve")->assertOk();

    // L'approbation consomme la demande : elle cède la place au transfert
    // réel, seul porteur des mouvements. Un second appel ne trouve plus rien.
    $this->actingAs($direction)->postJson("/api/v1/transfers/{$id}/approve")->assertNotFound();

    // Ce qui compte : une seule sortie de stock, 100 − 20.
    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(80);
});

it('ne laisse plus un responsable créer un transfert direct', function (): void {
    $user = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $product = demandeProduit();

    $this->actingAs($user)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 5]],
    ])->assertForbidden();
});

it('laisse le responsable du lieu SOURCE accorder la demande', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    // Celui qui fournit : c'est son stock qui part, la decision lui revient.
    $fournisseur = grantUser(['transfer.approve'], ['warehouse_id' => $this->source->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 30]],
    ])->json('data.id');

    $this->actingAs($fournisseur)->postJson("/api/v1/transfers/{$id}/approve")->assertOk();

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(70);
});

it('refuse a un tiers d\'arbitrer une demande qui ne le concerne pas', function (): void {
    $ailleurs = Warehouse::factory()->create(['code' => 'AILLEURS']);
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $etranger = grantUser(['transfer.approve'], ['warehouse_id' => $ailleurs->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 30]],
    ])->json('data.id');

    // Ni demandeur ni fournisseur : rien a arbitrer ici.
    $this->actingAs($etranger)->postJson("/api/v1/transfers/{$id}/approve")->assertForbidden();

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(100);
});

it('accorde une quantite reduite a celle que la source peut ceder', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $fournisseur = grantUser(['transfer.approve'], ['warehouse_id' => $this->source->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 100, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 80]],
    ])->json('data.id');

    // On demandait 80, la source n'en cede que 25.
    $this->actingAs($fournisseur)->postJson("/api/v1/transfers/{$id}/approve", [
        'lines' => [['product_id' => $product->id, 'quantity' => 25]],
    ])->assertOk();

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(75);
});

it('retire une ligne ramenee a zero et refuse une demande videe', function (): void {
    $demandeur = grantUser(['transfer.request'], ['warehouse_id' => $this->mien->id]);
    $fournisseur = grantUser(['transfer.approve'], ['warehouse_id' => $this->source->id]);
    $product = demandeProduit();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $product->id,
        'quantity' => 50, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $id = $this->actingAs($demandeur)->postJson('/api/v1/transfer-requests', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 10]],
    ])->json('data.id');

    // Tout a zero : c'est un refus deguise, mieux vaut le dire.
    $this->actingAs($fournisseur)->postJson("/api/v1/transfers/{$id}/approve", [
        'lines' => [['product_id' => $product->id, 'quantity' => 0]],
    ])->assertStatus(422);

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $product->id))->toBe(50);
});

it('ne montre pas les transferts des autres lieux', function (): void {
    $ailleurs = Warehouse::factory()->create(['code' => 'AILLEURS']);
    $moi = grantUser(['stock.view'], ['warehouse_id' => $this->mien->id]);
    $product = demandeProduit();

    $statut = TransferStatus::where('code', 'requested')->first();

    // Un transfert entre deux lieux tiers ne me regarde pas.
    Transfer::withoutGlobalScopes()->create([
        'reference' => 'TR-AILLEURS', 'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $ailleurs->id, 'transfer_status_id' => $statut->id,
    ]);
    Transfer::withoutGlobalScopes()->create([
        'reference' => 'TR-POUR-MOI', 'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->mien->id, 'transfer_status_id' => $statut->id,
    ]);

    $refs = collect($this->actingAs($moi)->getJson('/api/v1/transfers')->json('data'))
        ->pluck('reference');

    expect($refs)->toContain('TR-POUR-MOI')->and($refs)->not->toContain('TR-AILLEURS');
});
