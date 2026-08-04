import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, HandCoins, X } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { usePermission } from '@/hooks/usePermission'
import { cn, formatNumber } from '@/lib/utils'
import { fetchStockEntries, type StockEntryList } from '@/features/stock/api/stockEntriesApi'
import {
  useGoodsReceipts,
  usePaymentMethods,
  usePaySupplierCredit,
  usePurchaseOrders,
  useSupplierCredits,
} from '@/features/purchases/hooks'
import type { SupplierCreditRow } from '@/features/purchases/api/supplierCreditsApi'
import { fetchSupplier, fetchSupplierPayments, type Supplier, type SupplierPaymentHistory } from '../api/suppliersApi'
import { SupplierDetail } from '../components/SupplierDetail'

const PO_BADGES: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  draft: { label: 'Brouillon', tone: 'neutral' },
  pending_approval: { label: 'En attente', tone: 'warn' },
  sent: { label: 'Envoyé', tone: 'sky' },
  partially_received: { label: 'Partiellement reçu', tone: 'warn' },
  received: { label: 'Reçu', tone: 'ok' },
  cancelled: { label: 'Annulé', tone: 'bad' },
}

type Tab = 'credit' | 'orders' | 'receipts' | 'entries' | 'catalogue'

const TABS: { key: Tab; label: string }[] = [
  { key: 'credit', label: 'Crédit & règlements' },
  { key: 'orders', label: 'Bons de commande' },
  { key: 'receipts', label: 'Réceptions' },
  { key: 'entries', label: 'Entrées de stock' },
  { key: 'catalogue', label: 'Contacts & articles' },
]

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(value: string | null): string {
  return value ? new Date(value).toLocaleDateString('fr-FR') : '—'
}

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

export function SupplierDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const can = usePermission()

  const supplierId = id ? Number(id) : 0
  const [tab, setTab] = useState<Tab>('credit')

  const { data: supplier, isLoading } = useQuery<Supplier>({
    queryKey: ['supplier', supplierId],
    queryFn: () => fetchSupplier(supplierId),
    enabled: supplierId > 0,
  })

  const { data: credits } = useSupplierCredits({ supplier_id: supplierId })
  const { data: payments } = useQuery<SupplierPaymentHistory>({
    queryKey: ['supplier-payments', supplierId],
    queryFn: () => fetchSupplierPayments(supplierId),
    enabled: supplierId > 0 && can('receipt.view'),
  })
  const { data: orders } = usePurchaseOrders({ supplier_id: supplierId, per_page: 50 })
  const { data: receipts } = useGoodsReceipts({ supplier_id: supplierId, per_page: 50 })
  const { data: entries } = useQuery<StockEntryList>({
    queryKey: ['stock-entries', { supplier_id: supplierId }],
    queryFn: () => fetchStockEntries({ supplier_id: supplierId, per_page: 50 }),
    enabled: supplierId > 0,
  })
  const { data: methods = [] } = usePaymentMethods()

  // Règlement d'un crédit.
  const [paying, setPaying] = useState<SupplierCreditRow | null>(null)
  const [amount, setAmount] = useState('')
  const [methodId, setMethodId] = useState(0)
  const [paidAt, setPaidAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [notes, setNotes] = useState('')
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const payMutation = usePaySupplierCredit()

  function openPay(row: SupplierCreditRow) {
    setPaying(row)
    setAmount(String(row.remaining_amount))
    setMethodId(0)
    setPaidAt(new Date().toISOString().slice(0, 10))
    setNotes('')
    setSuccessMessage(null)
    payMutation.reset()
  }

  const amountValue = Number(amount)
  const isFullPayment = paying !== null && amountValue >= paying.remaining_amount - 0.005
  const invalidAmount =
    paying !== null &&
    (!Number.isFinite(amountValue) || amountValue <= 0 || amountValue > paying.remaining_amount + 0.005)

  async function submitPay() {
    if (!paying || invalidAmount) return
    try {
      const result = await payMutation.mutateAsync({
        receiptId: paying.id,
        input: {
          amount: amountValue,
          payment_method_id: methodId || null,
          paid_at: paidAt,
          notes: notes.trim() || null,
        },
      })
      setSuccessMessage(
        result.payment_status === 'paid'
          ? `${paying.number} entièrement réglé.`
          : `Règlement de ${formatMoney(amountValue)} DH enregistré — reste ${formatMoney(result.remaining_amount)} DH sur ${paying.number}.`,
      )
      setPaying(null)
    } catch {
      // Erreur affichée via payMutation.error.
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/fournisseurs')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!supplier) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/fournisseurs')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Fournisseur introuvable</h1>
      </div>
    )
  }

  const totalDue = credits?.total_due ?? 0
  const mutationError = apiErrorMessage(payMutation.error)

  return (
    <div className="space-y-6">
      {/* En-tête */}
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate('/fournisseurs')}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl font-semibold text-ink">
                <span className="mono text-muted">{supplier.code}</span> · {supplier.name}
              </h1>
              <Badge tone={supplier.is_active ? 'ok' : 'bad'}>{supplier.is_active ? 'Actif' : 'Inactif'}</Badge>
            </div>
            <p className="text-sm text-muted">
              {[supplier.contact_name, supplier.phone, supplier.email, supplier.city].filter(Boolean).join(' · ') || '—'}
            </p>
          </div>
        </div>
      </div>

      {successMessage ? (
        <p className="flex items-center justify-between rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          {successMessage}
          <button type="button" onClick={() => setSuccessMessage(null)} aria-label="Fermer">
            <X className="h-4 w-4" />
          </button>
        </p>
      ) : null}

      {/* Cartes de synthèse */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Crédit en cours</p>
            <p className={`text-2xl font-semibold ${totalDue > 0 ? 'text-bad' : 'text-ok'}`}>
              {formatMoney(totalDue)} DH
            </p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Total réglé</p>
            <p className="text-2xl font-semibold text-ink">{formatMoney(payments?.total_paid ?? 0)} DH</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Bons de commande</p>
            <p className="text-2xl font-semibold text-ink">{orders?.meta.total ?? 0}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Réceptions</p>
            <p className="text-2xl font-semibold text-ink">{receipts?.meta.total ?? 0}</p>
          </CardBody>
        </Card>
      </div>

      {/* Informations administratives */}
      <Card>
        <CardHeader title="Informations" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Adresse</p>
              <p className="text-sm text-ink">{[supplier.address, supplier.city].filter(Boolean).join(', ') || '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">ICE</p>
              <p className="mono text-sm text-ink">{supplier.ice ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">RC</p>
              <p className="mono text-sm text-ink">{supplier.rc ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Délai de paiement</p>
              <p className="text-sm text-ink">{supplier.payment_terms_days} jours</p>
            </div>
          </div>
          {supplier.notes ? (
            <div className="mt-4 rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Notes</p>
              <p className="whitespace-pre-wrap text-sm text-ink">{supplier.notes}</p>
            </div>
          ) : null}
        </CardBody>
      </Card>

      {/* Onglets */}
      <div className="flex flex-wrap gap-1 border-b border-line">
        {TABS.map((t) => (
          <button
            key={t.key}
            type="button"
            onClick={() => setTab(t.key)}
            className={cn(
              'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
              tab === t.key ? 'border-sky text-sky' : 'border-transparent text-muted hover:text-ink',
            )}
          >
            {t.label}
            {t.key === 'credit' && totalDue > 0 ? (
              <span className="ml-2 rounded-full bg-bad-bg px-2 py-0.5 text-xs text-bad">
                {formatMoney(totalDue)} DH
              </span>
            ) : null}
          </button>
        ))}
      </div>

      {/* Onglet Crédit & règlements */}
      {tab === 'credit' ? (
        <div className="space-y-6">
          {paying ? (
            <Card>
              <CardHeader
                title={`Régler ${paying.number}`}
                hint={`Crédit restant : ${formatMoney(paying.remaining_amount)} DH`}
              />
              <CardBody className="space-y-4">
                {mutationError ? (
                  <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{mutationError}</p>
                ) : null}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                  <Field label="Montant (DH)" htmlFor="sp-amount">
                    <div className="flex gap-2">
                      <Input
                        id="sp-amount"
                        type="number"
                        min={0}
                        step={0.01}
                        value={amount}
                        onChange={(e) => setAmount(e.target.value)}
                        className={cn('text-right', invalidAmount ? 'border-bad' : '')}
                      />
                      <Button variant="outline" onClick={() => setAmount(String(paying.remaining_amount))} title="Payer la totalité">
                        Tout
                      </Button>
                    </div>
                  </Field>
                  <Field label="Méthode de paiement" htmlFor="sp-method">
                    <Select id="sp-method" value={methodId} onChange={(e) => setMethodId(Number(e.target.value))}>
                      <option value={0}>— Choisir —</option>
                      {methods.map((m) => (
                        <option key={m.id} value={m.id}>{m.name}</option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="Date" htmlFor="sp-date">
                    <Input id="sp-date" type="date" value={paidAt} onChange={(e) => setPaidAt(e.target.value)} />
                  </Field>
                  <div>
                    <p className="mb-1.5 block text-sm font-medium text-ink">Après règlement</p>
                    <p
                      className={`flex h-10 items-center justify-end rounded border border-line bg-bg px-3 text-sm font-semibold ${
                        isFullPayment ? 'text-ok' : 'text-warn'
                      }`}
                    >
                      {isFullPayment
                        ? 'Payé intégralement'
                        : `Reste ${formatMoney(Math.max(0, paying.remaining_amount - (Number.isFinite(amountValue) ? amountValue : 0)))} DH`}
                    </p>
                  </div>
                </div>
                <Field label="Notes" htmlFor="sp-notes">
                  <Textarea id="sp-notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2} placeholder="N° de chèque, référence virement…" />
                </Field>
                <div className="flex gap-2">
                  <Button onClick={submitPay} disabled={payMutation.isPending || invalidAmount}>
                    <HandCoins className="h-4 w-4" />
                    {payMutation.isPending ? 'Enregistrement…' : isFullPayment ? 'Régler la totalité' : 'Paiement partiel'}
                  </Button>
                  <Button variant="ghost" onClick={() => setPaying(null)}>Annuler</Button>
                </div>
              </CardBody>
            </Card>
          ) : null}

          <Card>
            <CardHeader title="Réceptions à régler" hint={credits ? `${credits.receipts_count} en cours` : undefined} />
            <CardBody className="p-0">
              {(credits?.rows ?? []).length === 0 ? (
                <p className="p-5 text-center text-sm text-muted">Aucun crédit en cours pour ce fournisseur. ✓</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-line text-left text-muted">
                        <th className="px-5 py-3 font-medium">N° réception</th>
                        <th className="px-5 py-3 font-medium">Date</th>
                        <th className="px-5 py-3 font-medium">Statut</th>
                        <th className="px-5 py-3 text-right font-medium">Total HT</th>
                        <th className="px-5 py-3 text-right font-medium">Payé</th>
                        <th className="px-5 py-3 text-right font-medium">Reste</th>
                        <th className="px-5 py-3 text-right font-medium">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(credits?.rows ?? []).map((row) => (
                        <tr key={row.id} className="border-b border-line last:border-0">
                          <td className="px-5 py-3">
                            <Link to={`/goods-receipts/${row.id}`} className="mono font-medium text-sky hover:underline">
                              {row.number}
                            </Link>
                          </td>
                          <td className="px-5 py-3 text-muted">{formatDate(row.received_at)}</td>
                          <td className="px-5 py-3">
                            {row.payment_status === 'partial' ? (
                              <Badge tone="warn">Partiel</Badge>
                            ) : (
                              <Badge tone="bad">Non payé</Badge>
                            )}
                          </td>
                          <td className="tabular px-5 py-3 text-right text-muted">{formatMoney(row.total_amount)} DH</td>
                          <td className="tabular px-5 py-3 text-right text-ok">{formatMoney(row.amount_paid)} DH</td>
                          <td className="tabular px-5 py-3 text-right font-semibold text-bad">{formatMoney(row.remaining_amount)} DH</td>
                          <td className="px-5 py-3 text-right">
                            {can('receipt.pay') ? (
                              <Button variant="outline" size="sm" onClick={() => openPay(row)}>
                                <HandCoins className="h-4 w-4" />
                                Régler
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

          <Card>
            <CardHeader
              title="Historique des règlements"
              hint={payments ? `Total réglé : ${formatMoney(payments.total_paid)} DH` : undefined}
            />
            <CardBody className="p-0">
              {(payments?.rows ?? []).length === 0 ? (
                <p className="p-5 text-center text-sm text-muted">Aucun règlement enregistré.</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b border-line text-left text-muted">
                        <th className="px-5 py-3 font-medium">Date</th>
                        <th className="px-5 py-3 font-medium">Réception</th>
                        <th className="px-5 py-3 font-medium">Méthode</th>
                        <th className="px-5 py-3 font-medium">Notes</th>
                        <th className="px-5 py-3 font-medium">Par</th>
                        <th className="px-5 py-3 text-right font-medium">Montant</th>
                      </tr>
                    </thead>
                    <tbody>
                      {(payments?.rows ?? []).map((p) => (
                        <tr key={p.id} className="border-b border-line last:border-0">
                          <td className="px-5 py-3 text-muted">{formatDate(p.paid_at)}</td>
                          <td className="px-5 py-3">
                            {p.goods_receipt ? (
                              <Link to={`/goods-receipts/${p.goods_receipt.id}`} className="mono text-sky hover:underline">
                                {p.goods_receipt.number}
                              </Link>
                            ) : (
                              '—'
                            )}
                          </td>
                          <td className="px-5 py-3 text-muted">{p.payment_method ?? '—'}</td>
                          <td className="px-5 py-3 text-muted">{p.notes ?? ''}</td>
                          <td className="px-5 py-3 text-muted">{p.created_by ?? '—'}</td>
                          <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatMoney(p.amount)} DH</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardBody>
          </Card>
        </div>
      ) : null}

      {/* Onglet Bons de commande */}
      {tab === 'orders' ? (
        <Card>
          <CardHeader title="Bons de commande" hint={orders ? `${orders.meta.total} au total` : undefined} />
          <CardBody className="p-0">
            {(orders?.data ?? []).length === 0 ? (
              <p className="p-5 text-center text-sm text-muted">Aucun bon de commande pour ce fournisseur.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-line text-left text-muted">
                      <th className="px-5 py-3 font-medium">N°</th>
                      <th className="px-5 py-3 font-medium">Date</th>
                      <th className="px-5 py-3 font-medium">Lieu</th>
                      <th className="px-5 py-3 text-right font-medium">Unités</th>
                      <th className="px-5 py-3 font-medium">Avancement</th>
                      <th className="px-5 py-3 font-medium">Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(orders?.data ?? []).map((order) => {
                      const badge = PO_BADGES[order.status.code] ?? { label: order.status.name, tone: 'neutral' as const }
                      return (
                        <tr key={order.id} className="border-b border-line last:border-0">
                          <td className="px-5 py-3">
                            <Link to={`/purchase-orders/${order.id}`} className="mono font-medium text-sky hover:underline">
                              {order.number}
                            </Link>
                          </td>
                          <td className="px-5 py-3 text-muted">{formatDate(order.ordered_at)}</td>
                          <td className="px-5 py-3 text-muted">{order.warehouse.code ?? '—'}</td>
                          <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(order.total_quantity)}</td>
                          <td className="tabular px-5 py-3 text-muted">
                            {formatNumber(order.total_received)}/{formatNumber(order.total_quantity)}
                          </td>
                          <td className="px-5 py-3"><Badge tone={badge.tone}>{badge.label}</Badge></td>
                        </tr>
                      )
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </CardBody>
        </Card>
      ) : null}

      {/* Onglet Réceptions */}
      {tab === 'receipts' ? (
        <Card>
          <CardHeader title="Réceptions" hint={receipts ? `${receipts.meta.total} au total` : undefined} />
          <CardBody className="p-0">
            {(receipts?.data ?? []).length === 0 ? (
              <p className="p-5 text-center text-sm text-muted">Aucune réception pour ce fournisseur.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-line text-left text-muted">
                      <th className="px-5 py-3 font-medium">N°</th>
                      <th className="px-5 py-3 font-medium">Date</th>
                      <th className="px-5 py-3 font-medium">BC d'origine</th>
                      <th className="px-5 py-3 font-medium">Lieu</th>
                      <th className="px-5 py-3 text-right font-medium">Unités</th>
                      <th className="px-5 py-3 text-right font-medium">Montant HT</th>
                      <th className="px-5 py-3 font-medium">Règlement</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(receipts?.data ?? []).map((receipt) => (
                      <tr key={receipt.id} className="border-b border-line last:border-0">
                        <td className="px-5 py-3">
                          <Link to={`/goods-receipts/${receipt.id}`} className="mono font-medium text-sky hover:underline">
                            {receipt.number}
                          </Link>
                        </td>
                        <td className="px-5 py-3 text-muted">{formatDate(receipt.received_at)}</td>
                        <td className="px-5 py-3">
                          {receipt.purchase_order ? (
                            <Link to={`/purchase-orders/${receipt.purchase_order.id}`} className="mono text-sky hover:underline">
                              {receipt.purchase_order.number}
                            </Link>
                          ) : (
                            <span className="text-muted">Directe</span>
                          )}
                        </td>
                        <td className="px-5 py-3 text-muted">{receipt.warehouse.code ?? '—'}</td>
                        <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(receipt.total_quantity)}</td>
                        <td className="tabular px-5 py-3 text-right font-medium text-ink">
                          {formatMoney(receipt.total_amount)} DH
                        </td>
                        <td className="px-5 py-3">
                          {receipt.payment_status === 'paid' ? (
                            <Badge tone="ok">Payé</Badge>
                          ) : receipt.payment_status === 'partial' ? (
                            <Badge tone="warn">Partiel</Badge>
                          ) : (
                            <Badge tone="bad">Non payé</Badge>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardBody>
        </Card>
      ) : null}

      {/* Onglet Entrées de stock */}
      {tab === 'entries' ? (
        <Card>
          <CardHeader
            title="Entrées de stock"
            hint={entries ? `${entries.totals.lines_count} ligne(s) · ${formatMoney(entries.totals.total_value)} DH` : undefined}
          />
          <CardBody className="p-0">
            {(entries?.data ?? []).length === 0 ? (
              <p className="p-5 text-center text-sm text-muted">Aucune entrée de stock liée à ce fournisseur.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-line text-left text-muted">
                      <th className="px-5 py-3 font-medium">Date</th>
                      <th className="px-5 py-3 font-medium">Document</th>
                      <th className="px-5 py-3 font-medium">Lieu</th>
                      <th className="px-5 py-3 font-medium">Article</th>
                      <th className="px-5 py-3 text-right font-medium">Qté</th>
                      <th className="px-5 py-3 text-right font-medium">PU</th>
                      <th className="px-5 py-3 text-right font-medium">Valeur</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(entries?.data ?? []).map((row) => (
                      <tr key={row.id} className="border-b border-line last:border-0">
                        <td className="px-5 py-3 text-muted">{formatDate(row.date)}</td>
                        <td className="px-5 py-3">
                          {row.source?.type === 'goods_receipt' && row.source.id ? (
                            <Link to={`/goods-receipts/${row.source.id}`} className="mono text-sky hover:underline">
                              {row.source.label}
                            </Link>
                          ) : (
                            <span className="text-muted">{row.source?.label ?? '—'}</span>
                          )}
                        </td>
                        <td className="px-5 py-3 text-muted">{row.warehouse.code ?? '—'}</td>
                        <td className="px-5 py-3">
                          <span className="mono text-xs text-muted">{row.product.sku}</span>{' '}
                          <span className="text-ink">{row.product.name}</span>
                        </td>
                        <td className="tabular px-5 py-3 text-right font-medium text-ok">+{formatNumber(row.quantity)}</td>
                        <td className="tabular px-5 py-3 text-right text-muted">{formatMoney(row.unit_cost)}</td>
                        <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatMoney(row.line_value)} DH</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardBody>
        </Card>
      ) : null}

      {/* Onglet Contacts & articles référencés (composant existant) */}
      {tab === 'catalogue' ? (
        <SupplierDetail supplierId={supplier.id} name={supplier.name} onClose={() => setTab('credit')} />
      ) : null}
    </div>
  )
}
