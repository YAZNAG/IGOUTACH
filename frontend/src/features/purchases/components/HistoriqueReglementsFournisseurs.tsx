import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useSupplierOptions } from '@/features/access/hooks'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { api } from '@/lib/api'
import { usePaymentMethods } from '../hooks'
import { formatDate, formatDateHeure } from '@/lib/utils'

interface SupplierPaymentRow {
  id: number
  supplier: string | null
  supplier_id: number
  goods_receipt: string | null
  goods_receipt_id: number | null
  amount: number
  paid_at: string
  created_at: string | null
  payment_method: string | null
  notes: string | null
  created_by: string | null
}

interface Journal {
  rows: SupplierPaymentRow[]
  count: number
  total_paid: number
  /** Vrai quand la période dépasse les lignes rapportées. */
  truncated: boolean
}

function money(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function debutDuMois(): string {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10)
}

/**
 * Journal de tous les règlements versés aux fournisseurs sur une période.
 *
 * L'historique existant se lit fournisseur par fournisseur : pour savoir ce
 * qui est sorti dans la semaine il fallait les ouvrir un à un. Ce journal
 * couvre la période, tous fournisseurs confondus.
 */
export function HistoriqueReglementsFournisseurs() {
  const [dateFrom, setDateFrom] = useState(debutDuMois)
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().slice(0, 10))
  const [supplierId, setSupplierId] = useState(0)
  const [methodId, setMethodId] = useState(0)

  const { data: suppliers = [] } = useSupplierOptions()
  const { data: methods = [] } = usePaymentMethods()

  const { data, isLoading, isError } = useQuery<Journal>({
    queryKey: ['supplier-payments-journal', dateFrom, dateTo, supplierId, methodId],
    queryFn: async () => {
      const { data } = await api.get<{ data: Journal }>('/supplier-payments', {
        params: {
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
          supplier_id: supplierId || undefined,
          payment_method_id: methodId || undefined,
        },
      })
      return data.data
    },
  })

  const rows = data?.rows ?? []

  const { terme, setTerme, resultats, actif } = useRechercheLocale(rows, (r) => [
    r.supplier,
    r.goods_receipt,
    r.payment_method,
    r.notes,
    r.created_by,
  ])

  return (
    <Card>
      <CardHeader
        title="Historique des règlements"
        hint={
          data
            ? `${data.count} règlement(s) — ${money(data.total_paid)} DH versés sur la période`
            : undefined
        }
      />
      <CardBody className="space-y-4">
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <Field label="Du" htmlFor="hrf-from">
            <Input id="hrf-from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
          </Field>
          <Field label="Au" htmlFor="hrf-to">
            <Input id="hrf-to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
          </Field>
          <Field label="Fournisseur" htmlFor="hrf-supplier">
            <Select
              id="hrf-supplier"
              value={supplierId}
              onChange={(e) => setSupplierId(Number(e.target.value))}
            >
              <option value={0}>Tous les fournisseurs</option>
              {suppliers.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Recherche" htmlFor="hrf-recherche">
            <SearchInput
              id="hrf-recherche"
              value={terme}
              onChange={setTerme}
              placeholder="Fournisseur, réception, note…"
              className="w-full"
            />
          </Field>
          <Field label="Méthode" htmlFor="hrf-method">
            <Select id="hrf-method" value={methodId} onChange={(e) => setMethodId(Number(e.target.value))}>
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
              ? 'Aucun règlement ne correspond à cette recherche.'
              : 'Aucun règlement versé sur cette période.'}
          </p>
        ) : (
          <>
            <div className="overflow-x-auto rounded border border-line">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-2 font-medium">Date</th>
                    <th className="px-4 py-2 font-medium">Saisi le</th>
                    <th className="px-4 py-2 font-medium">Fournisseur</th>
                    <th className="px-4 py-2 font-medium">Réception</th>
                    <th className="px-4 py-2 font-medium">Méthode</th>
                    <th className="px-4 py-2 font-medium">Note</th>
                    <th className="px-4 py-2 font-medium">Enregistré par</th>
                    <th className="px-4 py-2 text-right font-medium">Montant</th>
                  </tr>
                </thead>
                <tbody>
                  {resultats.map((r) => (
                    <tr key={r.id} className="border-b border-line last:border-0">
                      <td className="px-4 py-2 text-muted">{formatDate(r.paid_at)}</td>
                      <td className="px-4 py-2 text-faint">{formatDateHeure(r.created_at)}</td>
                      <td className="px-4 py-2 text-ink">{r.supplier ?? '—'}</td>
                      <td className="mono px-4 py-2 text-muted">{r.goods_receipt ?? '—'}</td>
                      <td className="px-4 py-2 text-muted">{r.payment_method ?? '—'}</td>
                      <td className="px-4 py-2 text-muted">{r.notes ?? ''}</td>
                      <td className="px-4 py-2 text-muted">{r.created_by ?? '—'}</td>
                      <td className="tabular px-4 py-2 text-right font-medium text-bad">
                        −{money(r.amount)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Une liste coupée en silence se lirait comme un total : on le
                dit plutôt que de laisser croire à l'exhaustivité. */}
            {data?.truncated ? (
              <p className="text-xs text-faint">
                Les {rows.length} règlements les plus récents sont affichés. Le total ci-dessus
                couvre bien toute la période — resserrez les dates pour tout voir en détail.
              </p>
            ) : null}
          </>
        )}
      </CardBody>
    </Card>
  )
}
