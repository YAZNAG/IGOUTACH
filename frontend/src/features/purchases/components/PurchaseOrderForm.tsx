import { ArrowLeft } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { useSupplierOptions, useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { formatNumber } from '@/lib/utils'
import type { CreatePurchaseOrderInput, ProductOption } from '../api/purchaseOrdersApi'
import { BelowThresholdSuggestions } from './BelowThresholdSuggestions'
import { ProductAutocomplete } from './ProductAutocomplete'
import { PurchaseOrderLineForm, type PurchaseOrderLineItem } from './PurchaseOrderLineForm'

export interface PurchaseOrderFormInitialValues {
  supplierId: number
  warehouseId: number
  expectedAt: string
  notes: string
  lines: PurchaseOrderLineItem[]
}

interface PurchaseOrderFormProps {
  title: string
  subtitle?: string
  initialValues?: PurchaseOrderFormInitialValues
  submitLabel: string
  isPending: boolean
  errorMessage?: string | null
  onSubmit: (input: CreatePurchaseOrderInput) => void
}

/**
 * Formulaire partagé création / édition d'un bon de commande (brouillon).
 */
export function PurchaseOrderForm({
  title,
  subtitle,
  initialValues,
  submitLabel,
  isPending,
  errorMessage,
  onSubmit,
}: PurchaseOrderFormProps) {
  const navigate = useNavigate()
  const can = usePermission()

  const [supplierId, setSupplierId] = useState(initialValues?.supplierId ?? 0)
  const [warehouseId, setWarehouseId] = useState(initialValues?.warehouseId ?? 0)
  const [expectedAt, setExpectedAt] = useState(initialValues?.expectedAt ?? '')
  const [notes, setNotes] = useState(initialValues?.notes ?? '')
  const [lines, setLines] = useState<PurchaseOrderLineItem[]>(initialValues?.lines ?? [])
  const [searchValue, setSearchValue] = useState('')
  const [suggestionsOpen, setSuggestionsOpen] = useState(false)

  const { data: suppliers = [] } = useSupplierOptions()
  const { data: warehouses = [] } = useWarehouseOptions()

  const totalQuantity = lines.reduce((sum, l) => sum + l.quantity, 0)

  const handleAddProduct = (product: ProductOption & { suggested_quantity?: number }) => {
    const existing = lines.find((l) => l.product_id === product.id)
    if (existing) {
      const newQuantity = existing.quantity + (product.suggested_quantity || 1)
      setLines((prev) =>
        prev.map((l) => (l.product_id === product.id ? { ...l, quantity: newQuantity } : l)),
      )
    } else {
      setLines((prev) => [
        ...prev,
        {
          product_id: product.id,
          sku: product.sku,
          name: product.name,
          current_stock: product.current_stock,
          min_stock_alert: product.min_stock_alert,
          quantity: product.suggested_quantity || 1,
          cost_price: product.cost_price,
        },
      ])
    }
    setSearchValue('')
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    if (!supplierId || !warehouseId || lines.length === 0) {
      return
    }

    onSubmit({
      supplier_id: supplierId,
      warehouse_id: warehouseId,
      expected_at: expectedAt || null,
      notes: notes || null,
      lines: lines.map((l) => ({
        product_id: l.product_id,
        quantity: l.quantity,
      })),
    })
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pb-24">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate(-1)}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-xl font-semibold text-ink">{title}</h1>
          {subtitle ? <p className="text-sm text-muted">{subtitle}</p> : null}
        </div>
      </div>

      {/* Main Info Card */}
      <Card>
        <CardHeader title="Informations générales" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <Field label="Fournisseur *" htmlFor="supplier">
              <Select
                id="supplier"
                value={supplierId || ''}
                onChange={(e) => setSupplierId(Number(e.target.value))}
                required
              >
                <option value="" disabled>
                  Choisir…
                </option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.code} · {s.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Lieu de réception *" htmlFor="warehouse">
              <Select
                id="warehouse"
                value={warehouseId || ''}
                onChange={(e) => setWarehouseId(Number(e.target.value))}
                required
              >
                <option value="" disabled>
                  Choisir…
                </option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.code} · {w.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Date prévue" htmlFor="expected-at">
              <Input
                id="expected-at"
                type="date"
                value={expectedAt}
                onChange={(e) => setExpectedAt(e.target.value)}
              />
            </Field>
          </div>

          <Field label="Notes / Commentaires" htmlFor="notes">
            <Textarea
              id="notes"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Remarques, instructions spéciales…"
              rows={3}
            />
          </Field>

          {errorMessage ? (
            <div className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
              {errorMessage}
            </div>
          ) : null}
        </CardBody>
      </Card>

      {/* Search + Add Product */}
      <div>
        <label className="block text-sm font-medium text-ink mb-2">Ajouter un article</label>
        <ProductAutocomplete
          value={searchValue}
          onChange={setSearchValue}
          onSelect={handleAddProduct}
          exclude={lines.map((l) => l.product_id)}
          warehouseId={warehouseId}
        />
      </div>

      {/* Below Threshold Suggestions */}
      {warehouseId > 0 ? (
        <BelowThresholdSuggestions
          warehouseId={warehouseId}
          onAddProduct={handleAddProduct}
          exclude={lines.map((l) => l.product_id)}
          isOpen={suggestionsOpen}
          onToggle={() => setSuggestionsOpen(!suggestionsOpen)}
        />
      ) : null}

      {/* Lines */}
      {lines.length > 0 ? (
        <div className="space-y-2">
          <label className="block text-sm font-medium text-ink">Lignes de commande</label>
          <div className="space-y-2">
            {lines.map((line, index) => (
              <PurchaseOrderLineForm
                key={`${line.product_id}-${index}`}
                line={line}
                index={index}
                onQuantityChange={(i, qty) =>
                  setLines((prev) => prev.map((l, j) => (j === i ? { ...l, quantity: qty } : l)))
                }
                onRemove={(i) => setLines((prev) => prev.filter((_, j) => j !== i))}
                showCostPrice={can('product.view_cost_price')}
              />
            ))}
          </div>
        </div>
      ) : null}

      {/* Bottom Footer Bar */}
      <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-line-2 bg-card px-6 py-4 shadow-lg">
        <div className="mx-auto max-w-6xl">
          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <p className="text-sm font-medium text-ink">
                {lines.length} {lines.length === 1 ? 'ligne' : 'lignes'}
              </p>
              <p className="text-sm text-muted">
                Total: {formatNumber(totalQuantity)} {totalQuantity === 1 ? 'unité' : 'unités'}
              </p>
            </div>
            <div className="flex gap-2">
              <Button type="button" variant="ghost" onClick={() => navigate(-1)}>
                Annuler
              </Button>
              <Button
                type="submit"
                disabled={isPending || !supplierId || !warehouseId || lines.length === 0}
              >
                {isPending ? 'Enregistrement…' : submitLabel}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </form>
  )
}
