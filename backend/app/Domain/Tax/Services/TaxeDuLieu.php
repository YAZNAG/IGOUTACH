<?php

declare(strict_types=1);

namespace App\Domain\Tax\Services;

use App\Domain\Warehouses\Models\Warehouse;

/**
 * La TVA d'un lieu, et la décomposition d'un montant.
 *
 * Le taux vit sur le lieu : un dépôt déclaré à 20 % facture à 20 %, les
 * autres continuent hors taxe. Le calcul est réuni ici parce que trois
 * documents en dépendent — la vente, la réception, et les bons imprimés —
 * et que trois arrondis différents produiraient trois totaux différents pour
 * la même marchandise.
 */
final class TaxeDuLieu
{
    /** @var array<int, array{rate: float, included: bool}> */
    private array $connus = [];

    /**
     * Taux du lieu, et façon dont les prix y sont saisis.
     *
     * @return array{rate: float, included: bool}
     */
    public function reglage(?int $warehouseId): array
    {
        if ($warehouseId === null || $warehouseId <= 0) {
            return ['rate' => 0.0, 'included' => false];
        }

        if (isset($this->connus[$warehouseId])) {
            return $this->connus[$warehouseId];
        }

        $lieu = Warehouse::withoutGlobalScopes()
            ->whereKey($warehouseId)
            ->first(['id', 'vat_rate', 'prices_include_vat']);

        return $this->connus[$warehouseId] = [
            'rate' => round((float) ($lieu?->vat_rate ?? 0), 2),
            'included' => (bool) ($lieu?->prices_include_vat ?? false),
        ];
    }

    public function taux(?int $warehouseId): float
    {
        return $this->reglage($warehouseId)['rate'];
    }

    /**
     * Décompose un montant selon le lieu.
     *
     * `$base` est le montant tel qu'il a été saisi, remise déduite : hors
     * taxe là où les prix sont saisis hors taxe, tout compris ailleurs. On
     * rend les trois montants, pour que l'appelant n'ait plus à choisir.
     *
     * @return array{rate: float, ht: float, tva: float, ttc: float}
     */
    public function decomposer(float $base, ?int $warehouseId): array
    {
        $reglage = $this->reglage($warehouseId);
        $taux = $reglage['rate'];

        if ($taux <= 0) {
            $montant = round($base, 2);

            return ['rate' => 0.0, 'ht' => $montant, 'tva' => 0.0, 'ttc' => $montant];
        }

        if ($reglage['included']) {
            // Prix tout compris : la TVA se retire du montant affiché, qui ne
            // doit pas bouger — c'est le prix annoncé au client.
            $ttc = round($base, 2);
            $ht = round($ttc / (1 + $taux / 100), 2);

            return ['rate' => $taux, 'ht' => $ht, 'tva' => round($ttc - $ht, 2), 'ttc' => $ttc];
        }

        $ht = round($base, 2);
        $tva = round($ht * $taux / 100, 2);

        return ['rate' => $taux, 'ht' => $ht, 'tva' => $tva, 'ttc' => round($ht + $tva, 2)];
    }
}
