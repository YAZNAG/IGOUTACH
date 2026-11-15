<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\MovementType;
use App\Domain\Stock\Models\Stock;
use App\Domain\Store\Models\StoreOrder;
use App\Domain\Store\Models\StoreQuote;
use App\Domain\Store\Models\StoreSetting;
use App\Domain\Warehouses\Models\Warehouse;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;

/**
 * Paliers de prix et devis sur la boutique.
 *
 * Deux choses à prouver. D'abord que le prix suit la quantité : le moteur de
 * tarification de la gestion connaît déjà les paliers et leurs seuils, y
 * compris ceux propres à un article, et la boutique doit s'y plier — pas
 * recalculer de son côté. Ensuite qu'au-delà du palier choisi la vente ne
 * peut plus se conclure en libre-service, même en appelant l'API en direct.
 */
beforeEach(function (): void {
    $this->withoutMiddleware(ThrottleRequests::class);

    foreach ([['in', 1], ['out', -1]] as [$code, $sign]) {
        MovementType::firstOrCreate(['code' => $code], ['name' => $code, 'sign' => $sign, 'affects_valuation' => $sign > 0]);
    }

    foreach ([
        'shop_name' => 'iGouTech Store', 'shipping_fee' => '30', 'free_shipping_from' => '5000',
        'payment_cod' => '1', 'payment_transfer' => '1', 'quote_from' => 'semi_gros',
        'tiers_public' => '1', 'quote_validity_days' => '7',
    ] as $k => $v) {
        StoreSetting::query()->updateOrCreate(['key' => $k], ['value' => $v]);
    }

    $this->lieu = Warehouse::factory()->create(['code' => 'DCH']);
    $this->categorie = Category::factory()->create(['name' => 'Câbles']);

    // Les trois paliers, avec les seuils réellement en production.
    $this->types = [];
    foreach ([['detail', 'Détail', 1, 1], ['semi_gros', 'Demi-gros', 2, 3], ['gros', 'Gros', 3, 10]] as [$code, $nom, $rang, $seuil]) {
        $id = DB::table('price_types')->where('code', $code)->value('id');
        if ($id === null) {
            $id = DB::table('price_types')->insertGetId([
                'code' => $code, 'name' => $nom, 'rank' => $rang, 'min_quantity' => $seuil,
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            DB::table('price_types')->where('id', $id)->update(['name' => $nom, 'rank' => $rang, 'min_quantity' => $seuil, 'is_active' => 1]);
        }
        $this->types[$code] = $id;
    }
});

/**
 * Un article tarifé sur les trois paliers. $seuils surcharge le seuil d'un
 * palier pour cet article seulement — c'est ce que fait la gestion quand un
 * article se vend en gros à partir de 5 et non de 10.
 *
 * @param  array<string, float>  $prix
 * @param  array<string, int>  $seuils
 */
function articleAPaliers(string $sku, array $prix, int $stock = 100, array $seuils = []): Product
{
    $p = Product::factory()->create([
        'sku' => $sku, 'name' => 'Article '.$sku, 'cost_price' => 5, 'is_active' => true,
        'sale_price' => $prix['detail'] ?? 0,
        'category_id' => test()->categorie->id, 'unit_id' => Unit::factory()->create()->id,
    ]);

    foreach ($prix as $code => $montant) {
        DB::table('product_prices')->insert([
            'product_id' => $p->id, 'price_type_id' => test()->types[$code], 'amount' => $montant,
            'min_quantity' => $seuils[$code] ?? null,
            'valid_from' => now()->toDateString(), 'valid_to' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    if ($stock > 0) {
        Stock::withoutGlobalScopes()->create([
            'warehouse_id' => test()->lieu->id, 'product_id' => $p->id,
            'quantity' => $stock, 'reserved_quantity' => 0, 'average_cost' => '5.00',
        ]);
    }

    return $p;
}

function adminBoutique(): User
{
    return grantUser(['store.manage', 'stock.view_global']);
}

it('publie les trois paliers avec leurs seuils, y compris le seuil propre à un article', function (): void {
    articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);
    // Celui-ci passe en gros dès 5 unités : son seuil est porté par la ligne.
    articleAPaliers('SPECIAL', ['detail' => 100, 'semi_gros' => 80, 'gros' => 60], 50, ['gros' => 5]);

    $liste = $this->getJson('/api/store/products?per_page=24')->assertOk();

    $cable = collect($liste->json('data'))->firstWhere('sku', 'CABLE');
    expect($cable['tiers'])->toHaveCount(3)
        ->and(array_column($cable['tiers'], 'min_quantity'))->toBe([1, 3, 10])
        ->and(array_map('floatval', array_column($cable['tiers'], 'price')))->toBe([30.0, 20.0, 13.0])
        // « quote_from = semi_gros » : les deux paliers du haut passent par un devis.
        ->and(array_column($cable['tiers'], 'on_quote'))->toBe([false, true, true])
        ->and($cable['quote_from_quantity'])->toBe(3);

    $special = collect($liste->json('data'))->firstWhere('sku', 'SPECIAL');
    expect(array_column($special['tiers'], 'min_quantity'))->toBe([1, 3, 5]);

    // La fiche porte la même grille que la carte.
    $fiche = $this->getJson('/api/store/products/'.$cable['id'])->assertOk();
    expect($fiche->json('data.tiers'))->toHaveCount(3);

    expect($this->getJson('/api/store/settings')->json('data.quote_from'))->toBe(['semi_gros', 'gros']);
});

it('commande au prix du palier atteint par la quantité, et refuse ce qui relève du devis', function (): void {
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);

    $commander = fn (int $qte) => $this->postJson('/api/store/orders', [
        'customer_name' => 'Ahmed', 'customer_phone' => '0612345678',
        'city' => 'Agadir', 'address' => 'Rue 12, Dcheira', 'payment_method' => 'cod',
        'lines' => [['product_id' => $article->id, 'quantity' => $qte]],
    ]);

    // Sous le seuil du demi-gros : prix de détail.
    $commander(2)->assertCreated();
    $ligne = StoreOrder::query()->latest('id')->firstOrFail()->lines()->firstOrFail();
    expect((float) $ligne->unit_price)->toBe(30.0)
        ->and($ligne->price_type_code)->toBe('detail')
        ->and((float) $ligne->line_total)->toBe(60.0);

    // Au seuil du demi-gros : la vente directe est fermée.
    $refus = $commander(3)->assertStatus(422);
    expect($refus->json('message'))->toContain('sur devis')->and($refus->json('message'))->toContain('3 unités');
    expect(StoreOrder::query()->count())->toBe(1);
});

it('vend en demi-gros en direct quand la boutique n’impose le devis qu’au gros', function (): void {
    StoreSetting::query()->updateOrCreate(['key' => 'quote_from'], ['value' => 'gros']);
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);

    $this->postJson('/api/store/orders', [
        'customer_name' => 'Ahmed', 'customer_phone' => '0612345678',
        'city' => 'Agadir', 'address' => 'Rue 12, Dcheira', 'payment_method' => 'cod',
        'lines' => [['product_id' => $article->id, 'quantity' => 4]],
    ])->assertCreated();

    $commande = StoreOrder::query()->latest('id')->firstOrFail();
    $ligne = $commande->lines()->firstOrFail();
    expect((float) $ligne->unit_price)->toBe(20.0)->and($ligne->price_type_code)->toBe('semi_gros');
    // 4 × 20 = 80, plus 30 de port : le palier a bien servi au total.
    expect((float) $commande->total)->toBe(110.0);

    // Dix unités relèvent du gros : refusé.
    $this->postJson('/api/store/orders', [
        'customer_name' => 'Ahmed', 'customer_phone' => '0612345678',
        'city' => 'Agadir', 'address' => 'Rue 12, Dcheira', 'payment_method' => 'cod',
        'lines' => [['product_id' => $article->id, 'quantity' => 10]],
    ])->assertStatus(422);

    // La vente de gestion née de la livraison hérite du palier, pas d'un
    // « detail » de façade.
    $admin = adminBoutique();
    $this->actingAs($admin)->patchJson('/api/v1/store-admin/orders/'.$commande->id.'/status', [
        'status' => 'delivered', 'warehouse_id' => $this->lieu->id,
    ])->assertOk();

    $vente = Sale::withoutGlobalScopes()->where('type', Sale::TYPE_INVOICE)->latest('id')->firstOrFail();
    expect($vente->lines()->firstOrFail()->price_type_code)->toBe('semi_gros');
});

it('mène un devis de la demande à la commande, aux prix négociés', function (): void {
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13], 5);

    // Le stock ne bloque pas une demande : on demande 200 pour 5 en rayon.
    $demande = $this->postJson('/api/store/quotes', [
        'customer_name' => 'Youssef', 'customer_phone' => '0661223344',
        'company' => 'Électro Souss', 'city' => 'Inezgane',
        'note' => 'Livraison en deux fois si besoin.',
        'lines' => [['product_id' => $article->id, 'quantity' => 200]],
    ])->assertCreated();

    $jeton = $demande->json('data.public_token');
    expect($demande->json('data.status'))->toBe('requested')
        // Prix indicatif : le barème au palier gros.
        ->and((float) $demande->json('data.total'))->toBe(2600.0);

    $devis = StoreQuote::query()->latest('id')->firstOrFail();
    expect($devis->lines()->firstOrFail()->stock_at_request)->toBe(5);

    // Tant que la boutique n'a pas répondu, le client ne peut pas accepter.
    $this->postJson('/api/store/quotes/'.$jeton.'/accept', ['payment_method' => 'cod'])->assertStatus(422);

    $admin = adminBoutique();

    // Chiffrage : prix ferme à 11,50, port offert, lieu d'expédition.
    $this->actingAs($admin)->putJson('/api/v1/store-admin/quotes/'.$devis->id, [
        'lines' => [['id' => $devis->lines()->firstOrFail()->id, 'quantity' => 200, 'unit_price' => 11.5]],
        'shipping_fee' => 0,
        'valid_until' => now()->addDays(5)->toDateString(),
        'reply' => 'Prix ferme pour 200 pièces, livrables sous 5 jours.',
        'warehouse_id' => $this->lieu->id,
    ])->assertOk();

    $this->actingAs($admin)->postJson('/api/v1/store-admin/quotes/'.$devis->id.'/send')->assertOk();

    // Le devis existe aussi dans la gestion, où les commerciaux travaillent.
    $devis->refresh();
    expect($devis->status)->toBe('quoted')->and($devis->sale_id)->not->toBeNull();
    $devisGestion = Sale::withoutGlobalScopes()->findOrFail($devis->sale_id);
    expect($devisGestion->type)->toBe(Sale::TYPE_QUOTE)
        ->and($devisGestion->status)->toBe(Sale::STATUS_DRAFT)
        ->and((float) $devisGestion->lines()->firstOrFail()->unit_price)->toBe(11.5);

    // Le client ouvre son lien : il voit le prix ferme, pas le barème.
    $vue = $this->getJson('/api/store/quotes/'.$jeton)->assertOk();
    expect((float) $vue->json('data.total'))->toBe(2300.0)
        ->and($vue->json('data.reply'))->toContain('Prix ferme');

    $acceptation = $this->postJson('/api/store/quotes/'.$jeton.'/accept', [
        'payment_method' => 'cod', 'address' => 'Zone industrielle, Inezgane',
    ])->assertOk();

    $commande = StoreOrder::query()->where('number', $acceptation->json('data.order_number'))->firstOrFail();
    expect($commande->status)->toBe(StoreOrder::STATUS_CONFIRMED)
        ->and($commande->quote_id)->toBe($devis->id)
        ->and((float) $commande->total)->toBe(2300.0)
        ->and((float) $commande->lines()->firstOrFail()->unit_price)->toBe(11.5)
        ->and($commande->warehouse_id)->toBe($this->lieu->id);

    expect($devis->refresh()->status)->toBe('accepted');

    // On n'accepte pas deux fois.
    $this->postJson('/api/store/quotes/'.$jeton.'/accept', ['payment_method' => 'cod'])->assertStatus(422);
});

it('refuse d’envoyer un devis non chiffré, et d’accepter un devis périmé', function (): void {
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);
    $admin = adminBoutique();

    $jeton = $this->postJson('/api/store/quotes', [
        'customer_name' => 'Youssef', 'customer_phone' => '0661223344', 'city' => 'Inezgane',
        'lines' => [['product_id' => $article->id, 'quantity' => 50]],
    ])->assertCreated()->json('data.public_token');

    $devis = StoreQuote::query()->latest('id')->firstOrFail();

    // Une ligne à zéro : le devis ne part pas.
    $this->actingAs($admin)->putJson('/api/v1/store-admin/quotes/'.$devis->id, [
        'lines' => [['id' => $devis->lines()->firstOrFail()->id, 'quantity' => 50, 'unit_price' => 0]],
    ])->assertOk();
    $this->actingAs($admin)->postJson('/api/v1/store-admin/quotes/'.$devis->id.'/send')
        ->assertStatus(422)->assertJsonPath('message', 'Chiffrez toutes les lignes avant d’envoyer le devis.');

    // Chiffré et envoyé, mais avec une validité déjà passée.
    $this->actingAs($admin)->putJson('/api/v1/store-admin/quotes/'.$devis->id, [
        'lines' => [['id' => $devis->lines()->firstOrFail()->id, 'quantity' => 50, 'unit_price' => 12]],
        'valid_until' => now()->subDay()->toDateString(),
        'warehouse_id' => $this->lieu->id,
    ])->assertOk();
    $this->actingAs($admin)->postJson('/api/v1/store-admin/quotes/'.$devis->id.'/send')->assertOk();

    // La lecture du lien constate l'expiration.
    expect($this->getJson('/api/store/quotes/'.$jeton)->json('data.status'))->toBe('expired');
    $this->postJson('/api/store/quotes/'.$jeton.'/accept', ['payment_method' => 'cod'])->assertStatus(422);
    expect(StoreOrder::query()->count())->toBe(0);
});

it('laisse le client refuser, et montre aux responsables ce qui attend une réponse', function (): void {
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);
    $admin = adminBoutique();

    $jeton = $this->postJson('/api/store/quotes', [
        'customer_name' => 'Youssef', 'customer_phone' => '0661223344', 'city' => 'Inezgane',
        'lines' => [['product_id' => $article->id, 'quantity' => 40]],
    ])->assertCreated()->json('data.public_token');

    $devis = StoreQuote::query()->latest('id')->firstOrFail();

    // Une demande non traitée est ce que le tableau de bord met en avant.
    $bord = $this->actingAs($admin)->getJson('/api/v1/store-admin/dashboard')->assertOk();
    expect($bord->json('data.quotes.to_answer'))->toBe(1)
        ->and($bord->json('data.quotes.conversion'))->toBeNull()
        ->and($bord->json('data.recent_quotes'))->toHaveCount(1);

    $this->actingAs($admin)->putJson('/api/v1/store-admin/quotes/'.$devis->id, [
        'lines' => [['id' => $devis->lines()->firstOrFail()->id, 'quantity' => 40, 'unit_price' => 14]],
        'warehouse_id' => $this->lieu->id,
    ])->assertOk();
    $this->actingAs($admin)->postJson('/api/v1/store-admin/quotes/'.$devis->id.'/send')->assertOk();

    $this->postJson('/api/store/quotes/'.$jeton.'/decline', ['reason' => 'Trop cher'])->assertOk();
    expect($devis->refresh()->status)->toBe('declined')->and($devis->decline_reason)->toBe('Trop cher');

    $bord = $this->actingAs($admin)->getJson('/api/v1/store-admin/dashboard')->assertOk();
    expect($bord->json('data.quotes.to_answer'))->toBe(0)
        ->and((float) $bord->json('data.quotes.conversion'))->toBe(0.0)
        ->and((float) $bord->json('data.quotes.pipeline'))->toBe(0.0);

    // Le suivi par numéro et téléphone retrouve le devis.
    $suivi = $this->postJson('/api/store/quotes/track', ['number' => $devis->number, 'phone' => '0661223344'])->assertOk();
    expect($suivi->json('data.status'))->toBe('declined');
});

it('réserve le pilotage des devis à la permission de la boutique', function (): void {
    $article = articleAPaliers('CABLE', ['detail' => 30, 'semi_gros' => 20, 'gros' => 13]);
    $this->postJson('/api/store/quotes', [
        'customer_name' => 'Youssef', 'customer_phone' => '0661223344', 'city' => 'Inezgane',
        'lines' => [['product_id' => $article->id, 'quantity' => 40]],
    ])->assertCreated();

    $devis = StoreQuote::query()->latest('id')->firstOrFail();
    $quidam = User::factory()->create();

    $this->actingAs($quidam)->getJson('/api/v1/store-admin/quotes')->assertForbidden();
    $this->actingAs($quidam)->postJson('/api/v1/store-admin/quotes/'.$devis->id.'/send')->assertForbidden();
});
