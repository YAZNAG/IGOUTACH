<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\Customer;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\Inventory;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Un responsable de lieu (véhicule ou point de vente) ne doit voir QUE ce
 * qui relève de son lieu et de ses propres clients.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);

    $this->mine = Warehouse::factory()->create(['code' => 'VEH-TEST']);
    $this->other = Warehouse::factory()->create(['code' => 'DEP-TEST']);

    // Responsable de lieu : pas de stock.view_global, pas de customer.view_all.
    $this->manager = grantUser([
        'stock.view', 'sale.create', 'customer.view', 'customer.create',
        'payment.view', 'payment.create', 'credit.view', 'inventory.create', 'expense.create',
    ]);
    $this->manager->update(['warehouse_id' => $this->mine->id]);
});

function isoProduct(): Product
{
    return Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
}

function isoSale(int $warehouseId, ?int $customerId, string $ref, ?int $userId = null): Sale
{
    return Sale::withoutGlobalScopes()->create([
        'reference' => $ref,
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $customerId,
        'warehouse_id' => $warehouseId,
        'user_id' => $userId,
        'subtotal' => 100,
        'discount_percent' => 0,
        'total' => 100,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => now(),
    ]);
}

it('ne voit que le stock de son lieu', function (): void {
    $product = isoProduct();
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $this->mine->id, 'product_id' => $product->id, 'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '10.00']);
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $this->other->id, 'product_id' => $product->id, 'quantity' => 99, 'reserved_quantity' => 0, 'average_cost' => '10.00']);

    $response = $this->actingAs($this->manager)
        ->getJson('/api/v1/stock?warehouse_id='.$this->other->id)
        ->assertOk();

    // Même en demandant explicitement l'autre lieu : rien ne doit filtrer.
    $quantities = collect($response->json('data'))->pluck('quantity');
    expect($quantities)->not->toContain(99);
});

it('ne voit que les ventes de son lieu', function (): void {
    isoSale($this->mine->id, null, 'VT-MINE', $this->manager->id);
    isoSale($this->other->id, null, 'VT-OTHER', $this->manager->id);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/sales')->assertOk();
    $refs = collect($response->json('data'))->pluck('reference');

    expect($refs)->toContain('VT-MINE')->and($refs)->not->toContain('VT-OTHER');
});

it('ne voit pas la vente d\'un autre vendeur, même dans son propre lieu', function (): void {
    $colleague = grantUser(['sale.create'], ['warehouse_id' => $this->mine->id]);

    isoSale($this->mine->id, null, 'VT-A-MOI', $this->manager->id);
    isoSale($this->mine->id, null, 'VT-AU-COLLEGUE', $colleague->id);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/sales')->assertOk();
    $refs = collect($response->json('data'))->pluck('reference');

    expect($refs)->toContain('VT-A-MOI')->and($refs)->not->toContain('VT-AU-COLLEGUE');
});

it('voit la vente rattachée à son client même saisie par un autre', function (): void {
    $colleague = grantUser(['sale.create'], ['warehouse_id' => $this->mine->id]);
    $myCustomer = Customer::factory()->create(['created_by' => $this->manager->id]);

    isoSale($this->mine->id, $myCustomer->id, 'VT-MON-CLIENT', $colleague->id);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/sales')->assertOk();

    expect(collect($response->json('data'))->pluck('reference'))->toContain('VT-MON-CLIENT');
});

it('ne voit que les crédits de ses propres clients', function (): void {
    $mineCustomer = Customer::factory()->create(['name' => 'Client à moi', 'created_by' => $this->manager->id]);
    $otherUser = grantUser(['customer.create']);
    $otherCustomer = Customer::factory()->create(['name' => 'Client des autres', 'created_by' => $otherUser->id]);

    isoSale($this->mine->id, $mineCustomer->id, 'VT-CREDIT-MINE', $this->manager->id);
    isoSale($this->other->id, $otherCustomer->id, 'VT-CREDIT-OTHER', $otherUser->id);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/customers-aging')->assertOk();
    $names = collect($response->json('data'))->pluck('customer');

    expect($names)->toContain('Client à moi')->and($names)->not->toContain('Client des autres');
});

it('refuse le relevé d\'un client créé par quelqu\'un d\'autre', function (): void {
    $otherUser = grantUser(['customer.create']);
    $foreign = Customer::factory()->create(['created_by' => $otherUser->id]);

    $this->actingAs($this->manager)
        ->getJson("/api/v1/customers/{$foreign->id}/statement")
        ->assertForbidden();
});

it('ne voit que les inventaires de son lieu', function (): void {
    Inventory::withoutGlobalScopes()->create(['reference' => 'INV-MINE', 'warehouse_id' => $this->mine->id, 'counted_at' => now(), 'status' => 'draft']);
    Inventory::withoutGlobalScopes()->create(['reference' => 'INV-OTHER', 'warehouse_id' => $this->other->id, 'counted_at' => now(), 'status' => 'draft']);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/inventories')->assertOk();
    $refs = collect($response->json('data'))->pluck('reference');

    expect($refs)->toContain('INV-MINE')->and($refs)->not->toContain('INV-OTHER');
});

it('ne voit que les charges de son lieu', function (): void {
    $category = ExpenseCategory::firstOrCreate(['name' => 'Carburant'], []);

    Expense::withoutGlobalScopes()->create(['expense_category_id' => $category->id, 'warehouse_id' => $this->mine->id, 'label' => 'Gasoil véhicule', 'amount' => 500, 'expense_date' => now(), 'status' => 'pending', 'user_id' => $this->manager->id]);
    Expense::withoutGlobalScopes()->create(['expense_category_id' => $category->id, 'warehouse_id' => $this->other->id, 'label' => 'Loyer dépôt', 'amount' => 9000, 'expense_date' => now(), 'status' => 'pending', 'user_id' => $this->manager->id]);

    $response = $this->actingAs($this->manager)->getJson('/api/v1/expenses')->assertOk();
    $labels = collect($response->json('data'))->pluck('label');

    expect($labels)->toContain('Gasoil véhicule')->and($labels)->not->toContain('Loyer dépôt');
});

it('un administrateur avec vue globale voit tous les lieux', function (): void {
    $admin = grantUser(['sale.create', 'stock.view_global', 'customer.view', 'customer.view_all']);

    isoSale($this->mine->id, null, 'VT-A', $admin->id);
    isoSale($this->other->id, null, 'VT-B', $admin->id);

    $response = $this->actingAs($admin)->getJson('/api/v1/sales')->assertOk();
    $refs = collect($response->json('data'))->pluck('reference');

    expect($refs)->toContain('VT-A')->and($refs)->toContain('VT-B');
});

it('totalise tous les lieux quand aucun lieu n\'est choisi', function (): void {
    // Le cout moyen n'est lisible qu'avec la permission dediee.
    $admin = grantUser(['stock.view', 'stock.view_global', 'product.view_cost_price']);
    $product = isoProduct();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->mine->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '20.00',
    ]);
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->other->id, 'product_id' => $product->id,
        'quantity' => 5, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $ligne = collect($this->actingAs($admin)->getJson('/api/v1/stock')->assertOk()->json('data'))
        ->firstWhere('product_id', $product->id);

    // Sans ce cumul, la jointure filtrait sur « warehouse_id = 0 » et tout
    // le catalogue s'affichait à zéro pour un utilisateur en vue globale.
    expect($ligne['quantity'])->toBe(15)
        // Coût moyen pondéré : (10×20 + 5×10) / 15 = 16,67.
        ->and(round((float) $ligne['average_cost'], 2))->toBe(16.67);
});

it('n\'affiche que le lieu demandé quand il est précisé', function (): void {
    $admin = grantUser(['stock.view', 'stock.view_global']);
    $product = isoProduct();

    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->mine->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '20.00',
    ]);
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->other->id, 'product_id' => $product->id,
        'quantity' => 5, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);

    $ligne = collect($this->actingAs($admin)->getJson('/api/v1/stock?warehouse_id='.$this->other->id)->json('data'))
        ->firstWhere('product_id', $product->id);

    expect($ligne['quantity'])->toBe(5);
});
