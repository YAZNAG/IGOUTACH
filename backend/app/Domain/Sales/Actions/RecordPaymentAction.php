<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\PaymentAllocation;
use App\Domain\Sales\Models\Sale;
use App\Domain\Settings\Models\PaymentMethod;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Encaissement client : réduit l'encours, affecte éventuellement une
 * facture, alimente la session de caisse ouverte pour les espèces.
 */
final class RecordPaymentAction
{
    public function __construct(
        private readonly CustomerLedger $ledger,
        private readonly DocumentNumberGeneratorInterface $numbers,
    ) {}

    /**
     * @param  array{customer_id: int, amount: float, payment_method_id?: int|null, sale_id?: int|null, cash_session_id?: int|null, cheque_reference?: string|null, cheque_id?: int|null, received_at: string, note?: string|null, allocations?: list<array{sale_id: int, amount: float}>}  $data
     */
    public function execute(array $data, ?int $userId = null): Payment
    {
        if ($data['amount'] <= 0) {
            throw new RuntimeException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($data, $userId): Payment {
            $method = isset($data['payment_method_id'])
                ? PaymentMethod::query()->find($data['payment_method_id'])
                : null;

            $isCheque = $method !== null && str_contains(mb_strtolower($method->name), 'ch');

            $payment = Payment::query()->create([
                'reference' => $this->numbers->next('payment'),
                'customer_id' => $data['customer_id'],
                'sale_id' => $data['sale_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cash_session_id' => $data['cash_session_id'] ?? null,
                'amount' => $data['amount'],
                'cheque_status' => $isCheque ? Payment::CHEQUE_RECEIVED : null,
                'cheque_reference' => $data['cheque_reference'] ?? null,
                'cheque_id' => $data['cheque_id'] ?? null,
                'received_at' => $data['received_at'],
                'user_id' => $userId,
                'note' => $data['note'] ?? null,
            ]);

            $this->ledger->record(
                customerId: $data['customer_id'],
                type: CustomerLedgerEntry::TYPE_PAYMENT,
                amount: -$data['amount'],
                referenceType: Payment::class,
                referenceId: $payment->id,
                note: 'Encaissement '.$payment->reference,
                userId: $userId,
            );

            // Ventilation explicite : le versement solde plusieurs factures,
            // chacune pour la part indiquee. A defaut, on retombe sur la
            // facture unique historique.
            $ventilations = $data['allocations'] ?? (
                isset($data['sale_id'])
                    ? [['sale_id' => (int) $data['sale_id'], 'amount' => $data['amount']]]
                    : []
            );

            foreach ($ventilations as $part) {
                /** @var Sale|null $sale */
                $sale = Sale::query()->lockForUpdate()->find($part['sale_id']);
                if ($sale === null) {
                    continue;
                }

                $montant = round((float) $part['amount'], 2);
                $restant = round((float) $sale->total - (float) $sale->paid_amount, 2);

                // Encaisser plus que le du sur une facture creerait un
                // trop-percu invisible : la facture paraitrait soldee et
                // l'excedent disparaitrait du suivi.
                if ($montant > $restant) {
                    throw new RuntimeException(sprintf(
                        'La facture %s ne doit plus que %.2f DH : %.2f DH ne peuvent pas y etre affectes.',
                        $sale->reference,
                        $restant,
                        $montant,
                    ));
                }

                PaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'sale_id' => $sale->id,
                    'amount' => $montant,
                ]);

                $paid = round((float) $sale->paid_amount + $montant, 2);
                $sale->update([
                    'paid_amount' => $paid,
                    'payment_status' => $paid >= (float) $sale->total ? 'paid' : 'partial',
                ]);
            }

            return $payment;
        });
    }
}
