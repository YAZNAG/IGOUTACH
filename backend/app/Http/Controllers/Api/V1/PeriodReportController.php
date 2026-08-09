<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Sales\Models\Sale;
use App\Domain\Stock\Models\StockMovement;
use App\Http\Controllers\Controller;
use App\Support\Scopes\WarehouseScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Journaux PDF sur une période : ventes, entrées, sorties, charges.
 *
 * Les quatre journaux partagent une même mise en page. Le cloisonnement par
 * lieu s'applique : un responsable n'imprime que son propre journal, sans
 * avoir à le demander — c'est le scope des modèles qui s'en charge.
 */
final class PeriodReportController extends Controller
{
    public function sales(Request $request): HttpResponse
    {
        [$du, $au] = $this->periode($request);

        $ventes = Sale::query()
            ->with(['customer:id,name', 'warehouse:id,code'])
            ->where('type', Sale::TYPE_INVOICE)
            ->whereDate('created_at', '>=', $du)
            ->whereDate('created_at', '<=', $au)
            ->orderBy('created_at')
            ->get();

        $lignes = $ventes->map(fn (Sale $v): array => [
            'date' => $v->created_at?->format('d/m/Y'),
            'reference' => $v->reference,
            'tiers' => $v->customer?->name ?? 'Client de passage',
            'detail' => $this->libelleStatut($v),
            'lieu' => $v->warehouse?->code,
            'montant' => (float) $v->total,
        ])->all();

        return $this->rendre('Journal des ventes', $du, $au, [
            'Date', 'Référence', 'Client', 'État', 'Lieu', 'Montant',
        ], $lignes, 'ventes');
    }

    public function stockEntries(Request $request): HttpResponse
    {
        return $this->journalMouvements($request, ['in', 'transfer_in', 'return_in'], 'Journal des entrées de stock', 'entrees');
    }

    public function stockExits(Request $request): HttpResponse
    {
        return $this->journalMouvements($request, ['out', 'transfer_out', 'return_out'], 'Journal des sorties de stock', 'sorties');
    }

    public function expenses(Request $request): HttpResponse
    {
        [$du, $au] = $this->periode($request);

        $charges = Expense::query()
            ->with(['category:id,name', 'warehouse:id,code'])
            ->whereDate('expense_date', '>=', $du)
            ->whereDate('expense_date', '<=', $au)
            ->orderBy('expense_date')
            ->get();

        $lignes = $charges->map(fn (Expense $c): array => [
            'date' => $c->expense_date?->format('d/m/Y'),
            'reference' => $c->category?->name ?? '—',
            'tiers' => $c->label,
            'detail' => $this->libelleStatutCharge($c->status),
            'lieu' => $c->warehouse?->code ?? 'Société',
            'montant' => (float) $c->amount,
        ])->all();

        return $this->rendre('Journal des charges', $du, $au, [
            'Date', 'Catégorie', 'Libellé', 'État', 'Lieu', 'Montant',
        ], $lignes, 'charges');
    }

    /**
     * @param  list<string>  $codes
     */
    private function journalMouvements(Request $request, array $codes, string $titre, string $fichier): HttpResponse
    {
        [$du, $au] = $this->periode($request);

        $mouvements = StockMovement::query()
            ->with(['product:id,sku,name', 'movementType:id,code,name', 'warehouse:id,code'])
            ->whereHas('movementType', fn ($q) => $q->whereIn('code', $codes))
            ->whereDate('created_at', '>=', $du)
            ->whereDate('created_at', '<=', $au)
            ->orderBy('created_at')
            ->get();

        $lignes = $mouvements->map(fn (StockMovement $m): array => [
            'date' => $m->created_at?->format('d/m/Y'),
            'reference' => $m->product?->sku ?? '—',
            'tiers' => $m->product?->name ?? '—',
            'detail' => $m->movementType?->name ?? $m->note,
            'lieu' => $m->warehouse?->code,
            // La valeur absolue : le sens est déjà porté par le titre du
            // journal, un signe négatif dans une colonne « Quantité » d'un
            // journal de sorties n'apprend rien.
            'montant' => (float) abs($m->quantity),
            'unites' => true,
        ])->all();

        return $this->rendre($titre, $du, $au, [
            'Date', 'Réf', 'Article', 'Nature', 'Lieu', 'Quantité',
        ], $lignes, $fichier);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periode(Request $request): array
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        // Sans période demandée, le mois en cours : c'est le journal qu'on
        // sort le plus souvent, autant l'avoir sans rien saisir.
        $du = $request->string('date_from')->isNotEmpty()
            ? $request->string('date_from')->value()
            : Carbon::today()->startOfMonth()->toDateString();

        $au = $request->string('date_to')->isNotEmpty()
            ? $request->string('date_to')->value()
            : Carbon::today()->toDateString();

        return [$du, $au];
    }

    /**
     * @param  list<string>  $entetes
     * @param  list<array<string, mixed>>  $lignes
     */
    private function rendre(string $titre, string $du, string $au, array $entetes, array $lignes, string $fichier): HttpResponse
    {
        $total = array_sum(array_column($lignes, 'montant'));
        $unites = ($lignes[0]['unites'] ?? false) === true;

        $pdf = Pdf::loadView('pdf.period-journal', [
            'titre' => $titre,
            'du' => Carbon::parse($du)->format('d/m/Y'),
            'au' => Carbon::parse($au)->format('d/m/Y'),
            'entetes' => $entetes,
            'lignes' => $lignes,
            'total' => $total,
            'unites' => $unites,
            'lieu' => $this->lieuImprime(),
        ]);

        return $pdf->download(sprintf('%s-%s-%s.pdf', $fichier, $du, $au));
    }

    /**
     * Lieu à mentionner en tête : celui de l'utilisateur, ou « tous les lieux »
     * pour une vue multi-lieux. Un journal sans son périmètre prête à confusion.
     */
    private function lieuImprime(): ?string
    {
        $user = auth()->user();

        if ($user === null || $user->can(WarehouseScope::DEFAULT_GLOBAL_PERMISSION)) {
            return 'Tous les lieux';
        }

        return $user->warehouse?->code.' — '.$user->warehouse?->name;
    }

    private function libelleStatut(Sale $vente): string
    {
        $etat = match ($vente->status) {
            Sale::STATUS_CONFIRMED => 'Confirmée',
            'cancelled' => 'Annulée',
            default => 'Brouillon',
        };

        $reglement = match ($vente->payment_status) {
            'paid' => 'payée',
            'partial' => 'partiellement payée',
            default => 'impayée',
        };

        return $etat.' · '.$reglement;
    }

    private function libelleStatutCharge(?string $statut): string
    {
        return match ($statut) {
            'approved' => 'Validée',
            'rejected' => 'Refusée',
            default => 'En attente',
        };
    }
}
