import { ArrowLeft } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { usePermission } from '@/hooks/usePermission'
import { cn, formatNumber } from '@/lib/utils'
import { usePurchaseOrder, useReceivePurchaseOrder } from '../hooks'
import type { PaymentStatus, PurchaseOrderLine, ReceivePurchaseOrderLineInput } from '../api/purchaseOrdersApi'

interface ReceiveLineState {
  /** Case cochée = réception du reliquat complet. */
  checked: boolean
  /** Quantité saisie (chaîne pour tolérer le champ vide pendant la frappe). */
  quantity: string
  /** Prix unitaire HT saisi. */
  unitPrice: string
  /** Motif de sur-réception (obligatoire si reçu > reliquat). */
  reason: string
}

function toNumber(value: string): number {
  const n = Number(value)
  return Number.isFinite(n) ? n : 0
}

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

function initialLineState(line: PurchaseOrderLine): ReceiveLineState {
  const receivable = line.remaining > 0
  return {
    checked: receivable,
    quantity: receivable ? String(line.remaining) : '0',
    unitPrice: line.last_price_known != null ? String(Number(line.last_price_known)) : '',
    reason: '',
  }
}

export function ReceivePurchaseOrderPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const can = usePermission()

  const orderId = id ? Number(id) : 0
  const { data: order, isLoading } = usePurchaseOrder(orderId)
  const receiveMutation = useReceivePurchaseOrder()

  const [receivedAt, setReceivedAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [invoiceNumber, setInvoiceNumber] = useState('')
  const [notes, setNotes] = useState('')
  const [lineStates, setLineStates] = useState<Record<number, ReceiveLineState>>({})
  const [paymentStatus, setPaymentStatus] = useState<PaymentStatus>('unpaid')
  const [amountPaidInput, setAmountPaidInput] = useState('')

  const headerCheckboxRef = useRef<HTMLInputElement>(null)

  const lines = useMemo(() => order?.lines ?? [], [order])
  const receivableLines = useMemo(() => lines.filter((l) => l.remaining > 0), [lines])

  // Initialisation : dès que le BC est chargé, toutes les lignes à reliquat > 0
  // sont cochées avec leur reliquat complet (cas le plus courant).
  useEffect(() => {
    if (!order) return
    setLineStates((prev) => {
      if (Object.keys(prev).length > 0) return prev
      const next: Record<number, ReceiveLineState> = {}
      for (const line of order.lines) {
        next[line.id] = initialLineState(line)
      }
      return next
    })
  }, [order])

  const getState = (line: PurchaseOrderLine): ReceiveLineState =>
    lineStates[line.id] ?? initialLineState(line)

  // État de la case globale : cochée si toutes les lignes recevables sont cochées,
  // décochée si tout est à zéro, indéterminée sinon.
  const allChecked =
    receivableLines.length > 0 && receivableLines.every((l) => getState(l).checked)
  const allEmpty = receivableLines.every(
    (l) => !getState(l).checked && toNumber(getState(l).quantity) === 0,
  )

  useEffect(() => {
    if (headerCheckboxRef.current) {
      headerCheckboxRef.current.indeterminate = !allChecked && !allEmpty
    }
  }, [allChecked, allEmpty])

  const handleToggleAll = (checked: boolean) => {
    setLineStates((prev) => {
      const next = { ...prev }
      for (const line of receivableLines) {
        const state = next[line.id] ?? initialLineState(line)
        next[line.id] = {
          ...state,
          checked,
          quantity: checked ? String(line.remaining) : '0',
        }
      }
      return next
    })
  }

  const handleToggleLine = (line: PurchaseOrderLine, checked: boolean) => {
    setLineStates((prev) => ({
      ...prev,
      [line.id]: {
        ...(prev[line.id] ?? initialLineState(line)),
        checked,
        quantity: checked ? String(line.remaining) : '0',
      },
    }))
  }

  const handleQuantityChange = (line: PurchaseOrderLine, value: string) => {
    const qty = Math.max(0, toNumber(value))
    setLineStates((prev) => ({
      ...prev,
      [line.id]: {
        ...(prev[line.id] ?? initialLineState(line)),
        quantity: value,
        // Une saisie manuelle différente du reliquat décoche la case de la ligne seule.
        checked: line.remaining > 0 && qty === line.remaining,
      },
    }))
  }

  const handlePriceChange = (line: PurchaseOrderLine, value: string) => {
    setLineStates((prev) => ({
      ...prev,
      [line.id]: { ...(prev[line.id] ?? initialLineState(line)), unitPrice: value },
    }))
  }

  const handleReasonChange = (line: PurchaseOrderLine, value: string) => {
    setLineStates((prev) => ({
      ...prev,
      [line.id]: { ...(prev[line.id] ?? initialLineState(line)), reason: value },
    }))
  }

  // Lignes retenues + validation front.
  const retainedLines = lines.filter((l) => toNumber(getState(l).quantity) > 0)
  const totalQuantity = retainedLines.reduce((sum, l) => sum + toNumber(getState(l).quantity), 0)
  const totalAmount = retainedLines.reduce(
    (sum, l) => sum + toNumber(getState(l).quantity) * toNumber(getState(l).unitPrice),
    0,
  )

  const missingPrice = retainedLines.some((l) => toNumber(getState(l).unitPrice) <= 0)
  const missingReason = retainedLines.some((l) => {
    const state = getState(l)
    return toNumber(state.quantity) > l.remaining && state.reason.trim() === ''
  })

  // Paiement : montant payé selon le statut, reste = crédit fournisseur.
  const amountPaid =
    paymentStatus === 'paid' ? totalAmount : paymentStatus === 'partial' ? toNumber(amountPaidInput) : 0
  const remainingCredit = Math.max(0, totalAmount - amountPaid)
  const invalidPartial =
    paymentStatus === 'partial' && (amountPaid <= 0 || amountPaid >= totalAmount)

  let validationMessage: string | null = null
  if (retainedLines.length === 0) {
    validationMessage = 'Saisissez au moins une quantité reçue.'
  } else if (missingPrice) {
    validationMessage = 'Chaque ligne reçue doit avoir un prix unitaire supérieur à 0.'
  } else if (missingReason) {
    validationMessage = 'Un motif est obligatoire pour toute sur-réception.'
  } else if (!receivedAt) {
    validationMessage = 'La date de réception est obligatoire.'
  } else if (invalidPartial) {
    validationMessage = `Paiement partiel : le montant payé doit être entre 0 et ${formatMoney(totalAmount)} DH (exclus).`
  }

  const canSubmit = validationMessage === null && !receiveMutation.isPending

  const handleSubmit = async () => {
    if (!canSubmit || !order) return
    const payloadLines: ReceivePurchaseOrderLineInput[] = retainedLines.map((l) => {
      const state = getState(l)
      const quantity = toNumber(state.quantity)
      return {
        purchase_order_line_id: l.id,
        quantity,
        unit_price: toNumber(state.unitPrice),
        ...(quantity > l.remaining ? { over_receipt_reason: state.reason.trim() } : {}),
      }
    })

    try {
      const receipt = await receiveMutation.mutateAsync({
        id: orderId,
        input: {
          received_at: receivedAt,
          invoice_number: invoiceNumber.trim() || null,
          notes: notes.trim() || null,
          payment_status: paymentStatus,
          amount_paid: paymentStatus === 'partial' ? amountPaid : undefined,
          lines: payloadLines,
        },
      })
      navigate('/goods-receipts', { state: { createdReceipt: receipt.number } })
    } catch (error) {
      console.error('Failed to receive purchase order:', error)
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!order) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Bon de commande introuvable</h1>
      </div>
    )
  }

  if (!order.can_receive || !can('receipt.create')) {
    return (
      <div className="space-y-6">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate(`/purchase-orders/${orderId}`)}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <h1 className="mono text-xl font-semibold text-ink">Réception — {order.number}</h1>
        </div>
        <div className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
          Ce bon de commande ne peut pas être réceptionné, ou vous n'avez pas la permission requise.
        </div>
      </div>
    )
  }

  const mutationError = apiErrorMessage(receiveMutation.error)

  return (
    <div className="space-y-6 pb-28">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate(`/purchase-orders/${orderId}`)}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <div className="flex items-center gap-2">
            <h1 className="mono text-xl font-semibold text-ink">Réception — {order.number}</h1>
            <Badge tone="sky">{order.status.name}</Badge>
          </div>
          <p className="text-sm text-muted">Saisie des quantités reçues et des prix d'achat.</p>
        </div>
      </div>

      {/* En-tête de réception */}
      <Card>
        <CardHeader title="Informations de réception" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="mb-1.5 block text-sm font-medium text-ink">Fournisseur</p>
              <p className="flex h-10 items-center rounded border border-line bg-bg px-3 text-sm text-ink">
                {order.supplier.code ? `${order.supplier.code} · ` : ''}
                {order.supplier.name ?? '—'}
              </p>
            </div>
            <div>
              <p className="mb-1.5 block text-sm font-medium text-ink">Lieu de réception</p>
              <p className="flex h-10 items-center rounded border border-line bg-bg px-3 text-sm text-ink">
                {order.warehouse.code ? `${order.warehouse.code} · ` : ''}
                {order.warehouse.name ?? '—'}
              </p>
            </div>
            <Field label="Date de réception *" htmlFor="received-at">
              <Input
                id="received-at"
                type="date"
                value={receivedAt}
                onChange={(e) => setReceivedAt(e.target.value)}
                required
              />
            </Field>
            <Field label="N° facture fournisseur" htmlFor="invoice-number">
              <Input
                id="invoice-number"
                value={invoiceNumber}
                onChange={(e) => setInvoiceNumber(e.target.value)}
                placeholder="FA-…"
              />
            </Field>
          </div>

          <Field label="Notes" htmlFor="receive-notes">
            <Textarea
              id="receive-notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Remarques sur la livraison…"
              rows={2}
            />
          </Field>

          {mutationError ? (
            <div className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
              {mutationError}
            </div>
          ) : null}
        </CardBody>
      </Card>

      {/* Tableau des lignes */}
      <Card>
        <CardHeader title="Lignes à réceptionner" />
        <CardBody className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-4 py-3">
                    <label className="flex items-center gap-2 font-medium">
                      <input
                        ref={headerCheckboxRef}
                        type="checkbox"
                        className="h-4 w-4 accent-sky"
                        checked={allChecked}
                        onChange={(e) => handleToggleAll(e.target.checked)}
                        aria-label="Tout réceptionner"
                      />
                      <span className="whitespace-nowrap">Tout réceptionner</span>
                    </label>
                  </th>
                  <th className="px-4 py-3 font-medium">Article</th>
                  <th className="px-4 py-3 text-right font-medium">Commandé</th>
                  <th className="px-4 py-3 text-right font-medium">Déjà reçu</th>
                  <th className="px-4 py-3 text-right font-medium">Reliquat</th>
                  <th className="px-4 py-3 text-right font-medium">Quantité reçue</th>
                  <th className="px-4 py-3 text-right font-medium">Écart</th>
                  <th className="px-4 py-3 text-right font-medium">Prix unitaire (DH)</th>
                  <th className="px-4 py-3 text-right font-medium">Total ligne</th>
                </tr>
              </thead>
              <tbody>
                {lines.map((line) => {
                  const state = getState(line)
                  const qty = toNumber(state.quantity)
                  const price = toNumber(state.unitPrice)
                  const gap = qty - line.remaining
                  const overReceipt = qty > line.remaining
                  const priceMissing = qty > 0 && price <= 0

                  return (
                    <tr key={line.id} className="border-b border-line align-top last:border-0">
                      <td className="px-4 py-3">
                        <input
                          type="checkbox"
                          className="mt-1 h-4 w-4 accent-sky"
                          checked={state.checked}
                          disabled={line.remaining <= 0}
                          onChange={(e) => handleToggleLine(line, e.target.checked)}
                          aria-label={`Réceptionner ${line.product.name ?? ''}`}
                        />
                      </td>
                      <td className="px-4 py-3">
                        <p className="mono text-xs text-muted">{line.product.sku ?? '—'}</p>
                        <p className="text-ink">{line.product.name ?? '—'}</p>
                      </td>
                      <td className="tabular px-4 py-3 text-right font-medium text-ink">
                        {formatNumber(line.quantity)}
                      </td>
                      <td className="tabular px-4 py-3 text-right text-muted">
                        {formatNumber(line.received_quantity)}
                      </td>
                      <td
                        className={`tabular px-4 py-3 text-right font-medium ${
                          line.remaining > 0 ? 'text-warn' : 'text-ok'
                        }`}
                      >
                        {formatNumber(line.remaining)}
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min={0}
                          value={state.quantity}
                          disabled={line.remaining <= 0 && qty === 0}
                          onChange={(e) => handleQuantityChange(line, e.target.value)}
                          className="ml-auto w-24 text-right"
                          aria-label={`Quantité reçue pour ${line.product.name ?? ''}`}
                        />
                      </td>
                      <td className="px-4 py-3 text-right">
                        {qty === 0 || gap === 0 ? (
                          <span className="text-muted">—</span>
                        ) : (
                          <span className={`tabular font-medium ${gap > 0 ? 'text-bad' : 'text-warn'}`}>
                            {gap > 0 ? '+' : ''}
                            {formatNumber(gap)}
                          </span>
                        )}
                        {overReceipt ? (
                          <Input
                            value={state.reason}
                            onChange={(e) => handleReasonChange(line, e.target.value)}
                            placeholder="Motif de sur-réception *"
                            className={cn(
                              'mt-2 h-8 w-44 text-xs',
                              state.reason.trim() === '' ? 'border-bad' : '',
                            )}
                            aria-label={`Motif de sur-réception pour ${line.product.name ?? ''}`}
                          />
                        ) : null}
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min={0}
                          step={0.01}
                          value={state.unitPrice}
                          onChange={(e) => handlePriceChange(line, e.target.value)}
                          className={cn('ml-auto w-28 text-right', priceMissing ? 'border-bad' : '')}
                          aria-label={`Prix unitaire pour ${line.product.name ?? ''}`}
                        />
                      </td>
                      <td className="tabular px-4 py-3 text-right font-medium text-ink">
                        {qty > 0 && price > 0 ? `${formatMoney(qty * price)} DH` : '—'}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        </CardBody>
      </Card>

      {/* Paiement fournisseur */}
      <Card>
        <CardHeader title="Paiement fournisseur" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Field label="Règlement" htmlFor="payment-status">
              <Select
                id="payment-status"
                value={paymentStatus}
                onChange={(e) => {
                  setPaymentStatus(e.target.value as PaymentStatus)
                  setAmountPaidInput('')
                }}
              >
                <option value="unpaid">Non payé (tout en crédit)</option>
                <option value="partial">Paiement partiel</option>
                <option value="paid">Payé intégralement</option>
              </Select>
            </Field>

            {paymentStatus === 'partial' ? (
              <Field label="Montant payé (DH)" htmlFor="amount-paid">
                <Input
                  id="amount-paid"
                  type="number"
                  min={0}
                  step={0.01}
                  value={amountPaidInput}
                  onChange={(e) => setAmountPaidInput(e.target.value)}
                  className={cn('text-right', invalidPartial && amountPaidInput !== '' ? 'border-bad' : '')}
                  placeholder="0.00"
                />
              </Field>
            ) : null}

            <div>
              <p className="mb-1.5 block text-sm font-medium text-ink">Montant payé</p>
              <p className="flex h-10 items-center justify-end rounded border border-line bg-bg px-3 text-sm font-medium text-ok">
                {formatMoney(amountPaid)} DH
              </p>
            </div>

            <div>
              <p className="mb-1.5 block text-sm font-medium text-ink">Reste — crédit fournisseur</p>
              <p
                className={`flex h-10 items-center justify-end rounded border border-line bg-bg px-3 text-sm font-semibold ${
                  remainingCredit > 0 ? 'text-bad' : 'text-ok'
                }`}
              >
                {formatMoney(remainingCredit)} DH
              </p>
            </div>
          </div>

          {remainingCredit > 0 && retainedLines.length > 0 ? (
            <p className="mt-3 text-xs text-muted">
              Le reste de {formatMoney(remainingCredit)} DH sera enregistré comme crédit chez{' '}
              <span className="font-medium text-ink">{order.supplier.name ?? 'le fournisseur'}</span>, à régler
              ultérieurement.
            </p>
          ) : null}
        </CardBody>
      </Card>

      {/* Bandeau bas fixe */}
      <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-line-2 bg-card px-6 py-4 shadow-lg">
        <div className="mx-auto max-w-6xl">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="space-y-0.5">
              <p className="text-sm font-medium text-ink">
                {retainedLines.length} {retainedLines.length === 1 ? 'ligne retenue' : 'lignes retenues'}
                {' · '}
                {formatNumber(totalQuantity)} {totalQuantity === 1 ? 'unité' : 'unités'}
              </p>
              <p className="text-sm text-muted">
                Montant total HT :{' '}
                <span className="font-semibold text-ink">{formatMoney(totalAmount)} DH</span>
                {' · '}Payé : <span className="font-medium text-ok">{formatMoney(amountPaid)} DH</span>
                {' · '}Crédit :{' '}
                <span className={`font-medium ${remainingCredit > 0 ? 'text-bad' : 'text-ok'}`}>
                  {formatMoney(remainingCredit)} DH
                </span>
              </p>
              {validationMessage ? <p className="text-xs text-bad">{validationMessage}</p> : null}
            </div>
            <div className="flex gap-2">
              <Button
                type="button"
                variant="ghost"
                onClick={() => navigate(`/purchase-orders/${orderId}`)}
              >
                Annuler
              </Button>
              <Button type="button" onClick={handleSubmit} disabled={!canSubmit}>
                {receiveMutation.isPending ? 'Réception en cours…' : 'Valider la réception'}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
