<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\StockMovement;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Contrat de la liste des mouvements d'un article.
 *
 * L'ecran affiche le lieu, le type et l'auteur de chaque ligne. Ces champs
 * etaient declares cote client mais absents de la reponse : la fiche tombait
 * des le premier mouvement. Ce test fige ce que l'API doit rendre.
 */
function mouvement(Product $produit, Warehouse $lieu, ?int $userId): StockMovement
{
    $type = MovementType::firstOrCreate(
        ['code' => 'entry'],
        ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true],
    );

    return StockMovement::query()->create([
        'warehouse_id' => $lieu->id,
        'product_id' => $produit->id,
        'movement_type_id' => $type->id,
        'quantity' => 5,
        'unit_cost' => '10.00',
        'balance_after' => 5,
        'user_id' => $userId,
    ]);
}

it('rend le lieu, le type et l\'auteur de chaque mouvement', function (): void {
    $user = grantUser(['product.view', 'stock.view_global']);
    $produit = Product::factory()->create();
    $lieu = Warehouse::factory()->create(['name' => 'Dépôt Dcheira']);

    mouvement($produit, $lieu, $user->id);

    $ligne = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/movements")
        ->assertOk()->json('data.0');

    expect($ligne['warehouse_name'])->toBe('Dépôt Dcheira')
        ->and($ligne['type'])->toBe('entry')
        ->and($ligne['user']['name'])->toBe($user->name);
});

it('rend un auteur nul plutot que d\'omettre la cle', function (): void {
    $user = grantUser(['product.view', 'stock.view_global']);
    $produit = Product::factory()->create();
    $lieu = Warehouse::factory()->create();

    // Import initial ou compte supprime : le mouvement n'a pas d'auteur.
    mouvement($produit, $lieu, null);

    $ligne = test()->actingAs($user)
        ->getJson("/api/v1/products/{$produit->id}/movements")
        ->assertOk()->json('data.0');

    // La cle doit exister et valoir null : c'est ce que l'ecran attend pour
    // afficher « Non attribué » au lieu de tomber.
    expect($ligne)->toHaveKey('user')
        ->and($ligne['user'])->toBeNull();
});
