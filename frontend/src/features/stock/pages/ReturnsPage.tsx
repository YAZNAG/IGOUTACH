import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Trash2, Undo2, X } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useSupplierOptions, useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import { fetchStockEntries, type StockEntryList } from '../api/stockEntriesApi'
import { fetchStockExits, type StockExitList } from '../api/stockExitsApi'

interface ProductOption {
  id: number
  sku: string
  name: string
  current_stock?: number
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
  /** Retour client uniquement : revendable ou défectueux. */
  condition: 'resellable' | 'defective'
}

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Retours clients (marchandise rendue, remise en stock) et retours
 * fournisseurs (marchandise renvoyée, sortie de stock).
 *
 * Le serveur enregistre toutes les lignes dans une seule transaction :
 * en cas d'erreur, aucune ligne n'est appliquée.
 */
export function ReturnsPage({ direction }: { direction: 'customer' | 'supplier' }) {
  const isCustomer = direction === 'customer'
  const can = usePermission()
  const qc = useQueryClient()

  const canDo = can(isCustomer ? 'stock.entry' : 'stock.issue')

  const { data: warehouses = [] } = useWarehouseOptions()
  const { data: suppliers = [] } = useSupplierOptions()

  const [warehouseId, setWarehouseId] = useState(0)
  const [supplierId, setSupplierId] = useState(0)
  const [occurredAt, setOccurredAt] = useState(() => new Date().toISOString().slice(0, 10))
  const [reason, setReason] = useState('')
  const [note, setNote] = useState('')
  const [lines, setLines] = useState<DraftLine[]>([])
  const [search, setSearch] = useState('')
  const [successMessage, setSuccessMessage] = useState<string | null>(null)

  const { data: products = [] } = useQuery<ProductOption[]>({
    queryKey: ['return-product-search', search, warehouseId],
    queryFn: async () => {
      const { data } = await api.get<{ data: ProductOption[] }>('/products', {
        params: { search, warehouse_id: warehouseId || undefined, per_page: 20 },
      })
      return data.data
    },
    enabled: search.trim().length >= 2,
  })

  // Historique : les mouvements de retour déjà enregistrés.
  const { data: history } = useQuery<StockEntryList | StockExitList>({
    queryKey: ['returns-history', direction, warehouseId],
    queryFn: () =>
      isCustomer
        ? fetchStockEntries({ type: 'return_in', warehouse_id: warehouseId || undefined, per_page: 20 })
        : fetchStockExits({ type: 'return_out', warehouse_id: warehouseId || undefined, per_page: 20 }),
  })

  const submit = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const payload = {
        warehouse_id: warehouseId,
        occurred_at: occurredAt,
        note: note.trim() || undefined,
        lines: lines.map((l) => ({
          product_id: l.product_id,
          quantity: l.quantity,
          ...(isCustomer ? { condition: l.condition } : {}),
        })),
        ...(isCustomer ? {} : { supplier_id: supplierId || undefined, reason: reason.trim() }),
      }
      const url = isCustomer ? '/stock/return-multi' : '/stock/supplier-return'
      const { data } = await api.post<{ message: string; lines_count: number }>(url, payload)
      return data
    },
    onSuccess: (result) => {
      setSuccessMessage(result.message)
      setLines([])
      setNote('')
      setReason('')
      qc.invalidateQueries({ queryKey: ['returns-history'] })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['stock-entries'] })
      qc.invalidateQueries({ queryKey: ['stock-exits'] })
    },
  })

  function addProduct(p: ProductOption) {
    setLines((prev) => {
      const existing = prev.find((l) => l.product_id === p.id)
      if (existing) {
        return prev.map((l) => (l.product_id === p.id ? { ...l, quantity: l.quantity + 1 } : l))
      }
      return [...prev, { product_id: p.id, sku: p.sku, name: p.name, quantity: 1, condition: 'resellable' }]
    })
    setSearch('')
  }

  const totalQuantity = lines.reduce((sum, l) => sum + l.quantity, 0)
  const canSubmit =
    canDo &&
    warehouseId > 0 &&
    lines.length > 0 &&
    lines.every((l) => l.quantity > 0) &&
    (isCustomer || reason.trim() !== '') &&
    !submit.isPending

  if (!canDo) {
    return (
      <div className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
        Vous n'avez pas la permission d'enregistrer ce type de retour.
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">
          {isCustomer ? 'Retours clients' : 'Retours fournisseurs'}
        </h1>
        <p className="text-sm text-muted">
          {isCustomer
            ? 'Marchandise rendue par un client : elle revient en stock si elle est revendable.'
            : 'Marchandise renvoyée au fournisseur : elle sort du stock du lieu.'}
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

      {submit.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          Aucune ligne enregistrée — {errorMessage(submit.error, 'Enregistrement impossible.')}
        </p>
      ) : null}

      <Card>
        <CardHeader title="Nouveau retour" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Field label="Lieu *" htmlFor="ret-warehouse">
              <Select
                id="ret-warehouse"
                value={warehouseId || ''}
                onChange={(e) => setWarehouseId(Number(e.target.value))}
              >
                <option value="" disabled>Choisir…</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
                ))}
              </Select>
            </Field>

            <Field label="Date du retour" htmlFor="ret-date">
              <Input id="ret-date" type="date" value={occurredAt} onChange={(e) => setOccurredAt(e.target.value)} />
            </Field>

            {!isCustomer ? (
              <>
                <Field label="Fournisseur" htmlFor="ret-supplier">
                  <Select id="ret-supplier" value={supplierId || ''} onChange={(e) => setSupplierId(Number(e.target.value))}>
                    <option value="">— Non précisé —</option>
                    {suppliers.map((s) => (
                      <option key={s.id} value={s.id}>{s.code} · {s.name}</option>
                    ))}
                  </Select>
                </Field>
                <Field label="Motif du renvoi *" htmlFor="ret-reason">
                  <Input
                    id="ret-reason"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder="Défectueux, non conforme…"
                  />
                </Field>
              </>
            ) : (
              <div className="sm:col-span-2">
                <Field label="Note" htmlFor="ret-note">
                  <Input id="ret-note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Client, référence de vente…" />
                </Field>
              </div>
            )}
          </div>

          {!isCustomer ? (
            <Field label="Note" htmlFor="ret-note-sup">
              <Input id="ret-note-sup" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Numéro de bon, remarque…" />
            </Field>
          ) : null}

          <div className="space-y-3 rounded-lg border border-line p-3">
            <Input
              placeholder={warehouseId === 0 ? "Choisissez d'abord un lieu…" : 'Rechercher un article…'}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              disabled={warehouseId === 0}
            />

            {products.length > 0 && search.trim().length >= 2 ? (
              <ul className="max-h-48 overflow-auto rounded border border-line">
                {products
                  .filter((p) => !lines.some((l) => l.product_id === p.id))
                  .map((p) => (
                    <li key={p.id}>
                      <button
                        type="button"
                        onClick={() => addProduct(p)}
                        className="flex w-full items-center justify-between gap-2 border-b border-line px-3 py-2 text-left text-sm last:border-0 hover:bg-bg"
                      >
                        <span>
                          <span className="mono text-xs text-muted">{p.sku}</span>{' '}
                          <span className="text-ink">{p.name}</span>
                        </span>
                        {p.current_stock !== undefined ? (
                          <span className="tabular text-xs text-muted">stock {formatNumber(p.current_stock)}</span>
                        ) : null}
                      </button>
                    </li>
                  ))}
              </ul>
            ) : null}

            {lines.length === 0 ? (
              <p className="text-sm text-muted">Aucun article — recherchez puis cliquez pour ajouter.</p>
            ) : (
              lines.map((l, i) => (
                <div key={l.product_id} className="flex flex-wrap items-center gap-2 rounded border border-line bg-card p-2">
                  <span className="mono w-24 text-sm text-muted">{l.sku}</span>
                  <span className="min-w-0 flex-1 truncate text-sm text-ink">{l.name}</span>

                  {isCustomer ? (
                    <Select
                      value={l.condition}
                      onChange={(e) =>
                        setLines((prev) =>
                          prev.map((x, j) =>
                            j === i ? { ...x, condition: e.target.value as DraftLine['condition'] } : x,
                          ),
                        )
                      }
                      className="w-40"
                      aria-label="État de l'article"
                    >
                      <option value="resellable">Revendable</option>
                      <option value="defective">Défectueux</option>
                    </Select>
                  ) : null}

                  <Input
                    type="number"
                    min={1}
                    value={l.quantity}
                    onChange={(e) =>
                      setLines((prev) =>
                        prev.map((x, j) => (j === i ? { ...x, quantity: Math.max(1, Number(e.target.value)) } : x)),
                      )
                    }
                    className="w-24 text-right"
                    aria-label="Quantité"
                  />

                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-bad hover:bg-bad-bg"
                    onClick={() => setLines((prev) => prev.filter((_, j) => j !== i))}
                    aria-label={`Retirer ${l.name}`}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              ))
            )}
          </div>

          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted">
              {lines.length} ligne{lines.length > 1 ? 's' : ''} · {formatNumber(totalQuantity)} unité
              {totalQuantity > 1 ? 's' : ''}
              {isCustomer && lines.some((l) => l.condition === 'defective') ? (
                <span className="ml-2 text-warn">
                  Les articles défectueux entrent puis ressortent aussitôt (sortie SAV).
                </span>
              ) : null}
            </p>
            <Button onClick={() => submit.mutate()} disabled={!canSubmit}>
              <Undo2 className="h-4 w-4" />
              {submit.isPending ? 'Enregistrement…' : 'Enregistrer le retour'}
            </Button>
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Retours récents"
          hint={history ? `${history.totals.lines_count} mouvement(s)` : undefined}
        />
        <CardBody className="p-0">
          {(history?.data ?? []).length === 0 ? (
            <p className="p-5 text-center text-sm text-muted">Aucun retour enregistré.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Date</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 text-right font-medium">Quantité</th>
                    <th className="px-5 py-3 font-medium">Note</th>
                  </tr>
                </thead>
                <tbody>
                  {(history?.data ?? []).map((row) => (
                    <tr key={row.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-muted">
                        {row.date ? new Date(row.date).toLocaleDateString('fr-FR') : '—'}
                      </td>
                      <td className="px-5 py-3 text-muted">{row.warehouse.code ?? '—'}</td>
                      <td className="px-5 py-3">
                        <span className="mono text-xs text-muted">{row.product.sku}</span>{' '}
                        <span className="text-ink">{row.product.name}</span>
                      </td>
                      <td className={`tabular px-5 py-3 text-right font-medium ${isCustomer ? 'text-ok' : 'text-bad'}`}>
                        {isCustomer ? '+' : '−'}
                        {formatNumber(row.quantity)}
                      </td>
                      <td className="px-5 py-3 text-muted">{row.note ?? '—'}</td>
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

export function CustomerReturnsPage() {
  return <ReturnsPage direction="customer" />
}

export function SupplierReturnsPage() {
  return <ReturnsPage direction="supplier" />
}
