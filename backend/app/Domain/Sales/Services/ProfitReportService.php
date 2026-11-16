<?php

declare(strict_types=1);

namespace App\Domain\Sales\Services;

use Illuminate\Support\Facades\DB;

/**
 * Ce que rapporte chaque lieu, chaque client, chaque fournisseur.
 *
 * Le chiffre d'affaires dit combien on a vendu ; le bénéfice dit combien il
 * en reste. Les deux ensemble seulement permettent de trancher : un gros
 * client qui n'achète que des articles à faible marge pèse moins qu'un petit
 * qui prend les bons.
 *
 * Le coût retenu est « products.cost_price », le même que le rapport des
 * marges par article. Deux bases différentes donneraient deux bénéfices
 * différents pour la même période, et personne ne saurait lequel croire.
 */
final class ProfitReportService
{
    /**
     * Requête de base : les lignes des ventes confirmées de la période.
     */
    private function lignes(string $du, string $au): \Illuminate\Database\Query\Builder
    {
        $debut = $du.' 00:00:00';
        $fin = $au.' 23:59:59';

        // Les ventes de la période, en positif.
        $ventes = DB::table('sale_lines')
            ->join('sales as s', 's.id', '=', 'sale_lines.sale_id')
            ->where('s.type', 'invoice')
            ->where('s.status', 'confirmed')
            ->whereBetween('s.confirmed_at', [$debut, $fin])
            ->selectRaw('sale_lines.sale_id, sale_lines.product_id, sale_lines.quantity, sale_lines.quantity as cost_quantity, sale_lines.line_total, s.confirmed_at as occurred_at');

        // Les retours de la période, en négatif, au prix de la vente
        // d'origine. Un article revenu défectueux ne rend pas son coût : il
        // est perdu, seul le chiffre d'affaires recule.
        $retours = DB::table('sale_returns as r')
            ->whereBetween('r.occurred_at', [$debut, $fin])
            ->selectRaw("r.sale_id, r.product_id, -r.quantity, CASE WHEN r.`condition` = 'resellable' THEN -r.quantity ELSE 0 END, -r.amount, r.occurred_at");

        return DB::query()
            ->fromSub($ventes->unionAll($retours), 'sale_lines')
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id');
    }

    /** Les trois mesures que porte chaque ligne du rapport. */
    private function mesures(): array
    {
        return [
            DB::raw('ROUND(SUM(sale_lines.line_total), 2) as revenue'),
            DB::raw('ROUND(SUM(sale_lines.cost_quantity * products.cost_price), 2) as cost'),
            DB::raw('ROUND(SUM(sale_lines.line_total) - SUM(sale_lines.cost_quantity * products.cost_price), 2) as profit'),
        ];
    }

    /**
     * @param  iterable<object>  $rangs
     * @return list<array<string, mixed>>
     */
    private function formater(iterable $rangs): array
    {
        $sortie = [];

        foreach ($rangs as $r) {
            $ca = (float) $r->revenue;
            $benefice = (float) $r->profit;

            $sortie[] = [
                'name' => (string) $r->name,
                'documents' => (int) ($r->documents ?? 0),
                'revenue' => $ca,
                'cost' => (float) $r->cost,
                'profit' => $benefice,
                // Le taux situe la performance : 500 DH de marge sur 1 000 DH
                // de vente n'est pas la même affaire que sur 50 000 DH.
                'margin_percent' => $ca > 0 ? round(($benefice / $ca) * 100, 1) : null,
                'quantity' => isset($r->quantity) ? (int) $r->quantity : null,
            ];
        }

        return $sortie;
    }

    /**
     * Bénéfice par lieu de vente.
     *
     * @return list<array<string, mixed>>
     */
    public function parLieu(string $du, string $au): array
    {
        return $this->formater(
            $this->lignes($du, $au)
                ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
                ->groupBy('warehouses.id', 'warehouses.code')
                ->orderByDesc('profit')
                ->get([
                    DB::raw('warehouses.code as name'),
                    DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                    ...$this->mesures(),
                ]),
        );
    }

    /**
     * Bénéfice par client, le client de passage compris.
     *
     * Les ventes sans client nommé sont regroupées sous « Client de passage » :
     * les écarter ferait manquer une part du bénéfice, et les éclater en
     * lignes anonymes n'apprendrait rien.
     *
     * @return list<array<string, mixed>>
     */
    public function parClient(string $du, string $au, int $limite = 12): array
    {
        $nommes = $this->formater(
            $this->lignes($du, $au)
                ->join('customers', 'customers.id', '=', 'sales.customer_id')
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('profit')
                ->limit($limite)
                ->get([
                    DB::raw('customers.name as name'),
                    DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                    ...$this->mesures(),
                ]),
        );

        $passage = $this->formater(
            $this->lignes($du, $au)
                ->whereNull('sales.customer_id')
                ->get([
                    DB::raw("'Client de passage' as name"),
                    DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                    ...$this->mesures(),
                ]),
        );

        // Le passage ne se classe pas : il n'est pas un client parmi d'autres,
        // c'est le total de ceux qu'on ne suit pas. Il ferme la liste.
        $lignes = $nommes;
        foreach ($passage as $p) {
            if ($p['documents'] > 0) {
                $lignes[] = $p;
            }
        }

        return $lignes;
    }

    /**
     * Bénéfice par article.
     *
     * C'est le découpage le plus fin : il dit non seulement ce qui se vend,
     * mais ce qui vaut la peine d'être vendu. Un article très demandé à marge
     * nulle occupe du stock sans rien rapporter.
     *
     * @return list<array<string, mixed>>
     */
    public function parArticle(string $du, string $au, int $limite = 20): array
    {
        return $this->formater(
            $this->lignes($du, $au)
                ->groupBy('products.id', 'products.sku', 'products.name')
                ->orderByDesc('profit')
                ->limit($limite)
                ->get([
                    DB::raw("CONCAT(products.sku, ' — ', products.name) as name"),
                    DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                    DB::raw('SUM(sale_lines.quantity) as quantity'),
                    ...$this->mesures(),
                ]),
        );
    }

    /**
     * Tous les articles vendus sur la période, sans limite, avec leur bénéfice.
     *
     * Le classement « par article » ne garde que les meilleurs ; ici on veut
     * la liste entière, pour la fouiller : un article qui se vend à perte ne
     * figure jamais dans un palmarès, et c'est justement lui qu'on cherche.
     *
     * @return list<array<string, mixed>>
     */
    public function tousLesArticles(string $du, string $au, int $lieu = 0, string $recherche = ''): array
    {
        $lignes = $this->lignes($du, $au)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when($lieu > 0, fn ($q) => $q->where('sales.warehouse_id', $lieu))
            ->when($recherche !== '', function ($q) use ($recherche): void {
                $terme = '%'.$recherche.'%';
                $q->where(fn ($w) => $w->where('products.sku', 'like', $terme)->orWhere('products.name', 'like', $terme));
            })
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.cost_price', 'categories.name')
            ->orderByDesc('profit')
            ->get([
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'categories.name as category',
                DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                DB::raw('SUM(sale_lines.quantity) as quantity'),
                DB::raw('ROUND(products.cost_price, 2) as unit_cost'),
                ...$this->mesures(),
            ]);

        $sortie = [];
        foreach ($lignes as $r) {
            $ca = (float) $r->revenue;
            $benefice = (float) $r->profit;
            $qte = (int) $r->quantity;
            $sortie[] = [
                'product_id' => (int) $r->product_id,
                'sku' => (string) $r->sku,
                'name' => (string) $r->product_name,
                'category' => $r->category !== null ? (string) $r->category : null,
                'documents' => (int) $r->documents,
                'quantity' => $qte,
                'revenue' => $ca,
                'cost' => (float) $r->cost,
                'unit_cost' => (float) $r->unit_cost,
                // Prix de vente moyen constaté : utile pour repérer les remises.
                'avg_price' => $qte > 0 ? round($ca / $qte, 2) : null,
                'profit' => $benefice,
                'unit_profit' => $qte > 0 ? round($benefice / $qte, 2) : null,
                'margin_percent' => $ca > 0 ? round(($benefice / $ca) * 100, 1) : null,
            ];
        }

        return $sortie;
    }

    /**
     * Bénéfice par fournisseur : la marge dégagée sur les articles qu'il livre.
     *
     * Le rattachement vient des réceptions, pas du catalogue. La table de
     * référencement « product_supplier » est vide en pratique — personne ne la
     * remplit — alors que les bons de réception disent qui a réellement livré
     * quoi. S'appuyer sur le catalogue aurait donné un tableau vide.
     *
     * Un article reçu de deux fournisseurs compte pour les deux : la base ne
     * dit pas lequel a livré l'exemplaire vendu. Le chiffre répond donc à
     * « que me rapportent les articles de ce fournisseur », pas à « quelle
     * part de mon bénéfice vient de lui ». Les additionner n'aurait pas de
     * sens, et le total de la période n'est pas leur somme.
     *
     * @return list<array<string, mixed>>
     */
    public function parFournisseur(string $du, string $au, int $limite = 12): array
    {
        // Couples article/fournisseur effectivement constatés à la réception.
        $livraisons = DB::table('goods_receipt_lines as grl')
            ->join('goods_receipts as gr', 'gr.id', '=', 'grl.goods_receipt_id')
            ->select('grl.product_id', 'gr.supplier_id')
            ->distinct();

        return $this->formater(
            $this->lignes($du, $au)
                ->joinSub($livraisons, 'liv', 'liv.product_id', '=', 'products.id')
                ->join('suppliers', 'suppliers.id', '=', 'liv.supplier_id')
                ->groupBy('suppliers.id', 'suppliers.name')
                ->orderByDesc('profit')
                ->limit($limite)
                ->get([
                    DB::raw('suppliers.name as name'),
                    DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                    ...$this->mesures(),
                ]),
        );
    }

    /**
     * Ce que le bénéfice affiché doit à des prix d'achat manquants.
     *
     * Un article vendu sans prix d'achat connu ressort à 100 % de marge :
     * ce n'est pas du bénéfice pur, c'est un coût que personne n'a saisi.
     * Sans cet avertissement, le chiffre se lit comme une performance alors
     * qu'il signale une lacune de saisie.
     *
     * @return array<string, mixed>
     */
    public function coutsManquants(string $du, string $au): array
    {
        $r = $this->lignes($du, $au)
            ->where(function ($q): void {
                $q->whereNull('products.cost_price')->orWhere('products.cost_price', '<=', 0);
            })
            ->first([
                DB::raw('COUNT(DISTINCT products.id) as articles'),
                DB::raw('ROUND(SUM(sale_lines.line_total), 2) as revenue'),
            ]);

        $caSansCout = (float) ($r->revenue ?? 0);
        $caTotal = (float) ($this->lignes($du, $au)->sum('sale_lines.line_total'));

        return [
            'products' => (int) ($r->articles ?? 0),
            'revenue' => $caSansCout,
            'revenue_share' => $caTotal > 0 ? round(($caSansCout / $caTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * Les charges de la période, au total et par famille.
     *
     * Sans elles, la page appelle « bénéfice » ce qui n'est que la marge
     * brute : le loyer, le carburant et les salaires n'apparaissent nulle
     * part, et le chiffre affiché se lit comme un gain net qu'il n'est pas.
     *
     * @return array{total: float, count: int, by_category: list<array{name: string, amount: float, count: int}>}
     */
    public function charges(string $du, string $au): array
    {
        $familles = DB::table('expenses as e')
            ->leftJoin('expense_categories as c', 'c.id', '=', 'e.expense_category_id')
            // La date de la charge, pas celle de sa saisie : une facture de
            // juillet enregistrée en août reste une charge de juillet.
            ->whereBetween('e.expense_date', [$du, $au])
            ->groupBy('c.name')
            ->orderByDesc(DB::raw('SUM(e.amount)'))
            ->get(['c.name', DB::raw('ROUND(SUM(e.amount), 2) as montant'), DB::raw('COUNT(*) as n')]);

        return [
            'total' => round((float) $familles->sum('montant'), 2),
            'count' => (int) $familles->sum('n'),
            'by_category' => $familles->map(fn ($f): array => [
                'name' => (string) ($f->name ?? 'Sans catégorie'),
                'amount' => round((float) $f->montant, 2),
                'count' => (int) $f->n,
            ])->all(),
        ];
    }

    /**
     * Ce qui a réellement été encaissé sur les factures de la période.
     *
     * Le chiffre d'affaires dit ce qui a été vendu ; il ne dit pas ce qui est
     * rentré. Avec plus du tiers du chiffre à crédit, confondre les deux
     * conduit à croire disponible un argent qui ne l'est pas.
     *
     * @return array{revenue: float, collected: float, credit: float, documents: int}
     */
    public function encaissements(string $du, string $au): array
    {
        $r = DB::table('sales')
            ->where('type', 'invoice')->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$du.' 00:00:00', $au.' 23:59:59'])
            ->first([
                DB::raw('COALESCE(ROUND(SUM(total), 2), 0) as ca'),
                DB::raw('COALESCE(ROUND(SUM(paid_amount), 2), 0) as encaisse'),
                DB::raw('COUNT(*) as n'),
            ]);

        $ca = (float) ($r->ca ?? 0);
        $encaisse = (float) ($r->encaisse ?? 0);

        return [
            'revenue' => $ca,
            'collected' => $encaisse,
            'credit' => round($ca - $encaisse, 2),
            'documents' => (int) ($r->n ?? 0),
        ];
    }

    /**
     * L'évolution jour par jour, pour voir la forme de la période.
     *
     * Un total dit combien ; il ne dit pas si la tendance monte ou tombe.
     *
     * @return list<array{date: string, revenue: float, cost: float, profit: float, documents: int}>
     */
    public function serie(string $du, string $au): array
    {
        return $this->lignes($du, $au)
            ->groupByRaw('DATE(sale_lines.occurred_at)')
            ->orderByRaw('DATE(sale_lines.occurred_at)')
            ->get([
                DB::raw('DATE(sale_lines.occurred_at) as jour'),
                DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
                ...$this->mesures(),
            ])
            ->map(fn ($r): array => [
                'date' => (string) $r->jour,
                'documents' => (int) $r->documents,
                'revenue' => (float) $r->revenue,
                'cost' => (float) $r->cost,
                'profit' => (float) $r->profit,
            ])
            ->all();
    }

    /**
     * Les totaux de la période : chiffre d'affaires, coût, bénéfice.
     *
     * @return array<string, mixed>
     */
    public function totaux(string $du, string $au): array
    {
        $r = $this->lignes($du, $au)->first([
            DB::raw('COUNT(DISTINCT CASE WHEN sale_lines.quantity > 0 THEN sales.id END) as documents'),
            ...$this->mesures(),
        ]);

        $ca = (float) ($r->revenue ?? 0);
        $benefice = (float) ($r->profit ?? 0);

        // Part du chiffre d'affaires portant sur des articles dont le coût
        // d'achat n'a jamais été saisi. Ces ventes affichent 100 % de marge :
        // sans ce chiffre, le bénéfice se lirait comme acquis alors qu'il est
        // surestimé d'autant.
        $sansCout = $this->lignes($du, $au)
            ->where('products.cost_price', '<=', 0)
            ->first([
                DB::raw('ROUND(SUM(sale_lines.line_total), 2) as revenue'),
                DB::raw('COUNT(DISTINCT products.id) as articles'),
            ]);

        $caSansCout = (float) ($sansCout->revenue ?? 0);

        return [
            'documents' => (int) ($r->documents ?? 0),
            'revenue' => $ca,
            'cost' => (float) ($r->cost ?? 0),
            'profit' => $benefice,
            'margin_percent' => $ca > 0 ? round(($benefice / $ca) * 100, 1) : null,
            'revenue_without_cost' => $caSansCout,
            'products_without_cost' => (int) ($sansCout->articles ?? 0),
            'share_without_cost' => $ca > 0 ? round(($caSansCout / $ca) * 100, 1) : 0.0,
        ];
    }
}
