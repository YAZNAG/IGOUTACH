<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Contracts\StockReaderInterface;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Models\StoreSetting;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * La boutique en ligne : catalogue public, prise de commande, suivi, puis
 * traitement par la gestion jusqu'a la vente de gestion a la livraison.
 */
beforeEach(function (): void {
    // La limitation de debit protege le site ; en test elle fausserait les scenarios.
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    foreach ([['in', 1], ['out', -1]] as [$code, $sign]) {
        MovementType::firstOrCreate(['code' => $code], ['name' => $code, 'sign' => $sign, 'affects_valuation' => $sign > 0]);
    }
    foreach (['shop_name' => 'iGouTech Store', 'shipping_fee' => '30', 'free_shipping_from' => '500', 'payment_cod' => '1', 'payment_transfer' => '1', 'bank_details' => 'RIB 123'] as $k => $v) {
        StoreSetting::query()->updateOrCreate(['key' => $k], ['value' => $v]);
    }

    $this->lieu = Warehouse::factory()->create(['code' => 'DCH']);
    $this->categorie = Category::factory()->create(['name' => 'Câbles']);
    $this->typeDetail = DB::table('price_types')->where('code', 'detail')->value('id')
        ?? DB::table('price_types')->insertGetId(['code' => 'detail', 'name' => 'Détail', 'rank' => 1, 'min_quantity' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
});

function articleBoutique(string $sku, float $prix, int $stock, array $extra = []): Product
{
    $p = Product::factory()->create(array_merge([
        'sku' => $sku, 'name' => 'Article '.$sku, 'cost_price' => 5, 'is_active' => true, 'sale_price' => $prix,
        'category_id' => test()->categorie->id, 'unit_id' => Unit::factory()->create()->id,
    ], $extra));
    DB::table('product_prices')->insert([
        'product_id' => $p->id, 'price_type_id' => test()->typeDetail, 'amount' => $prix,
        'valid_from' => now()->toDateString(), 'valid_to' => null, 'created_at' => now(), 'updated_at' => now(),
    ]);
    if ($stock > 0) {
        Stock::withoutGlobalScopes()->create(['warehouse_id' => test()->lieu->id, 'product_id' => $p->id, 'quantity' => $stock, 'reserved_quantity' => 0, 'average_cost' => '5.00']);
    }

    return $p;
}

it('expose le catalogue public : articles visibles avec prix, catégories, fiche', function (): void {
    $visible = articleBoutique('VIS', 120, 10);
    articleBoutique('CACHE', 50, 10, ['store_visible' => false]);
    articleBoutique('RUPTURE', 80, 0);

    $reglages = $this->getJson('/api/store/settings')->assertOk();
    expect($reglages->json('data.shop_name'))->toBe('iGouTech Store')
        ->and($reglages->json('data.payment_methods'))->toBe(['cod', 'transfer']);

    $liste = $this->getJson('/api/store/products?per_page=12')->assertOk();
    expect(collect($liste->json('data'))->pluck('sku')->sort()->values()->all())->toBe(['RUPTURE', 'VIS']);
    $ligne = collect($liste->json('data'))->firstWhere('sku', 'VIS');
    expect((float) $ligne['price'])->toBe(120.0)->and($ligne['in_stock'])->toBeTrue()->and($ligne['stock_level'])->toBe('in');
    expect(collect($liste->json('data'))->firstWhere('sku', 'RUPTURE')['in_stock'])->toBeFalse();

    expect($this->getJson('/api/store/products?in_stock=1')->json('meta.total'))->toBe(1);

    $cats = $this->getJson('/api/store/categories')->assertOk();
    expect($cats->json('data.0.name'))->toBe('Câbles')->and($cats->json('data.0.count'))->toBe(2);

    $this->getJson("/api/store/products/{$visible->id}")->assertOk()->assertJsonPath('data.sku', 'VIS');
    $cache = Product::query()->where('sku', 'CACHE')->firstOrFail();
    $this->getJson("/api/store/products/{$cache->id}")->assertNotFound();
});

it('prend une commande aux prix de la base, avec frais de port, et la rend traçable', function (): void {
    $a = articleBoutique('A1', 100, 5);
    $b = articleBoutique('B2', 40, 5);

    $reponse = $this->postJson('/api/store/orders', [
        'customer_name' => 'Ahmed Client', 'customer_phone' => '06 61 11 22 33', 'city' => 'Agadir',
        'address' => 'Hay Dakhla, rue 5', 'payment_method' => 'transfer',
        'lines' => [['product_id' => $a->id, 'quantity' => 2], ['product_id' => $b->id, 'quantity' => 1]],
    ])->assertCreated();

    expect((float) $reponse->json('data.subtotal'))->toBe(240.0)
        ->and((float) $reponse->json('data.shipping_fee'))->toBe(30.0)
        ->and((float) $reponse->json('data.total'))->toBe(270.0)
        ->and($reponse->json('data.status'))->toBe('new')
        ->and($reponse->json('data.bank_details'))->toBe('RIB 123')
        ->and($reponse->json('data.number'))->toStartWith('WEB-');

    // Au-dessus du seuil, le port est offert.
    $grosse = $this->postJson('/api/store/orders', [
        'customer_name' => 'Big', 'customer_phone' => '0600000000', 'city' => 'Agadir', 'address' => 'Quelque part 12',
        'payment_method' => 'cod', 'lines' => [['product_id' => $a->id, 'quantity' => 5]],
    ])->assertCreated();
    expect((float) $grosse->json('data.shipping_fee'))->toBe(0.0)->and((float) $grosse->json('data.total'))->toBe(500.0);

    // Le stock n'a pas bouge : une commande n'est pas une vente.
    expect(app(StockReaderInterface::class)->quantityFor($this->lieu->id, $a->id))->toBe(5);

    // Suivi par numero + telephone (derniers chiffres suffisent).
    $this->postJson('/api/store/orders/track', ['number' => $reponse->json('data.number'), 'phone' => '0661112233'])
        ->assertOk()->assertJsonPath('data.total', 270);
    $this->postJson('/api/store/orders/track', ['number' => $reponse->json('data.number'), 'phone' => '0600099999'])
        ->assertNotFound();

    // Plus que le stock : refuse.
    $this->postJson('/api/store/orders', [
        'customer_name' => 'Trop', 'customer_phone' => '0600000001', 'city' => 'X', 'address' => 'Y rue longue',
        'payment_method' => 'cod', 'lines' => [['product_id' => $b->id, 'quantity' => 50]],
    ])->assertStatus(422);
});

it('la gestion traite la commande : confirmation, livraison crée la vente et sort le stock, règlement', function (): void {
    $admin = grantUser(['store.manage', 'stock.view_global']);
    $a = articleBoutique('A1', 100, 5);

    $numero = $this->postJson('/api/store/orders', [
        'customer_name' => 'Ahmed', 'customer_phone' => '0661112233', 'city' => 'Agadir', 'address' => 'Rue 5 numéro 10',
        'payment_method' => 'cod', 'lines' => [['product_id' => $a->id, 'quantity' => 2]],
    ])->assertCreated()->json('data.number');
    $commande = StoreOrder::query()->where('number', $numero)->firstOrFail();

    $tdb = $this->actingAs($admin)->getJson('/api/v1/store-admin/dashboard')->assertOk();
    expect($tdb->json('data.by_status.new'))->toBe(1)->and($tdb->json('data.today.orders'))->toBe(1);

    $this->actingAs($admin)->patchJson("/api/v1/store-admin/orders/{$commande->id}/status", ['status' => 'confirmed', 'warehouse_id' => $this->lieu->id])
        ->assertOk()->assertJsonPath('data.status', 'confirmed')->assertJsonPath('data.warehouse', 'DCH');

    $this->actingAs($admin)->patchJson("/api/v1/store-admin/orders/{$commande->id}/status", ['status' => 'shipped'])->assertOk();

    $livree = $this->actingAs($admin)->patchJson("/api/v1/store-admin/orders/{$commande->id}/status", ['status' => 'delivered'])->assertOk();
    expect($livree->json('data.sale_reference'))->not->toBeNull()
        ->and(app(StockReaderInterface::class)->quantityFor($this->lieu->id, $a->id))->toBe(3);

    $vente = Sale::query()->findOrFail($commande->refresh()->sale_id);
    // La vente de gestion porte les articles (200), pas les frais de port.
    expect($vente->status)->toBe(Sale::STATUS_CONFIRMED)->and((float) $vente->total)->toBe(200.0)->and($vente->customer_id)->toBeNull();

    // Reglement a la livraison.
    $payee = $this->actingAs($admin)->postJson("/api/v1/store-admin/orders/{$commande->id}/payments", [
        'method' => 'cod', 'amount' => 230, 'received_at' => now()->toDateString(),
    ])->assertOk();
    expect($payee->json('data.payment_status'))->toBe('paid')->and((float) $payee->json('data.paid_amount'))->toBe(230.0);

    // Livree : plus de retour en arriere.
    $this->actingAs($admin)->patchJson("/api/v1/store-admin/orders/{$commande->id}/status", ['status' => 'cancelled'])->assertStatus(422);
});

it('refuse le tableau de bord sans la permission, et laisse régler la visibilité des articles', function (): void {
    $sansDroit = grantUser(['stock.view']);
    $this->actingAs($sansDroit)->getJson('/api/v1/store-admin/dashboard')->assertForbidden();

    $admin = grantUser(['store.manage']);
    $a = articleBoutique('A1', 100, 5);

    $this->actingAs($admin)->patchJson("/api/v1/store-admin/products/{$a->id}", ['store_visible' => false, 'store_description' => 'Texte boutique'])
        ->assertOk()->assertJsonPath('data.store_visible', false);
    expect($this->getJson('/api/store/products')->json('meta.total'))->toBe(0);

    $this->actingAs($admin)->putJson('/api/v1/store-admin/settings', ['shipping_fee' => '45', 'announcement' => 'Promo'])
        ->assertOk()->assertJsonPath('data.shipping_fee', '45');
    expect((float) $this->getJson('/api/store/settings')->json('data.shipping_fee'))->toBe(45.0);
});
