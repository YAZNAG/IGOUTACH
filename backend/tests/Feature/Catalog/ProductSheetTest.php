<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;

it('enregistre puis relit la fiche technique d\'un article', function () {
    $user = grantUser(['product.view', 'product.attributes_manage']);
    $product = Product::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/products/{$product->id}/attributes", [
        'attributes' => [
            ['name' => 'Processeur', 'value' => 'Intel i5-12400'],
            ['name' => 'RAM', 'value' => '16 Go DDR4'],
        ],
    ])->assertOk();

    $this->actingAs($user)
        ->getJson("/api/v1/products/{$product->id}/attributes")
        ->assertOk()
        ->assertJsonPath('data.attributes.0.name', 'Processeur')
        ->assertJsonPath('data.attributes.1.value', '16 Go DDR4');
});

it('refuse la fiche technique sans la permission', function () {
    $user = grantUser(['product.view']);
    $product = Product::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/products/{$product->id}/attributes", [
        'attributes' => [['name' => 'X', 'value' => 'Y']],
    ])->assertForbidden();
});

it('ajoute des numéros de série en lot en ignorant les doublons', function () {
    $user = grantUser(['product.view', 'product.update', 'serial.view']);
    $product = Product::factory()->create(['is_serialized' => true]);

    $this->actingAs($user)->postJson("/api/v1/products/{$product->id}/serials", [
        'serials' => "SN-001\nSN-002\nSN-001",
    ])->assertCreated()
        ->assertJsonPath('data.created', 2)
        ->assertJsonPath('data.skipped', 1);

    $this->actingAs($user)
        ->getJson("/api/v1/products/{$product->id}/serials")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('référence un article chez un fournisseur avec prix et délai', function () {
    $user = grantUser(['supplier.view', 'supplier.update']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->putJson("/api/v1/suppliers/{$supplier->id}/products/{$product->id}", [
        'supplier_reference' => 'REF-FOURN-1',
        'last_price' => 850.50,
        'lead_time_days' => 7,
    ])->assertCreated();

    $this->actingAs($user)
        ->getJson("/api/v1/suppliers/{$supplier->id}/products")
        ->assertOk()
        ->assertJsonPath('data.0.supplier_reference', 'REF-FOURN-1')
        ->assertJsonPath('data.0.lead_time_days', 7);

    $this->actingAs($user)
        ->getJson("/api/v1/suppliers/{$supplier->id}/stats")
        ->assertOk()
        ->assertJsonPath('data.products_count', 1);
});

it('gère les contacts multiples d\'un fournisseur', function () {
    $user = grantUser(['supplier.view', 'supplier.update']);
    $supplier = Supplier::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/suppliers/{$supplier->id}/contacts", [
        'name' => 'Karim Alaoui',
        'role' => 'Commercial',
        'phone' => '0661-000000',
    ])->assertCreated();

    $this->actingAs($user)
        ->getJson("/api/v1/suppliers/{$supplier->id}/contacts")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Karim Alaoui');
});
