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

/**
 * Transfert en brouillon : enregistre, modifiable, le stock ne bouge qu'a
 * l'envoi.
 */
beforeEach(function (): void {
    foreach ([['in', 1], ['out', -1], ['transfer_in', 1], ['transfer_out', -1]] as [$code, $sign]) {
        MovementType::firstOrCreate(['code' => $code], ['name' => $code, 'sign' => $sign, 'affects_valuation' => $sign > 0]);
    }
    foreach ([['draft', 'Brouillon'], ['in_transit', 'En transit'], ['received', 'Reçu'], ['requested', 'Demandé']] as [$c, $n]) {
        TransferStatus::firstOrCreate(['code' => $c], ['name' => $n]);
    }

    $this->source = Warehouse::factory()->create(['code' => 'SRC']);
    $this->cible = Warehouse::factory()->create(['code' => 'DST']);
    $this->admin = grantUser(['transfer.create', 'stock.view', 'stock.view_global']);
    $this->article = Product::factory()->create([
        'name' => 'TC RECSON LED', 'sku' => 'TC-RECSON-LED', 'cost_price' => 12,
        'category_id' => Category::factory()->create()->id, 'unit_id' => Unit::factory()->create()->id,
    ]);
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->source->id, 'product_id' => $this->article->id,
        'quantity' => 50, 'reserved_quantity' => 0, 'average_cost' => '12.00',
    ]);
});

it('enregistre un brouillon sans toucher au stock, puis le modifie et l\'envoie', function (): void {
    $creation = $this->actingAs($this->admin)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->cible->id,
        'draft' => true,
        'note' => 'à préparer',
        'lines' => [['product_id' => $this->article->id, 'quantity' => 10]],
    ])->assertCreated()->assertJsonPath('data.status', 'draft');

    $id = $creation->json('data.id');
    expect($creation->json('data.reference'))->toStartWith('BRT-')
        ->and(app(StockReaderInterface::class)->quantityFor($this->source->id, $this->article->id))->toBe(50);

    // La liste et la fiche le montrent modifiable.
    $liste = $this->actingAs($this->admin)->getJson('/api/v1/transfers?status=draft')->assertOk();
    expect($liste->json('data.0.id'))->toBe($id)->and($liste->json('data.0.can_edit'))->toBeTrue();

    $fiche = $this->actingAs($this->admin)->getJson("/api/v1/transfers/{$id}")->assertOk();
    expect($fiche->json('data.can_edit'))->toBeTrue()
        ->and($fiche->json('data.lines.0.product_id'))->toBe($this->article->id);

    // Modification : quantité portée à 20, note changée.
    $this->actingAs($this->admin)->putJson("/api/v1/transfers/{$id}", [
        'from_warehouse_id' => $this->source->id,
        'to_warehouse_id' => $this->cible->id,
        'note' => 'prêt',
        'lines' => [['product_id' => $this->article->id, 'quantity' => 20]],
    ])->assertOk()->assertJsonPath('data.lines.0.quantity_sent', 20)->assertJsonPath('data.note', 'prêt');

    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $this->article->id))->toBe(50);

    // Envoi : le stock sort, le bon reçoit son numéro, le brouillon disparaît.
    $envoi = $this->actingAs($this->admin)->postJson("/api/v1/transfers/{$id}/send")->assertOk()
        ->assertJsonPath('data.status', 'in_transit');

    expect($envoi->json('data.reference'))->not->toStartWith('BRT-')
        ->and(app(StockReaderInterface::class)->quantityFor($this->source->id, $this->article->id))->toBe(30)
        ->and(Transfer::query()->find($id))->toBeNull();

    $reel = Transfer::query()->findOrFail($envoi->json('data.id'));
    expect($reel->created_by)->toBe($this->admin->id)
        ->and((int) $reel->lines()->first()->quantity_sent)->toBe(20)
        ->and((float) $reel->lines()->first()->unit_cost)->toBe(12.0);
});

it('supprime un brouillon et refuse de modifier un transfert envoyé', function (): void {
    $id = $this->actingAs($this->admin)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $this->source->id, 'to_warehouse_id' => $this->cible->id, 'draft' => true,
        'lines' => [['product_id' => $this->article->id, 'quantity' => 5]],
    ])->assertCreated()->json('data.id');

    $this->actingAs($this->admin)->deleteJson("/api/v1/transfers/{$id}")->assertOk();
    expect(Transfer::query()->find($id))->toBeNull();

    $envoye = $this->actingAs($this->admin)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $this->source->id, 'to_warehouse_id' => $this->cible->id,
        'lines' => [['product_id' => $this->article->id, 'quantity' => 5]],
    ])->assertCreated()->json('data.id');

    $this->actingAs($this->admin)->putJson("/api/v1/transfers/{$envoye}", [
        'from_warehouse_id' => $this->source->id, 'to_warehouse_id' => $this->cible->id,
        'lines' => [['product_id' => $this->article->id, 'quantity' => 1]],
    ])->assertStatus(422);
    $this->actingAs($this->admin)->deleteJson("/api/v1/transfers/{$envoye}")->assertStatus(422);
});

it('un autre utilisateur ne touche pas au brouillon d\'un collègue', function (): void {
    $auteur = grantUser(['transfer.create', 'stock.view', 'stock.view_global']);
    $autre = grantUser(['transfer.create', 'stock.view'], ['warehouse_id' => $this->source->id]);

    $id = $this->actingAs($auteur)->postJson('/api/v1/transfers', [
        'from_warehouse_id' => $this->source->id, 'to_warehouse_id' => $this->cible->id, 'draft' => true,
        'lines' => [['product_id' => $this->article->id, 'quantity' => 5]],
    ])->assertCreated()->json('data.id');

    $this->actingAs($autre)->postJson("/api/v1/transfers/{$id}/send")->assertStatus(422);
    $this->actingAs($autre)->deleteJson("/api/v1/transfers/{$id}")->assertStatus(422);
    expect(app(StockReaderInterface::class)->quantityFor($this->source->id, $this->article->id))->toBe(50);
});

it('trouve un article par ses mots, dans n\'importe quel ordre', function (): void {
    Product::factory()->create([
        'name' => 'TC REVOLUTION RECSON LED', 'sku' => 'TC-REV-REC-LED', 'cost_price' => 12,
        'category_id' => Category::factory()->create()->id, 'unit_id' => Unit::factory()->create()->id,
    ]);
    Product::factory()->create([
        'name' => 'TV 43 RECSON Q-LED', 'sku' => 'TV-43', 'cost_price' => 12,
        'category_id' => Category::factory()->create()->id, 'unit_id' => Unit::factory()->create()->id,
    ]);
    $user = grantUser(['product.view']);

    $noms = fn (string $q) => collect($this->actingAs($user)->getJson('/api/v1/products?per_page=50&search='.rawurlencode($q))->assertOk()->json('data'))->pluck('name')->sort()->values()->all();

    expect($noms('TC RECSON LED'))->toBe(['TC RECSON LED', 'TC REVOLUTION RECSON LED'])
        ->and($noms('led recson'))->toBe(['TC RECSON LED', 'TC REVOLUTION RECSON LED', 'TV 43 RECSON Q-LED'])
        ->and($noms('TC-RECSON'))->toBe(['TC RECSON LED']);
});
