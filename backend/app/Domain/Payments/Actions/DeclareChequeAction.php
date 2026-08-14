<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Cheque;
use RuntimeException;

/**
 * Déclare un effet (chèque ou lettre de change) et le porte au portefeuille.
 *
 * Un effet naît toujours d'un règlement : on le reçoit d'un client, ou on le
 * remet à un fournisseur. Le faire naître ailleurs obligerait celui qui
 * encaisse à détenir en plus le droit de gérer le portefeuille — alors que
 * déclarer le papier qu'on vient de recevoir fait partie de l'encaissement.
 *
 * C'est pourquoi cette action est appelée depuis les règlements eux-mêmes,
 * et pas seulement depuis l'écran du portefeuille.
 */
final class DeclareChequeAction
{
    /**
     * @param  array{instrument?: string|null, number: string, cheque_date: string, bank?: string|null, origin: string, drawer_name?: string|null}  $donnees
     * @param  string  $direction  Cheque::DIRECTION_IN (reçu) ou DIRECTION_OUT (remis)
     */
    public function execute(
        array $donnees,
        string $direction,
        float $montant,
        ?int $customerId = null,
        ?int $supplierId = null,
        ?int $createdBy = null,
        ?string $imagePath = null,
    ): Cheque {
        $origine = $donnees['origin'];

        // Un effet signé par un tiers n'a d'intérêt que si l'on sait qui l'a
        // signé : sans ce nom, impossible de le réclamer en cas de rejet.
        if ($origine === Cheque::ORIGIN_THIRD_PARTY && trim((string) ($donnees['drawer_name'] ?? '')) === '') {
            throw new RuntimeException(
                'Le nom porté sur l’effet est obligatoire lorsqu’il est signé par une autre personne.',
            );
        }

        return Cheque::query()->create([
            'instrument' => $donnees['instrument'] ?? Cheque::INSTRUMENT_CHEQUE,
            'number' => $donnees['number'],
            'cheque_date' => $donnees['cheque_date'],
            'amount' => round($montant, 2),
            'bank' => $donnees['bank'] ?? null,
            'direction' => $direction,
            'origin' => $origine,
            'drawer_name' => $donnees['drawer_name'] ?? null,
            'customer_id' => $customerId,
            'supplier_id' => $supplierId,
            'image_path' => $imagePath,
            'status' => Cheque::STATUS_PORTFOLIO,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Règles de validation d'un effet déclaré au fil d'un règlement.
     *
     * @param  list<string>  $origines  provenances acceptables de ce côté-ci
     * @return array<string, mixed>
     */
    public static function reglesImbriquees(array $origines): array
    {
        return [
            'cheque' => ['sometimes', 'array'],
            'cheque.instrument' => ['nullable', 'in:'.Cheque::INSTRUMENT_CHEQUE.','.Cheque::INSTRUMENT_TRAITE],
            'cheque.number' => ['required_with:cheque', 'string', 'max:50'],
            'cheque.cheque_date' => ['required_with:cheque', 'date'],
            'cheque.bank' => ['nullable', 'string', 'max:100'],
            'cheque.origin' => ['required_with:cheque', 'in:'.implode(',', $origines)],
            'cheque.drawer_name' => ['nullable', 'string', 'max:191'],
        ];
    }
}
