<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Un meme acheteur peut etre client dans plusieurs lieux : une fiche par
 * lieu, chacune avec son credit, ses ventes et ses reglements.
 */
beforeEach(function (): void {
    MovementType::firstOrCreate(['code' => 'in'], ['name' => 'Entrée', 'sign' => 1, 'affects_valuation' => true]);
    MovementType::firstOrCreate(['code' => 'out'], ['name' => 'Sortie', 'sign' => -1, 'affects_valuation' => false]);

    $this->dchiera = Warehouse::factory()->create(['code' => 'DCH-TEST']);
    $this->vehicule = Warehouse::factory()->create(['code' => 'VEH-TEST']);
    $this->admin = grantUser(['customer.create', 'customer.view', 'customer.view_all', 'stock.view_global', 'sale.create', 'sale.view', 'customer.set_credit_limit']);
});

function articleDuLieuClient(int $warehouseId, int $quantite = 50): Product
{
    $p = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'cost_price' => 10,
        'is_active' => true,
    ]);
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $warehouseId, 'product_id' => $p->id, 'quantity' => $quantite]);

    return $p;
}

it('le même nom peut exister dans deux lieux, avec un crédit séparé', function (): void {
    $a = Customer::factory()->create(['name' => 'Ahmed', 'warehouse_id' => $this->dchiera->id, 'balance' => 300]);
    $b = Customer::factory()->create(['name' => 'Ahmed', 'warehouse_id' => $this->vehicule->id, 'balance' => 0]);

    expect($a->id)->not->toBe($b->id)
        ->and((float) $a->balance)->toBe(300.0)
        ->and((float) $b->balance)->toBe(0.0);

    // Mais dans un meme lieu, pas deux fiches du meme nom.
    $this->actingAs($this->admin)->postJson('/api/v1/customers', ['name' => 'ahmed ', 'warehouse_id' => $this->dchiera->id])
        ->assertStatus(422)
        ->assertJsonPath('existing_id', $a->id);
});

it('copie la fiche d\'un client vers un autre lieu, crédit à zéro', function (): void {
    $origine = Customer::factory()->create([
        'name' => 'Brahim', 'phone' => '0611111111', 'city' => 'Agadir',
        'warehouse_id' => $this->dchiera->id, 'balance' => 900, 'credit_limit' => 2000, 'is_blocked' => true,
    ]);

    $reponse = $this->actingAs($this->admin)
        ->postJson("/api/v1/customers/{$origine->id}/copy", ['warehouse_id' => $this->vehicule->id])
        ->assertCreated();

    $copie = Customer::query()->findOrFail($reponse->json('data.id'));
    expect($copie->name)->toBe('Brahim')
        ->and($copie->phone)->toBe('0611111111')
        ->and($copie->warehouse_id)->toBe($this->vehicule->id)
        ->and((float) $copie->balance)->toBe(0.0)
        ->and($copie->is_blocked)->toBeFalse()
        ->and($copie->code)->not->toBe($origine->code);

    // Une seconde copie vers le meme lieu est refusee.
    $this->actingAs($this->admin)
        ->postJson("/api/v1/customers/{$origine->id}/copy", ['warehouse_id' => $this->vehicule->id])
        ->assertStatus(422)
        ->assertJsonPath('existing_id', $copie->id);
});

it('refuse une vente à un client d\'un autre lieu', function (): void {
    $client = Customer::factory()->create(['name' => 'Client DCH', 'warehouse_id' => $this->dchiera->id]);
    $article = articleDuLieuClient($this->vehicule->id);

    $this->actingAs($this->admin)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'customer_id' => $client->id,
        'warehouse_id' => $this->vehicule->id,
        'lines' => [['product_id' => $article->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertStatus(422)->assertJsonValidationErrors(['customer_id']);
});

it('une fiche sans lieu se rattache au lieu de sa première vente', function (): void {
    $client = Customer::factory()->create(['name' => 'Ancien client', 'warehouse_id' => null]);
    $article = articleDuLieuClient($this->vehicule->id);

    $this->actingAs($this->admin)->postJson('/api/v1/sales', [
        'type' => 'invoice',
        'customer_id' => $client->id,
        'warehouse_id' => $this->vehicule->id,
        'lines' => [['product_id' => $article->id, 'quantity' => 1, 'unit_price' => 100]],
    ])->assertCreated();

    expect($client->refresh()->warehouse_id)->toBe($this->vehicule->id);
});

it('un responsable n\'encaisse pas un client d\'un autre lieu', function (): void {
    $responsable = grantUser(['payment.create', 'payment.view', 'customer.view'], ['warehouse_id' => $this->vehicule->id]);
    $client = Customer::factory()->create(['warehouse_id' => $this->dchiera->id, 'balance' => 100]);
    $mode = DB::table('payment_methods')->insertGetId(['code' => 'CASH-T', 'name' => 'Espèces', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($responsable)->postJson('/api/v1/payments', [
        'customer_id' => $client->id,
        'amount' => 50,
        'payment_method_id' => $mode,
        'received_at' => now()->toDateString(),
    ])->assertForbidden();

    $this->actingAs($responsable)->getJson("/api/v1/customers/{$client->id}/statement")->assertForbidden();
});

it('la commande de séparation dédouble un client présent dans deux lieux', function (): void {
    $client = Customer::factory()->create(['name' => 'Partout', 'warehouse_id' => null, 'balance' => 0]);
    $user = $this->admin;

    $vente = fn (int $lieu, string $ref, float $total) => Sale::withoutGlobalScopes()->create([
        'reference' => $ref, 'type' => Sale::TYPE_INVOICE, 'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client->id, 'warehouse_id' => $lieu, 'user_id' => $user->id,
        'subtotal' => $total, 'discount_percent' => 0, 'total' => $total, 'paid_amount' => 0,
        'payment_status' => 'unpaid', 'confirmed_at' => now(),
    ]);

    // Deux ventes a DCHIERA, une au vehicule, chacune avec son ecriture.
    $d1 = $vente($this->dchiera->id, 'VT-D1', 100);
    $d2 = $vente($this->dchiera->id, 'VT-D2', 150);
    $v1 = $vente($this->vehicule->id, 'VT-V1', 40);
    foreach ([$d1, $d2, $v1] as $v) {
        CustomerLedgerEntry::query()->create([
            'customer_id' => $client->id, 'type' => 'invoice', 'amount' => $v->total, 'balance_after' => 0,
            'reference_type' => Sale::class, 'reference_id' => $v->id,
        ]);
    }
    // Un reglement de 40 impute a la vente du vehicule.
    $p = Payment::query()->create([
        'reference' => 'PAY-T1', 'customer_id' => $client->id, 'sale_id' => $v1->id, 'amount' => 40,
        'received_at' => now()->toDateString(),
    ]);
    DB::table('payment_allocations')->insert(['payment_id' => $p->id, 'sale_id' => $v1->id, 'amount' => 40, 'created_at' => now(), 'updated_at' => now()]);
    CustomerLedgerEntry::query()->create([
        'customer_id' => $client->id, 'type' => 'payment', 'amount' => -40, 'balance_after' => 0,
        'reference_type' => Payment::class, 'reference_id' => $p->id,
    ]);

    Artisan::call('clients:separer-par-lieu', ['--executer' => true]);

    // La fiche d'origine garde DCHIERA (deux ventes) et son solde de 250.
    $client->refresh();
    expect($client->warehouse_id)->toBe($this->dchiera->id)
        ->and((float) $client->balance)->toBe(250.0);

    // Une copie pour le vehicule : la vente, le reglement et un solde a 0.
    $copie = Customer::query()->where('name', 'Partout')->where('warehouse_id', $this->vehicule->id)->firstOrFail();
    expect($copie->id)->not->toBe($client->id)
        ->and((float) $copie->balance)->toBe(0.0)
        ->and($v1->refresh()->customer_id)->toBe($copie->id)
        ->and($p->refresh()->customer_id)->toBe($copie->id)
        ->and($d1->refresh()->customer_id)->toBe($client->id)
        ->and(CustomerLedgerEntry::query()->where('customer_id', $copie->id)->count())->toBe(2);
});
