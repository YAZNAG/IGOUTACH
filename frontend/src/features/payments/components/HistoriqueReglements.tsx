import { useQuery } from '@tanstack/react-query'
import { Receipt } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { api } from '@/lib/api'
import { formatDate, formatDateHeure } from '@/lib/utils'

interface PaymentRow {
  id: number
  reference: string | null
  customer: string | null
  method: string | null
  amount: number
  cheque_status: string | null
  cheque_reference: string | null
  received_at: string
  created_at: string | null
  receipt_url: string | null
}

interface PaymentsPage {
  data: PaymentRow[]
  meta: { current_page: number; last_page: number; total: number; total_amount: number }
}

const CHEQUE_TONES: Record<string, 'ok' | 'warn' | 'bad' | 'sky'> = {
  pending: 'warn',
  deposited: 'sky',
  cashed: 'ok',
  bounced: 'bad',
}

const CHEQUE_LABELS: Record<string, string> = {
  pending: 'En attente',
  deposited: 'Déposé',
  cashed: 'Encaissé',
  bounced: 'Impayé',
}

function money(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** Premier jour du mois courant, au format attendu par un champ date. */
function debutDuMois(): string {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10)
}

/**
 * Journal de tous les encaissements clients sur une période.
 *
 * Le relevé de compte répond à « que doit ce client » ; il faut ouvrir un
 * client à la fois. Ce journal répond à « qu'a-t-on encaissé cette semaine »,
 * question qu'aucun écran ne traitait.
 */
export function HistoriqueReglements() {
  const [dateFrom, setDateFrom] = useState(debutDuMois)
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().slice(0, 10))
  const [methodId, setMethodId] = useState(0)
  const [page, setPage] = useState(1)

  const { data: methods = [] } = useQuery<{ id: number; name: string }[]>({
    queryKey: ['payment-method-options'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { id: number; name: string }[] }>('/payment-methods')
      return data.data
    },
    staleTime: 5 * 60_000,
  })

  const { data, isLoading, isError } = useQuery<PaymentsPage>({
    queryKey: ['payments-journal', dateFrom, dateTo, methodId, page],
    queryFn: async () => {
      const { data } = await api.get<PaymentsPage>('/payments', {
        params: {
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
          payment_method_id: methodId || undefined,
          per_page: 50,
          page,
        },
      })
      return data
    },
  })

  const rows = data?.data ?? []
  const meta = data?.meta

  // La recherche affine la page affichee ; la periode et la methode, elles,
  // filtrent cote serveur. Le libelle du champ le dit pour eviter le
  // malentendu.
  const { terme, setTerme, resultats, actif } = useRechercheLocale(rows, (r) => [
    r.customer,
    r.reference,
    r.method,
  ])

  /** Tout changement de filtre repart de la première page. */
  function filtrer(appliquer: () => void) {
    appliquer()
    setPage(1)
  }

  return (
    <Card>
      <CardHeader
        title="Historique des règlements"
        hint={
          meta
            ? `${meta.total} règlement(s) — ${money(meta.total_amount)} DH encaissés sur la période`
            : undefined
        }
      />
      <CardBody className="space-y-4">
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <Field label="Du" htmlFor="hr-from">
            <Input
              id="hr-from"
              type="date"
              value={dateFrom}
              onChange={(e) => filtrer(() => setDateFrom(e.target.value))}
            />
          </Field>
          <Field label="Au" htmlFor="hr-to">
            <Input
              id="hr-to"
              type="date"
              value={dateTo}
              onChange={(e) => filtrer(() => setDateTo(e.target.value))}
            />
          </Field>
          <Field label="Chercher dans la page" htmlFor="hr-recherche">
            <SearchInput
              id="hr-recherche"
              value={terme}
              onChange={setTerme}
              placeholder="Client ou référence…"
              className="w-full"
            />
          </Field>
          <Field label="Méthode" htmlFor="hr-method">
            <Select
              id="hr-method"
              value={methodId}
              onChange={(e) => filtrer(() => setMethodId(Number(e.target.value)))}
            >
              <option value={0}>Toutes les méthodes</option>
              {methods.map((m) => (
                <option key={m.id} value={m.id}>
                  {m.name}
                </option>
              ))}
            </Select>
          </Field>
        </div>

        {isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Impossible de charger l'historique des règlements.
          </p>
        ) : isLoading ? (
          <p className="text-sm text-muted">Chargement…</p>
        ) : resultats.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted">
            {actif
              ? 'Aucun règlement de cette page ne correspond à la recherche.'
              : 'Aucun règlement encaissé sur cette période.'}
          </p>
        ) : (
          <>
            <div className="overflow-x-auto rounded border border-line">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-2 font-medium">Date</th>
                    <th className="px-4 py-2 font-medium">Saisi le</th>
                    <th className="px-4 py-2 font-medium">Référence</th>
                    <th className="px-4 py-2 font-medium">Client</th>
                    <th className="px-4 py-2 font-medium">Méthode</th>
                    <th className="px-4 py-2 text-right font-medium">Montant</th>
                    <th className="px-4 py-2 font-medium">Justificatif</th>
                  </tr>
                </thead>
                <tbody>
                  {resultats.map((r) => (
                    <tr key={r.id} className="border-b border-line last:border-0">
                      <td className="px-4 py-2 text-muted">{formatDate(r.received_at)}</td>
                      <td className="px-4 py-2 text-faint">{formatDateHeure(r.created_at)}</td>
                      <td className="mono px-4 py-2 text-muted">{r.reference ?? '—'}</td>
                      <td className="px-4 py-2 text-ink">{r.customer ?? 'Client de passage'}</td>
                      <td className="px-4 py-2">
                        <span className="text-muted">{r.method ?? '—'}</span>
                        {/* Un chèque encaissé et un chèque impayé pèsent le
                            même montant : sans son état, la ligne induit en
                            erreur. */}
                        {r.cheque_status ? (
                          <Badge tone={CHEQUE_TONES[r.cheque_status] ?? 'sky'} className="ml-2">
                            {CHEQUE_LABELS[r.cheque_status] ?? r.cheque_status}
                          </Badge>
                        ) : null}
                      </td>
                      <td className="tabular px-4 py-2 text-right font-medium text-ok">
                        {money(r.amount)}
                      </td>
                      <td className="px-4 py-2">
                        {r.receipt_url ? (
                          <a
                            href={r.receipt_url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1 text-sky hover:underline"
                          >
                            <Receipt className="h-4 w-4" />
                            Voir
                          </a>
                        ) : (
                          <span className="text-faint">—</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {meta && meta.last_page > 1 ? (
              <div className="flex items-center justify-between text-sm text-muted">
                <span>
                  Page {meta.current_page} sur {meta.last_page}
                </span>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={meta.current_page <= 1}
                    onClick={() => setPage((p) => p - 1)}
                  >
                    Précédent
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => setPage((p) => p + 1)}
                  >
                    Suivant
                  </Button>
                </div>
              </div>
            ) : null}
          </>
        )}
      </CardBody>
    </Card>
  )
}
