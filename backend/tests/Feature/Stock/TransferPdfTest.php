<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Transfer;
use App\Domain\Stock\Models\TransferLine;
use App\Domain\Stock\Models\TransferStatus;
use App\Domain\Warehouses\Models\Warehouse;

function pdfProduit(string $nom = 'CABLE HDMI 2M'): Product
{
    return Product::factory()->create([
        'name' => $nom,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function pdfTransfert(Warehouse $de, Warehouse $vers, int $envoye = 12, ?int $recu = null): Transfer
{
    $statut = TransferStatus::firstOrCreate(
        ['code' => $recu === null ? TransferStatus::IN_TRANSIT : 'received'],
        ['name' => $recu === null ? 'En transit' : 'Reçu'],
    );

    $transfert = Transfer::create([
        'reference' => 'TR--'.str_pad((string) random_int(1, 99999), 6, '0', STR_PAD_LEFT),
        'from_warehouse_id' => $de->id,
        'to_warehouse_id' => $vers->id,
        'transfer_status_id' => $statut->id,
        'sent_at' => now(),
    ]);

    TransferLine::create([
        'transfer_id' => $transfert->id,
        'product_id' => pdfProduit()->id,
        'quantity_sent' => $envoye,
        'quantity_received' => $recu,
        'unit_cost' => '10.00',
    ]);

    return $transfert;
}

it('produit un bon de transfert au format PDF', function (): void {
    $de = Warehouse::factory()->create();
    $vers = Warehouse::factory()->create();
    $user = grantUser(['stock.view', 'stock.view_global']);
    $transfert = pdfTransfert($de, $vers);

    $reponse = $this->actingAs($user)->get("/api/v1/transfers/{$transfert->id}/pdf");

    $reponse->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($reponse->headers->get('content-disposition'))
        ->toContain("{$transfert->reference}.pdf");
});

it('refuse le bon d\'un transfert qui ne concerne pas le lieu du demandeur', function (): void {
    $de = Warehouse::factory()->create();
    $vers = Warehouse::factory()->create();
    $ailleurs = Warehouse::factory()->create();

    $user = grantUser(['stock.view'], ['warehouse_id' => $ailleurs->id]);
    $transfert = pdfTransfert($de, $vers);

    $this->actingAs($user)
        ->get("/api/v1/transfers/{$transfert->id}/pdf")
        ->assertForbidden();
});

it('accorde le bon au lieu destinataire comme au lieu expediteur', function (): void {
    $de = Warehouse::factory()->create();
    $vers = Warehouse::factory()->create();
    $transfert = pdfTransfert($de, $vers);

    foreach ([$de, $vers] as $lieu) {
        $user = grantUser(['stock.view'], ['warehouse_id' => $lieu->id]);

        $this->actingAs($user)
            ->get("/api/v1/transfers/{$transfert->id}/pdf")
            ->assertOk();
    }
});

it('ferme aussi la fiche a qui n\'est pas concerne', function (): void {
    // La liste filtrait deja, mais la consultation par identifiant ne
    // verifiait rien : ce cas verrouille la regression.
    $transfert = pdfTransfert(Warehouse::factory()->create(), Warehouse::factory()->create());
    $user = grantUser(['stock.view'], ['warehouse_id' => Warehouse::factory()->create()->id]);

    $this->actingAs($user)
        ->getJson("/api/v1/transfers/{$transfert->id}")
        ->assertForbidden();
});
