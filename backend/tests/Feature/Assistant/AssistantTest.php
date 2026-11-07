<?php

declare(strict_types=1);

use App\Domain\Assistant\Models\AssistantConversation;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\Unit;
use App\Domain\Stock\Models\Stock;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * L'assistant : les modules, le contexte envoyé à Gemini, la réponse rendue,
 * l'historique conservé par utilisateur et supprimable.
 */
function geminiRepond(string $texte): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $texte]]]]],
        ]),
    ]);
}

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

it('répond 503 quand la clé Gemini manque, sans créer de conversation', function (): void {
    config(['services.gemini.key' => '']);
    $admin = grantUser(['stock.view_global']);

    $this->actingAs($admin)->postJson('/api/v1/assistant/ask', ['module' => 'general', 'question' => 'Résume.'])
        ->assertStatus(503);

    expect(AssistantConversation::query()->count())->toBe(0);
});

it('envoie à Gemini le contexte des achats avec le taux de vente et la quantité suggérée', function (): void {
    config(['services.gemini.key' => 'cle-de-test', 'services.gemini.model' => 'gemini-test']);
    geminiRepond("## Priorités\n| Réf | Qté |\n|---|---|\n| CAB-1 | 40 |");

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
    DB::table('sale_lines')->insert([
        'sale_id' => $vente->id, 'product_id' => $article->id, 'quantity' => 30, 'unit_price' => 10,
        'line_total' => 300, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $reponse = $this->actingAs($admin)->postJson('/api/v1/assistant/ask', [
        'module' => 'achats',
        'question' => 'Que dois-je commander ?',
    ])->assertOk();

    expect($reponse->json('data.answer'))->toContain('CAB-1')
        ->and($reponse->json('data.model'))->toBe('gemini-test')
        ->and($reponse->json('data.conversation_id'))->toBeInt();

    Http::assertSent(function (\Illuminate\Http\Client\Request $r): bool {
        $corps = $r->data();
        $dernier = end($corps['contents'])['parts'][0]['text'];

        return str_contains($r->url(), 'gemini-test:generateContent')
            && $r->hasHeader('x-goog-api-key', 'cle-de-test')
            && count($corps['contents']) === 1
            && str_contains($dernier, '"ref":"CAB-1"')
            && str_contains($dernier, '"couverture_jours":5')
            && str_contains($dernier, '"qte_suggeree_45j":40')
            && str_contains($dernier, 'Que dois-je commander ?');
    });
});

it('conserve la conversation et renvoie le fil précédent à Gemini', function (): void {
    config(['services.gemini.key' => 'cle-de-test', 'services.gemini.model' => 'gemini-test']);
    // Deux réponses successives : un seul faux, en séquence (un second
    // Http::fake ne remplacerait pas le premier, il s'y ajouterait après).
    $reponse = fn (string $texte): array => ['candidates' => [['content' => ['parts' => [['text' => $texte]]]]]];
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($reponse('Première réponse.'))
            ->push($reponse('Seconde réponse.')),
    ]);
    $admin = grantUser(['stock.view_global']);

    $id = $this->actingAs($admin)->postJson('/api/v1/assistant/ask', ['module' => 'general', 'question' => 'Résume la situation.'])
        ->assertOk()->json('data.conversation_id');

    $this->actingAs($admin)->postJson('/api/v1/assistant/ask', [
        'module' => 'general', 'question' => 'Et pour le lieu 2 ?', 'conversation_id' => $id,
    ])->assertOk()->assertJsonPath('data.conversation_id', $id);

    // Le second appel porte la question et la réponse précédentes, puis la nouvelle.
    Http::assertSent(function (\Illuminate\Http\Client\Request $r): bool {
        $c = $r->data()['contents'];

        return count($c) === 3
            && $c[0]['role'] === 'user' && $c[0]['parts'][0]['text'] === 'Résume la situation.'
            && $c[1]['role'] === 'model' && $c[1]['parts'][0]['text'] === 'Première réponse.'
            && str_contains($c[2]['parts'][0]['text'], 'Et pour le lieu 2 ?');
    });

    $liste = $this->actingAs($admin)->getJson('/api/v1/assistant/conversations')->assertOk();
    expect($liste->json('data'))->toHaveCount(1)
        ->and($liste->json('data.0.title'))->toBe('Résume la situation.')
        ->and($liste->json('data.0.messages_count'))->toBe(4);

    $detail = $this->actingAs($admin)->getJson("/api/v1/assistant/conversations/{$id}")->assertOk();
    expect(collect($detail->json('data.messages'))->pluck('content')->all())
        ->toBe(['Résume la situation.', 'Première réponse.', 'Et pour le lieu 2 ?', 'Seconde réponse.']);
});

it('chacun ne voit et ne supprime que ses conversations', function (): void {
    $a = grantUser(['stock.view_global']);
    $b = grantUser(['stock.view_global']);
    $deA = AssistantConversation::query()->create(['user_id' => $a->id, 'module' => 'ventes', 'title' => 'Fil de A']);
    $deB = AssistantConversation::query()->create(['user_id' => $b->id, 'module' => 'ventes', 'title' => 'Fil de B']);

    expect(collect($this->actingAs($a)->getJson('/api/v1/assistant/conversations')->json('data'))->pluck('title')->all())->toBe(['Fil de A']);

    $this->actingAs($a)->getJson("/api/v1/assistant/conversations/{$deB->id}")->assertForbidden();
    $this->actingAs($a)->deleteJson("/api/v1/assistant/conversations/{$deB->id}")->assertForbidden();
    $this->actingAs($a)->postJson('/api/v1/assistant/ask', ['module' => 'ventes', 'question' => 'Suite ?', 'conversation_id' => $deB->id])->assertForbidden();

    $this->actingAs($a)->deleteJson("/api/v1/assistant/conversations/{$deA->id}")->assertOk();
    expect(AssistantConversation::query()->find($deA->id))->toBeNull()
        ->and(AssistantConversation::query()->find($deB->id))->not->toBeNull();

    AssistantConversation::query()->create(['user_id' => $a->id, 'module' => 'achats', 'title' => 'Encore A']);
    $this->actingAs($a)->deleteJson('/api/v1/assistant/conversations')->assertOk();
    expect(AssistantConversation::query()->where('user_id', $a->id)->count())->toBe(0)
        ->and(AssistantConversation::query()->where('user_id', $b->id)->count())->toBe(1);
});
