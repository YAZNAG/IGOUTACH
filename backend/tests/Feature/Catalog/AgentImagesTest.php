<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImageProposal;
use App\Domain\Catalog\Models\Unit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * L'agent images : recherche, proposition, validation par l'administrateur.
 */
function articleSansImage(string $sku, string $nom): Product
{
    return Product::factory()->create([
        'sku' => $sku, 'name' => $nom, 'is_active' => true, 'cost_price' => 10,
        'category_id' => Category::factory()->create()->id, 'unit_id' => Unit::factory()->create()->id,
    ]);
}

function googleTrouve(array $liens): void
{
    Http::fake([
        'www.googleapis.com/customsearch/*' => Http::response(['items' => array_map(fn (string $l, int $i): array => [
            'link' => $l,
            'title' => "Image $i",
            'displayLink' => 'exemple.ma',
            'image' => ['thumbnailLink' => "https://encrypted-tbn0.gstatic.com/images?q=$i", 'width' => 800, 'height' => 600],
        ], $liens, array_keys($liens))]),
        // L'image retenue, telle qu'on la télécharge : un JPEG minimal mais valide.
        'cdn.exemple.ma/*' => Http::response("\xFF\xD8\xFF".str_repeat('x', 2000), 200, ['Content-Type' => 'image/jpeg']),
        'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'télécommande Echolink RX5']]]]]]),
    ]);
}

it('dit si l\'agent est configuré et compte les articles sans image', function (): void {
    config(['services.google_cse.key' => '', 'services.google_cse.cx' => '', 'services.anthropic.key' => '']);
    $admin = grantUser(['product.media_manage']);
    articleSansImage('A-1', 'TC ECHOLINK RX5 / IG125');

    $this->actingAs($admin)->getJson('/api/v1/agent-images/overview')
        ->assertOk()
        ->assertJsonPath('data.configured', false)
        ->assertJsonPath('data.counts.sans_image', 1)
        ->assertJsonPath('data.next_products.0.sku', 'A-1');

    $this->actingAs($admin)->postJson('/api/v1/agent-images/run', ['limit' => 5])->assertStatus(503);
});

it('refuse l\'agent sans la permission médias', function (): void {
    $user = grantUser(['product.view']);

    $this->actingAs($user)->getJson('/api/v1/agent-images/overview')->assertForbidden();
});

it('propose des images, puis n\'enregistre que celle validée par l\'administrateur', function (): void {
    config(['services.google_cse.key' => 'cle', 'services.google_cse.cx' => 'moteur', 'services.gemini.key' => 'cle-gemini']);
    Storage::fake('public');
    googleTrouve(['https://cdn.exemple.ma/rx5-1.jpg', 'https://cdn.exemple.ma/rx5-2.jpg']);

    $admin = grantUser(['product.media_manage']);
    $article = articleSansImage('A-1', 'TC ECHOLINK RX5 / IG125');

    $lancement = $this->actingAs($admin)->postJson('/api/v1/agent-images/run', ['limit' => 5])->assertOk();
    expect($lancement->json('data.proposees'))->toBe(1);

    // La requête envoyée à Google est celle reformulée par Gemini, sans code interne.
    Http::assertSent(fn (\Illuminate\Http\Client\Request $r): bool => str_contains($r->url(), 'customsearch')
        && str_contains(urldecode($r->url()), 'q=télécommande Echolink RX5')
        && str_contains($r->url(), 'searchType=image'));

    $liste = $this->actingAs($admin)->getJson('/api/v1/agent-images/proposals?status=pending')->assertOk();
    expect($liste->json('data'))->toHaveCount(1)
        ->and($liste->json('data.0.product.sku'))->toBe('A-1')
        ->and($liste->json('data.0.candidates'))->toHaveCount(2);

    // Rien n'est entré dans la base avant validation.
    expect($article->images()->count())->toBe(0);

    $id = $liste->json('data.0.id');
    $this->actingAs($admin)->postJson("/api/v1/agent-images/proposals/{$id}/approve", ['index' => 1])
        ->assertOk()
        ->assertJsonPath('data.image.is_main', true);

    expect($article->images()->count())->toBe(1);
    Storage::disk('public')->assertExists($article->images()->first()->path);

    $proposition = ProductImageProposal::query()->findOrFail($id);
    expect($proposition->status)->toBe('approved')
        ->and($proposition->chosen_index)->toBe(1)
        ->and($proposition->reviewed_by)->toBe($admin->id);

    // Une proposition traitée ne se revalide pas, et l'article ne revient
    // plus dans la file des articles sans image.
    $this->actingAs($admin)->postJson("/api/v1/agent-images/proposals/{$id}/approve", ['index' => 0])->assertStatus(422);
    expect($this->actingAs($admin)->getJson('/api/v1/agent-images/overview')->json('data.counts.sans_image'))->toBe(0);
});

it('rejette une proposition et relance avec une requête corrigée', function (): void {
    config(['services.google_cse.key' => 'cle', 'services.google_cse.cx' => 'moteur', 'services.gemini.key' => '']);
    googleTrouve(['https://cdn.exemple.ma/a.jpg']);
    $admin = grantUser(['product.media_manage']);
    $article = articleSansImage('B-2', 'RLT RJ45 305M WDLINK NOIRE');

    $this->actingAs($admin)->postJson('/api/v1/agent-images/run', ['product_ids' => [$article->id]])->assertOk();
    $p = ProductImageProposal::query()->firstOrFail();

    // Sans Gemini, la requête est le nom nettoyé des abréviations.
    expect($p->query)->toBe('rouleau RJ45 305M WDLINK NOIRE');

    $relance = $this->actingAs($admin)->postJson("/api/v1/agent-images/proposals/{$p->id}/retry", ['query' => 'câble réseau 305 m wdlink'])->assertOk();
    expect($relance->json('data.query'))->toBe('câble réseau 305 m wdlink')
        ->and(ProductImageProposal::query()->where('status', 'pending')->count())->toBe(1);

    $nouvelId = $relance->json('data.id');
    $this->actingAs($admin)->postJson("/api/v1/agent-images/proposals/{$nouvelId}/reject")->assertOk();
    expect(ProductImageProposal::query()->findOrFail($nouvelId)->status)->toBe('rejected')
        ->and($article->images()->count())->toBe(0);
});

it('se rabat sur Claude quand Google n\'est pas configuré, et ne garde que de vraies images', function (): void {
    config(['services.google_cse.key' => '', 'services.google_cse.cx' => '', 'services.anthropic.key' => 'cle-anthropic', 'services.gemini.key' => '']);
    $admin = grantUser(['product.media_manage']);
    $article = articleSansImage('C-3', 'PARABOL 70CM');

    // Claude est simulé : il rend trois adresses, dont une page HTML et une
    // adresse morte, que l'agent doit écarter.
    $this->mock(\App\Domain\Catalog\Services\RechercheImagesClaude::class, function ($m): void {
        $m->shouldReceive('disponible')->andReturn(true);
        $m->shouldReceive('nom')->andReturn('claude');
        $m->shouldReceive('maxParLancement')->andReturn(5);
        $m->shouldReceive('chercher')->once()->andReturn([
            ['url' => 'https://cdn.exemple.ma/parabole.jpg', 'thumbnail' => 'https://cdn.exemple.ma/parabole.jpg', 'title' => 'Parabole 70 cm', 'source' => 'cdn.exemple.ma', 'width' => null, 'height' => null],
            ['url' => 'https://www.exemple.ma/produit/parabole', 'thumbnail' => 'https://www.exemple.ma/produit/parabole', 'title' => 'Page', 'source' => 'www.exemple.ma', 'width' => null, 'height' => null],
            ['url' => 'https://cdn.exemple.ma/absente.png', 'thumbnail' => 'https://cdn.exemple.ma/absente.png', 'title' => 'Morte', 'source' => 'cdn.exemple.ma', 'width' => null, 'height' => null],
        ]);
    });
    Http::fake([
        'cdn.exemple.ma/parabole.jpg' => Http::response('', 200, ['Content-Type' => 'image/jpeg']),
        'www.exemple.ma/*' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
        'cdn.exemple.ma/absente.png' => Http::response('', 404),
    ]);

    $apercu = $this->actingAs($admin)->getJson('/api/v1/agent-images/overview')->assertOk();
    expect($apercu->json('data.configured'))->toBeTrue()
        ->and($apercu->json('data.engine'))->toBe('claude')
        ->and($apercu->json('data.max_per_run'))->toBe(5);

    $this->actingAs($admin)->postJson('/api/v1/agent-images/run', ['product_ids' => [$article->id]])
        ->assertOk()->assertJsonPath('data.proposees', 1);

    $p = ProductImageProposal::query()->firstOrFail();
    expect(array_column($p->candidates, 'url'))->toBe(['https://cdn.exemple.ma/parabole.jpg']);
});

it('lit les candidates dans la réponse de Claude, même entourée de texte', function (): void {
    $moteur = app(\App\Domain\Catalog\Services\RechercheImagesClaude::class);

    $liste = $moteur->extraire('Voici : {"candidates":[{"url":"https://a.ma/x.jpg","title":"X","source":"a.ma"},{"url":"ftp://pas","title":"","source":""},{"url":"https://a.ma/x.jpg","title":"doublon","source":"a.ma"}]} merci');

    expect($liste)->toHaveCount(1)
        ->and($liste[0]['url'])->toBe('https://a.ma/x.jpg')
        ->and($liste[0]['source'])->toBe('a.ma');
});
