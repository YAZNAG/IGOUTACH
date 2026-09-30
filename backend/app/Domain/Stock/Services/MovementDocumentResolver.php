<?php

declare(strict_types=1);

namespace App\Domain\Stock\Services;

use App\Domain\Stock\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Traduit la référence technique d'un mouvement en libellé lisible.
 *
 * `reference_type` mélange deux conventions héritées : des noms de classe
 * complets pour les ventes et les transferts, des chaînes courtes pour le
 * reste (`opening`, `inventory`, `goods_receipt`…). Sans traduction, l'écran
 * affiche « #127 », qui ne désigne rien pour qui lit la fiche article.
 *
 * Les libellés sont résolus par lots, une requête par famille de document :
 * une résolution ligne par ligne ferait cent requêtes sur une page de cent
 * mouvements.
 */
final class MovementDocumentResolver
{
    /** Familles reconnues : type de référence → table et colonne du numéro. */
    private const SOURCES = [
        'App\Domain\Sales\Models\Sale' => ['sales', 'reference', 'sale'],
        'App\Domain\Stock\Models\Transfer' => ['transfers', 'reference', 'transfer'],
        'goods_receipt' => ['goods_receipts', 'number', 'goods_receipt'],
        'inventory' => ['inventories', 'reference', 'inventory'],
    ];

    /** Types sans document propre : le libellé est fixe. */
    private const LIBELLES_FIXES = [
        'opening' => "Inventaire d'ouverture",
        'stock_entry' => 'Entrée de stock',
        'customer_return' => 'Retour client',
    ];

    /**
     * @param  Collection<int, StockMovement>|array<int, StockMovement>  $movements
     * @return array<string, array{label: string, kind: string|null, id: int|null}>
     *                                                                             indexé par « type|id »
     */
    public function pour(Collection|array $movements): array
    {
        $movements = $movements instanceof Collection ? $movements : collect($movements);

        // Un identifiant par famille, sans doublon : dix ventes sur la même
        // facture ne justifient pas dix lectures.
        $parType = [];
        foreach ($movements as $m) {
            $type = (string) $m->reference_type;
            if ($m->reference_id !== null && isset(self::SOURCES[$type])) {
                $parType[$type][] = (int) $m->reference_id;
            }
        }

        $numeros = [];
        foreach ($parType as $type => $ids) {
            [$table, $colonne] = self::SOURCES[$type];
            $numeros[$type] = DB::table($table)
                ->whereIn('id', array_unique($ids))
                ->pluck($colonne, 'id')
                ->all();
        }

        $resolus = [];
        foreach ($movements as $m) {
            $cle = $this->cle($m);
            if (isset($resolus[$cle])) {
                continue;
            }
            $resolus[$cle] = $this->libelle($m, $numeros);
        }

        return $resolus;
    }

    /** Clé de correspondance entre un mouvement et son libellé résolu. */
    public function cle(StockMovement $m): string
    {
        return ((string) $m->reference_type).'|'.((string) $m->reference_id);
    }

    /**
     * @param  array<string, array<int, string|null>>  $numeros
     * @return array{label: string, kind: string|null, id: int|null}
     */
    private function libelle(StockMovement $m, array $numeros): array
    {
        $type = (string) $m->reference_type;
        $id = $m->reference_id !== null ? (int) $m->reference_id : null;

        if (isset(self::SOURCES[$type])) {
            $kind = self::SOURCES[$type][2];
            $numero = $id !== null ? ($numeros[$type][$id] ?? null) : null;

            // Le document a pu être supprimé depuis : on le dit plutôt que
            // d'afficher un blanc qui passerait pour un défaut d'affichage.
            return [
                'label' => $numero !== null ? (string) $numero : "Document supprimé (#{$id})",
                'kind' => $kind,
                'id' => $id,
            ];
        }

        if (isset(self::LIBELLES_FIXES[$type])) {
            // La note porte souvent la précision utile (« Chargement initial
            // DCHIERA OUT ») : elle complète le libellé générique.
            $note = $m->note !== null && $m->note !== '' ? ' — '.$m->note : '';

            return ['label' => self::LIBELLES_FIXES[$type].$note, 'kind' => null, 'id' => null];
        }

        if ($m->note !== null && $m->note !== '') {
            return ['label' => $m->note, 'kind' => null, 'id' => null];
        }

        return ['label' => '—', 'kind' => null, 'id' => null];
    }
}
