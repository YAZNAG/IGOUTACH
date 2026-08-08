<?php

declare(strict_types=1);

namespace App\Domain\Purchasing\Actions;

use App\Domain\Purchasing\Models\GoodsReceipt;
use App\Domain\Purchasing\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Règle (totalement ou partiellement) le crédit fournisseur d'un bon de
 * réception : enregistre le paiement avec sa méthode et met à jour le
 * statut de règlement du bon. Transaction unique.
 */
final class PaySupplierCreditAction
{
    public function execute(
        GoodsReceipt $receipt,
        float $amount,
        ?int $paymentMethodId,
        string $paidAt,
        ?string $notes,
        ?int $createdBy,
        ?int $chequeId = null
    ): SupplierPayment {
        return DB::transaction(function () use ($receipt, $amount, $paymentMethodId, $paidAt, $notes, $createdBy, $chequeId): SupplierPayment {
            /** @var GoodsReceipt $locked */
            $locked = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $locked->load('lines');

            $remaining = $locked->remainingAmount();

            if ($remaining <= 0) {
                throw new RuntimeException('Ce bon de réception est déjà entièrement réglé.');
            }

            if ($amount <= 0) {
                throw new RuntimeException('Le montant du règlement doit être supérieur à zéro.');
            }

            if ($amount > $remaining + 0.005) {
                throw new RuntimeException(
                    'Le montant dépasse le crédit restant ('.number_format($remaining, 2, '.', '').' DH).'
                );
            }

            $payment = SupplierPayment::create([
                'goods_receipt_id' => $locked->id,
                'supplier_id' => $locked->supplier_id,
                'payment_method_id' => $paymentMethodId,
                'cheque_id' => $chequeId,
                'amount' => number_format($amount, 2, '.', ''),
                'paid_at' => $paidAt,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $newPaid = (float) $locked->amount_paid + $amount;
            $total = $locked->totalAmount();

            $locked->update([
                'amount_paid' => number_format($newPaid, 2, '.', ''),
                'payment_status' => $newPaid >= $total - 0.005
                    ? GoodsReceipt::PAYMENT_PAID
                    : GoodsReceipt::PAYMENT_PARTIAL,
            ]);

            return $payment;
        });
    }
}
