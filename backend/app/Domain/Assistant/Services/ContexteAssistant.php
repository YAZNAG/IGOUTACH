<?php

declare(strict_types=1);

namespace App\Domain\Assistant\Services;

use App\Domain\Sales\Services\CashBoxService;
use App\Domain\Sales\Services\DashboardMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ce que l'assistant sait de l'entreprise au moment de répondre.
 *
 * Le modèle ne lit pas la base : on lui prépare, par module, un extrait
 * compact et déjà calculé (taux de vente, couverture, quantité suggérée…).
 * Il explique, hiérarchise et conseille ; les chiffres viennent d'ici.
 */
final class ContexteAssistant
{
    public const MODULES = ['achats', 'ventes', 'credits', 'tresorerie', 'general'];

    public function __construct(
        private readonly DashboardMetricsService $metriques,
        private readonly CashBoxService $caisse,
    ) {}

    /**
     * Les modules proposés à l'écran, avec des questions de départ.
     *
     * @return list<array{id: string, label: string, description: string, suggestions: list<string>}>
     */
    public function modules(): array
    {
        return [
            [
                'id' => 'achats',
                'label' => 'Articles et achats',
                'description' => 'Articles qui se vendent vite, stock qui s’épuise, quantités à commander.',
                'suggestions' => [
                    'Quels articles dois-je commander en priorité, et en quelle quantité ?',
                    'Quels articles risquent la rupture dans les 7 jours ?',
                    'Quels articles ont beaucoup de stock mais ne se vendent plus ?',
                ],
            ],
            [
                'id' => 'ventes',
                'label' => 'Ventes',
                'description' => 'Chiffre d’affaires par lieu, tendance, meilleurs articles et clients.',
                'suggestions' => [
                    'Comment évoluent les ventes ce mois-ci par rapport au mois précédent ?',
                    'Quel lieu vend le mieux et lequel décroche ?',
                    'Quels sont les articles les plus vendus sur 30 jours ?',
                ],
            ],
            [
                'id' => 'credits',
                'label' => 'Crédits clients',
                'description' => 'Encours, ancienneté des créances, clients à relancer.',
                'suggestions' => [
                    'Quels clients dois-je relancer en premier ?',
                    'Quel est le total des créances de plus de 60 jours ?',
                    'Quels clients dépassent leur plafond de crédit ?',
                ],
            ],
            [
                'id' => 'tresorerie',
                'label' => 'Caisses et charges',
                'description' => 'Caisses des lieux, transferts en attente, charges du mois.',
                'suggestions' => [
                    'Quel est l’état des caisses aujourd’hui ?',
                    'Quelles charges pèsent le plus ce mois-ci ?',
                    'Y a-t-il des transferts de caisse à confirmer ?',
                ],
            ],
            [
                'id' => 'general',
                'label' => 'Vue d’ensemble',
                'description' => 'Une question libre sur l’activité, avec le résumé global sous les yeux.',
                'suggestions' => [
                    'Résume la santé de l’entreprise en cinq points.',
                    'Quels sont les trois chantiers prioritaires cette semaine ?',
                ],
            ],
        ];
    }

    /**
     * Le contexte d'un module, en texte prêt à joindre à la question.
     *
     * @return array{texte: string, lignes: int}
     */
    public function construire(string $module): array
    {
        $blocs = match ($module) {
            'achats' => $this->achats(),
            'ventes' => $this->ventes(),
            'credits' => $this->credits(),
            'tresorerie' => $this->tresorerie(),
            default => $this->general(),
        };

        $texte = "Date du jour : ".now()->format('d/m/Y')."\n\n";
        $lignes = 0;

        foreach ($blocs as $titre => $contenu) {
            $texte .= "## {$titre}\n";
            $texte .= is_string($contenu) ? $contenu : json_encode($contenu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $texte .= "\n\n";
            $lignes += is_array($contenu) ? count($contenu) : substr_count($contenu, "\n");
        }

        return ['texte' => $texte, 'lignes' => $lignes];
    }

    /**
     * Articles : vitesse de vente, couverture du stock, quantité suggérée.
     *
     * Le taux de vente se lit sur 90 jours (lissé), sur 30 jours quand le
     * produit est récent. La quantité suggérée couvre 45 jours (un mois de
     * vente plus le délai de livraison), stock actuel déduit.
     *
     * @return array<string, mixed>
     */
    private function achats(): array
    {
        $depuis30 = Carbon::today()->subDays(30);
        $depuis90 = Carbon::today()->subDays(90);

        $ventes = fn (Carbon $depuis) => DB::table('sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->where('sales.type', 'invoice')
            ->where('sales.status', 'confirmed')
            ->where('sales.confirmed_at', '>=', $depuis)
            ->groupBy('sale_lines.product_id')
            ->select('sale_lines.product_id', DB::raw('SUM(sale_lines.quantity) as qte'));

        $rows = DB::table('products as p')
            ->leftJoinSub(DB::table('stocks')->groupBy('product_id')->select('product_id', DB::raw('SUM(quantity) as qte')), 'st', 'st.product_id', '=', 'p.id')
            ->leftJoinSub($ventes($depuis30), 'v30', 'v30.product_id', '=', 'p.id')
            ->leftJoinSub($ventes($depuis90), 'v90', 'v90.product_id', '=', 'p.id')
            ->where('p.is_active', true)
            ->whereNull('p.deleted_at')
            ->select('p.id', 'p.sku', 'p.name', 'p.cost_price', 'p.min_stock')
            ->selectRaw('COALESCE(st.qte, 0) as stock, COALESCE(v30.qte, 0) as v30, COALESCE(v90.qte, 0) as v90')
            ->get();

        $analyse = $rows->map(function ($r): array {
            $v30 = (int) $r->v30;
            $v90 = (int) $r->v90;
            // Un article vendu ce mois mais pas avant : le rythme récent fait foi.
            $taux = $v90 > $v30 ? $v90 / 90 : $v30 / 30;
            $stock = (int) $r->stock;
            $couverture = $taux > 0 ? (int) floor($stock / $taux) : null;
            $suggere = $taux > 0 ? max(0, (int) ceil($taux * 45 - $stock)) : 0;

            return [
                'ref' => $r->sku,
                'article' => $r->name,
                'stock' => $stock,
                'ventes_30j' => $v30,
                'ventes_90j' => $v90,
                'taux_par_jour' => round($taux, 2),
                'couverture_jours' => $couverture,
                'stock_min' => $r->min_stock !== null ? (int) $r->min_stock : null,
                'prix_achat' => round((float) $r->cost_price, 2),
                'qte_suggeree_45j' => $suggere,
                'id' => $r->id,
            ];
        });

        // Ce qui se vend : les 60 plus rapides, puis ceux qui s'épuisent.
        $rapides = $analyse->where('ventes_30j', '>', 0)->sortByDesc('ventes_30j')->take(60)->values();
        $rupture = $analyse
            ->filter(fn (array $a): bool => $a['couverture_jours'] !== null && $a['couverture_jours'] <= 10)
            ->sortBy('couverture_jours')->take(40)->values();
        $dormants = $analyse
            ->filter(fn (array $a): bool => $a['stock'] >= 10 && $a['ventes_90j'] === 0)
            ->sortByDesc('stock')->take(30)->values();

        $dernierFournisseur = DB::table('goods_receipt_lines as l')
            ->join('goods_receipts as g', 'g.id', '=', 'l.goods_receipt_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'g.supplier_id')
            ->whereIn('l.product_id', $rapides->pluck('id')->merge($rupture->pluck('id'))->unique()->all())
            ->orderByDesc('g.received_at')
            ->get(['l.product_id', 's.name as fournisseur', 'l.unit_price', 'g.received_at'])
            ->unique('product_id')
            ->keyBy('product_id');

        $enrichir = fn (array $a): array => [
            ...collect($a)->except('id')->all(),
            'dernier_fournisseur' => $dernierFournisseur[$a['id']]->fournisseur ?? null,
            'dernier_prix' => isset($dernierFournisseur[$a['id']]) ? (float) $dernierFournisseur[$a['id']]->unit_price : null,
        ];

        return [
            'Méthode' => "taux_par_jour = ventes sur 90 jours / 90 (ou sur 30 jours si l'article est récent). "
                ."couverture_jours = stock / taux. qte_suggeree_45j = quantité à commander pour tenir 45 jours, stock déduit. "
                ."Un article sans ventes a une couverture nulle (non renseignée).",
            'Articles qui se vendent le plus (30 jours)' => $rapides->map($enrichir)->all(),
            'Articles en risque de rupture (couverture ≤ 10 jours)' => $rupture->map($enrichir)->all(),
            'Articles dormants (stock ≥ 10, aucune vente en 90 jours)' => $dormants->map(fn (array $a): array => collect($a)->except('id')->all())->all(),
            'Totaux' => [
                'articles_actifs' => $analyse->count(),
                'articles_vendus_30j' => $analyse->where('ventes_30j', '>', 0)->count(),
                'articles_en_rupture' => $analyse->where('stock', '<=', 0)->where('ventes_90j', '>', 0)->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function ventes(): array
    {
        $ca = fn (Carbon $debut, Carbon $fin) => DB::table('sales')
            ->where('type', 'invoice')->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$debut, $fin])
            ->selectRaw('COUNT(*) as factures, COALESCE(SUM(total), 0) as ca, COALESCE(SUM(paid_amount), 0) as encaisse')
            ->first();

        $m = $ca(Carbon::today()->subDays(29)->startOfDay(), now());
        $p = $ca(Carbon::today()->subDays(59)->startOfDay(), Carbon::today()->subDays(30)->endOfDay());

        return [
            '30 derniers jours vs 30 jours précédents' => [
                'periode_actuelle' => ['factures' => (int) $m->factures, 'ca' => round((float) $m->ca, 2), 'encaisse' => round((float) $m->encaisse, 2)],
                'periode_precedente' => ['factures' => (int) $p->factures, 'ca' => round((float) $p->ca, 2), 'encaisse' => round((float) $p->encaisse, 2)],
                'variation_ca_pct' => (float) $p->ca > 0 ? round(((float) $m->ca - (float) $p->ca) / (float) $p->ca * 100, 1) : null,
            ],
            'Chiffre d’affaires par lieu (30 jours)' => $this->metriques->revenueByWarehouse(30),
            'Tendance quotidienne (30 jours)' => $this->metriques->salesTrend(30),
            'Meilleurs articles (30 jours)' => $this->metriques->topProducts(30, 15),
            'Meilleurs clients (30 jours)' => $this->metriques->topCustomers(30, 10),
            'Modes de paiement (30 jours)' => $this->metriques->paymentMix(30),
        ];
    }

    /** @return array<string, mixed> */
    private function credits(): array
    {
        $debiteurs = DB::table('customers as c')
            ->leftJoin('warehouses as w', 'w.id', '=', 'c.warehouse_id')
            ->whereNull('c.deleted_at')
            ->where('c.balance', '>', 0)
            ->orderByDesc('c.balance')
            ->limit(40)
            ->get(['c.code', 'c.name', 'c.phone', 'c.balance', 'c.credit_limit', 'c.is_blocked', 'w.code as lieu'])
            ->map(fn ($c): array => [
                'code' => $c->code,
                'client' => $c->name,
                'telephone' => $c->phone,
                'lieu' => $c->lieu,
                'encours' => round((float) $c->balance, 2),
                'plafond' => round((float) $c->credit_limit, 2),
                'bloque' => (bool) $c->is_blocked,
                'plus_ancienne_facture_due' => DB::table('sales')
                    ->where('customer_id', DB::table('customers')->where('code', $c->code)->value('id'))
                    ->where('type', 'invoice')->where('status', 'confirmed')
                    ->whereRaw('paid_amount < total')
                    ->min('confirmed_at'),
            ])->all();

        return [
            'Balance âgée (toutes créances)' => $this->metriques->agingBuckets(),
            'Clients débiteurs (40 plus gros encours)' => $debiteurs,
            'Totaux' => [
                'encours_total' => round((float) DB::table('customers')->whereNull('deleted_at')->where('balance', '>', 0)->sum('balance'), 2),
                'clients_debiteurs' => DB::table('customers')->whereNull('deleted_at')->where('balance', '>', 0)->count(),
                'clients_bloques' => DB::table('customers')->whereNull('deleted_at')->where('is_blocked', true)->count(),
                'clients_au_dessus_du_plafond' => DB::table('customers')->whereNull('deleted_at')->where('credit_limit', '>', 0)->whereColumn('balance', '>', 'credit_limit')->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function tresorerie(): array
    {
        $lieux = DB::table('warehouses')->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        $caisses = $lieux->map(function ($l): array {
            $session = DB::table('cash_sessions')->where('warehouse_id', $l->id)->whereNull('closed_at')->orderByDesc('id')->first();
            $solde = $this->caisse->solde((int) $l->id, null, $session !== null ? Carbon::parse($session->opened_at) : Carbon::today());

            return [
                'lieu' => $l->code,
                'journee_ouverte' => $session !== null,
                'fonds_ouverture' => $session !== null ? round((float) $session->opening_amount, 2) : 0.0,
                'entrees_especes' => $solde['cash_in'],
                'charges_especes' => $solde['cash_expenses'],
                'remis_caisse_generale' => $solde['remitted'],
                'attendu_en_caisse' => round(($session !== null ? (float) $session->opening_amount : 0) + $solde['cash_in'] - $solde['cash_expenses'] - $solde['remitted'], 2),
            ];
        })->all();

        $remises = DB::table('cash_remittances as r')
            ->join('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->where('r.status', 'pending')
            ->orderBy('r.remitted_at')
            ->get(['r.reference', 'w.code as lieu', 'r.amount', 'r.remitted_at'])
            ->map(fn ($r): array => ['reference' => $r->reference, 'lieu' => $r->lieu, 'montant' => round((float) $r->amount, 2), 'date' => $r->remitted_at])
            ->all();

        return [
            'Caisses des lieux (journée en cours)' => $caisses,
            'Transferts de caisse en attente de confirmation' => $remises,
            'Charges par catégorie (30 jours)' => $this->metriques->expensesByCategory(30),
            'Résumé financier' => $this->metriques->financialSummary(),
        ];
    }

    /** @return array<string, mixed> */
    private function general(): array
    {
        return [
            'Résumé financier' => $this->metriques->financialSummary(),
            'Chiffre d’affaires par lieu (30 jours)' => $this->metriques->revenueByWarehouse(30),
            'Balance âgée des créances' => $this->metriques->agingBuckets(),
            'Meilleurs articles (30 jours)' => $this->metriques->topProducts(30, 10),
            'Charges par catégorie (30 jours)' => $this->metriques->expensesByCategory(30),
            'Stock par lieu' => $this->metriques->stockByWarehouse(),
        ];
    }
}
