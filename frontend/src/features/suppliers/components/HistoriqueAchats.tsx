import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useSupplierOptions } from '@/features/access/hooks'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { api } from '@/lib/api'
import { formatDate, formatDateHeure } from '@/lib/utils'

interface ReceiptRow {
  id: number
  number: string
  received_at: string | null
  supplier: { id: number | null; code: string | null; name: string | null }
  warehouse: { id: number | null; code: string | null; name: string | null }
  purchase_order: { id: number; number: string } | null
  lines_count: number
  total_quantity: number
  total_amount: number
  payment_status: string | null
  amount_paid: number
  created_at: string | null
}

interface ReceiptsPage {
  data: ReceiptRow[]
  meta: { current_page: number; last_page: number; total: number }
}

const STATUT_TONES: Record<string, 'ok' | 'warn' | 'bad'> = {
  paid: 'ok',
  partial: 'warn',
  unpaid: 'bad',
}

const STATUT_LABELS: Record<string, string> = {
  paid: 'Réglé',
  partial: 'Partiel',
  unpaid: 'Non réglé',
}

function money(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** Le premier jour d'il y a trois mois : un achat n'est pas quotidien. */
function debutParDefaut(): string {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth() - 3, 1).toISOString().slice(0, 10)
}

/**
 * Journal des achats : les réceptions de marchandise, tous fournisseurs
 * confondus.
 *
 * La fiche d'un fournisseur montre ses propres achats ; il faut les ouvrir un
 * à un pour reconstituer une période. Ce journal donne la période d'abord, et
 * le fournisseur devient un filtre.
 */
export function HistoriqueAchats() {
  const [dateFrom, setDateFrom] = useState(debutParDefaut)
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().slice(0, 10))
  const [supplierId, setSupplierId] = useState(0)
  const [page, setPage] = useState(1)

  const { data: suppliers = [] } = useSupplierOptions()

  const { data, isLoading, isError } = useQuery<ReceiptsPage>({
    queryKey: ['goods-receipts-journal', dateFrom, dateTo, supplierId, page],
    queryFn: async () => {
      const { data } = await api.get<ReceiptsPage>('/goods-receipts', {
        params: {
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
          supplier_id: supplierId || undefined,
          page,
        },
      })
      return data
    },
  })

  const rows = data?.data ?? []
  const meta = data?.meta

  const { terme, setTerme, resultats, actif } = useRechercheLocale(rows, (r) => [
    r.number,
    r.supplier.name,
    r.warehouse.code,
    r.warehouse.name,
  ])

  // Total de la page seulement : l'API pagine sans totaliser la période.
  // Le dire vaut mieux que d'afficher un chiffre qu'on croirait global.
  const totalPage = resultats.reduce((somme, r) => somme + r.total_amount, 0)

  function filtrer(appliquer: () => void) {
    appliquer()
    setPage(1)
  }

  return (
    <Card>
      <CardHeader
        title="Historique des achats"
        hint={meta ? `${meta.total} réception(s) sur la période` : undefined}
      />
      <CardBody className="space-y-4">
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <Field label="Du" htmlFor="ha-from">
            <Input
              id="ha-from"
              type="date"
              value={dateFrom}
              onChange={(e) => filtrer(() => setDateFrom(e.target.value))}
            />
          </Field>
          <Field label="Au" htmlFor="ha-to">
            <Input
              id="ha-to"
              type="date"
              value={dateTo}
              onChange={(e) => filtrer(() => setDateTo(e.target.value))}
            />
          </Field>
          <Field label="Chercher dans la page" htmlFor="ha-recherche">
            <SearchInput
              id="ha-recherche"
              value={terme}
              onChange={setTerme}
              placeholder="N° de réception ou lieu…"
              className="w-full"
            />
          </Field>
          <Field label="Fournisseur" htmlFor="ha-supplier">
            <Select
              id="ha-supplier"
              value={supplierId}
              onChange={(e) => filtrer(() => setSupplierId(Number(e.target.value)))}
            >
              <option value={0}>Tous les fournisseurs</option>
              {suppliers.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </Select>
          </Field>
        </div>

        {isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Impossible de charger l'historique des achats.
          </p>
        ) : isLoading ? (
          <p className="text-sm text-muted">Chargement…</p>
        ) : resultats.length === 0 ? (
          <p className="py-6 text-center text-sm text-muted">
            {actif
              ? 'Aucune réception de cette page ne correspond à la recherche.'
              : 'Aucune réception sur cette période.'}
          </p>
        ) : (
          <>
            <div className="overflow-x-auto rounded border border-line">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-2 font-medium">Date</th>
                    <th className="px-4 py-2 font-medium">Saisie le</th>
                    <th className="px-4 py-2 font-medium">Réception</th>
                    <th className="px-4 py-2 font-medium">Fournisseur</th>
                    <th className="px-4 py-2 font-medium">Lieu</th>
                    <th className="px-4 py-2 text-right font-medium">Articles</th>
                    <th className="px-4 py-2 text-right font-medium">Quantité</th>
                    <th className="px-4 py-2 text-right font-medium">Montant</th>
                    <th className="px-4 py-2 font-medium">Règlement</th>
                  </tr>
                </thead>
                <tbody>
                  {resultats.map((r) => (
                    <tr key={r.id} className="border-b border-line last:border-0">
                      <td className="px-4 py-2 text-muted">{formatDate(r.received_at)}</td>
                      <td className="px-4 py-2 text-faint">{formatDateHeure(r.created_at)}</td>
                      <td className="mono px-4 py-2">
                        <Link to={`/goods-receipts/${r.id}`} className="text-ink hover:text-sky hover:underline">
                          {r.number}
                        </Link>
                      </td>
                      <td className="px-4 py-2 text-ink">{r.supplier?.name ?? '—'}</td>
                      <td className="px-4 py-2 text-muted">{r.warehouse?.code ?? '—'}</td>
                      <td className="tabular px-4 py-2 text-right text-muted">{r.lines_count}</td>
                      <td className="tabular px-4 py-2 text-right text-muted">
                        {r.total_quantity.toLocaleString('fr-FR')}
                      </td>
                      <td className="tabular px-4 py-2 text-right font-medium text-ink">
                        {money(r.total_amount)}
                      </td>
                      <td className="px-4 py-2">
                        {r.payment_status ? (
                          <Badge tone={STATUT_TONES[r.payment_status] ?? 'warn'}>
                            {STATUT_LABELS[r.payment_status] ?? r.payment_status}
                          </Badge>
                        ) : (
                          <span className="text-faint">—</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
              <span className="text-muted">
                Total de cette page :{' '}
                <span className="tabular font-semibold text-ink">{money(totalPage)} DH</span>
              </span>
              {meta && meta.last_page > 1 ? (
                <div className="flex items-center gap-2 text-muted">
                  <span>
                    Page {meta.current_page} / {meta.last_page}
                  </span>
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
              ) : null}
            </div>
          </>
        )}
      </CardBody>
    </Card>
  )
}
