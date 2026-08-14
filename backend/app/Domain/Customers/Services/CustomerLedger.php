<?php

declare(strict_types=1);

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use Illuminate\Support\Facades\DB;

/**
 * Grand-livre client : toute variation d'encours passe par une écriture.
 * Le blocage automatique s'applique dès que l'encours dépasse le plafond.
 */
final class CustomerLedger
{
    /**
     * @param  float  $amount  signé : positif = augmente l'encours (facture),
     *                         négatif = le réduit (encaissement, retour).
     */
    public function record(
        int $customerId,
        string $type,
        float $amount,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        ?int $userId = null,
    ): CustomerLedgerEntry {
        return DB::transaction(function () use ($customerId, $type, $amount, $referenceType, $referenceId, $note, $userId): CustomerLedgerEntry {
            /** @var Customer $customer */
            $customer = Customer::query()->lockForUpdate()->findOrFail($customerId);

            $newBalance = round((float) $customer->balance + $amount, 2);

            $entry = CustomerLedgerEntry::query()->create([
                'customer_id' => $customer->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'user_id' => $userId,
            ]);

            $attributes = ['balance' => $newBalance];

            // Blocage automatique au dépassement ; le déblocage reste manuel.
            if ((float) $customer->credit_limit > 0 && $newBalance > (float) $customer->credit_limit) {
                $attributes['is_blocked'] = true;
            }

            $customer->update($attributes);

            return $entry;
        });
    }

    /**
     * Retire une ecriture et reconstruit la suite du grand-livre.
     *
     * Les soldes cumules qui suivent doivent etre recalcules : laisser
     * « balance_after » tel quel donnerait un releve dont les lignes ne
     * s'enchainent plus, et un solde client faux.
     *
     * Le deblocage n'est pas automatique : un client bloque pour depassement
     * le reste tant qu'on ne l'a pas debloque a la main, comme a l'ordinaire.
     */
    public function supprimerEcriture(CustomerLedgerEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            /** @var Customer $customer */
            $customer = Customer::query()->lockForUpdate()->findOrFail($entry->customer_id);

            $entry->delete();

            $solde = 0.0;
            CustomerLedgerEntry::query()
                ->where('customer_id', $customer->id)
                ->orderBy('id')
                ->each(function (CustomerLedgerEntry $ligne) use (&$solde): void {
                    $solde = round($solde + (float) $ligne->amount, 2);
                    $ligne->update(['balance_after' => $solde]);
                });

            $customer->update(['balance' => $solde]);
        });
    }
}
