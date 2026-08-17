<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Sales\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques et historique transverse d'un article.
 *
 * L'historique agrège ce que l'article a vécu dans les autres modules
 * (ventes, réceptions, transferts, inventaires, retours) en une seule frise
 * datée : consulter une fiche article ne doit pas obliger à ouvrir cinq écrans.
 */
final class ProductInsightsService
{
    /** Fenêtres d'analyse acceptées, en mois. */
    private const PERIODES = ['1m' => 1, '3m' => 3, '6m' => 6, '12m' => 12];

    /**
     * @return array<string, mixed>
     */
    public function statistics(Product $product, string $period = '12m'): array
    {
        $mois = self::PERIODES[$period] ?? 12;
        $from = Carbon::today()->startOfMonth()->subMonths($mois - 1);

        $ventes = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(sale_lines.quantity), 0) as qte, COALESCE(SUM(sale_lines.line_total), 0) as ca')
            ->first();

        $quantite = (int) ($ventes?->qte ?? 0);
        $chiffre = round((float) ($ventes?->ca ?? 0), 2);
        $cout = round((float) $product->cost_price * $quantite, 2);
        $marge = round($chiffre - $cout, 2);

        return [
            'product_id' => $product->id,
            'period' => $period,
            'sales_volume' => $quantite,
            'revenue' => $chiffre,
            'average_sale_price' => $quantite > 0 ? round($chiffre / $quantite, 2) : (float) $product->sale_price,
            'cost_of_goods' => $cout,
            'gross_margin' => $marge,
            // Marge rapportée au chiffre d'affaires : c'est la part réellement
            // conservée sur ce qui a été encaissé.
            'margin_percent' => $chiffre > 0 ? round($marge / $chiffre * 100, 2) : 0.0,
            'purchased_quantity' => $this->quantiteAchetee($product, $from),
            'monthly' => $this->serieMensuelle($product, $from, $mois),
            'by_warehouse' => $this->ventesParLieu($product, $from),
            'top_customers' => $this->meilleursClients($product, $from),
            // Le prix applique, mois par mois. Avec trois tarifs et la
            // possibilite de descendre au cas par cas, la moyenne seule ne dit
            // pas si l'on a brade : l'ecart entre le plus bas et le plus haut,
            // lui, le montre.
            'price_history' => $this->historiquePrix($product, $from, $mois),
            'recent_sales' => $this->ventesDetaillees($product, $from),
            'recent_purchases' => $this->achatsDetailles($product, $from),
        ];
    }

    /**
     * Frise datée de tout ce qui a touché l'article, tous modules confondus.
     *
     * @return list<array<string, mixed>>
     */
    public function history(Product $product, int $limit = 60): array
    {
        $evenements = array_merge(
            $this->evenementsVentes($product),
            $this->evenementsReceptions($product),
            $this->evenementsTransferts($product),
            $this->evenementsInventaires($product),
        );

        usort($evenements, static fn (array $a, array $b): int => strcmp((string) $b['date'], (string) $a['date']));

        return array_slice($evenements, 0, $limit);
    }

    private function quantiteAchetee(Product $product, Carbon $from): int
    {
        return (int) DB::table('goods_receipt_lines')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->where('goods_receipt_lines.product_id', $product->id)
            ->where('goods_receipts.received_at', '>=', $from)
            ->sum('goods_receipt_lines.quantity');
    }

    /**
     * @return list<array{month: string, label: string, quantity: int, revenue: float}>
     */
    private function serieMensuelle(Product $product, Carbon $from, int $mois): array
    {
        $lignes = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->selectRaw('DATE(sales.confirmed_at) as jour, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->groupBy('jour')
            ->get();

        $paniers = [];

        for ($i = 0; $i < $mois; $i++) {
            $m = $from->copy()->addMonths($i);
            $paniers[$m->format('Y-m')] = [
                'month' => $m->format('Y-m'),
                'label' => $this->moisCourt($m),
                'quantity' => 0,
                'revenue' => 0.0,
            ];
        }

        foreach ($lignes as $ligne) {
            $cle = substr((string) $ligne->jour, 0, 7);

            if (isset($paniers[$cle])) {
                $paniers[$cle]['quantity'] += (int) $ligne->qte;
                $paniers[$cle]['revenue'] = round($paniers[$cle]['revenue'] + (float) $ligne->ca, 2);
            }
        }

        return array_values($paniers);
    }

    /**
     * @return list<array{warehouse: string, name: string, quantity: int, revenue: float}>
     */
    private function ventesParLieu(Product $product, Carbon $from): array
    {
        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->selectRaw('warehouses.code as code, warehouses.name as nom, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
            ->orderByDesc('qte')
            ->get()
            ->map(fn ($r): array => [
                'warehouse' => (string) $r->code,
                'name' => (string) $r->nom,
                'quantity' => (int) $r->qte,
                'revenue' => round((float) $r->ca, 2),
            ])->all();
    }

    /**
     * Prix de vente applique mois par mois : le plus bas, le moyen, le plus
     * haut, face au cout de revient.
     *
     * Les mois sans vente restent a null plutot qu'a zero : un prix nul n'a
     * jamais ete pratique, et une courbe qui plonge a zero ferait croire a une
     * vente a perte.
     *
     * @return list<array<string, mixed>>
     */
    private function historiquePrix(Product $product, Carbon $from, int $mois): array
    {
        $lignes = DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->selectRaw('DATE(sales.confirmed_at) as jour, sale_lines.unit_price as prix, sale_lines.quantity as qte')
            ->get();

        $paniers = [];

        for ($i = 0; $i < $mois; $i++) {
            $m = $from->copy()->addMonths($i);
            $paniers[$m->format('Y-m')] = [
                'month' => $m->format('Y-m'),
                'label' => $this->moisCourt($m),
                'min' => null,
                'avg' => null,
                'max' => null,
                'cost' => round((float) $product->cost_price, 2),
                'somme' => 0.0,
                'unites' => 0,
            ];
        }

        foreach ($lignes as $ligne) {
            $cle = substr((string) $ligne->jour, 0, 7);
            if (! isset($paniers[$cle])) {
                continue;
            }

            $prix = round((float) $ligne->prix, 2);

            $paniers[$cle]['min'] = $paniers[$cle]['min'] === null
                ? $prix
                : min($paniers[$cle]['min'], $prix);
            $paniers[$cle]['max'] = $paniers[$cle]['max'] === null
                ? $prix
                : max($paniers[$cle]['max'], $prix);
            // Moyenne ponderee par les quantites : dix pieces a 100 pesent
            // plus qu'une piece a 200.
            $paniers[$cle]['somme'] += $prix * (int) $ligne->qte;
            $paniers[$cle]['unites'] += (int) $ligne->qte;
        }

        return array_values(array_map(static function (array $panier): array {
            $panier['avg'] = $panier['unites'] > 0
                ? round($panier['somme'] / $panier['unites'], 2)
                : null;
            unset($panier['somme'], $panier['unites']);

            return $panier;
        }, $paniers));
    }

    /**
     * Chaque vente de l'article, avec le prix qui y a ete applique.
     *
     * @return list<array<string, mixed>>
     */
    private function ventesDetaillees(Product $product, Carbon $from, int $limit = 50): array
    {
        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->orderByDesc('sales.confirmed_at')
            ->limit($limit)
            ->get([
                'sales.id as sale_id',
                'sales.reference as reference',
                'sales.confirmed_at as date',
                'customers.name as client',
                'warehouses.code as lieu',
                'sale_lines.quantity as qte',
                'sale_lines.unit_price as prix',
                'sale_lines.line_total as total',
                'sale_lines.price_type_code as tarif',
            ])
            ->map(fn ($r): array => [
                'sale_id' => (int) $r->sale_id,
                'reference' => (string) $r->reference,
                'date' => substr((string) $r->date, 0, 10),
                'customer' => $r->client !== null ? (string) $r->client : null,
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'quantity' => (int) $r->qte,
                'unit_price' => round((float) $r->prix, 2),
                'line_total' => round((float) $r->total, 2),
                'price_type' => $r->tarif !== null ? (string) $r->tarif : null,
            ])->all();
    }

    /**
     * Chaque reception de l'article, avec son prix d'achat.
     *
     * @return list<array<string, mixed>>
     */
    private function achatsDetailles(Product $product, Carbon $from, int $limit = 50): array
    {
        return DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as r', 'r.id', '=', 'l.goods_receipt_id')
            ->leftJoin('suppliers as f', 'f.id', '=', 'r.supplier_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->where('l.product_id', $product->id)
            ->where('r.received_at', '>=', $from)
            ->orderByDesc('r.received_at')
            ->limit($limit)
            ->get([
                'r.id as receipt_id',
                'r.number as reference',
                'r.received_at as date',
                'f.name as fournisseur',
                'w.code as lieu',
                'l.quantity as qte',
                'l.unit_price as prix',
            ])
            ->map(fn ($r): array => [
                'receipt_id' => (int) $r->receipt_id,
                'reference' => (string) $r->reference,
                'date' => substr((string) $r->date, 0, 10),
                'supplier' => $r->fournisseur !== null ? (string) $r->fournisseur : null,
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'quantity' => (int) $r->qte,
                'unit_price' => round((float) $r->prix, 2),
                'line_total' => round((float) $r->prix * (int) $r->qte, 2),
            ])->all();
    }

    /**
     * @return list<array{customer: string, quantity: int, revenue: float}>
     */
    private function meilleursClients(Product $product, Carbon $from): array
    {
        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('sale_lines.product_id', $product->id)
            ->where('sales.type', Sale::TYPE_INVOICE)
            ->where('sales.status', Sale::STATUS_CONFIRMED)
            ->where('sales.confirmed_at', '>=', $from)
            ->selectRaw('customers.name as nom, SUM(sale_lines.quantity) as qte, SUM(sale_lines.line_total) as ca')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('ca')
            ->limit(5)
            ->get()
            ->map(fn ($r): array => [
                'customer' => (string) $r->nom,
                'quantity' => (int) $r->qte,
                'revenue' => round((float) $r->ca, 2),
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function evenementsVentes(Product $product): array
    {
        return DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->where('sale_lines.product_id', $product->id)
            ->whereNotNull('sales.confirmed_at')
            ->selectRaw('sales.id, sales.reference, sales.type, sales.confirmed_at, sale_lines.quantity, sale_lines.line_total, customers.name as client, warehouses.code as lieu')
            ->orderByDesc('sales.confirmed_at')
            ->limit(40)
            ->get()
            ->map(fn ($r): array => [
                'module' => $r->type === Sale::TYPE_QUOTE ? 'quote' : 'sale',
                'label' => $r->type === Sale::TYPE_QUOTE ? 'Devis' : 'Vente',
                'date' => (string) $r->confirmed_at,
                'reference' => (string) $r->reference,
                'quantity' => -(int) $r->quantity,
                'amount' => round((float) $r->line_total, 2),
                'party' => $r->client !== null ? (string) $r->client : 'Client de passage',
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'link' => '/ventes',
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function evenementsReceptions(Product $product): array
    {
        return DB::table('goods_receipt_lines')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'goods_receipts.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'goods_receipts.warehouse_id')
            ->where('goods_receipt_lines.product_id', $product->id)
            ->selectRaw('goods_receipts.id, goods_receipts.number, goods_receipts.received_at, goods_receipt_lines.quantity, goods_receipt_lines.unit_price, suppliers.name as fournisseur, warehouses.code as lieu')
            ->orderByDesc('goods_receipts.received_at')
            ->limit(40)
            ->get()
            ->map(fn ($r): array => [
                'module' => 'receipt',
                'label' => 'Réception',
                'date' => (string) $r->received_at,
                'reference' => (string) $r->number,
                'quantity' => (int) $r->quantity,
                'amount' => round((float) $r->quantity * (float) $r->unit_price, 2),
                'party' => $r->fournisseur !== null ? (string) $r->fournisseur : '—',
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'link' => '/goods-receipts',
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function evenementsTransferts(Product $product): array
    {
        return DB::table('transfer_lines')
            ->join('transfers', 'transfers.id', '=', 'transfer_lines.transfer_id')
            ->leftJoin('warehouses as depart', 'depart.id', '=', 'transfers.from_warehouse_id')
            ->leftJoin('warehouses as arrivee', 'arrivee.id', '=', 'transfers.to_warehouse_id')
            ->where('transfer_lines.product_id', $product->id)
            ->selectRaw('transfers.id, transfers.reference, transfers.created_at, transfer_lines.quantity_sent, depart.code as depart, arrivee.code as arrivee')
            ->orderByDesc('transfers.created_at')
            ->limit(20)
            ->get()
            ->map(fn ($r): array => [
                'module' => 'transfer',
                'label' => 'Transfert',
                'date' => (string) $r->created_at,
                'reference' => (string) $r->reference,
                'quantity' => (int) $r->quantity_sent,
                'amount' => null,
                'party' => trim(((string) $r->depart).' → '.((string) $r->arrivee)),
                'warehouse' => (string) $r->depart,
                'link' => '/transferts',
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function evenementsInventaires(Product $product): array
    {
        return DB::table('inventory_lines')
            ->join('inventories', 'inventories.id', '=', 'inventory_lines.inventory_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'inventories.warehouse_id')
            ->where('inventory_lines.product_id', $product->id)
            ->where('inventories.status', 'approved')
            ->selectRaw('inventories.id, inventories.reference, inventories.counted_at, inventory_lines.difference, inventory_lines.reason, warehouses.code as lieu')
            ->orderByDesc('inventories.counted_at')
            ->limit(20)
            ->get()
            ->map(fn ($r): array => [
                'module' => 'inventory',
                'label' => 'Inventaire',
                'date' => (string) $r->counted_at,
                'reference' => (string) $r->reference,
                'quantity' => (int) $r->difference,
                'amount' => null,
                'party' => $r->reason !== null && $r->reason !== '' ? (string) $r->reason : 'Régularisation',
                'warehouse' => $r->lieu !== null ? (string) $r->lieu : null,
                'link' => '/inventaire',
            ])->all();
    }

    private function moisCourt(Carbon $date): string
    {
        $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

        return $mois[$date->month - 1].' '.$date->format('y');
    }
}
