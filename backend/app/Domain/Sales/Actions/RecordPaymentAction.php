<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Customers\Services\CustomerLedger;
use App\Domain\Sales\Models\Payment;
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
     * @param  array{customer_id: int, amount: float, payment_method_id?: int|null, sale_id?: int|null, cash_session_id?: int|null, cheque_reference?: string|null, received_at: string, note?: string|null}  $data
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

            if (isset($data['sale_id'])) {
                /** @var Sale|null $sale */
                $sale = Sale::query()->lockForUpdate()->find($data['sale_id']);
                if ($sale !== null) {
                    $paid = round((float) $sale->paid_amount + $data['amount'], 2);
                    $sale->update([
                        'paid_amount' => $paid,
                        'payment_status' => $paid >= (float) $sale->total ? 'paid' : 'partial',
                    ]);
                }
            }

            return $payment;
        });
    }
}
