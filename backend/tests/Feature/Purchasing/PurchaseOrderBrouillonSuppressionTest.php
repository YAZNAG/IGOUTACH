<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Le brouillon comme vrai état de travail, et le ménage des bons qui
 * n'auraient jamais dû exister.
 *
 * Un bon de commande se prépare souvent en plusieurs fois : on ouvre le
 * brouillon, on ajoute des articles au fil des besoins, on l'envoie quand il
 * est prêt. Il faut donc pouvoir l'enregistrer vide. En contrepartie, c'est
 * l'envoi qui exige au moins une ligne — un bon vide chez le fournisseur ne
 * veut rien dire.
 */
function contexteBc(): array
{
    return [Supplier::factory()->create(), Warehouse::factory()->create()];
}

function bcAuStatut(string $code, bool $avecLigne = true): PurchaseOrder
{
    [$fournisseur, $lieu] = contexteBc();

    $order = PurchaseOrder::factory()
        ->for($fournisseur)
        ->for($lieu)
        ->create(['status_id' => PurchaseOrderStatus::where('code', $code)->firstOrFail()->id]);

    if ($avecLigne) {
        $order->lines()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 5,
            'received_quantity' => 0,
            'position' => 0,
        ]);
    }

    return $order;
}

it('enregistre un brouillon encore vide, puis le complète', function (): void {
    $user = grantUser(['purchase.create', 'purchase.view']);
    [$fournisseur, $lieu] = contexteBc();

    $creation = $this->actingAs($user)->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $fournisseur->id,
        'warehouse_id' => $lieu->id,
        'notes' => 'À compléter avec le magasinier.',
        'lines' => [],
    ])->assertCreated()
        ->assertJsonPath('status.code', 'draft')
        // Vide, il ne part pas : le bouton d'envoi doit rester fermé.
        ->assertJsonPath('can_send', false)
        ->assertJsonPath('can_delete', true);

    $id = $creation->json('id');
    expect($creation->json('lines'))->toHaveCount(0);

    // On le reprend et on le complète : il devient envoyable.
    $produit = Product::factory()->create();
    $this->actingAs($user)->putJson("/api/v1/purchase-orders/{$id}", [
        'supplier_id' => $fournisseur->id,
        'warehouse_id' => $lieu->id,
        'notes' => 'À compléter avec le magasinier.',
        'lines' => [['product_id' => $produit->id, 'quantity' => 12]],
    ])->assertOk()
        ->assertJsonPath('can_send', true)
        ->assertJsonPath('lines.0.quantity', 12);
});

it('refuse d’envoyer un brouillon sans article', function (): void {
    $user = grantUser(['purchase.create']);
    $order = bcAuStatut('draft', avecLigne: false);

    $this->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/send")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Ajoutez au moins un article avant d’envoyer le bon de commande.');

    expect($order->refresh()->status()->first()->code)->toBe('draft');
});

it('supprime un brouillon et un bon annulé, avec leurs lignes', function (): void {
    $user = grantUser(['purchase.create']);

    foreach (['draft', 'cancelled'] as $code) {
        $order = bcAuStatut($code);
        $idLigne = $order->lines()->firstOrFail()->id;

        $this->actingAs($user)->deleteJson("/api/v1/purchase-orders/{$order->id}")
            ->assertNoContent();

        expect(PurchaseOrder::query()->whereKey($order->id)->exists())->toBeFalse()
            ->and(DB::table('purchase_order_lines')->where('id', $idLigne)->exists())->toBeFalse();
    }
});

it('garde les bons qui ont vécu : envoyé, reçu, ou porteur d’une réception', function (): void {
    $user = grantUser(['purchase.create']);

    foreach (['sent', 'pending_approval', 'partially_received', 'received'] as $code) {
        $order = bcAuStatut($code);

        $this->actingAs($user)->deleteJson("/api/v1/purchase-orders/{$order->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Seul un bon de commande en brouillon ou annulé peut être supprimé. Annulez-le d’abord.');

        expect(PurchaseOrder::query()->whereKey($order->id)->exists())->toBeTrue();
    }

    // Un bon annulé auquel une réception reste rattachée ne s'efface pas non
    // plus : la réception perdrait son origine.
    $annule = bcAuStatut('cancelled');
    GoodsReceipt::query()->create([
        'number' => 'BR-TEST-001',
        'purchase_order_id' => $annule->id,
        'supplier_id' => $annule->supplier_id,
        'warehouse_id' => $annule->warehouse_id,
        'received_at' => now(),
        'payment_status' => 'unpaid',
        'amount_paid' => 0,
    ]);

    $reponse = $this->actingAs($user)->deleteJson("/api/v1/purchase-orders/{$annule->id}")
        ->assertStatus(422);
    expect($reponse->json('message'))->toContain('une réception y est rattachée');
    expect(PurchaseOrder::query()->whereKey($annule->id)->exists())->toBeTrue();
});

it('réserve la suppression à qui peut créer un bon de commande', function (): void {
    $order = bcAuStatut('cancelled');
    $lecteur = grantUser(['purchase.view']);

    $this->actingAs($lecteur)->deleteJson("/api/v1/purchase-orders/{$order->id}")->assertForbidden();
    expect(PurchaseOrder::query()->whereKey($order->id)->exists())->toBeTrue();
});
