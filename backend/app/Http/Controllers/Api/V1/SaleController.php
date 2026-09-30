<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Pricing\Contracts\MarginCalculatorInterface;
use App\Domain\Pricing\Contracts\PriceResolverInterface;
use App\Domain\Pricing\Exceptions\NoPriceDefinedException;
use App\Domain\Pricing\Services\ProductCostResolver;
use App\Domain\Sales\Actions\CancelSaleAction;
use App\Domain\Sales\Actions\ConfirmSaleAction;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Exports\ArrayExport;
use App\Support\Documents\DocumentNumberGeneratorInterface;
use App\Support\Export\HtmlTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use App\Rules\WarehouseAccessible;
use App\Domain\Stock\Contracts\StockReaderInterface;

/**
 * Ventes : devis et factures. Prix résolus côté serveur (type de prix du
 * client puis paliers de quantité), contrôle du prix plancher et du crédit.
 */
final class SaleController extends Controller
{
    /**
     * Vue globale : voit les ventes de tous les vendeurs et de tous les lieux.
     */
    private function hasGlobalView(Request $request): bool
    {
        $user = $request->user();

        return $user !== null && ($user->can('stock.view_global') || $user->can('customer.view_all'));
    }

    /**
     * Restreint aux ventes du vendeur : celles qu'il a créées, plus celles
     * rattachées à ses propres clients (reprise d'un dossier client).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Sale>  $query
     */
    private function scopeToSeller(Request $request, $query): void
    {
        $userId = $request->user()?->id;

        $query->where(function ($q) use ($userId): void {
            $q->where('user_id', $userId)
                ->orWhereIn(
                    'customer_id',
                    Customer::query()->select('id')->where('created_by', $userId),
                );
        });
    }

    /**
     * Refuse l'accès à une vente d'un autre vendeur (sauf vue globale).
     */
    private function assertCanSeeSale(Request $request, Sale $sale): void
    {
        if ($this->hasGlobalView($request)) {
            return;
        }

        $userId = $request->user()?->id;
        $ownsCustomer = $sale->customer_id !== null
            && Customer::query()->whereKey($sale->customer_id)->where('created_by', $userId)->exists();

        if ($sale->user_id !== $userId && ! $ownsCustomer) {
            abort(403, 'Cette vente a été enregistrée par un autre vendeur.');
        }
    }

    /**
     * Filtres communs a la liste et aux exports.
     *
     * Un export qui ne filtrerait pas comme l'ecran produirait un fichier
     * different de ce que l'utilisateur a sous les yeux — et le cloisonnement
     * par vendeur sauterait avec.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Sale>  $q
     * @return \Illuminate\Database\Eloquent\Builder<Sale>
     */
    private function appliquerFiltres(Request $request, $q)
    {
        return $q
            // Cloisonnement vendeur : ses ventes + celles de ses clients.
            ->when(! $this->hasGlobalView($request), fn ($x) => $this->scopeToSeller($request, $x))
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')->value()))
            ->when($request->integer('customer_id') > 0, fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            // On cherche une vente par sa reference ou par son client :
            // limiter a la reference obligerait a la connaitre par coeur.
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request): void {
                $terme = '%'.$request->string('search')->value().'%';
                $q->where(function ($x) use ($terme): void {
                    $x->where('reference', 'like', $terme)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $terme)->orWhere('code', 'like', $terme));
                });
            })
            // Filtre par famille d'articles : la vente est retenue dès qu'une
            // de ses lignes en relève. Une vente mêlant deux familles apparaît
            // donc dans les deux — c'est voulu : on cherche « les ventes où il
            // y a eu des câbles », pas « les ventes de câbles uniquement ».
            ->when($request->integer('category_id') > 0, fn ($q) => $q->whereHas(
                'lines',
                fn ($l) => $l->whereHas(
                    'product',
                    fn ($p) => $p->where('category_id', $request->integer('category_id')),
                ),
            ))
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()));
    }

    public function index(Request $request): JsonResponse
    {
        // « user » : qui a saisi la vente. Charge ici pour eviter une requete
        // par ligne affichee.
        $sales = $this->appliquerFiltres($request, Sale::query()
            ->with(['customer:id,code,name', 'warehouse:id,code', 'user:id,name'])
            ->withCount('lines'))
            ->orderByDesc('id')
            ->paginate(in_array($request->integer('per_page', 20), [20, 50, 100], true) ? $request->integer('per_page', 20) : 20);

        // Devis déjà convertis en facture (pour les griser dans la sélection).
        $convertedQuoteIds = Sale::query()
            ->whereNotNull('quote_id')
            ->pluck('quote_id')
            ->all();

        // Savoir qui a saisi une vente releve du pilotage, pas du comptoir :
        // la colonne n'est servie qu'a la direction. On s'appuie sur la seule
        // vue multi-lieux, pas sur « hasGlobalView » qui accepte aussi
        // « customer.view_all » — un responsable de lieu la possede, et il
        // aurait vu qui a saisi quoi.
        $voitLesAuteurs = $request->user()?->can('stock.view_global') ?? false;

        $sales->through(fn (Sale $s): array => [
            'id' => $s->id,
            'reference' => $s->reference,
            'type' => $s->type,
            'status' => $s->status,
            'customer' => $s->customer?->name,
            'warehouse' => $s->warehouse?->code,
            'total' => (float) $s->total,
            'paid_amount' => (float) $s->paid_amount,
            'payment_status' => $s->payment_status,
            'lines_count' => (int) ($s->lines_count ?? 0),
            'quote_id' => $s->quote_id,
            'converted' => $s->type === Sale::TYPE_QUOTE && in_array($s->id, $convertedQuoteIds, true),
            'created_at' => $s->created_at?->format('Y-m-d H:i'),
            'created_by' => $voitLesAuteurs ? $s->user?->name : null,
        ]);

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Journal des ventes : une ligne par jour, avec les documents du jour.
     *
     * La liste paginée ne dit pas ce qu'a pesé une journée — il faudrait
     * additionner de tête sur plusieurs pages. Le journal agrège côté
     * serveur, sur l'ensemble filtré et non sur la page courante.
     *
     * Mêmes filtres et même cloisonnement que la liste : un vendeur ne voit
     * dans son journal que ce qu'il voit dans sa liste.
     */
    /**
     * GET /sales/export?format=pdf|xlsx — le tableau tel qu'il est affiche.
     */
    /**
     * Bilan d'un lieu sur la periode filtree : ventes, credit clients, charges
     * et reste en caisse. Il suit les memes filtres que la liste, pour que le
     * total lu corresponde aux lignes affichees.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $lieu = $request->integer('warehouse_id');
        $du = $request->string('date_from')->value();
        $au = $request->string('date_to')->value();

        $ventes = $this->appliquerFiltres($request, Sale::query())
            ->where('type', Sale::TYPE_INVOICE)
            ->where('status', Sale::STATUS_CONFIRMED)
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(total),0) as ca, COALESCE(SUM(paid_amount),0) as encaisse')
            ->first();

        $ca = round((float) ($ventes?->ca ?? 0), 2);
        $encaisse = round((float) ($ventes?->encaisse ?? 0), 2);

        $charges = \App\Domain\Expenses\Models\Expense::query()
            ->where('warehouse_id', $lieu)
            ->where('status', '!=', 'rejected')
            ->when($du !== '', fn ($q) => $q->whereDate('expense_date', '>=', $du))
            ->when($au !== '', fn ($q) => $q->whereDate('expense_date', '<=', $au))
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(amount),0) as total')
            ->first();

        // Encours actuel des clients ayant achete dans ce lieu : il vit sur la
        // fiche client, sans date.
        $encours = (float) DB::table('customers')
            ->whereIn('id', DB::table('sales')->select('customer_id')->where('warehouse_id', $lieu)->whereNotNull('customer_id'))
            ->where('balance', '>', 0)
            ->sum('balance');

        $debut = $du !== '' ? \Illuminate\Support\Carbon::parse($du)->startOfDay() : \Illuminate\Support\Carbon::create(2000, 1, 1);
        $fin = $au !== '' ? \Illuminate\Support\Carbon::parse($au)->endOfDay() : now();
        $caisse = app(\App\Domain\Sales\Services\CashBoxService::class)->soldePeriode($lieu, $debut, $fin);

        return response()->json(['data' => [
            'sales' => [
                'count' => (int) ($ventes?->nb ?? 0),
                'total' => $ca,
                'collected' => $encaisse,
                'credit' => round($ca - $encaisse, 2),
            ],
            'customers_balance' => round($encours, 2),
            'expenses' => [
                'count' => (int) ($charges?->nb ?? 0),
                'total' => round((float) ($charges?->total ?? 0), 2),
            ],
            'cash' => $caisse,
        ]]);
    }

    public function export(Request $request): BinaryFileResponse|HttpResponse|JsonResponse
    {
        $ventes = $this->appliquerFiltres($request, Sale::query()
            ->with(['customer:id,code,name', 'warehouse:id,code', 'user:id,name'])
            ->withCount('lines'))
            ->orderByDesc('id')
            ->get();

        $voitLesAuteurs = $request->user()?->can('stock.view_global') ?? false;

        $headings = ['Reference', 'Date et heure', 'Type', 'Statut', 'Client', 'Lieu',
            'Lignes', 'Total (DH)', 'Paye (DH)', 'Reste (DH)', 'Reglement'];
        if ($voitLesAuteurs) {
            $headings[] = 'Saisie par';
        }

        $rows = $ventes->map(fn (Sale $v): array => array_merge([
            $v->reference,
            $v->created_at?->format('d/m/Y H:i') ?? '',
            $this->libelleType($v->type),
            $this->libelleStatut($v->status),
            $v->customer?->name ?? 'Comptoir',
            $v->warehouse?->code ?? '',
            $v->lines_count,
            number_format((float) $v->total, 2, '.', ''),
            number_format((float) $v->paid_amount, 2, '.', ''),
            number_format(max((float) $v->total - (float) $v->paid_amount, 0), 2, '.', ''),
            $this->libelleReglement($v->payment_status),
        ], $voitLesAuteurs ? [$v->user?->name ?? ''] : []))->values()->all();

        $titre = 'Ventes'.$this->suffixePeriode($request);

        if ($request->string('format')->value() === 'pdf') {
            if (($refus = $this->refuserPdfTropLong(count($rows))) !== null) {
                return $refus;
            }

            return Pdf::loadHtml(HtmlTable::render($titre, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download('IGOUTECH_ventes.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'IGOUTECH_ventes.xlsx');
    }

    /**
     * GET /sales/lines/export?date_from&date_to&format — une ligne par article vendu.
     *
     * Le prix d'achat est celui que porte la fiche AUJOURD'HUI : la ligne de
     * vente n'enregistre pas le cout du jour de la vente. Sur un article dont
     * le prix d'achat a change depuis, la marge affichee est donc celle que
     * l'on ferait en revendant maintenant, pas celle realisee a l'epoque.
     */
    public function linesExport(Request $request): BinaryFileResponse|HttpResponse|JsonResponse
    {
        $ventes = $this->appliquerFiltres($request, Sale::query()
            ->with(['customer:id,code,name', 'warehouse:id,code', 'lines.product:id,sku,name,cost_price']))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $headings = ['Date et heure', 'Reference', 'Client', 'Lieu',
            'Reference article', 'Article', 'Quantite vendue', 'Prix de vente (DH)',
            'Total vente (DH)', "Prix d'achat (DH)", "Total achat (DH)",
            'Benefice unitaire (DH)', 'Benefice total (DH)', 'Statut'];

        $rows = [];

        // Les totaux sont tenus par statut : melanger une vente annulee aux
        // ventes reelles gonflerait le chiffre d'affaires d'une marchandise
        // qui est revenue en stock. Chaque statut a donc son bilan.
        $bilans = [];
        $bilanVide = ['ventes' => [], 'lignes' => 0, 'quantite' => 0, 'vente' => 0.0, 'achat' => 0.0];

        foreach ($ventes as $v) {
            $statut = (string) $v->status;
            $bilans[$statut] ??= $bilanVide;

            foreach ($v->lines as $l) {
                $quantite = (int) $l->quantity;
                $prixVente = round((float) $l->unit_price, 2);
                $totalLigne = round((float) $l->line_total, 2);
                $prixAchat = round((float) ($l->product->cost_price ?? 0), 2);
                $achatLigne = round($quantite * $prixAchat, 2);
                $beneficeUnitaire = round($prixVente - $prixAchat, 2);
                $beneficeLigne = round($totalLigne - $achatLigne, 2);

                $bilans[$statut]['ventes'][$v->id] = true;
                $bilans[$statut]['lignes']++;
                $bilans[$statut]['quantite'] += $quantite;
                $bilans[$statut]['vente'] += $totalLigne;
                $bilans[$statut]['achat'] += $achatLigne;

                // Sans prix d'achat connu, le benefice n'est pas calculable :
                // afficher zero laisserait croire a une vente a prix coutant.
                $connu = $prixAchat > 0;

                $rows[] = [
                    $v->created_at?->format('d/m/Y H:i') ?? '',
                    $v->reference,
                    $v->customer?->name ?? 'Comptoir',
                    $v->warehouse?->code ?? '',
                    $l->product?->sku ?? '',
                    $l->product?->name ?? '',
                    $quantite,
                    number_format($prixVente, 2, '.', ''),
                    number_format($totalLigne, 2, '.', ''),
                    $connu ? number_format($prixAchat, 2, '.', '') : '',
                    $connu ? number_format($achatLigne, 2, '.', '') : '',
                    $connu ? number_format($beneficeUnitaire, 2, '.', '') : '',
                    $connu ? number_format($beneficeLigne, 2, '.', '') : '',
                    $this->libelleStatut($v->status),
                ];
            }
        }

        // La synthese, en bas du tableau. Une ligne vide l'en detache pour
        // qu'elle ne se lise pas comme une vente de plus.
        $vide = array_fill(0, count($headings), '');
        $bilan = function (string $libelle, string $valeur) use ($headings): array {
            $ligne = array_fill(0, count($headings), '');
            $ligne[0] = $libelle;
            $ligne[count($headings) - 2] = $valeur;

            return $ligne;
        };

        // Les confirmees d'abord : ce sont elles qui font le resultat. Les
        // annulees suivent, pour memoire — on veut savoir combien de ventes
        // ont ete defaites et quel benefice a ete perdu avec.
        $ordre = [
            Sale::STATUS_CONFIRMED => 'VENTES CONFIRMEES',
            Sale::STATUS_CANCELLED => 'VENTES ANNULEES',
            'draft' => 'BROUILLONS (non valides)',
        ];

        foreach ($ordre as $statut => $titreBloc) {
            $b = $bilans[$statut] ?? null;

            // Un statut absent de la periode ne merite pas un bloc de zeros.
            if ($b === null) {
                continue;
            }

            $beneficeBloc = round($b['vente'] - $b['achat'], 2);

            $rows[] = $vide;
            $rows[] = $bilan($titreBloc, '');
            $rows[] = $bilan('   Nombre de ventes', (string) count($b['ventes']));
            $rows[] = $bilan('   Nombre de lignes', (string) $b['lignes']);
            $rows[] = $bilan('   Quantite vendue', (string) $b['quantite']);
            $rows[] = $bilan('   Total des ventes', number_format($b['vente'], 2, '.', ''));
            $rows[] = $bilan("   Total des prix d'achat", number_format($b['achat'], 2, '.', ''));
            $rows[] = $bilan('   Total des benefices', number_format($beneficeBloc, 2, '.', ''));

            // Les 30 % ne se calculent que sur ce qui a ete reellement vendu :
            // une part d'un benefice annule ne se verse pas.
            if ($statut === Sale::STATUS_CONFIRMED) {
                $rows[] = $bilan('   30 % DU BENEFICE', number_format(round($beneficeBloc * 0.30, 2), 2, '.', ''));
            }
        }

        $titre = 'Detail des ventes, ligne par ligne'.$this->suffixePeriode($request);

        if ($request->string('format')->value() === 'pdf') {
            if (($refus = $this->refuserPdfTropLong(count($rows))) !== null) {
                return $refus;
            }

            return Pdf::loadHtml(HtmlTable::render($titre, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download('IGOUTECH_ventes-detail.pdf');
        }

        return Excel::download(new ArrayExport($headings, $rows), 'IGOUTECH_ventes-detail.xlsx');
    }

    /**
     * Refuse un PDF trop volumineux plutot que de laisser le serveur tomber.
     *
     * dompdf compose la page entiere en memoire : au-dela de quelques
     * milliers de lignes il epuise la limite php et la requete meurt sans
     * message. Excel n'a pas cette limite — on y renvoie.
     */
    private function refuserPdfTropLong(int $lignes): ?JsonResponse
    {
        $plafond = $this->plafondPdf();

        if ($lignes <= $plafond) {
            return null;
        }

        return response()->json([
            'message' => "Cet export represente {$lignes} lignes : c'est plus que ce que la "
                ."composition d'un PDF peut tenir en memoire sur ce serveur (limite : {$plafond} "
                ."lignes). Resserrez la periode, ou choisissez l'export Excel, qui n'a pas cette limite.",
            'lines' => $lignes,
            'limit' => $plafond,
        ], 422);
    }

    /**
     * Nombre de lignes qu'un PDF peut tenir, deduit de la memoire allouee.
     *
     * Le plafond etait ecrit en dur. Il ne pouvait qu'etre faux : la memoire
     * accordee a PHP depend du serveur, et une valeur figee laissait soit des
     * exports refuses pour rien, soit — bien pire — des requetes tuees sans
     * message, l'utilisateur ne voyant qu'un « Server Error ».
     *
     * Mesure sur ce serveur : 100 lignes tiennent dans 72 Mo, 200 dans 122,
     * 300 dans 166, 500 dans 302. Soit environ 0,6 Mo par ligne au-dela d'une
     * base de 25 Mo. On ne s'autorise que 75 % de la memoire : le reste sert a
     * Laravel, a la requete et a la reponse.
     */
    private function plafondPdf(): int
    {
        $limite = $this->memoireAlloueeEnMo();

        // Sans limite declaree (-1), on plafonne quand meme : composer 10 000
        // lignes prendrait des minutes et immobiliserait le serveur.
        if ($limite <= 0) {
            return 1000;
        }

        $utilisable = ($limite * 0.75) - 25;

        return max(50, (int) floor($utilisable / 0.6));
    }

    private function memoireAlloueeEnMo(): int
    {
        $brut = trim((string) ini_get('memory_limit'));

        if ($brut === '' || $brut === '-1') {
            return -1;
        }

        $valeur = (int) $brut;

        return match (strtoupper(substr($brut, -1))) {
            'G' => $valeur * 1024,
            'M' => $valeur,
            'K' => intdiv($valeur, 1024),
            default => intdiv($valeur, 1048576),
        };
    }

    /**
     * « du 01/09/2026 au 08/09/2026 », ou rien si aucune borne n'est posee.
     * Un export sans sa periode ne se relit pas trois mois plus tard.
     */
    private function suffixePeriode(Request $request): string
    {
        $du = $request->string('date_from')->value();
        $au = $request->string('date_to')->value();

        if ($du === '' && $au === '') {
            return '';
        }

        $format = static fn (string $d): string => $d !== ''
            ? \Carbon\Carbon::parse($d)->format('d/m/Y')
            : '';

        if ($du !== '' && $au !== '') {
            return ' du '.$format($du).' au '.$format($au);
        }

        return $du !== '' ? ' depuis le '.$format($du) : " jusqu'au ".$format($au);
    }

    private function libelleType(?string $type): string
    {
        return match ($type) {
            'invoice' => 'Facture',
            'ticket' => 'Ticket',
            'quote' => 'Devis',
            default => (string) $type,
        };
    }

    private function libelleStatut(?string $statut): string
    {
        return match ($statut) {
            'draft' => 'Brouillon',
            'confirmed' => 'Confirmee',
            'cancelled' => 'Annulee',
            default => (string) $statut,
        };
    }

    private function libelleReglement(?string $statut): string
    {
        return match ($statut) {
            'paid' => 'Paye',
            'partial' => 'Partiel',
            'unpaid' => 'Non paye',
            default => (string) $statut,
        };
    }

    public function journal(Request $request): JsonResponse
    {
        $base = fn () => Sale::query()
            ->when(! $this->hasGlobalView($request), fn ($q) => $this->scopeToSeller($request, $q))
            ->where('type', Sale::TYPE_INVOICE)
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->integer('customer_id') > 0, fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->integer('category_id') > 0, fn ($q) => $q->whereHas(
                'lines',
                fn ($l) => $l->whereHas(
                    'product',
                    fn ($p) => $p->where('category_id', $request->integer('category_id')),
                ),
            ))
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')->value()));

        // Un devis n'est pas une vente : le journal ne compte que les
        // factures, sinon les totaux annonceraient un CA qui n'existe pas.
        $jours = $base()
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as documents,
                         COALESCE(SUM(total), 0) as ca,
                         COALESCE(SUM(paid_amount), 0) as encaisse')
            ->groupByRaw('DATE(created_at)')
            ->orderByDesc('jour')
            ->limit(120)
            ->get();

        $lignes = $jours->map(fn (Sale $j): array => [
            'date' => (string) $j->getAttribute('jour'),
            'documents' => (int) $j->getAttribute('documents'),
            'revenue' => round((float) $j->getAttribute('ca'), 2),
            'collected' => round((float) $j->getAttribute('encaisse'), 2),
            'credit' => round((float) $j->getAttribute('ca') - (float) $j->getAttribute('encaisse'), 2),
        ])->all();

        // Les totaux portent sur tout le filtre, pas sur les 120 jours
        // affichés : un total qui ne couvre que l'écran ment.
        $totaux = $base()
            ->selectRaw('COUNT(*) as documents, COALESCE(SUM(total), 0) as ca, COALESCE(SUM(paid_amount), 0) as encaisse')
            ->first();

        $ca = round((float) ($totaux?->getAttribute('ca') ?? 0), 2);
        $encaisse = round((float) ($totaux?->getAttribute('encaisse') ?? 0), 2);

        return response()->json(['data' => [
            'days' => $lignes,
            'totals' => [
                'documents' => (int) ($totaux?->getAttribute('documents') ?? 0),
                'revenue' => $ca,
                'collected' => $encaisse,
                'credit' => round($ca - $encaisse, 2),
            ],
        ]]);
    }

    /**
     * Prix applicable pour un article / client / quantité (aide à la saisie).
     */
    public function price(Request $request, PriceResolverInterface $resolver, MarginCalculatorInterface $margins, ProductCostResolver $cost): JsonResponse
    {
        /** @var array{product_id: int, quantity: int, customer_id?: int|null} $data */
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        try {
            // Les paramètres de requête GET arrivent en chaînes : cast explicite.
            $resolved = $resolver->resolve(
                (int) $data['product_id'],
                (int) $data['quantity'],
                isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            );
        } catch (NoPriceDefinedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

    /** @var Product $product */
        $product = Product::query()->findOrFail((int) $data['product_id']);
        $floor = $margins->floorPrice($cost->purchaseCost($product), 0.0);

        // Le plancher EST le prix d'achat (marge minimale nulle) : le renvoyer
        // à qui n'a pas le droit de consulter les coûts revenait à publier le
        // prix d'achat de chaque article à tout vendeur. Sans ce nombre,
        // l'interface ne peut pas afficher le minimum, mais le serveur refuse
        // toujours une vente en dessous — la règle tient, c'est le chiffre qui
        // reste couvert.
        $voitLesCouts = $request->user()?->can('product.view_cost_price') ?? false;

        return response()->json(['data' => [
            'unit_price' => $resolved->amount,
            'price_type_code' => $resolved->priceTypeCode,
            'reason' => $resolved->reason,
            'floor_price' => $voitLesCouts ? round($floor, 2) : null,
            // Dit à l'interface si elle peut valider la saisie elle-même.
            'floor_visible' => $voitLesCouts,
        ]]);
    }

    /**
     * Lignes dont la quantite depasse le stock du lieu.
     *
     * Les quantites d'un meme article sont additionnees : deux lignes de 6
     * sur un stock de 10 doivent etre refusees, alors que chacune passe
     * isolement.
     *
     * @param  list<array{product_id: int, quantity: int}>  $lines
     * @return list<array{product_id: int, requested: int, available: int, message: string}>
     */
    private function lignesSansStock(int $warehouseId, array $lines, StockReaderInterface $stock): array
    {
        $demande = [];

        foreach ($lines as $line) {
            $id = (int) $line['product_id'];
            $demande[$id] = ($demande[$id] ?? 0) + (int) $line['quantity'];
        }

        $manquants = [];

        foreach ($demande as $productId => $quantite) {
            $disponible = $stock->quantityFor($warehouseId, $productId);

            if ($quantite > $disponible) {
                $nom = Product::query()->find($productId)?->name ?? ('article #'.$productId);

                $manquants[] = [
                    'product_id' => $productId,
                    'requested' => $quantite,
                    'available' => $disponible,
                    'message' => sprintf('%s — demande %d, disponible %d', $nom, $quantite, $disponible),
                ];
            }
        }

        return $manquants;
    }

    /**
     * Valorise les lignes soumises et rend le sous-total.
     *
     * Partagé par la création et la modification : les contrôles de prix
     * (tarif applicable, prix plancher) doivent s'appliquer exactement de la
     * même façon, sinon on pourrait contourner le plancher en créant une
     * ligne conforme puis en la modifiant.
     *
     * @param  list<array{product_id: int, quantity: int, unit_price?: float|null}>  $lignes
     * @return array{0: list<array<string, mixed>>, 1: float}
     */
    private function preparerLignes(
        array $lignes,
        ?int $clientId,
        bool $peutVendreSousPlancher,
        bool $voitLesCouts,
        PriceResolverInterface $resolver,
        MarginCalculatorInterface $margins,
        ProductCostResolver $cost,
    ): array {
        $subtotal = 0.0;
        $prepared = [];

        foreach ($lignes as $line) {
            $priceTypeCode = null;

            if (isset($line['unit_price'])) {
                // Prix saisi par le vendeur : prioritaire, le niveau reste informatif.
                $unitPrice = (float) $line['unit_price'];
                try {
                    $priceTypeCode = $resolver->resolve($line['product_id'], $line['quantity'], $clientId)->priceTypeCode;
                } catch (NoPriceDefinedException) {
                    // Article sans tarif défini : le prix saisi fait foi.
                }
            } else {
                $resolved = $resolver->resolve($line['product_id'], $line['quantity'], $clientId);
                $unitPrice = $resolved->amount;
                $priceTypeCode = $resolved->priceTypeCode;
            }

            /** @var Product $product */
            $product = Product::query()->findOrFail($line['product_id']);
            $floor = $margins->floorPrice($cost->purchaseCost($product), 0.0);

            if ($unitPrice < $floor && ! $peutVendreSousPlancher) {
                // Le plancher est le prix d'achat : l'écrire dans le message
                // le révélerait à qui n'a pas le droit de le consulter.
                throw new RuntimeException($voitLesCouts
                    ? sprintf(
                        'Prix sous le plancher pour %s (%.2f < %.2f DH) : autorisation requise.',
                        $product->sku,
                        $unitPrice,
                        $floor,
                    )
                    : sprintf(
                        'Prix trop bas pour %s : il ne peut pas descendre sous le coût de l’article.',
                        $product->sku,
                    ));
            }

            $lineTotal = round($unitPrice * $line['quantity'], 2);
            $subtotal += $lineTotal;

            $prepared[] = [
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $unitPrice,
                'price_type_code' => $priceTypeCode,
                'line_total' => $lineTotal,
            ];
        }

        return [$prepared, $subtotal];
    }

    public function store(
        Request $request,
        PriceResolverInterface $resolver,
        MarginCalculatorInterface $margins,
        ProductCostResolver $cost,
        DocumentNumberGeneratorInterface $numbers,
    ): JsonResponse {
        /** @var array{type: string, customer_id: int, warehouse_id: int, discount_percent?: float, note?: string|null, lines: list<array{product_id: int, quantity: int, unit_price?: float|null}>} $data */
        $data = $request->validate([
            'type' => ['required', 'in:quote,invoice'],
            // Nullable : client de passage (vente comptoir sans fiche ni crédit).
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'discount_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'note' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $discount = (float) ($data['discount_percent'] ?? 0);
        if ($discount > 10 && ! ($request->user()?->can('sale.discount_over_limit') ?? false)) {
            return response()->json(['message' => 'Remise supérieure à 10 % : autorisation requise.'], 422);
        }

        $canBelowFloor = $request->user()?->can('sale.sell_below_floor') ?? false;

        // Une facture sort du stock : refuser tout de suite ce qui ne pourra
        // pas etre livre. La confirmation le bloquerait de toute facon, mais
        // apres que le vendeur a saisi toute sa vente.
        // Un devis reste libre : il peut porter sur ce qu'on va commander.
        if ($data['type'] === Sale::TYPE_INVOICE) {
            $manquants = $this->lignesSansStock(
                (int) $data['warehouse_id'],
                $data['lines'],
                app(StockReaderInterface::class),
            );

            if ($manquants !== []) {
                return response()->json([
                    'message' => 'Stock insuffisant : '.implode(' ; ', array_column($manquants, 'message')),
                    'errors' => ['lines' => array_column($manquants, 'message')],
                    'insufficient' => $manquants,
                ], 422);
            }
        }

        try {
            $sale = DB::transaction(function () use ($data, $discount, $canBelowFloor, $resolver, $margins, $cost, $numbers, $request): Sale {
                [$prepared, $subtotal] = $this->preparerLignes(
                    $data['lines'],
                    $data['customer_id'] ?? null,
                    $canBelowFloor,
                    $request->user()?->can('product.view_cost_price') ?? false,
                    $resolver,
                    $margins,
                    $cost,
                );

                $total = round($subtotal * (1 - $discount / 100), 2);

                $sale = Sale::query()->create([
                    'reference' => $numbers->next('sale'),
                    'type' => $data['type'],
                    'status' => Sale::STATUS_DRAFT,
                    'customer_id' => $data['customer_id'] ?? null,
                    'warehouse_id' => $data['warehouse_id'],
                    'user_id' => $request->user()?->id,
                    'subtotal' => round($subtotal, 2),
                    'discount_percent' => $discount,
                    'total' => $total,
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($prepared as $line) {
                    $sale->lines()->create($line);
                }

                return $sale;
            });
        } catch (RuntimeException|NoPriceDefinedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $sale->id, 'reference' => $sale->reference, 'total' => (float) $sale->total]], 201);
    }

    /**
     * Modifie un document encore en brouillon : lignes ajoutées, retirées,
     * quantités et prix revus, remise et note.
     *
     * Le brouillon est la seule fenêtre où la modification est sans risque :
     * rien n'est encore sorti du stock, rien n'est comptabilisé, aucun
     * règlement n'est rattaché. Une fois confirmé, le document a été remis au
     * client et fait foi — il se corrige par un avoir, pas par une réécriture
     * silencieuse.
     *
     * Les lignes sont remplacées en bloc plutôt que rapprochées une à une :
     * l'interface envoie l'état voulu du document, et un rapprochement ligne
     * à ligne réintroduirait la question de savoir quoi faire des lignes
     * absentes — pour un résultat identique.
     */
    public function update(
        Request $request,
        Sale $sale,
        PriceResolverInterface $resolver,
        MarginCalculatorInterface $margins,
        ProductCostResolver $cost,
    ): JsonResponse {
        $this->assertCanSeeSale($request, $sale);

        if ($sale->status !== Sale::STATUS_DRAFT) {
            return response()->json([
                'message' => $sale->status === Sale::STATUS_CANCELLED
                    ? 'Ce document est annulé : il ne peut plus être modifié.'
                    : 'Ce document est confirmé : il ne peut plus être modifié.',
            ], 422);
        }

        /** @var array{customer_id?: int|null, discount_percent?: float, note?: string|null, lines: list<array{product_id: int, quantity: int, unit_price?: float|null}>} $data */
        $data = $request->validate([
            'customer_id' => ['sometimes', 'nullable', 'integer', 'exists:customers,id'],
            'discount_percent' => ['sometimes', 'numeric', 'between:0,100'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Le lieu n'est pas modifiable : en changer reviendrait à déplacer le
        // document d'un dépôt à l'autre, avec un numéro déjà attribué.
        $clientId = array_key_exists('customer_id', $data) ? $data['customer_id'] : $sale->customer_id;
        $discount = (float) ($data['discount_percent'] ?? $sale->discount_percent);

        if ($discount > 10 && ! ($request->user()?->can('sale.discount_over_limit') ?? false)) {
            return response()->json(['message' => 'Remise supérieure à 10 % : autorisation requise.'], 422);
        }

        // Une facture sortira du stock à la confirmation : autant refuser tout
        // de suite ce qui ne pourra pas être livré. Un devis reste libre, il
        // peut porter sur ce qu'on va commander.
        if ($sale->type === Sale::TYPE_INVOICE) {
            $manquants = $this->lignesSansStock(
                (int) $sale->warehouse_id,
                $data['lines'],
                app(StockReaderInterface::class),
            );

            if ($manquants !== []) {
                return response()->json([
                    'message' => 'Stock insuffisant : '.implode(' ; ', array_column($manquants, 'message')),
                    'errors' => ['lines' => array_column($manquants, 'message')],
                    'insufficient' => $manquants,
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($sale, $data, $clientId, $discount, $request, $resolver, $margins, $cost): void {
                [$prepared, $subtotal] = $this->preparerLignes(
                    $data['lines'],
                    $clientId,
                    $request->user()?->can('sale.sell_below_floor') ?? false,
                    $request->user()?->can('product.view_cost_price') ?? false,
                    $resolver,
                    $margins,
                    $cost,
                );

                $sale->lines()->delete();
                foreach ($prepared as $line) {
                    $sale->lines()->create($line);
                }

                $sale->update([
                    'customer_id' => $clientId,
                    'subtotal' => round($subtotal, 2),
                    'discount_percent' => $discount,
                    'total' => round($subtotal * (1 - $discount / 100), 2),
                    'note' => array_key_exists('note', $data) ? $data['note'] : $sale->note,
                ]);
            });
        } catch (RuntimeException|NoPriceDefinedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($sale->refresh());
    }

    /**
     * Supprime définitivement une vente annulée.
     *
     * Annuler laisse le document dans l'historique, ce qui encombre la liste
     * quand il ne s'agissait que d'une saisie ratée. La suppression n'est
     * ouverte qu'aux ventes qui n'ont laissé aucune trace ailleurs : un
     * règlement, une écriture au crédit du client ou un mouvement de stock
     * survivraient à la vente et pointeraient dans le vide, faussant les
     * rapprochements pour toujours.
     *
     * En pratique cela vise les ventes annulées avant confirmation. Une vente
     * confirmée puis annulée garde son sillage : elle reste consultable.
     */
    public function destroy(Request $request, Sale $sale): JsonResponse
    {
        $this->assertCanSeeSale($request, $sale);

        if ($sale->status !== Sale::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Seule une vente annulée peut être supprimée.',
            ], 422);
        }

        $obstacles = [];

        if (DB::table('payments')->where('sale_id', $sale->id)->exists()) {
            $obstacles[] = 'un règlement y est rattaché';
        }

        // Le grand livre client référence par couple type/identifiant, pas par
        // une colonne sale_id.
        if (DB::table('customer_ledger_entries')
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->exists()
        ) {
            $obstacles[] = 'elle a mouvementé le crédit du client';
        }

        if (DB::table('stock_movements')
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->exists()
        ) {
            $obstacles[] = 'elle a mouvementé le stock';
        }

        if (Sale::query()->where('quote_id', $sale->id)->exists()) {
            $obstacles[] = 'une facture en est issue';
        }

        if ($obstacles !== []) {
            return response()->json([
                'message' => 'Cette vente ne peut pas être supprimée : '
                    .implode(', ', $obstacles).'. Elle reste consultable comme annulée.',
            ], 422);
        }

        // Les lignes partent avec la vente (cascade en base) ; la transaction
        // garantit qu'on ne laisse pas une vente sans ses lignes.
        DB::transaction(function () use ($sale): void {
            $sale->lines()->delete();
            $sale->delete();
        });

        return response()->json(null, 204);
    }

    public function show(Sale $sale): JsonResponse
    {
        $this->assertCanSeeSale(request(), $sale);

        $sale->load(['customer:id,code,name,balance,credit_limit,is_blocked', 'warehouse:id,code', 'lines.product:id,sku,name']);

        return response()->json(['data' => [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'type' => $sale->type,
            'status' => $sale->status,
            'customer' => $sale->customer !== null ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
                'balance' => (float) $sale->customer->balance,
                'credit_limit' => (float) $sale->customer->credit_limit,
                'is_blocked' => $sale->customer->is_blocked,
            ] : null,
            'warehouse' => $sale->warehouse?->code,
            'subtotal' => (float) $sale->subtotal,
            'discount_percent' => (float) $sale->discount_percent,
            'total' => (float) $sale->total,
            'paid_amount' => (float) $sale->paid_amount,
            'payment_status' => $sale->payment_status,
            'confirmed_at' => $sale->confirmed_at?->format('Y-m-d H:i'),
            // Date ET heure de saisie : une facture confirmee le
            // lendemain de sa creation ne se distingue pas autrement.
            'created_at' => $sale->created_at?->format('Y-m-d H:i'),
            'updated_at' => $sale->updated_at?->format('Y-m-d H:i'),
            'note' => $sale->note,
            'lines' => $sale->lines->map(fn ($l): array => [
                // Nécessaire à la modification d'un brouillon : sans lui,
                // l'interface ne peut pas renvoyer la ligne au serveur.
                'product_id' => (int) $l->product_id,
                'sku' => $l->product?->sku,
                'name' => $l->product?->name,
                'quantity' => $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'price_type_code' => $l->price_type_code,
                'line_total' => (float) $l->line_total,
            ])->values()->all(),
        ]]);
    }

    public function confirm(Request $request, Sale $sale, ConfirmSaleAction $action): JsonResponse
    {
        $allowOverCredit = $request->user()?->can('customer.set_credit_limit') ?? false;

        try {
            $action->execute($sale, $request->user()?->id, $allowOverCredit);
        } catch (RuntimeException|InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($sale->refresh());
    }

    public function cancel(Request $request, Sale $sale, CancelSaleAction $action): JsonResponse
    {
        try {
            $action->execute($sale, $request->user()?->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($sale->refresh());
    }

    /**
     * Convertit un devis en vente (facture brouillon reliée au devis).
     * POST /sales/{sale}/convert
     */
    public function convert(Request $request, Sale $sale, DocumentNumberGeneratorInterface $numbers): JsonResponse
    {
        if ($sale->type !== Sale::TYPE_QUOTE) {
            return response()->json(['message' => 'Seul un devis peut être converti en vente.'], 422);
        }

        if ($sale->status === Sale::STATUS_CANCELLED) {
            return response()->json(['message' => 'Ce devis est annulé.'], 422);
        }

        if (Sale::query()->where('quote_id', $sale->id)->exists()) {
            return response()->json(['message' => 'Ce devis a déjà été converti en vente.'], 422);
        }

        $invoice = DB::transaction(function () use ($sale, $numbers, $request): Sale {
            $invoice = Sale::query()->create([
                'reference' => $numbers->next('sale'),
                'type' => Sale::TYPE_INVOICE,
                'status' => Sale::STATUS_DRAFT,
                'customer_id' => $sale->customer_id,
                'quote_id' => $sale->id,
                'warehouse_id' => $sale->warehouse_id,
                'user_id' => $request->user()?->id,
                'subtotal' => $sale->subtotal,
                'discount_percent' => $sale->discount_percent,
                'total' => $sale->total,
                'note' => trim('Issu du devis '.$sale->reference.'. '.($sale->note ?? '')),
            ]);

            foreach ($sale->lines()->get() as $line) {
                $invoice->lines()->create([
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'price_type_code' => $line->price_type_code,
                    'line_total' => $line->line_total,
                ]);
            }

            return $invoice;
        });

        return response()->json(['data' => [
            'id' => $invoice->id,
            'reference' => $invoice->reference,
            'quote_reference' => $sale->reference,
        ]], 201);
    }

    /**
     * PDF de la facture / du devis (avec montants).
     * GET /sales/{sale}/pdf
     */
    public function pdf(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'warehouse']);
        $lines = $sale->lines()->with('product:id,sku,name')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sale-invoice', [
            'sale' => $sale,
            'lines' => $lines,
        ])->download($sale->reference.'.pdf');
    }

    /**
     * PDF du bon de sortie (quantités seules, aucun montant).
     * GET /sales/{sale}/exit-pdf
     */
    /**
     * Bon de sortie : quantités seules, pour le magasinier.
     *
     * Distinct du bon de livraison, qui part chez le client avec les prix. Y
     * porter les montants les exposerait à toute la chaîne logistique.
     */
    public function exitPdf(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'warehouse']);
        $lines = $sale->lines()->with('product:id,sku,name')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.exit-note', [
            'sale' => $sale,
            'lines' => $lines,
        ])->download('BS-'.$sale->reference.'.pdf');
    }

    /**
     * Bon de livraison : remis au client, avec les prix pratiqués sur la vente.
     */
    public function deliveryPdf(Sale $sale): HttpResponse
    {
        $sale->load(['customer', 'warehouse']);
        $lines = $sale->lines()->with('product:id,sku,name')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.delivery-note', [
            'sale' => $sale,
            'lines' => $lines,
        ])->download('BL-'.$sale->reference.'.pdf');
    }
}
