<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\PurchaseOrder;
use App\Domain\Purchasing\Models\PurchaseOrderStatus;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Warehouses\Models\Warehouse;
use Database\Seeders\MovementTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Bon de livraison du fournisseur joint à la réception : une photo ou un
 * PDF, gardé avec le BR et ressorti d'un clic.
 */
beforeEach(function (): void {
    $this->seed(MovementTypeSeeder::class);
    Storage::fake('local');
});

function receptionPourBonLivraison(): GoodsReceipt
{
    $user = grantUser(['receipt.create', 'receipt.view']);
    $order = PurchaseOrder::factory()
        ->for(Supplier::factory()->create())
        ->for(Warehouse::factory()->create())
        ->create(['status_id' => PurchaseOrderStatus::where('code', 'sent')->firstOrFail()->id]);
    $line = $order->lines()->create(['product_id' => Product::factory()->create()->id, 'quantity' => 4, 'position' => 0]);

    $id = test()->actingAs($user)->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
        'received_at' => now()->format('Y-m-d H:i:s'),
        'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 4, 'unit_price' => 10]],
    ])->assertCreated()->json('id');

    return GoodsReceipt::query()->findOrFail($id);
}

it('joint une photo du bon de livraison puis la ressert telle quelle', function (): void {
    $reception = receptionPourBonLivraison();
    $user = grantUser(['receipt.create', 'receipt.view']);

    $reponse = test()->actingAs($user)->post("/api/v1/goods-receipts/{$reception->id}/delivery-note", [
        'file' => UploadedFile::fake()->image('bl-fournisseur.jpg', 800, 600),
    ]);

    $reponse->assertCreated();
    expect($reponse->json('supplier_delivery_note.name'))->toBe('bl-fournisseur.jpg')
        ->and($reponse->json('supplier_delivery_note.is_image'))->toBeTrue();

    $chemin = $reception->refresh()->supplier_note_path;
    Storage::disk('local')->assertExists($chemin);

    // La réception l'annonce dans sa fiche et dans la liste.
    test()->actingAs($user)->getJson("/api/v1/goods-receipts/{$reception->id}")
        ->assertOk()->assertJsonPath('supplier_delivery_note.is_image', true);
    test()->actingAs($user)->getJson('/api/v1/goods-receipts')
        ->assertOk()->assertJsonPath('data.0.supplier_delivery_note.name', 'bl-fournisseur.jpg');

    $fichier = test()->actingAs($user)->get("/api/v1/goods-receipts/{$reception->id}/delivery-note");
    $fichier->assertOk();
    expect($fichier->headers->get('content-type'))->toContain('image/jpeg')
        ->and($fichier->headers->get('content-disposition'))->toContain('bl-fournisseur.jpg');
});

it('remplace le bon joint par un PDF et supprime l ancien fichier, puis le retire', function (): void {
    $reception = receptionPourBonLivraison();
    $user = grantUser(['receipt.create', 'receipt.view']);

    test()->actingAs($user)->post("/api/v1/goods-receipts/{$reception->id}/delivery-note", [
        'file' => UploadedFile::fake()->image('premier.jpg'),
    ])->assertCreated();
    $premier = $reception->refresh()->supplier_note_path;

    test()->actingAs($user)->post("/api/v1/goods-receipts/{$reception->id}/delivery-note", [
        'file' => UploadedFile::fake()->create('bl.pdf', 120, 'application/pdf'),
    ])->assertCreated()->assertJsonPath('supplier_delivery_note.is_image', false);

    $second = $reception->refresh()->supplier_note_path;
    Storage::disk('local')->assertMissing($premier);
    Storage::disk('local')->assertExists($second);

    test()->actingAs($user)->deleteJson("/api/v1/goods-receipts/{$reception->id}/delivery-note")->assertNoContent();
    Storage::disk('local')->assertMissing($second);
    expect($reception->refresh()->aUnBonDeLivraison())->toBeFalse();

    test()->actingAs($user)->get("/api/v1/goods-receipts/{$reception->id}/delivery-note")->assertNotFound();
});

it('refuse un fichier qui n est ni une image ni un PDF, et refuse sans la permission', function (): void {
    $reception = receptionPourBonLivraison();

    test()->actingAs(grantUser(['receipt.create', 'receipt.view']))
        ->postJson("/api/v1/goods-receipts/{$reception->id}/delivery-note", [
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])->assertUnprocessable();

    test()->actingAs(grantUser(['receipt.view']))
        ->post("/api/v1/goods-receipts/{$reception->id}/delivery-note", [
            'file' => UploadedFile::fake()->image('bl.jpg'),
        ])->assertForbidden();
});
