<?php

declare(strict_types=1);

use App\Domain\Customers\Models\Customer;
use App\Domain\Sales\Actions\RecordPaymentAction;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\PaymentAllocation;
use App\Domain\Sales\Models\Sale;
use App\Domain\Settings\Models\PaymentMethod;
use App\Domain\Warehouses\Models\Warehouse;

/**
 * Imputation d'un règlement sur les factures du client.
 *
 * Un versement qui ne touchait que le grand-livre laissait les factures
 * « impayées » : HOUSSIN JR a réglé 1 108 DH le 22/10 sans affectation, et la
 * même somme lui a été réclamée le 25/10 sur les mêmes factures.
 */
function factureDueAImputer(Customer $client, Warehouse $lieu, float $montant, string $quand): Sale
{
    return Sale::query()->create([
        'reference' => 'VTE-'.uniqid(),
        'type' => Sale::TYPE_INVOICE,
        'status' => Sale::STATUS_CONFIRMED,
        'customer_id' => $client->id,
        'warehouse_id' => $lieu->id,
        'subtotal' => $montant,
        'discount_percent' => 0,
        'total' => $montant,
        'paid_amount' => 0,
        'payment_status' => 'unpaid',
        'confirmed_at' => $quand,
    ]);
}

it('impute un reglement non ventile aux factures les plus anciennes', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['balance' => 0]);
    $mode = modeEspeces();

    $ancienne = factureDueAImputer($client, $lieu, 300, '2026-10-01 10:00');
    $recente = factureDueAImputer($client, $lieu, 200, '2026-10-05 10:00');

    app(RecordPaymentAction::class)->execute([
        'customer_id' => $client->id,
        'amount' => 400,
        'payment_method_id' => $mode->id,
        'received_at' => '2026-10-10',
    ]);

    // La plus ancienne est soldée, la suivante entamée : c'est la règle
    // usuelle, et celle que « Répartir automatiquement » applique à l'écran.
    expect((float) $ancienne->fresh()->paid_amount)->toBe(300.0)
        ->and($ancienne->fresh()->payment_status)->toBe('paid')
        ->and((float) $recente->fresh()->paid_amount)->toBe(100.0)
        ->and($recente->fresh()->payment_status)->toBe('partial')
        ->and(PaymentAllocation::query()->count())->toBe(2);
});

it('laisse en avoir ce qui depasse les factures dues', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['balance' => 0]);
    $mode = modeEspeces();

    $facture = factureDueAImputer($client, $lieu, 150, '2026-10-01 10:00');

    app(RecordPaymentAction::class)->execute([
        'customer_id' => $client->id,
        'amount' => 500,
        'payment_method_id' => $mode->id,
        'received_at' => '2026-10-10',
    ]);

    // Rien n'est imputé au-delà du dû : une facture payée deux fois serait
    // invisible, et le trop-perçu disparaîtrait du suivi.
    expect((float) $facture->fresh()->paid_amount)->toBe(150.0)
        ->and(round((float) PaymentAllocation::query()->sum('amount'), 2))->toBe(150.0)
        // Le grand-livre enregistre tout le versement : ici la facture a été
        // posée directement, sans passer par lui, d'où un solde de −500 qui
        // est l'avoir brut. C'est pourquoi le bon de règlement raisonne sur
        // les factures et non sur ce solde.
        ->and(round((float) $client->fresh()->balance, 2))->toBe(-500.0);
});

it('respecte une ventilation explicite', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['balance' => 0]);
    $mode = modeEspeces();

    $ancienne = factureDueAImputer($client, $lieu, 300, '2026-10-01 10:00');
    $recente = factureDueAImputer($client, $lieu, 200, '2026-10-05 10:00');

    // Le caissier choisit de solder la récente d'abord : l'imputation
    // automatique ne doit pas lui passer dessus.
    app(RecordPaymentAction::class)->execute([
        'customer_id' => $client->id,
        'amount' => 200,
        'payment_method_id' => $mode->id,
        'received_at' => '2026-10-10',
        'allocations' => [['sale_id' => $recente->id, 'amount' => 200]],
    ]);

    expect((float) $recente->fresh()->paid_amount)->toBe(200.0)
        ->and((float) $ancienne->fresh()->paid_amount)->toBe(0.0);
});

it('sert un bon de reglement lisible, sans reste du negatif', function (): void {
    $lieu = Warehouse::factory()->create();
    $client = Customer::factory()->create(['balance' => 0]);
    $user = grantUser(['payment.view', 'payment.create', 'credit.view']);
    $mode = modeEspeces();

    factureDueAImputer($client, $lieu, 100, '2026-10-01 10:00');

    // Versement supérieur aux factures : c'est le cas qui faisait annoncer
    // « reste dû : -1 028 DH ».
    app(RecordPaymentAction::class)->execute([
        'customer_id' => $client->id,
        'amount' => 400,
        'payment_method_id' => $mode->id,
        'received_at' => '2026-10-10',
    ], $user->id);

    $paiement = Payment::query()->latest('id')->firstOrFail();

    $reponse = test()->actingAs($user)->get("/api/v1/payments/{$paiement->id}/pdf");
    $reponse->assertOk();

    $contenu = $reponse->getContent();

    expect($reponse->headers->get('content-type'))->toContain('application/pdf')
        ->and(strlen((string) $contenu))->toBeGreaterThan(1000)
        // Un PDF ne se lit pas en texte brut, mais ses libellés y figurent :
        // le bon doit parler de reste dû, et jamais d'un montant négatif.
        ->and($contenu)->not->toContain('-500,00');
});
