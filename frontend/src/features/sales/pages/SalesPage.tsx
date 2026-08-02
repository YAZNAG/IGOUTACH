import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface SaleRow {
  id: number
  reference: string
  type: string
  status: string
  customer: string | null
  warehouse: string | null
  total: number
  paid_amount: number
  payment_status: string
  created_at: string | null
}

interface SaleDetail {
  id: number
  reference: string
  type: string
  status: string
  customer: { id: number; name: string; balance: number; credit_limit: number; is_blocked: boolean } | null
  warehouse: string | null
  subtotal: number
  discount_percent: number
  total: number
  paid_amount: number
  payment_status: string
  confirmed_at: string | null
  note: string | null
  lines: { sku: string | null; name: string | null; quantity: number; unit_price: number; price_type_code: string | null; line_total: number }[]
}

interface CustomerOption {
  id: number
  code: string
  name: string
  is_blocked: boolean
}

interface ProductOption {
  id: number
  sku: string
  name: string
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
  unit_price: number
  price_type_code: string | null
  floor_price: number
}

const KEY = ['sales'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Ventes : devis et factures. Les prix sont résolus par le serveur
 * (type de prix du client puis paliers de quantité), le stock sort à la
 * confirmation et la créance alimente le crédit client.
 */
export function SalesPage() {
  const [detailId, setDetailId] = useState<number | null>(null)

  if (detailId !== null) {
    return <SaleDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  return <SalesList onOpen={setDetailId} />
}

function SalesList({ onOpen }: { onOpen: (id: number) => void }) {
  const can = usePermission()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: [...KEY, page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { page } })
      return r
    },
  })

  const sales = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Ventes</h1>
          <p className="text-sm text-muted">Devis et factures — contrôle du crédit et du prix plancher.</p>
        </div>
        {can('sale.create') && !creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouvelle vente
          </Button>
        ) : null}
      </div>

      {creating ? <CreateSalePanel onClose={() => setCreating(false)} onCreated={onOpen} /> : null}

      <Card>
        <CardHeader title="Documents" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Type</th>
                  <th className="px-5 py-3 font-medium">Client</th>
                  <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 font-medium">Règlement</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {sales.length === 0 ? (
                  <tr><td colSpan={7} className="px-5 py-8 text-center text-muted">Aucune vente.</td></tr>
                ) : (
                  sales.map((s) => (
                    <tr key={s.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{s.reference}</td>
                      <td className="px-5 py-3">{s.type === 'quote' ? <Badge tone="sky">Devis</Badge> : <Badge tone="neutral">Facture</Badge>}</td>
                      <td className="px-5 py-3 text-ink">{s.customer}</td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(s.total)}</td>
                      <td className="px-5 py-3">
                        {s.status === 'confirmed' ? <Badge tone="ok">Confirmé</Badge> : null}
                        {s.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
                        {s.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
                      </td>
                      <td className="px-5 py-3">
                        {s.type === 'invoice' && s.status === 'confirmed' ? (
                          s.payment_status === 'paid' ? <Badge tone="ok">Payé</Badge>
                            : s.payment_status === 'partial' ? <Badge tone="warn">Partiel</Badge>
                              : <Badge tone="bad">Impayé</Badge>
                        ) : '—'}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <Button variant="ghost" size="sm" onClick={() => onOpen(s.id)}>Ouvrir</Button>
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

function CreateSalePanel({ onClose, onCreated }: { onClose: () => void; onCreated: (id: number) => void }) {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [type, setType] = useState<'invoice' | 'quote'>('invoice')
  const [customerId, setCustomerId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [discount, setDiscount] = useState(0)
  const [lines, setLines] = useState<DraftLine[]>([])
  const [customerSearch, setCustomerSearch] = useState('')
  const [productSearch, setProductSearch] = useState('')

  const { data: customers = [] } = useQuery<CustomerOption[]>({
    queryKey: ['sale-customer-search', customerSearch],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<CustomerOption>>('/customers', {
        params: { q: customerSearch || undefined, per_page: 20 },
      })
      return r.data
    },
  })

  const { data: products = [] } = useQuery<ProductOption[]>({
    queryKey: ['sale-product-search', productSearch],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', { params: { search: productSearch, per_page: 20 } })
      return r.data
    },
    enabled: productSearch.trim().length >= 2,
  })

  async function addProduct(p: ProductOption) {
    // Prix résolu côté serveur : type de prix du client, puis paliers.
    const { data: r } = await api.get<{ data: { unit_price: number; price_type_code: string; floor_price: number } }>(
      '/sales/price',
      { params: { product_id: p.id, quantity: 1, customer_id: customerId || undefined } },
    )
    setLines((prev) => [...prev, {
      product_id: p.id,
      sku: p.sku,
      name: p.name,
      quantity: 1,
      unit_price: r.data.unit_price,
      price_type_code: r.data.price_type_code,
      floor_price: r.data.floor_price,
    }])
    setProductSearch('')
  }

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { id: number } }>('/sales', {
        type,
        customer_id: customerId,
        warehouse_id: warehouseId,
        discount_percent: discount,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity, unit_price: l.unit_price })),
      })
      return r.data
    },
    onSuccess: (result) => {
      qc.invalidateQueries({ queryKey: KEY })
      onClose()
      onCreated(result.id)
    },
  })

  const subtotal = lines.reduce((sum, l) => sum + l.quantity * l.unit_price, 0)
  const total = subtotal * (1 - discount / 100)
  const selectedCustomer = customers.find((c) => c.id === customerId)

  return (
    <Card>
      <CardHeader title="Nouvelle vente" hint={`Total : ${formatNumber(total)} DH`} />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-4">
          <Field label="Type" htmlFor="sale-type">
            <Select id="sale-type" value={type} onChange={(e) => setType(e.target.value as 'invoice' | 'quote')}>
              <option value="invoice">Facture (sortie de stock)</option>
              <option value="quote">Devis (sans effet stock)</option>
            </Select>
          </Field>
          <Field label="Client" htmlFor="sale-customer">
            <div className="space-y-1">
              <Input
                id="sale-customer"
                placeholder="Rechercher…"
                value={customerSearch}
                onChange={(e) => { setCustomerSearch(e.target.value); setCustomerId(0) }}
              />
              {customerId === 0 && customers.length > 0 ? (
                <ul className="max-h-32 overflow-auto rounded border border-line">
                  {customers.map((c) => (
                    <li key={c.id}>
                      <button
                        type="button"
                        onClick={() => { setCustomerId(c.id); setCustomerSearch(`${c.code} · ${c.name}`) }}
                        className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                      >
                        <span className="mono text-muted">{c.code}</span>
                        <span className="text-ink">{c.name}</span>
                        {c.is_blocked ? <Badge tone="bad">Bloqué</Badge> : null}
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </Field>
          <Field label="Lieu" htmlFor="sale-warehouse">
            <Select id="sale-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Remise globale (%)" htmlFor="sale-discount">
            <Input id="sale-discount" type="number" min={0} max={100} value={discount} onChange={(e) => setDiscount(Number(e.target.value))} />
          </Field>
        </div>

        {selectedCustomer?.is_blocked ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Ce client est bloqué (plafond de crédit dépassé) — la confirmation d'une facture sera refusée.
          </p>
        ) : null}

        <div className="space-y-2 rounded-lg border border-line p-3">
          <Input
            placeholder={customerId === 0 ? 'Sélectionnez d\'abord un client…' : 'Rechercher un article…'}
            value={productSearch}
            onChange={(e) => setProductSearch(e.target.value)}
            disabled={customerId === 0}
          />
          {products.length > 0 && productSearch.trim().length >= 2 ? (
            <ul className="max-h-40 overflow-auto rounded border border-line">
              {products.filter((o) => !lines.some((l) => l.product_id === o.id)).map((o) => (
                <li key={o.id}>
                  <button
                    type="button"
                    onClick={() => void addProduct(o)}
                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                  >
                    <span className="mono text-muted">{o.sku}</span>
                    <span className="text-ink">{o.name}</span>
                  </button>
                </li>
              ))}
            </ul>
          ) : null}

          {lines.map((l, i) => (
            <div key={l.product_id} className="flex items-center gap-2">
              <span className="mono w-24 text-sm text-muted">{l.sku}</span>
              <span className="flex-1 truncate text-sm text-ink">{l.name}</span>
              {l.price_type_code ? <Badge tone="sky">{l.price_type_code}</Badge> : null}
              <Input
                type="number"
                min={1}
                value={l.quantity}
                onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))}
                className="w-20"
                aria-label="Quantité"
              />
              <Input
                type="number"
                min={0}
                step="0.01"
                value={l.unit_price}
                onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, unit_price: Number(e.target.value) } : x)))}
                className={`w-28 ${l.unit_price < l.floor_price ? 'border-bad' : ''}`}
                aria-label="Prix unitaire"
                title={l.unit_price < l.floor_price ? `Sous le plancher (${formatNumber(l.floor_price)} DH)` : undefined}
              />
              <span className="tabular w-24 text-right text-sm text-ink">{formatNumber(l.quantity * l.unit_price)}</span>
              <Button
                variant="ghost"
                size="sm"
                className="text-bad hover:bg-bad-bg"
                onClick={() => setLines((p) => p.filter((_, j) => j !== i))}
                aria-label={`Retirer ${l.name}`}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          ))}
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article — le prix se remplit automatiquement selon le client.</p> : null}
        </div>

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Création impossible.')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button onClick={() => create.mutate()} disabled={create.isPending || !customerId || !warehouseId || lines.length === 0}>
            {create.isPending ? 'Création…' : 'Créer le document'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

function SaleDetailView({ id, onBack }: { id: number; onBack: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [cancelOpen, setCancelOpen] = useState(false)

  const { data: sale } = useQuery<SaleDetail>({
    queryKey: [...KEY, 'detail', id],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: SaleDetail }>(`/sales/${id}`)
      return r.data
    },
  })

  const confirm = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/sales/${id}/confirm`)
    },
    onSuccess: () => {
      setConfirmOpen(false)
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  const cancel = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/sales/${id}/cancel`)
    },
    onSuccess: () => {
      setCancelOpen(false)
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">
              {sale?.type === 'quote' ? 'Devis' : 'Facture'} {sale?.reference}
            </h1>
            <p className="text-sm text-muted">
              {sale?.customer?.name} · {sale?.warehouse}{' '}
              {sale?.status === 'confirmed' ? <Badge tone="ok">Confirmé</Badge> : null}
              {sale?.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
              {sale?.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          {sale?.status === 'draft' ? (
            <Button onClick={() => setConfirmOpen(true)} disabled={confirm.isPending}>
              Confirmer {sale.type === 'invoice' ? '(sortie de stock)' : ''}
            </Button>
          ) : null}
          {sale && sale.status !== 'cancelled' && can('sale.cancel') ? (
            <Button variant="ghost" onClick={() => setCancelOpen(true)} disabled={cancel.isPending}>
              Annuler
            </Button>
          ) : null}
        </div>
      </div>

      {confirm.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(confirm.error, 'Confirmation impossible.')}
        </p>
      ) : null}
      {cancel.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(cancel.error, 'Annulation impossible.')}
        </p>
      ) : null}

      {sale?.customer ? (
        <div className="grid gap-3 sm:grid-cols-3">
          <Card><CardBody><p className="text-xs uppercase text-faint">Encours client</p><p className="tabular text-lg font-semibold text-ink">{formatNumber(sale.customer.balance)} DH</p></CardBody></Card>
          <Card><CardBody><p className="text-xs uppercase text-faint">Plafond</p><p className="tabular text-lg font-semibold text-ink">{sale.customer.credit_limit > 0 ? `${formatNumber(sale.customer.credit_limit)} DH` : 'Illimité'}</p></CardBody></Card>
          <Card><CardBody><p className="text-xs uppercase text-faint">Règlement</p><p className="text-lg font-semibold text-ink">{formatNumber(sale.paid_amount)} / {formatNumber(sale.total)} DH</p></CardBody></Card>
        </div>
      ) : null}

      <Card>
        <CardHeader
          title="Lignes"
          hint={`Sous-total ${formatNumber(sale?.subtotal ?? 0)} DH · remise ${sale?.discount_percent ?? 0}% · total ${formatNumber(sale?.total ?? 0)} DH`}
        />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 font-medium">Niveau</th>
                <th className="px-5 py-3 text-right font-medium">Qté</th>
                <th className="px-5 py-3 text-right font-medium">PU (DH)</th>
                <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
              </tr>
            </thead>
            <tbody>
              {(sale?.lines ?? []).map((l, i) => (
                <tr key={i} className="border-b border-line last:border-0">
                  <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                  <td className="px-5 py-3 text-ink">{l.name}</td>
                  <td className="px-5 py-3">{l.price_type_code ? <Badge tone="sky">{l.price_type_code}</Badge> : '—'}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.quantity}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(l.unit_price)}</td>
                  <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatNumber(l.line_total)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardBody>
      </Card>

      <ConfirmDialog
        open={confirmOpen}
        title="Confirmer la vente"
        message={
          <>
            Confirmer <strong>{sale?.reference}</strong> ?
            {sale?.type === 'invoice' ? (
              <> Le stock du lieu sera <strong>sorti automatiquement</strong> et la créance ajoutée à l'encours du client.</>
            ) : ' Le devis sera figé.'}
          </>
        }
        confirmLabel="Confirmer"
        danger={false}
        isPending={confirm.isPending}
        error={confirm.isError ? errorMessage(confirm.error, 'Confirmation impossible.') : undefined}
        onConfirm={() => confirm.mutate()}
        onCancel={() => setConfirmOpen(false)}
      />

      <ConfirmDialog
        open={cancelOpen}
        title="Annuler la vente"
        message={
          <>
            Annuler <strong>{sale?.reference}</strong> ?
            {sale?.status === 'confirmed' && sale.type === 'invoice' ? (
              <> La marchandise <strong>reviendra en stock</strong> (mouvement retour) et la créance sera contre-passée.</>
            ) : null}
          </>
        }
        confirmLabel="Annuler la vente"
        danger
        isPending={cancel.isPending}
        error={cancel.isError ? errorMessage(cancel.error, 'Annulation impossible.') : undefined}
        onConfirm={() => cancel.mutate()}
        onCancel={() => setCancelOpen(false)}
      />
    </div>
  )
}
