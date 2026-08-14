import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { HandCoins, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
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
  id: number
  /** Vrai quand l'écriture reflète une facture ou un règlement. */
  from_document: boolean
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

/** Facture encore due, proposée au règlement. */
interface OpenInvoice {
  id: number
  reference: string
  total: number
  paid_amount: number
  remaining: number
  date: string | null
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

  /**
   * Montant affecté à chaque facture, par identifiant.
   *
   * Une facture absente de cet objet n'est pas réglée par ce versement. Vide,
   * l'encaissement retombe sur l'encours global — comportement historique.
   */
  const [parFacture, setParFacture] = useState<Record<number, string>>({})

  /** Écriture dont la suppression attend confirmation. */
  const [aConfirmer, setAConfirmer] = useState<LedgerEntry | null>(null)
  const [erreurSuppression, setErreurSuppression] = useState<string | null>(null)

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

  /** Factures encore dues du client sélectionné, de la plus ancienne. */
  const { data: facturesDues = [] } = useQuery<OpenInvoice[]>({
    queryKey: ['open-invoices', selected?.customer_id],
    queryFn: async () => {
      const { data } = await api.get<{ data: OpenInvoice[] }>(
        `/customers/${selected?.customer_id}/open-invoices`,
      )
      return data.data
    },
    enabled: selected !== null && canCollect,
  })

  const collect = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const ventilations = Object.entries(parFacture)
        .map(([id, montant]) => ({ sale_id: Number(id), amount: Number(montant) }))
        .filter((v) => Number.isFinite(v.amount) && v.amount > 0)

      await api.post('/payments', {
        customer_id: selected?.customer_id,
        amount: Number(amount),
        payment_method_id: methodId || null,
        received_at: receivedAt,
        note: note.trim() || null,
        // Sans ventilation, le versement tombe dans l'encours global : c'est
        // le comportement historique, conservé quand aucune facture n'est
        // cochée.
        ...(ventilations.length > 0 ? { allocations: ventilations } : {}),
      })
    },
    onSuccess: () => {
      setSuccessMessage(`Encaissement de ${money(Number(amount))} DH enregistré pour ${selected?.customer}.`)
      setSelected(null)
      setNote('')
      setParFacture({})
      qc.invalidateQueries({ queryKey: ['customers-aging'] })
      qc.invalidateQueries({ queryKey: ['customer-statement'] })
      qc.invalidateQueries({ queryKey: ['payments'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  const supprimerEcriture = useMutation({
    mutationFn: async (id: number) => {
      await ensureCsrfCookie()
      await api.delete(`/customer-ledger-entries/${id}`)
    },
    onSuccess: () => {
      setAConfirmer(null)
      qc.invalidateQueries({ queryKey: ['customer-statement'] })
      qc.invalidateQueries({ queryKey: ['customers-aging'] })
      qc.invalidateQueries({ queryKey: ['open-invoices'] })
    },
    onError: (e) => {
      setAConfirmer(null)
      setErreurSuppression(errorMessage(e, 'Suppression impossible.'))
    },
  })

  const totalDue = rows.reduce((sum, r) => sum + r.total_due, 0)
  const overdue = rows.reduce((sum, r) => sum + r.bucket_61_90 + r.bucket_over_90, 0)

  function openCollect(row: AgingRow) {
    // Repartir de zero : garder la ventilation d'un autre client affecterait
    // ses factures a celui-ci.
    setParFacture({})
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

  const totalReparti = Object.values(parFacture)
    .reduce((somme, v) => somme + (Number(v) || 0), 0)

  // Une répartition qui ne tombe pas juste ferait disparaître la différence :
  // le serveur la refuse, autant le dire avant l'envoi.
  const ecartVentilation =
    Object.keys(parFacture).length > 0 &&
    Math.abs(totalReparti - (Number(amount) || 0)) > 0.001

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

            {/* Règlement facture par facture. Sans sélection, le versement
                réduit l'encours global, comme auparavant. */}
            {facturesDues.length > 0 ? (
              <div className="rounded border border-line">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-2">
                  <span className="text-sm font-medium text-ink">
                    Affecter à des factures ({facturesDues.length} due{facturesDues.length > 1 ? 's' : ''})
                  </span>
                  <div className="flex gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => {
                        // Solder au plus juste : on remplit les factures les
                        // plus anciennes tant que le montant encaissé suffit.
                        let reste = Number(amount)
                        const suivant: Record<number, string> = {}
                        for (const f of facturesDues) {
                          if (reste <= 0) break
                          const part = Math.min(reste, f.remaining)
                          suivant[f.id] = part.toFixed(2)
                          reste = Math.round((reste - part) * 100) / 100
                        }
                        setParFacture(suivant)
                      }}
                    >
                      Répartir automatiquement
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setParFacture({})}>
                      Tout décocher
                    </Button>
                  </div>
                </div>
                <div className="max-h-64 overflow-y-auto">
                  {facturesDues.map((f) => {
                    const coche = parFacture[f.id] !== undefined
                    return (
                      <div key={f.id} className="flex flex-wrap items-center gap-3 border-b border-line px-4 py-2 last:border-0">
                        <input
                          type="checkbox"
                          checked={coche}
                          aria-label={`Régler ${f.reference}`}
                          onChange={(e) =>
                            setParFacture((prev) => {
                              const suivant = { ...prev }
                              if (e.target.checked) suivant[f.id] = f.remaining.toFixed(2)
                              else delete suivant[f.id]
                              return suivant
                            })
                          }
                        />
                        <span className="mono text-sm text-muted">{f.reference}</span>
                        <span className="text-xs text-faint">{f.date ?? ''}</span>
                        <span className="ml-auto text-sm text-muted">
                          reste {money(f.remaining)} DH
                        </span>
                        <Input
                          type="number"
                          min={0}
                          max={f.remaining}
                          step={0.01}
                          disabled={!coche}
                          value={parFacture[f.id] ?? ''}
                          onChange={(e) => setParFacture((prev) => ({ ...prev, [f.id]: e.target.value }))}
                          className="w-28 text-right"
                          aria-label={`Montant affecté à ${f.reference}`}
                        />
                      </div>
                    )
                  })}
                </div>
                <div className="flex flex-wrap items-center justify-between gap-2 border-t border-line px-4 py-2 text-sm">
                  <span className="text-muted">Réparti</span>
                  <span className={cn('tabular font-medium', ecartVentilation ? 'text-bad' : 'text-ink')}>
                    {money(totalReparti)} / {money(Number(amount) || 0)} DH
                  </span>
                </div>
              </div>
            ) : null}

            {ecartVentilation ? (
              <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                La répartition doit égaler le montant encaissé, sinon la différence disparaîtrait
                sans trace. Ajustez les montants ou le total.
              </p>
            ) : null}

            <div className="flex gap-2">
              <Button
                onClick={() => collect.mutate()}
                disabled={collect.isPending || invalidAmount || !canCollect || ecartVentilation}
              >
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
                        <th className="px-4 py-2" />
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
                            <td className="px-4 py-2 text-right">
                              {/* Une écriture issue d'une facture ou d'un
                                  règlement se défait par son document : le
                                  bouton n'apparaît pas, il serait refusé. */}
                              {!e.from_document && canCollect ? (
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  aria-label="Supprimer cette écriture"
                                  onClick={() => { setErreurSuppression(null); setAConfirmer(e) }}
                                >
                                  <Trash2 className="h-4 w-4 text-bad" />
                                </Button>
                              ) : null}
                            </td>
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

      {erreurSuppression ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreurSuppression}</p>
      ) : null}

      <ConfirmDialog
        open={aConfirmer !== null}
        title="Supprimer cette écriture"
        message={
          <>
            Supprimer l'écriture de <strong>{aConfirmer ? money(aConfirmer.amount) : ''} DH</strong>
            {aConfirmer?.note ? <> « {aConfirmer.note} »</> : null} ? L'encours du client et les
            soldes suivants du relevé seront recalculés. Cette opération est définitive.
          </>
        }
        confirmLabel="Supprimer"
        danger
        isPending={supprimerEcriture.isPending}
        onConfirm={() => aConfirmer && supprimerEcriture.mutate(aConfirmer.id)}
        onCancel={() => setAConfirmer(null)}
      />
    </div>
  )
}
