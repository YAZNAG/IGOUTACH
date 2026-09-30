<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Models\ExpenseCategory;
use App\Domain\Expenses\Models\RecurringExpense;
use App\Domain\Sales\Models\CashSession;
use App\Domain\Sales\Services\CashBoxService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use App\Rules\WarehouseAccessible;

/**
 * Charges : catégories, saisie avec justificatif photo (facultatif),
 * validation ou rejet par le responsable.
 */
final class ExpenseController extends Controller
{
    /**
     * Types de charge.
     *
     * Par défaut seuls les types actifs sont renvoyés : ce sont les listes
     * déroulantes de saisie qui appellent ce point, et proposer un type retiré
     * du service ferait ressurgir ce qu'on venait d'écarter. L'écran de
     * paramétrage, lui, demande explicitement les inactifs pour pouvoir les
     * réactiver.
     */
    public function categories(Request $request): JsonResponse
    {
        $avecInactifs = $request->boolean('with_inactive');

        return response()->json(['data' => ExpenseCategory::query()
            ->when(! $avecInactifs, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])]);
    }

    /**
     * Renomme un type de charge ou le retire du service.
     *
     * Renommer n'a aucun effet sur les charges déjà saisies : elles pointent
     * sur l'identifiant, pas sur le libellé.
     */
    public function updateCategory(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        /** @var array{name?: string, is_active?: bool} $data */
        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'max:120',
                Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $expenseCategory->update($data);

        return response()->json(['data' => [
            'id' => $expenseCategory->id,
            'name' => $expenseCategory->name,
            'is_active' => $expenseCategory->is_active,
        ]]);
    }

    /**
     * Supprime un type de charge, à condition qu'aucune écriture ne s'y
     * rattache.
     *
     * Supprimer un type déjà utilisé laisserait des charges orphelines et
     * fausserait les totaux par type sur tout l'historique. Dans ce cas on
     * propose la désactivation : le type disparaît des listes de saisie sans
     * amputer le passé.
     */
    public function destroyCategory(ExpenseCategory $expenseCategory): JsonResponse
    {
        $charges = Expense::query()->where('expense_category_id', $expenseCategory->id)->count();
        $recurrentes = RecurringExpense::query()->where('expense_category_id', $expenseCategory->id)->count();

        if ($charges > 0 || $recurrentes > 0) {
            return response()->json([
                'message' => sprintf(
                    'Ce type est utilisé par %d charge(s) et %d charge(s) fixe(s) : il ne peut pas être supprimé. Désactivez-le pour le retirer des listes.',
                    $charges,
                    $recurrentes,
                ),
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json(null, 204);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        /** @var array{name: string} $data */
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:expense_categories,name']]);

        $category = ExpenseCategory::query()->create(['name' => $data['name']]);

        return response()->json(['data' => ['id' => $category->id, 'name' => $category->name]], 201);
    }

    /**
     * Filtres communs a la liste et aux exports : un fichier qui ne
     * correspondrait pas au tableau affiche serait un piege.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Expense>
     */
    private function filtrer(Request $request)
    {
        return Expense::query()
            ->when($request->integer('warehouse_id') > 0, fn ($q) => $q->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')->value()))
            // La periode porte sur la date de la charge, pas sur sa saisie :
            // une facture de juillet enregistree en aout reste une charge de
            // juillet.
            ->when($request->string('date_from')->isNotEmpty(), fn ($q) => $q->whereDate('expense_date', '>=', $request->string('date_from')->value()))
            ->when($request->string('date_to')->isNotEmpty(), fn ($q) => $q->whereDate('expense_date', '<=', $request->string('date_to')->value()))
            // La recherche porte aussi sur la categorie : on cherche « loyer »
            // sans savoir si c'est le libelle de la charge ou sa famille.
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request): void {
                $terme = '%'.$request->string('search')->value().'%';
                $q->where(function ($x) use ($terme): void {
                    $x->where('label', 'like', $terme)
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $terme));
                });
            });
    }

    public function index(Request $request): JsonResponse
    {
        // Total de la selection entiere, pas des vingt lignes de la page : c'est
        // la somme d'un lieu sur une periode qu'on vient chercher en filtrant.
        $montantTotal = (float) $this->filtrer($request)->where('status', '!=', 'rejected')->sum('amount');

        $expenses = $this->filtrer($request)
            ->with(['category:id,name', 'warehouse:id,code', 'user:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20);

        $expenses->through(fn (Expense $e): array => [
            'id' => $e->id,
            'label' => $e->label,
            'category' => $e->category?->name,
            'warehouse' => $e->warehouse?->code,
            'user' => $e->user?->name,
            'amount' => (float) $e->amount,
            // Le moyen de reglement est saisi : il doit se lire dans la liste,
            // sinon l'information est enregistree sans jamais etre montree.
            'payment_method' => $e->paymentMethod?->name,
            'expense_date' => $e->expense_date->format('Y-m-d'),
            // `expense_date` est la date de la charge ; `created_at`
            // celle de sa saisie. Une facture de juillet enregistree
            // en aout n'a pas la meme histoire selon qu'on lit l'une
            // ou l'autre.
            'created_at' => $e->created_at?->format('Y-m-d H:i'),
            'has_receipt' => $e->receipt_path !== null,
            'status' => $e->status,
            // Réglée ou portée au crédit : sans cette information, une charge
            // impayée serait indiscernable d'une charge réglée en espèces.
            'payment_status' => $e->payment_status,
            'paid_at' => $e->paid_at?->format('Y-m-d'),
        ]);

        return response()->json([
            'data' => $expenses->items(),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'total_amount' => round($montantTotal, 2),
            ],
        ]);
    }

    /**
     * Export Excel ou PDF des charges filtrees (lieu, periode, statut, recherche).
     */
    public function export(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $charges = $this->filtrer($request)
            ->with(['category:id,name', 'warehouse:id,code', 'user:id,name', 'paymentMethod:id,name'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $headings = ['Date', 'Libelle', 'Type', 'Lieu', 'Saisie par', 'Mode de reglement', 'Reglement', 'Statut', 'Montant (DH)'];

        $rows = $charges->map(fn (Expense $e): array => [
            $e->expense_date->format('d/m/Y'),
            $e->label,
            $e->category?->name ?? '',
            $e->warehouse?->code ?? 'Societe',
            $e->user?->name ?? '',
            $e->paymentMethod?->name ?? '',
            $e->payment_status === 'paid' ? 'Reglee' : 'A payer',
            match ($e->status) {
                'approved' => 'Validee',
                'pending' => 'En attente',
                'rejected' => 'Rejetee',
                default => (string) $e->status,
            },
            number_format((float) $e->amount, 2, '.', ''),
        ])->all();

        // Les charges rejetees figurent dans le fichier mais pas dans le total :
        // elles n'ont jamais ete depensees.
        $retenues = $charges->where('status', '!=', 'rejected');
        $rows[] = array_fill(0, count($headings), '');
        $rows[] = ['', 'TOTAL ('.$retenues->count().' charge(s), hors rejetees)', '', '', '', '', '', '',
            number_format((float) $retenues->sum('amount'), 2, '.', '')];

        $titre = 'Charges'.$this->libelleLieu($request).$this->suffixePeriode($request);

        if ($request->string('format')->value() === 'pdf') {
            $plafond = \App\Support\Pdf\PdfLimit::lignes();
            if (count($rows) > $plafond) {
                return response()->json([
                    'message' => 'Cet export represente '.count($rows)." lignes, plus que ce qu'un PDF peut tenir "
                        ."sur ce serveur (limite : {$plafond}). Resserrez la periode, ou choisissez l'export Excel.",
                ], 422);
            }

            return \Barryvdh\DomPDF\Facade\Pdf::loadHtml(\App\Support\Export\HtmlTable::render($titre, $headings, $rows))
                ->setPaper('a4', 'landscape')
                ->download('IGOUTECH_charges.pdf');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ArrayExport($headings, $rows),
            'IGOUTECH_charges.xlsx',
        );
    }

    private function libelleLieu(Request $request): string
    {
        $id = $request->integer('warehouse_id');
        if ($id <= 0) {
            return '';
        }

        $code = \App\Domain\Warehouses\Models\Warehouse::query()->whereKey($id)->value('code');

        return $code ? ' — '.$code : '';
    }

    /** « du 01/09/2026 au 15/09/2026 », ou rien sans borne. */
    private function suffixePeriode(Request $request): string
    {
        $du = $request->string('date_from')->value();
        $au = $request->string('date_to')->value();
        $f = static fn (string $d): string => \Carbon\Carbon::parse($d)->format('d/m/Y');

        return match (true) {
            $du !== '' && $au !== '' => ' du '.$f($du).' au '.$f($au),
            $du !== '' => ' depuis le '.$f($du),
            $au !== '' => " jusqu'au ".$f($au),
            default => '',
        };
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Le type se choisit dans la liste, ou se nomme sur-le-champ avec
            // « category_name ». Obliger à créer le type d'abord conduisait
            // à ranger une dépense inhabituelle sous un type approchant, et
            // le libellé perdait alors la seule trace de sa vraie nature.
            'expense_category_id' => [
                'required_without:category_name', 'nullable', 'integer', 'exists:expense_categories,id',
            ],
            'category_name' => ['required_without:expense_category_id', 'nullable', 'string', 'max:120'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id', new WarehouseAccessible],
            'label' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Réglée sur-le-champ, ou portée au crédit et due.
            'payment_status' => ['sometimes', 'in:paid,unpaid'],
            // Exigé dès lors que la charge est déclarée payée : une charge
            // réglée sans mode de règlement ne se rapproche d'aucune caisse.
            'payment_method_id' => [
                'nullable', 'integer', 'exists:payment_methods,id',
                Rule::requiredIf(fn (): bool => $request->input('payment_status', 'paid') === 'paid'),
            ],
        ]);

        // Un type saisi à la main rejoint le référentiel : le suivant qui
        // engage la même dépense le trouvera dans la liste. La recherche est
        // insensible à la casse et aux espaces de bord, sans quoi « Taxes »,
        // « taxes » et « Taxes  » deviendraient trois familles distinctes.
        $categorieId = $data['expense_category_id'] ?? null;

        if ($categorieId === null) {
            $nom = trim((string) $data['category_name']);

            $categorie = ExpenseCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($nom)])->first()
                ?? ExpenseCategory::query()->create(['name' => $nom]);

            $categorieId = $categorie->id;
        }

        $reglee = ($data['payment_status'] ?? 'paid') === 'paid';

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            /** @var UploadedFile $file */
            $file = $request->file('receipt');
            $receiptPath = (string) $file->store('receipts', 'public');
        }

        $expense = Expense::query()->create([
            'expense_category_id' => $categorieId,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'user_id' => (int) $request->user()?->id,
            'label' => $data['label'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'receipt_path' => $receiptPath,
            'status' => Expense::STATUS_PENDING,
            'payment_status' => $reglee ? 'paid' : 'unpaid',
            // Le mode n'a de sens que sur une charge réglée : le conserver sur
            // une charge à crédit laisserait croire qu'elle a été payée.
            'payment_method_id' => $reglee ? ($data['payment_method_id'] ?? null) : null,
            'paid_at' => $reglee ? $data['expense_date'] : null,
        ]);

        return response()->json(['data' => [
            'id' => $expense->id,
            'payment_status' => $expense->payment_status,
        ]], 201);
    }

    /**
     * Règle une charge restée au crédit.
     *
     * Sans ce point, une charge portée au crédit n'aurait aucun moyen d'en
     * sortir : la dette resterait ouverte indéfiniment.
     */
    public function pay(Request $request, Expense $expense): JsonResponse
    {
        /** @var array{payment_method_id: int, paid_at?: string} $data */
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['sometimes', 'date'],
        ]);

        if ($expense->payment_status === 'paid') {
            return response()->json(['message' => 'Cette charge est déjà réglée.'], 422);
        }

        $expense->update([
            'payment_status' => 'paid',
            'payment_method_id' => $data['payment_method_id'],
            'paid_at' => $data['paid_at'] ?? now()->toDateString(),
        ]);

        return response()->json(['data' => [
            'id' => $expense->id,
            'payment_status' => $expense->payment_status,
            'paid_at' => $expense->paid_at?->toDateString(),
        ]]);
    }

    /**
     * Supprime une charge, et rend son montant à la caisse s'il en était sorti.
     *
     * Rien n'est à recréditer explicitement : le solde du tiroir se recalcule
     * à partir des charges réglées en espèces. La charge disparue, la somme
     * revient d'elle-même — c'est la même vérité lue deux fois, pas deux
     * écritures à tenir d'accord.
     *
     * Une seule porte reste fermée : une caisse déjà clôturée a été comptée
     * sur la foi de cette charge. La retirer après coup ferait mentir un écart
     * que quelqu'un a constaté et signé.
     */
    public function destroy(Request $request, Expense $expense, CashBoxService $caisse): JsonResponse
    {
        $sortieEspeces = $expense->payment_status === 'paid'
            && $expense->paymentMethod?->type === 'cash'
            && $expense->warehouse_id !== null;

        if ($sortieEspeces) {
            $jour = ($expense->paid_at ?? $expense->created_at)?->toDateString();

            $sessionClose = CashSession::withoutGlobalScopes()
                ->where('warehouse_id', $expense->warehouse_id)
                ->where('status', CashSession::STATUS_CLOSED)
                ->whereDate('opened_at', '<=', $jour)
                ->whereDate('closed_at', '>=', $jour)
                ->exists();

            if ($sessionClose) {
                return response()->json([
                    'message' => 'La caisse de ce jour est déjà clôturée : cette charge y a été comptée. '
                        .'Saisissez plutôt une régularisation.',
                ], 422);
            }
        }

        $expense->delete();

        $solde = $expense->warehouse_id !== null
            ? $caisse->solde((int) $expense->warehouse_id, CashSession::withoutGlobalScopes()
                ->where('warehouse_id', $expense->warehouse_id)
                ->where('status', CashSession::STATUS_OPEN)
                ->latest('id')
                ->first())
            : null;

        return response()->json([
            'message' => $sortieEspeces
                ? 'Charge supprimée : son montant est revenu en caisse.'
                : 'Charge supprimée.',
            'cash' => $solde,
        ]);
    }

    public function decide(Request $request, Expense $expense): JsonResponse
    {
        /** @var array{decision: string} $data */
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected']]);

        if ($expense->status !== Expense::STATUS_PENDING) {
            return response()->json(['message' => 'Charge déjà traitée.'], 422);
        }

        $expense->update([
            'status' => $data['decision'],
            'approved_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => ['id' => $expense->id, 'status' => $expense->status]]);
    }
}
