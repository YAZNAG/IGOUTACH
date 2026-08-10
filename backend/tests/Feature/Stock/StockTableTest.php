<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Tri, filtre d'état et pagination du tableau de stock.
 *
 * Ces requêtes construisent du SQL brut à partir de paramètres publics : les
 * cas ci-dessous vérifient à la fois qu'elles répondent juste et qu'une
 * colonne inconnue ne peut pas s'y glisser.
 */
function tableProduit(string $nom, string $sku, int $seuil = 0): Product
{
    return Product::factory()->create([
        'name' => $nom,
        'sku' => $sku,
        'min_stock' => $seuil,
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function tableStock(int $lieu, int $produit, int $qte, string $cout = '10.00'): void
{
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $lieu,
        'product_id' => $produit,
        'quantity' => $qte,
        'reserved_quantity' => 0,
        'average_cost' => $cout,
    ]);
}

/** @return array{0: \App\Models\User, 1: \App\Domain\Warehouses\Models\Warehouse} */
function tableContexte(array $permissions = ['stock.view', 'stock.view_global']): array
{
    $lieu = Warehouse::factory()->create();

    return [grantUser($permissions, ['warehouse_id' => $lieu->id]), $lieu];
}

it('trie le stock par nom dans les deux sens', function (): void {
    [$user, $lieu] = tableContexte();
    tableStock($lieu->id, tableProduit('ZEBRE', 'Z-1')->id, 1);
    tableStock($lieu->id, tableProduit('ANTENNE', 'A-1')->id, 99);

    $croissant = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=name&direction=asc")
        ->assertOk()->json('data');

    expect($croissant[0]['name'])->toBe('ANTENNE');

    $decroissant = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=name&direction=desc")
        ->assertOk()->json('data');

    expect($decroissant[0]['name'])->toBe('ZEBRE');
});

it('trie sur la quantite agregee, pas sur l\'ordre du catalogue', function (): void {
    [$user, $lieu] = tableContexte();
    tableStock($lieu->id, tableProduit('AAA', 'A-2')->id, 3);
    tableStock($lieu->id, tableProduit('BBB', 'B-2')->id, 500);

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=quantity&direction=desc")
        ->assertOk()->json('data');

    expect($reponse[0]['name'])->toBe('BBB')
        ->and($reponse[0]['quantity'])->toBe(500);
});

it('ne filtre que les articles en rupture quand on le demande', function (): void {
    [$user, $lieu] = tableContexte();
    tableStock($lieu->id, tableProduit('EN STOCK', 'S-1')->id, 12);
    tableStock($lieu->id, tableProduit('EPUISE', 'S-2')->id, 0);

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&status=rupture")
        ->assertOk()->json();

    expect($reponse['data'])->toHaveCount(1)
        ->and($reponse['data'][0]['name'])->toBe('EPUISE')
        // Le total doit suivre le filtre : sinon la pagination promettrait des
        // pages qui n'existent pas.
        ->and($reponse['meta']['total'])->toBe(1);
});

it('isole les articles passes sous leur seuil', function (): void {
    [$user, $lieu] = tableContexte();
    tableStock($lieu->id, tableProduit('SOUS SEUIL', 'L-1', 10)->id, 4);
    tableStock($lieu->id, tableProduit('CONFORTABLE', 'L-2', 10)->id, 40);
    tableStock($lieu->id, tableProduit('SANS SEUIL', 'L-3', 0)->id, 1);

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&status=low")
        ->assertOk()->json('data');

    expect($reponse)->toHaveCount(1)
        ->and($reponse[0]['name'])->toBe('SOUS SEUIL');
});

it('ignore une colonne de tri inconnue au lieu de l\'executer', function (): void {
    [$user, $lieu] = tableContexte();
    tableStock($lieu->id, tableProduit('UNIQUE', 'U-1')->id, 7);

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=(SELECT 1)&direction=asc")
        ->assertOk()->json();

    // La requete aboutit sur le tri par defaut plutot que d'injecter le texte.
    expect($reponse['meta']['sort'])->toBe('quantity')
        ->and($reponse['data'])->toHaveCount(1);
});

it('refuse le tri par valeur a qui ne voit pas les prix d\'achat', function (): void {
    [$user, $lieu] = tableContexte(['stock.view', 'stock.view_global']);
    tableStock($lieu->id, tableProduit('CHER', 'V-1')->id, 1, '900.00');
    tableStock($lieu->id, tableProduit('BON MARCHE', 'V-2')->id, 1, '1.00');

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=value&direction=desc")
        ->assertOk()->json();

    // Classer par valeur revelerait l'ordre des prix d'achat que la colonne
    // masque : le tri retombe sur la quantite.
    expect($reponse['meta']['sort'])->toBe('quantity')
        ->and($reponse['data'][0]['value'])->toBeNull();
});

it('accorde le tri par valeur a qui voit les prix d\'achat', function (): void {
    [$user, $lieu] = tableContexte(['stock.view', 'stock.view_global', 'product.view_cost_price']);
    tableStock($lieu->id, tableProduit('CHER', 'W-1')->id, 1, '900.00');
    tableStock($lieu->id, tableProduit('BON MARCHE', 'W-2')->id, 1, '1.00');

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&sort=value&direction=desc")
        ->assertOk()->json();

    expect($reponse['meta']['sort'])->toBe('value')
        ->and($reponse['data'][0]['name'])->toBe('CHER');
});

it('respecte le nombre de lignes par page demande', function (): void {
    [$user, $lieu] = tableContexte();
    foreach (range(1, 25) as $i) {
        tableStock($lieu->id, tableProduit("ART {$i}", "P-{$i}")->id, $i);
    }

    $reponse = $this->actingAs($user)
        ->getJson("/api/v1/stock?warehouse_id={$lieu->id}&per_page=20")
        ->assertOk()->json();

    expect($reponse['data'])->toHaveCount(20)
        ->and($reponse['meta']['per_page'])->toBe(20)
        ->and($reponse['meta']['last_page'])->toBe(2);
});
