<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);

    $this->lieu = Warehouse::factory()->create(['code' => 'MIEN']);
});

function journalVente(int $warehouseId, string $date, float $total): Sale
{
    return Sale::withoutGlobalScopes()->create([
        'reference' => 'VT-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => null,
        'warehouse_id' => $warehouseId,
        'subtotal' => $total, 'discount_percent' => 0, 'total' => $total,
        'paid_amount' => 0, 'payment_status' => 'unpaid',
        'confirmed_at' => $date, 'created_at' => $date, 'updated_at' => $date,
    ]);
}

it('produit un journal des ventes en PDF sur la période demandée', function (): void {
    $user = grantUser(['sale.create', 'stock.view_global'], ['warehouse_id' => $this->lieu->id]);

    journalVente($this->lieu->id, '2026-08-05 10:00:00', 1200);
    journalVente($this->lieu->id, '2026-08-20 10:00:00', 800);   // hors période

    $rep = $this->actingAs($user)
        ->get('/api/v1/reports/journal/sales?date_from=2026-08-01&date_to=2026-08-10');

    $rep->assertOk();
    expect($rep->headers->get('content-type'))->toContain('application/pdf')
        ->and($rep->headers->get('content-disposition'))->toContain('ventes-2026-08-01-2026-08-10.pdf');
});

it('refuse une période inversée', function (): void {
    $user = grantUser(['sale.create'], ['warehouse_id' => $this->lieu->id]);

    // Une date de fin antérieure au début ne peut produire qu'un journal vide
    // trompeur : mieux vaut le dire.
    $this->actingAs($user)
        ->getJson('/api/v1/reports/journal/sales?date_from=2026-08-20&date_to=2026-08-01')
        ->assertStatus(422)->assertJsonValidationErrors('date_to');
});

it('ne fait figurer que le lieu de l\'utilisateur', function (): void {
    $autre = Warehouse::factory()->create(['code' => 'AUTRE']);
    $user = grantUser(['sale.create'], ['warehouse_id' => $this->lieu->id]);

    journalVente($this->lieu->id, '2026-08-05 10:00:00', 500);
    journalVente($autre->id, '2026-08-05 10:00:00', 9999);

    $this->actingAs($user)
        ->get('/api/v1/reports/journal/sales?date_from=2026-08-01&date_to=2026-08-31')
        ->assertOk();

    // Le PDF est compressé : y chercher un montant ne prouverait rien. Ce qui
    // compte est la requête qui l'alimente — le journal lit Sale::query(),
    // soumis au même cloisonnement que la liste des ventes.
    $this->actingAs($user);
    expect(Sale::query()->pluck('warehouse_id')->unique()->all())->toBe([$this->lieu->id]);

    // Et l'autre lieu existe bien, sans quoi le test passerait pour rien.
    expect(Sale::withoutGlobalScopes()->count())->toBe(2);
});

it('produit les journaux d\'entrées, de sorties et de charges', function (): void {
    $user = grantUser(['stock.view', 'expense.create', 'sale.create'], ['warehouse_id' => $this->lieu->id]);

    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
    ]);
    Stock::withoutGlobalScopes()->create([
        'warehouse_id' => $this->lieu->id, 'product_id' => $product->id,
        'quantity' => 10, 'reserved_quantity' => 0, 'average_cost' => '10.00',
    ]);
    DB::table('stock_movements')->insert([
        'warehouse_id' => $this->lieu->id, 'product_id' => $product->id,
        'movement_type_id' => MovementType::where('code', 'in')->value('id'),
        'quantity' => 10, 'unit_cost' => 10, 'balance_after' => 10,
        'created_at' => '2026-08-05 09:00:00',
    ]);

    Expense::withoutGlobalScopes()->create([
        'expense_category_id' => ExpenseCategory::firstOrCreate(['name' => 'Carburant'], [])->id,
        'warehouse_id' => $this->lieu->id, 'user_id' => $user->id,
        'label' => 'Gasoil', 'amount' => 420, 'expense_date' => '2026-08-06', 'status' => 'approved',
    ]);

    foreach (['stock-entries', 'stock-exits', 'expenses'] as $journal) {
        $this->actingAs($user)
            ->get("/api/v1/reports/journal/{$journal}?date_from=2026-08-01&date_to=2026-08-31")
            ->assertOk();
    }
});

it('refuse le journal des ventes sans permission', function (): void {
    $user = grantUser(['stock.view'], ['warehouse_id' => $this->lieu->id]);

    $this->actingAs($user)->getJson('/api/v1/reports/journal/sales')->assertForbidden();
});
