<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerLedgerEntry;
use App\Domain\Sales\Models\Payment;
use App\Domain\Sales\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rattache chaque fiche client a un lieu, et dedouble celles qui ont achete
 * dans plusieurs lieux.
 *
 * Avant : un fichier client commun, un seul solde par client, et des ventes
 * dans n'importe quel lieu. Apres : une fiche par lieu, avec son credit, ses
 * ventes et ses reglements. Pour un client ayant achete dans deux lieux, la
 * fiche d'origine garde le lieu ou il a le plus achete ; une seconde fiche
 * est creee pour l'autre lieu et recoit les ventes, reglements et ecritures
 * qui s'y rapportent. Les soldes sont ensuite recalcules depuis le
 * grand-livre de chaque fiche.
 *
 * Sans --executer, la commande ne fait que decrire ce qu'elle ferait.
 */
final class SeparerClientsParLieu extends Command
{
    protected $signature = 'clients:separer-par-lieu {--executer : Applique les changements (sinon simulation)}';

    protected $description = 'Rattache chaque fiche client a un lieu et dedouble celles presentes dans plusieurs lieux';

    private int $rattaches = 0;

    private int $dedoubles = 0;

    private int $sansLieu = 0;

    public function handle(): int
    {
        $executer = (bool) $this->option('executer');
        $this->info($executer ? 'Application des changements.' : 'SIMULATION : rien ne sera modifie.');

        $clients = Customer::query()->orderBy('id')->get();

        DB::beginTransaction();

        try {
            foreach ($clients as $client) {
                $this->traiter($client);
            }

            if ($executer) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Interrompu : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            '%d fiche(s) rattachee(s) a un lieu, %d fiche(s) creee(s) par dedoublement, %d fiche(s) restee(s) sans lieu.',
            $this->rattaches,
            $this->dedoubles,
            $this->sansLieu,
        ));

        return self::SUCCESS;
    }

    private function traiter(Customer $client): void
    {
        // Nombre de ventes par lieu, tous types et statuts : un devis compte
        // aussi, il dit ou le client est connu.
        $parLieu = DB::table('sales')
            ->where('customer_id', $client->id)
            ->selectRaw('warehouse_id, COUNT(*) as nb, MAX(created_at) as derniere')
            ->groupBy('warehouse_id')
            ->orderByDesc('nb')
            ->orderByDesc('derniere')
            ->get();

        if ($parLieu->isEmpty()) {
            $this->rattacherSansVente($client);

            return;
        }

        // Le lieu deja renseigne sur la fiche l'emporte s'il fait partie des
        // lieux d'achat ; sinon, celui ou le client a le plus achete.
        $principal = $parLieu->firstWhere('warehouse_id', $client->warehouse_id)?->warehouse_id
            ?? (int) $parLieu->first()->warehouse_id;

        if ((int) $client->warehouse_id !== (int) $principal) {
            $client->update(['warehouse_id' => $principal]);
            $this->rattaches++;
        }

        $autres = $parLieu->where('warehouse_id', '!=', $principal)->pluck('warehouse_id')->map(fn ($v) => (int) $v);

        if ($autres->isEmpty()) {
            $this->line(sprintf('  %-9s %-30s -> lieu %d', $client->code, mb_substr($client->name, 0, 30), $principal));

            return;
        }

        foreach ($autres as $lieu) {
            $this->dedoubler($client, $lieu);
        }

        $this->recalculerSolde($client);
    }

    /**
     * Une fiche sans vente : le lieu de son createur, s'il en a un.
     */
    private function rattacherSansVente(Customer $client): void
    {
        if ($client->warehouse_id !== null) {
            return;
        }

        $lieu = DB::table('users')->where('id', $client->created_by)->value('warehouse_id');

        if ($lieu === null) {
            $this->sansLieu++;
            $this->warn(sprintf('  %-9s %-30s sans vente et sans lieu : a rattacher a la main', $client->code, mb_substr($client->name, 0, 30)));

            return;
        }

        $client->update(['warehouse_id' => $lieu]);
        $this->rattaches++;
        $this->line(sprintf('  %-9s %-30s -> lieu %d (lieu de son createur)', $client->code, mb_substr($client->name, 0, 30), $lieu));
    }

    /**
     * Cree la fiche du client pour un autre lieu et y deplace ce qui s'y
     * rapporte : ventes du lieu, reglements imputes a ces ventes (ou
     * encaisses dans la caisse du lieu), ecritures de grand-livre liees.
     */
    private function dedoubler(Customer $origine, int $lieu): void
    {
        $copie = Customer::query()->create([
            'code' => $this->prochainCode(),
            'name' => $origine->name,
            'is_company' => $origine->is_company,
            'contact_name' => $origine->contact_name,
            'phone' => $origine->phone,
            'email' => $origine->email,
            'address' => $origine->address,
            'city' => $origine->city,
            'ice' => $origine->ice,
            'price_type_id' => $origine->price_type_id,
            'seller_id' => $origine->seller_id,
            'warehouse_id' => $lieu,
            'credit_limit' => $origine->credit_limit,
            'balance' => 0,
            'is_blocked' => false,
            'notes' => $origine->notes,
            'is_active' => $origine->is_active,
            'created_by' => $origine->created_by,
        ]);
        $this->dedoubles++;

        $ventes = Sale::withoutGlobalScopes()
            ->where('customer_id', $origine->id)
            ->where('warehouse_id', $lieu)
            ->pluck('id');

        Sale::withoutGlobalScopes()->whereIn('id', $ventes)->update(['customer_id' => $copie->id]);

        // Un reglement suit ses factures : s'il est impute uniquement a des
        // ventes deplacees, il part avec elles. Sans imputation, c'est la
        // caisse qui l'a encaisse qui dit son lieu.
        $reglements = Payment::query()
            ->where('customer_id', $origine->id)
            ->get()
            ->filter(function (Payment $p) use ($ventes, $lieu): bool {
                $imputees = DB::table('payment_allocations')->where('payment_id', $p->id)->pluck('sale_id');

                if ($imputees->isNotEmpty()) {
                    return $imputees->every(fn ($id) => $ventes->contains((int) $id));
                }

                if ($p->sale_id !== null) {
                    return $ventes->contains((int) $p->sale_id);
                }

                if ($p->cash_session_id !== null) {
                    return (int) DB::table('cash_sessions')->where('id', $p->cash_session_id)->value('warehouse_id') === $lieu;
                }

                return false;
            });

        $idsReglements = $reglements->pluck('id');
        Payment::query()->whereIn('id', $idsReglements)->update(['customer_id' => $copie->id]);
        DB::table('cheques')->whereIn('id', $reglements->pluck('cheque_id')->filter())->update(['customer_id' => $copie->id]);

        CustomerLedgerEntry::query()
            ->where('customer_id', $origine->id)
            ->where(fn ($q) => $q
                ->where(fn ($s) => $s->where('reference_type', Sale::class)->whereIn('reference_id', $ventes))
                ->orWhere(fn ($s) => $s->where('reference_type', Payment::class)->whereIn('reference_id', $idsReglements)))
            ->update(['customer_id' => $copie->id]);

        $this->recalculerSolde($copie);

        $this->line(sprintf(
            '  %-9s %-30s -> lieu %d ; copie %s pour le lieu %d : %d vente(s), %d reglement(s), solde %.2f',
            $origine->code,
            mb_substr($origine->name, 0, 30),
            (int) $origine->warehouse_id,
            $copie->code,
            $lieu,
            $ventes->count(),
            $idsReglements->count(),
            (float) $copie->refresh()->balance,
        ));
    }

    /**
     * Le solde d'une fiche est la somme de ses ecritures, et chaque ecriture
     * porte le cumul a sa date : apres un deplacement, tout se recalcule.
     */
    private function recalculerSolde(Customer $client): void
    {
        $solde = 0.0;

        CustomerLedgerEntry::query()
            ->where('customer_id', $client->id)
            ->orderBy('id')
            ->each(function (CustomerLedgerEntry $ligne) use (&$solde): void {
                $solde = round($solde + (float) $ligne->amount, 2);
                $ligne->update(['balance_after' => $solde]);
            });

        $client->update(['balance' => $solde]);
    }

    private function prochainCode(): string
    {
        $last = Customer::withTrashed()->where('code', 'like', 'CL-%')->orderByDesc('id')->value('code');
        $next = $last !== null ? ((int) substr((string) $last, 3)) + 1 : 1;

        return 'CL-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
