import { HandCoins, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { useSupplierOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { cn } from '@/lib/utils'
import {
  usePaymentMethods,
  usePaySupplierCredit,
  useReceiptPayments,
  useSupplierCredits,
} from '../hooks'
import type { SupplierCreditRow } from '../api/supplierCreditsApi'

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

export function SupplierCreditsPage() {
  const can = usePermission()
  const canPay = can('receipt.pay')

  const [supplierId, setSupplierId] = useState(0)
  const [search, setSearch] = useState('')

  const { data: credits, isLoading } = useSupplierCredits({
    supplier_id: supplierId || undefined,
    search: search || undefined,
  })
  const { data: suppliers = [] } = useSupplierOptions()
  const { data: methods = [] } = usePaymentMethods()

  // Règlement en cours de saisie.
  const [paying, setPaying] = useState<SupplierCreditRow | null>(null)
  const [amount, setAmount] = useState('')
  const [methodId, setMethodId] = useState(0)
  const [paidAt, setPaidAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [notes, setNotes] = useState('')
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const payMutation = usePaySupplierCredit()
  const { data: history = [] } = useReceiptPayments(paying?.id ?? null)

  const rows = credits?.rows ?? []

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
    paying !== null && (!Number.isFinite(amountValue) || amountValue <= 0 || amountValue > paying.remaining_amount + 0.005)

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
          : `Règlement de ${formatMoney(amountValue)} DH enregistré sur ${paying.number} — reste ${formatMoney(result.remaining_amount)} DH.`,
      )
      setPaying(null)
    } catch {
      // L'erreur est affichée via payMutation.error.
    }
  }

  const mutationError = apiErrorMessage(payMutation.error)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Crédits fournisseurs</h1>
        <p className="text-sm text-muted">
          Réceptions non réglées : déclarez un paiement total ou partiel avec sa méthode de paiement.
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

      {/* Synthèse */}
      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Total des crédits</p>
            <p className="text-2xl font-semibold text-bad">{formatMoney(credits?.total_due ?? 0)} DH</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Réceptions concernées</p>
            <p className="text-2xl font-semibold text-ink">{credits?.receipts_count ?? 0}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Fournisseurs</p>
            <p className="text-2xl font-semibold text-ink">{credits?.suppliers.length ?? 0}</p>
          </CardBody>
        </Card>
      </div>

      {/* Panneau de règlement */}
      {paying ? (
        <Card>
          <CardHeader
            title={`Régler ${paying.number} — ${paying.supplier.name ?? ''}`}
            hint={`Crédit restant : ${formatMoney(paying.remaining_amount)} DH`}
          />
          <CardBody className="space-y-4">
            {mutationError ? (
              <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{mutationError}</p>
            ) : null}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <Field label="Montant (DH)" htmlFor="pay-amount">
                <div className="flex gap-2">
                  <Input
                    id="pay-amount"
                    type="number"
                    min={0}
                    step={0.01}
                    value={amount}
                    onChange={(e) => setAmount(e.target.value)}
                    className={cn('text-right', invalidAmount ? 'border-bad' : '')}
                  />
                  <Button
                    variant="outline"
                    size="md"
                    onClick={() => setAmount(String(paying.remaining_amount))}
                    title="Payer la totalité"
                  >
                    Tout
                  </Button>
                </div>
              </Field>
              <Field label="Méthode de paiement" htmlFor="pay-method">
                <Select id="pay-method" value={methodId} onChange={(e) => setMethodId(Number(e.target.value))}>
                  <option value={0}>— Choisir —</option>
                  {methods.map((m) => (
                    <option key={m.id} value={m.id}>{m.name}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Date du règlement" htmlFor="pay-date">
                <Input id="pay-date" type="date" value={paidAt} onChange={(e) => setPaidAt(e.target.value)} />
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

            <Field label="Notes" htmlFor="pay-notes">
              <Textarea id="pay-notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2} placeholder="Référence du virement, n° de chèque…" />
            </Field>

            {history.length > 0 ? (
              <div>
                <p className="mb-2 text-sm font-medium text-ink">Règlements précédents</p>
                <div className="rounded border border-line">
                  <table className="w-full text-sm">
                    <tbody>
                      {history.map((p) => (
                        <tr key={p.id} className="border-b border-line last:border-0">
                          <td className="px-3 py-2 text-muted">{new Date(p.paid_at).toLocaleDateString('fr-FR')}</td>
                          <td className="px-3 py-2 text-muted">{p.payment_method ?? '—'}</td>
                          <td className="px-3 py-2 text-muted">{p.notes ?? ''}</td>
                          <td className="tabular px-3 py-2 text-right font-medium text-ink">{formatMoney(p.amount)} DH</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            ) : null}

            <div className="flex gap-2">
              <Button onClick={submitPay} disabled={payMutation.isPending || invalidAmount}>
                <HandCoins className="h-4 w-4" />
                {payMutation.isPending
                  ? 'Enregistrement…'
                  : isFullPayment
                    ? 'Régler la totalité'
                    : 'Enregistrer le paiement partiel'}
              </Button>
              <Button variant="ghost" onClick={() => setPaying(null)}>Annuler</Button>
            </div>
          </CardBody>
        </Card>
      ) : null}

      {/* Filtres */}
      <Card>
        <CardHeader title="Filtres" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Field label="Fournisseur" htmlFor="credit-supplier">
              <Select id="credit-supplier" value={supplierId} onChange={(e) => setSupplierId(Number(e.target.value))}>
                <option value={0}>Tous</option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>{s.code} · {s.name}</option>
                ))}
              </Select>
            </Field>
            <Field label="N° de réception" htmlFor="credit-search">
              <Input
                id="credit-search"
                placeholder="BR-2026-…"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </Field>
          </div>
        </CardBody>
      </Card>

      {/* Tableau des crédits */}
      <Card>
        <CardHeader
          title="Réceptions à régler"
          hint={credits ? `${credits.receipts_count} réception(s) · ${formatMoney(credits.total_due)} DH` : undefined}
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : rows.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted">
              Aucun crédit fournisseur en cours — tout est réglé. ✓
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">N° réception</th>
                    <th className="px-5 py-3 font-medium">Date</th>
                    <th className="px-5 py-3 font-medium">Fournisseur</th>
                    <th className="px-5 py-3 font-medium">Facture</th>
                    <th className="px-5 py-3 font-medium">Statut</th>
                    <th className="px-5 py-3 text-right font-medium">Total HT</th>
                    <th className="px-5 py-3 text-right font-medium">Payé</th>
                    <th className="px-5 py-3 text-right font-medium">Reste</th>
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        <Link to={`/goods-receipts/${row.id}`} className="mono font-medium text-sky hover:underline">
                          {row.number}
                        </Link>
                      </td>
                      <td className="px-5 py-3 text-muted">
                        {row.received_at ? new Date(row.received_at).toLocaleDateString('fr-FR') : '—'}
                      </td>
                      <td className="px-5 py-3 text-ink">
                        {row.supplier.code ? `${row.supplier.code} · ` : ''}
                        {row.supplier.name ?? '—'}
                      </td>
                      <td className="px-5 py-3 text-muted">{row.invoice_number ?? '—'}</td>
                      <td className="px-5 py-3">
                        {row.payment_status === 'partial' ? (
                          <Badge tone="warn">Partiel</Badge>
                        ) : (
                          <Badge tone="bad">Non payé</Badge>
                        )}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{formatMoney(row.total_amount)} DH</td>
                      <td className="tabular px-5 py-3 text-right text-ok">{formatMoney(row.amount_paid)} DH</td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-bad">
                        {formatMoney(row.remaining_amount)} DH
                      </td>
                      <td className="px-5 py-3 text-right">
                        {canPay ? (
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

      {/* Synthèse par fournisseur */}
      {(credits?.suppliers.length ?? 0) > 1 ? (
        <Card>
          <CardHeader title="Crédit par fournisseur" />
          <CardBody className="p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Fournisseur</th>
                  <th className="px-5 py-3 text-right font-medium">Réceptions</th>
                  <th className="px-5 py-3 text-right font-medium">Crédit total</th>
                </tr>
              </thead>
              <tbody>
                {credits?.suppliers.map((s) => (
                  <tr key={s.supplier.id ?? 0} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink">
                      {s.supplier.code ? `${s.supplier.code} · ` : ''}
                      {s.supplier.name ?? '—'}
                    </td>
                    <td className="tabular px-5 py-3 text-right text-muted">{s.receipts_count}</td>
                    <td className="tabular px-5 py-3 text-right font-semibold text-bad">
                      {formatMoney(s.total_due)} DH
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardBody>
        </Card>
      ) : null}
    </div>
  )
}
