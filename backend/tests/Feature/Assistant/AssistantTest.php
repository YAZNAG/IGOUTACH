<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\Http;

/**
 * L'assistant : les modules, le contexte envoyé à Gemini, la réponse rendue.
 */
it('liste les modules et dit si la clé est configurée', function (): void {
    config(['services.gemini.key' => '']);
    $admin = grantUser(['stock.view_global']);

    $this->actingAs($admin)->getJson('/api/v1/assistant/modules')
        ->assertOk()
        ->assertJsonPath('meta.configured', false)
        ->assertJsonCount(5, 'data');
});

it('refuse l\'assistant sans la vue globale', function (): void {
    $user = grantUser(['stock.view']);

    $this->actingAs($user)->postJson('/api/v1/assistant/ask', ['module' => 'achats', 'question' => 'Bonjour'])
        ->assertForbidden();
});

it('répond 503 quand la clé Gemini manque', function (): void {
    config(['services.gemini.key' => '']);
    $admin = grantUser(['stock.view_global']);

    $this->actingAs($admin)->postJson('/api/v1/assistant/ask', ['module' => 'general', 'question' => 'Résume.'])
        ->assertStatus(503);
});

it('envoie à Gemini le contexte des achats avec le taux de vente et la quantité suggérée', function (): void {
    config(['services.gemini.key' => 'cle-de-test', 'services.gemini.model' => 'gemini-test']);
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => "## Priorités\n| Réf | Qté |\n|---|---|\n| CAB-1 | 40 |"]]]]],
        ]),
    ]);

    $admin = grantUser(['stock.view_global', 'sale.create']);
    $lieu = Warehouse::factory()->create();
    $article = Product::factory()->create([
        'sku' => 'CAB-1', 'name' => 'Câble test', 'cost_price' => 10, 'is_active' => true,
        'category_id' => Category::factory()->create()->id, 'unit_id' => Unit::factory()->create()->id,
    ]);
    Stock::withoutGlobalScopes()->create(['warehouse_id' => $lieu->id, 'product_id' => $article->id, 'quantity' => 5]);

    // 30 unités vendues en 30 jours : 1 par jour, 5 jours de couverture.
    $vente = \App\Domain\Sales\Models\Sale::withoutGlobalScopes()->create([
        'reference' => 'VT-ASS-1', 'type' => 'invoice', 'status' => 'confirmed', 'customer_id' => null,
        'warehouse_id' => $lieu->id, 'user_id' => $admin->id, 'subtotal' => 300, 'discount_percent' => 0,
        'total' => 300, 'paid_amount' => 300, 'payment_status' => 'paid', 'confirmed_at' => now()->subDays(3),
    ]);
    \Illuminate\Support\Facades\DB::table('sale_lines')->insert([
        'sale_id' => $vente->id, 'product_id' => $article->id, 'quantity' => 30, 'unit_price' => 10,
        'line_total' => 300, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $reponse = $this->actingAs($admin)->postJson('/api/v1/assistant/ask', [
        'module' => 'achats',
        'question' => 'Que dois-je commander ?',
        'history' => [['role' => 'user', 'content' => 'Bonjour'], ['role' => 'assistant', 'content' => 'Bonjour !']],
    ])->assertOk();

    expect($reponse->json('data.answer'))->toContain('CAB-1')
        ->and($reponse->json('data.model'))->toBe('gemini-test');

    Http::assertSent(function (\Illuminate\Http\Client\Request $r): bool {
        $corps = $r->data();
        $dernier = end($corps['contents'])['parts'][0]['text'];

        return str_contains($r->url(), 'gemini-test:generateContent')
            && $r->hasHeader('x-goog-api-key', 'cle-de-test')
            && count($corps['contents']) === 3
            && $corps['contents'][1]['role'] === 'model'
            && str_contains($dernier, '"ref": "CAB-1"')
            && str_contains($dernier, '"couverture_jours": 5')
            && str_contains($dernier, '"qte_suggeree_45j": 40')
            && str_contains($dernier, 'Que dois-je commander ?');
    });
});
