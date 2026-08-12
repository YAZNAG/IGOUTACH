import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { chequeDraftComplet, chequeDraftVide } from '@/features/cheques/components/ChequeDraftFields'
import {
  CustomerChequePanel,
  type CustomerChequeValue,
} from '@/features/cheques/components/CustomerChequePanel'
import { useCreateCheque } from '@/features/cheques/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface PaymentRow {
  id: number
  reference: string
  customer: string | null
  method: string | null
  amount: number
  cheque_status: string | null
  cheque_reference: string | null
  received_at: string
}

interface AgingRow {
  customer_id: number | null
  customer: string | null
  bucket_0_30: number
  bucket_31_60: number
  bucket_61_90: number
  bucket_over_90: number
  total_due: number
}

interface CustomerOption {
  id: number
  code: string
  name: string
  balance: number
}

interface MethodOption {
  id: number
  name: string
  code: string
}

const KEY = ['payments'] as const

const CHEQUE_LABELS: Record<string, string> = {
  received: 'Reçu',
  deposited: 'Déposé',
  cleared: 'Encaissé',
  bounced: 'Impayé',
}

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

type Tab = 'payments' | 'aging'

/**
 * Règlements : encaissements clients (avec cycle des chèques) et
 * balance âgée des créances.
 */
export function PaymentsPage() {
  const [tab, setTab] = useState<Tab>('payments')

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Règlements</h1>
        <p className="text-sm text-muted">Encaissements clients, cycle des chèques et balance âgée.</p>
      </div>

      <div className="flex gap-1 overflow-x-auto border-b border-line">
        {([
          { key: 'payments', label: 'Encaissements' },
          { key: 'aging', label: 'Balance âgée' },
        ] as { key: Tab; label: string }[]).map((t) => (
          <button
            key={t.key}
            type="button"
            onClick={() => setTab(t.key)}
            className={cn(
              'px-4 py-2 text-sm font-medium transition-colors',
              tab === t.key ? 'border-b-2 border-sky text-ink' : 'text-muted hover:text-ink',
            )}
          >
            {t.label}
          </button>
        ))}
      </div>

      {tab === 'payments' ? <PaymentsTab /> : <AgingTab />}
    </div>
  )
}

function PaymentsTab() {
  const can = usePermission()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)

  const { data, isLoading } = useQuery<Paginated<PaymentRow>>({
    queryKey: [...KEY, page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<PaymentRow>>('/payments', { params: { page } })
      return r
    },
  })

  const chequeUpdate = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await ensureCsrfCookie()
      await api.patch(`/payments/${id}/cheque`, { status })
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  const payments = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-4">
      {can('payment.create') && !creating ? (
        <Button size="sm" onClick={() => setCreating(true)}>
          <Plus className="h-4 w-4" />
          Nouvel encaissement
        </Button>
      ) : null}

      {creating ? <CreatePaymentPanel onClose={() => setCreating(false)} /> : null}

      <Card>
        <CardHeader title="Encaissements" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Client</th>
                  <th className="px-5 py-3 font-medium">Mode</th>
                  <th className="px-5 py-3 text-right font-medium">Montant (DH)</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Chèque</th>
                </tr>
              </thead>
              <tbody>
                {payments.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-8 text-center text-muted">Aucun encaissement.</td></tr>
                ) : (
                  payments.map((p) => (
                    <tr key={p.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{p.reference}</td>
                      <td className="px-5 py-3 text-ink">{p.customer}</td>
                      <td className="px-5 py-3 text-muted">{p.method ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ok">{formatNumber(p.amount)}</td>
                      <td className="px-5 py-3 text-muted">{p.received_at}</td>
                      <td className="px-5 py-3">
                        {p.cheque_status !== null ? (
                          <div className="flex items-center gap-2">
                            <Badge tone={p.cheque_status === 'bounced' ? 'bad' : p.cheque_status === 'cleared' ? 'ok' : 'warn'}>
                              {CHEQUE_LABELS[p.cheque_status] ?? p.cheque_status}
                            </Badge>
                            {can('payment.create') && !['cleared', 'bounced'].includes(p.cheque_status) ? (
                              <Select
                                value=""
                                onChange={(e) => {
                                  if (e.target.value) chequeUpdate.mutate({ id: p.id, status: e.target.value })
                                }}
                                className="w-32"
                                aria-label="Faire évoluer le chèque"
                              >
                                <option value="">Évoluer…</option>
                                {p.cheque_status === 'received' ? <option value="deposited">Déposé</option> : null}
                                <option value="cleared">Encaissé</option>
                                <option value="bounced">Impayé</option>
                              </Select>
                            ) : null}
                          </div>
                        ) : '—'}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}

function CreatePaymentPanel({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient()
  const [search, setSearch] = useState('')
  const [customerId, setCustomerId] = useState(0)
  const [amount, setAmount] = useState('')
  const [methodId, setMethodId] = useState(0)
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10))
  const [cheque, setCheque] = useState<CustomerChequeValue>({
    draft: chequeDraftVide(),
    autreSignataire: false,
  })
  const creerCheque = useCreateCheque()

  const { data: customers = [] } = useQuery<CustomerOption[]>({
    queryKey: ['payment-customer-search', search],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<CustomerOption>>('/customers', { params: { q: search || undefined, per_page: 20 } })
      return r.data
    },
  })

  const { data: methods = [] } = useQuery<MethodOption[]>({
    queryKey: ['payment-method-options'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: MethodOption[] }>('/payment-methods')
      return r.data
    },
    staleTime: 5 * 60_000,
  })

  const selected = customers.find((c) => c.id === customerId)
  const method = methods.find((m) => m.id === methodId)
  // Le code fait foi : « inclut ch » dans le libellé attrapait d'autres modes.
  const isCheque = (method?.code ?? '').toUpperCase() === 'CHEQUE'

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()

      // Le chèque est créé avant le règlement : il doit exister pour être
      // référencé, et il reste au portefeuille même si l'encaissement échoue.
      let chequeId: number | null = null

      if (isCheque && chequeDraftComplet(cheque.draft)) {
        const cree = await creerCheque.mutateAsync({
          number: cheque.draft.number.trim(),
          cheque_date: cheque.draft.cheque_date,
          amount: Number(amount),
          bank: cheque.draft.bank.trim() || null,
          direction: 'in',
          origin: cheque.autreSignataire ? 'third_party' : 'customer',
          drawer_name: cheque.autreSignataire ? cheque.draft.drawer_name.trim() : null,
          customer_id: customerId,
          image: cheque.draft.image,
        })
        chequeId = cree.id
      }

      await api.post('/payments', {
        customer_id: customerId,
        amount: Number(amount),
        payment_method_id: methodId || null,
        cheque_reference: isCheque ? cheque.draft.number.trim() || null : null,
        cheque_id: chequeId,
        received_at: date,
      })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['customers'] })
      onClose()
    },
  })

  return (
    <Card>
      <CardHeader title="Nouvel encaissement" hint={selected ? `Encours actuel : ${formatNumber(selected.balance)} DH` : undefined} />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-4">
          <Field label="Client" htmlFor="pay-customer">
            <div className="space-y-1">
              <Input
                id="pay-customer"
                placeholder="Rechercher…"
                value={search}
                onChange={(e) => { setSearch(e.target.value); setCustomerId(0) }}
              />
              {customerId === 0 && customers.length > 0 ? (
                <ul className="max-h-32 overflow-auto rounded border border-line">
                  {customers.map((c) => (
                    <li key={c.id}>
                      <button
                        type="button"
                        onClick={() => { setCustomerId(c.id); setSearch(`${c.code} · ${c.name}`) }}
                        className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                      >
                        <span className="mono text-muted">{c.code}</span>
                        <span className="text-ink">{c.name}</span>
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </Field>
          <Field label="Montant (DH)" htmlFor="pay-amount">
            <Input id="pay-amount" type="number" min={0.01} step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
          </Field>
          <Field label="Mode de paiement" htmlFor="pay-method">
            <Select id="pay-method" value={methodId || ''} onChange={(e) => setMethodId(Number(e.target.value))}>
              <option value="">— Choisir —</option>
              {methods.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
            </Select>
          </Field>
          <Field label="Date" htmlFor="pay-date">
            <Input id="pay-date" type="date" value={date} onChange={(e) => setDate(e.target.value)} />
          </Field>
        </div>

        {isCheque ? (
          <CustomerChequePanel
            value={cheque}
            onChange={setCheque}
            customerName={selected?.name}
          />
        ) : null}

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Enregistrement impossible.')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button
            onClick={() => create.mutate()}
            disabled={
              create.isPending ||
              !customerId ||
              Number(amount) <= 0 ||
              (isCheque && !chequeDraftComplet(cheque.draft)) ||
              (isCheque && cheque.autreSignataire && cheque.draft.drawer_name.trim() === '')
            }
          >
            {create.isPending ? 'Enregistrement…' : "Enregistrer l'encaissement"}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

function AgingTab() {
  const { data: rows = [], isLoading } = useQuery<AgingRow[]>({
    queryKey: ['customers-aging'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: AgingRow[] }>('/customers-aging')
      return r.data
    },
  })

  const totals = rows.reduce(
    (acc, r) => ({
      b0: acc.b0 + r.bucket_0_30,
      b31: acc.b31 + r.bucket_31_60,
      b61: acc.b61 + r.bucket_61_90,
      over: acc.over + r.bucket_over_90,
      total: acc.total + r.total_due,
    }),
    { b0: 0, b31: 0, b61: 0, over: 0, total: 0 },
  )

  return (
    <Card>
      <CardHeader title="Balance âgée des créances" hint={`Total dû : ${formatNumber(totals.total)} DH`} />
      <CardBody className="p-0">
        {isLoading ? (
          <p className="p-5 text-sm text-muted">Chargement…</p>
        ) : rows.length === 0 ? (
          <p className="p-5 text-sm text-muted">Aucune facture impayée.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Client</th>
                <th className="px-5 py-3 text-right font-medium">0-30 j</th>
                <th className="px-5 py-3 text-right font-medium">31-60 j</th>
                <th className="px-5 py-3 text-right font-medium">61-90 j</th>
                <th className="px-5 py-3 text-right font-medium">+90 j</th>
                <th className="px-5 py-3 text-right font-medium">Total dû (DH)</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.customer_id ?? 0} className="border-b border-line last:border-0">
                  <td className="px-5 py-3 text-ink">{r.customer}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(r.bucket_0_30)}</td>
                  <td className="tabular px-5 py-3 text-right text-warn">{formatNumber(r.bucket_31_60)}</td>
                  <td className="tabular px-5 py-3 text-right text-warn">{formatNumber(r.bucket_61_90)}</td>
                  <td className="tabular px-5 py-3 text-right font-medium text-bad">{formatNumber(r.bucket_over_90)}</td>
                  <td className="tabular px-5 py-3 text-right font-semibold text-ink">{formatNumber(r.total_due)}</td>
                </tr>
              ))}
              <tr className="bg-surface-2 font-semibold">
                <td className="px-5 py-3 text-ink">Total</td>
                <td className="tabular px-5 py-3 text-right">{formatNumber(totals.b0)}</td>
                <td className="tabular px-5 py-3 text-right">{formatNumber(totals.b31)}</td>
                <td className="tabular px-5 py-3 text-right">{formatNumber(totals.b61)}</td>
                <td className="tabular px-5 py-3 text-right text-bad">{formatNumber(totals.over)}</td>
                <td className="tabular px-5 py-3 text-right">{formatNumber(totals.total)}</td>
              </tr>
            </tbody>
          </table>
        )}
      </CardBody>
    </Card>
  )
}
