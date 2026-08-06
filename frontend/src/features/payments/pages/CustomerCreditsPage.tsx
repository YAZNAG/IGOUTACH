import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { HandCoins, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn } from '@/lib/utils'

interface AgingRow {
  customer_id: number
  customer: string
  bucket_0_30: number
  bucket_31_60: number
  bucket_61_90: number
  bucket_over_90: number
  total_due: number
}

interface LedgerEntry {
  date: string | null
  type: string
  amount: number
  balance_after: number
  note: string | null
}

interface Statement {
  customer: { id: number; code: string; name: string }
  balance: number
  credit_limit: number
  is_blocked: boolean
  entries: LedgerEntry[]
}

const LEDGER_LABELS: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  invoice: { label: 'Vente à crédit', tone: 'warn' },
  sale: { label: 'Vente à crédit', tone: 'warn' },
  payment: { label: 'Règlement', tone: 'ok' },
  refund: { label: 'Remboursement', tone: 'sky' },
  adjustment: { label: 'Ajustement', tone: 'neutral' },
  cancel: { label: 'Annulation', tone: 'sky' },
}

function money(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Crédits clients : ce que chaque client doit, par ancienneté, avec son
 * relevé de compte et l'encaissement direct. Symétrique des crédits
 * fournisseurs. La portée suit les règles de visibilité : chacun ne voit
 * que les créances de ses ventes et de ses clients.
 */
export function CustomerCreditsPage() {
  const can = usePermission()
  const canCollect = can('payment.create')
  const qc = useQueryClient()

  const [selected, setSelected] = useState<AgingRow | null>(null)
  const [amount, setAmount] = useState('')
  const [methodId, setMethodId] = useState(0)
  const [receivedAt, setReceivedAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [note, setNote] = useState('')
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const { data: rows = [], isLoading } = useQuery<AgingRow[]>({
    queryKey: ['customers-aging'],
    queryFn: async () => {
      const { data } = await api.get<{ data: AgingRow[] }>('/customers-aging')
      return data.data
    },
  })

  const { data: statement } = useQuery<Statement>({
    queryKey: ['customer-statement', selected?.customer_id],
    queryFn: async () => {
      const { data } = await api.get<{ data: Statement }>(`/customers/${selected?.customer_id}/statement`)
      return data.data
    },
    enabled: selected !== null,
  })

  const { data: methods = [] } = useQuery<{ id: number; name: string }[]>({
    queryKey: ['payment-method-options'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { id: number; name: string }[] }>('/payment-methods')
      return data.data
    },
    enabled: selected !== null && canCollect,
    staleTime: 5 * 60_000,
  })

  const collect = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post('/payments', {
        customer_id: selected?.customer_id,
        amount: Number(amount),
        payment_method_id: methodId || null,
        received_at: receivedAt,
        note: note.trim() || null,
      })
    },
    onSuccess: () => {
      setSuccessMessage(`Encaissement de ${money(Number(amount))} DH enregistré pour ${selected?.customer}.`)
      setSelected(null)
      setNote('')
      qc.invalidateQueries({ queryKey: ['customers-aging'] })
      qc.invalidateQueries({ queryKey: ['customer-statement'] })
      qc.invalidateQueries({ queryKey: ['payments'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  const totalDue = rows.reduce((sum, r) => sum + r.total_due, 0)
  const overdue = rows.reduce((sum, r) => sum + r.bucket_61_90 + r.bucket_over_90, 0)

  function openCollect(row: AgingRow) {
    setSelected(row)
    setAmount(String(row.total_due))
    setMethodId(0)
    setReceivedAt(new Date().toISOString().slice(0, 10))
    setNote('')
    setSuccessMessage(null)
    collect.reset()
  }

  const amountValue = Number(amount)
  const invalidAmount = selected !== null && (!Number.isFinite(amountValue) || amountValue <= 0)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Crédits clients</h1>
        <p className="text-sm text-muted">
          Créances par ancienneté, relevé de compte et encaissement.
        </p>
      </div>

      {successMessage ? (
        <p className="flex items-center justify-between rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          {successMessage}
          <button type="button" onClick={() => setSuccessMessage(null)} aria-label="Fermer">
            <X className="h-4 w-4" />
          </button>
        </p>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Total dû</p>
            <p className="text-2xl font-semibold text-bad">{money(totalDue)} DH</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Dont plus de 60 jours</p>
            <p className={`text-2xl font-semibold ${overdue > 0 ? 'text-warn' : 'text-ok'}`}>{money(overdue)} DH</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Clients concernés</p>
            <p className="text-2xl font-semibold text-ink">{rows.length}</p>
          </CardBody>
        </Card>
      </div>

      {/* Encaissement */}
      {selected ? (
        <Card>
          <CardHeader
            title={`Encaisser — ${selected.customer}`}
            hint={`Encours : ${money(selected.total_due)} DH`}
          />
          <CardBody className="space-y-4">
            {collect.isError ? (
              <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(collect.error, 'Encaissement impossible.')}
              </p>
            ) : null}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <Field label="Montant (DH)" htmlFor="cc-amount">
                <div className="flex gap-2">
                  <Input
                    id="cc-amount"
                    type="number"
                    min={0}
                    step={0.01}
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    className={cn('text-right', invalidAmount ? 'border-bad' : '')}
                  />
                  <Button variant="outline" onClick={() => setAmount(String(selected.total_due))} title="Solder">
                    Tout
                  </Button>
                </div>
              </Field>
              <Field label="Méthode de paiement" htmlFor="cc-method">
                <Select id="cc-method" value={methodId} onChange={(e) => setMethodId(Number(e.target.value))}>
                  <option value={0}>— Choisir —</option>
                  {methods.map((m) => (
                    <option key={m.id} value={m.id}>{m.name}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Date" htmlFor="cc-date">
                <Input id="cc-date" type="date" value={receivedAt} onChange={(e) => setReceivedAt(e.target.value)} />
              </Field>
              <Field label="Note" htmlFor="cc-note">
                <Input id="cc-note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="N° de chèque…" />
              </Field>
            </div>

            <div className="flex gap-2">
              <Button onClick={() => collect.mutate()} disabled={collect.isPending || invalidAmount || !canCollect}>
                <HandCoins className="h-4 w-4" />
                {collect.isPending ? 'Enregistrement…' : 'Encaisser'}
              </Button>
              <Button variant="ghost" onClick={() => setSelected(null)}>Fermer</Button>
            </div>

            {/* Relevé du client */}
            {statement && statement.entries.length > 0 ? (
              <div>
                <p className="mb-2 text-sm font-medium text-ink">
                  Relevé de compte — plafond {money(statement.credit_limit)} DH
                  {statement.is_blocked ? <Badge tone="bad" className="ml-2">Bloqué</Badge> : null}
                </p>
                <div className="max-h-72 overflow-auto rounded border border-line">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-line text-left text-muted">
                        <th className="px-4 py-2 font-medium">Date</th>
                        <th className="px-4 py-2 font-medium">Opération</th>
                        <th className="px-4 py-2 font-medium">Note</th>
                        <th className="px-4 py-2 text-right font-medium">Montant</th>
                        <th className="px-4 py-2 text-right font-medium">Encours après</th>
                      </tr>
                    </thead>
                    <tbody>
                      {statement.entries.map((e, i) => {
                        const label = LEDGER_LABELS[e.type] ?? { label: e.type, tone: 'neutral' as const }
                        return (
                          <tr key={i} className="border-b border-line last:border-0">
                            <td className="px-4 py-2 text-muted">{e.date ?? '—'}</td>
                            <td className="px-4 py-2"><Badge tone={label.tone}>{label.label}</Badge></td>
                            <td className="px-4 py-2 text-muted">{e.note ?? ''}</td>
                            <td className={`tabular px-4 py-2 text-right font-medium ${e.amount > 0 ? 'text-warn' : 'text-ok'}`}>
                              {e.amount > 0 ? '+' : ''}{money(e.amount)}
                            </td>
                            <td className="tabular px-4 py-2 text-right text-ink">{money(e.balance_after)}</td>
                          </tr>
                        )
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            ) : null}
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Créances par ancienneté" hint={rows.length > 0 ? `${rows.length} client(s)` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : rows.length === 0 ? (
            <p className="p-8 text-center text-sm text-muted">Aucune créance en cours — tout est réglé. ✓</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Client</th>
                    <th className="px-5 py-3 text-right font-medium">0 – 30 j</th>
                    <th className="px-5 py-3 text-right font-medium">31 – 60 j</th>
                    <th className="px-5 py-3 text-right font-medium">61 – 90 j</th>
                    <th className="px-5 py-3 text-right font-medium">+ 90 j</th>
                    <th className="px-5 py-3 text-right font-medium">Total dû</th>
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((r) => (
                    <tr key={r.customer_id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        <Link to={`/clients/${r.customer_id}`} className="text-ink hover:text-sky hover:underline">
                          {r.customer}
                        </Link>
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{money(r.bucket_0_30)}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{money(r.bucket_31_60)}</td>
                      <td className="tabular px-5 py-3 text-right text-warn">{money(r.bucket_61_90)}</td>
                      <td className="tabular px-5 py-3 text-right text-bad">{money(r.bucket_over_90)}</td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-ink">{money(r.total_due)} DH</td>
                      <td className="px-5 py-3 text-right">
                        {canCollect ? (
                          <Button variant="outline" size="sm" onClick={() => openCollect(r)}>
                            <HandCoins className="h-4 w-4" />
                            Encaisser
                          </Button>
                        ) : null}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
