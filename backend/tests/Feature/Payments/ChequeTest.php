<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Payments\Models\Cheque;
use App\Domain\Purchasing\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function chequeRecu(array $overrides = []): Cheque
{
    return Cheque::query()->create(array_merge([
        'number' => 'CH-'.uniqid(),
        'cheque_date' => '2026-09-15',
        'amount' => 5000,
        'bank' => 'Attijariwafa',
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_CUSTOMER,
        'status' => Cheque::STATUS_PORTFOLIO,
    ], $overrides));
}

it('enregistre un chèque reçu d\'un client avec sa date, sa série et son image', function (): void {
    Storage::fake('public');
    $user = grantUser(['cheque.manage', 'cheque.view']);
    $client = Customer::factory()->create(['name' => 'Comptoir du Nord']);

    $data = $this->actingAs($user)->postJson('/api/v1/cheques', [
        'number' => 'CH-778899',
        'cheque_date' => '2026-09-30',
        'amount' => 12000,
        'bank' => 'BMCE',
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_CUSTOMER,
        'customer_id' => $client->id,
    ])->assertCreated()->json('data');

    expect($data['number'])->toBe('CH-778899')
        ->and($data['cheque_date'])->toBe('2026-09-30')
        ->and($data['status'])->toBe(Cheque::STATUS_PORTFOLIO)
        // Sans nom saisi, le signataire est le client lui-même.
        ->and($data['signataire'])->toBe('Comptoir du Nord');
});

it('retient le nom du tiers quand le chèque n\'est pas au nom du client', function (): void {
    $user = grantUser(['cheque.manage']);
    $client = Customer::factory()->create(['name' => 'Comptoir du Nord']);

    $data = $this->actingAs($user)->postJson('/api/v1/cheques', [
        'number' => 'CH-TIERS-1',
        'cheque_date' => '2026-09-30',
        'amount' => 3000,
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_THIRD_PARTY,
        'drawer_name' => 'Hassan Alaoui',
        'customer_id' => $client->id,
    ])->assertCreated()->json('data');

    expect($data['signataire'])->toBe('Hassan Alaoui')
        ->and($data['drawer_name'])->toBe('Hassan Alaoui');
});

it('refuse un chèque de tiers sans nom de signataire', function (): void {
    $user = grantUser(['cheque.manage']);

    $this->actingAs($user)->postJson('/api/v1/cheques', [
        'number' => 'CH-SANS-NOM',
        'cheque_date' => '2026-09-30',
        'amount' => 3000,
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_THIRD_PARTY,
    ])->assertStatus(422)
        ->assertJsonPath('errors.drawer_name.0', 'Nom du signataire requis.');
});

it('joint l\'image du chèque', function (): void {
    Storage::fake('public');
    $user = grantUser(['cheque.manage']);

    $data = $this->actingAs($user)->post('/api/v1/cheques', [
        'number' => 'CH-IMAGE-1',
        'cheque_date' => '2026-09-30',
        'amount' => 800,
        'direction' => Cheque::DIRECTION_IN,
        'origin' => Cheque::ORIGIN_OWN,
        'image' => UploadedFile::fake()->image('cheque.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated()->json('data');

    expect($data['image_url'])->not->toBeNull();
    Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($data['image_url'], PHP_URL_PATH)));
});

it('endosse un chèque reçu au profit d\'un fournisseur', function (): void {
    $user = grantUser(['cheque.manage']);
    $cheque = chequeRecu();
    $fournisseur = Supplier::factory()->create(['name' => 'Grossiste Casa']);

    $data = $this->actingAs($user)
        ->postJson("/api/v1/cheques/{$cheque->id}/endorse", ['supplier_id' => $fournisseur->id])
        ->assertOk()
        ->json('data');

    expect($data['status'])->toBe(Cheque::STATUS_HANDED_OVER)
        ->and($data['supplier']['name'])->toBe('Grossiste Casa');
});

it('refuse d\'endosser deux fois le même chèque', function (): void {
    $user = grantUser(['cheque.manage']);
    $cheque = chequeRecu();
    $a = Supplier::factory()->create();
    $b = Supplier::factory()->create();

    $this->actingAs($user)->postJson("/api/v1/cheques/{$cheque->id}/endorse", ['supplier_id' => $a->id])->assertOk();

    // Un chèque déjà remis ne doit pas pouvoir régler un second fournisseur.
    $this->actingAs($user)->postJson("/api/v1/cheques/{$cheque->id}/endorse", ['supplier_id' => $b->id])
        ->assertStatus(422);

    expect(Cheque::find($cheque->id)->supplier_id)->toBe($a->id);
});

it('ne propose à l\'endossement que les chèques reçus encore en portefeuille', function (): void {
    $user = grantUser(['cheque.view', 'cheque.manage']);

    $disponible = chequeRecu(['number' => 'CH-DISPO']);
    chequeRecu(['number' => 'CH-REMIS', 'status' => Cheque::STATUS_HANDED_OVER]);
    chequeRecu(['number' => 'CH-EMIS', 'direction' => Cheque::DIRECTION_OUT, 'origin' => Cheque::ORIGIN_OWN]);

    $numeros = collect($this->actingAs($user)->getJson('/api/v1/cheques?endorsable=1')->json('data'))
        ->pluck('number');

    expect($numeros)->toContain('CH-DISPO')
        ->and($numeros)->not->toContain('CH-REMIS')
        ->and($numeros)->not->toContain('CH-EMIS');
});

it('interdit la suppression d\'un chèque déjà remis', function (): void {
    $user = grantUser(['cheque.manage']);
    $cheque = chequeRecu(['status' => Cheque::STATUS_HANDED_OVER]);

    $this->actingAs($user)->deleteJson("/api/v1/cheques/{$cheque->id}")->assertStatus(422);

    expect(Cheque::find($cheque->id))->not->toBeNull();
});

it('refuse l\'accès sans permission chèque', function (): void {
    $user = grantUser(['sale.create']);

    $this->actingAs($user)->getJson('/api/v1/cheques')->assertForbidden();
    $this->actingAs($user)->postJson('/api/v1/cheques', [])->assertForbidden();
});
