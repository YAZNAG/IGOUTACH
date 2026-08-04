import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Download, FileText, Lock, LockOpen, Plus, Trash2 } from 'lucide-react'
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
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

export interface SaleRow {
  id: number
  reference: string
  type: string
  status: string
  customer: string | null
  warehouse: string | null
  total: number
  paid_amount: number
  payment_status: string
  lines_count: number
  quote_id: number | null
  converted: boolean
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
  current_stock?: number
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
  unit_price: number
  price_type_code: string | null
  floor_price: number
  /** Stock disponible sur le lieu choisi. */
  current_stock: number
  /** Prix déverrouillé pour saisie manuelle. */
  manual: boolean
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
  const [pickingQuote, setPickingQuote] = useState(false)

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: [...KEY, 'invoices', page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { page, type: 'invoice' } })
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
          <p className="text-sm text-muted">
            Factures — la validation sort le stock et génère le bon de sortie. Les devis se créent dans la page Devis.
          </p>
        </div>
        {can('sale.create') ? (
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => { setPickingQuote((v) => !v); setCreating(false) }}
            >
              <FileText className="h-4 w-4" />
              Depuis un devis
            </Button>
            <Button onClick={() => { setCreating((v) => !v); setPickingQuote(false) }}>
              <Plus className="h-4 w-4" />
              Vente directe
            </Button>
          </div>
        ) : null}
      </div>

      {pickingQuote ? <QuotePicker onClose={() => setPickingQuote(false)} onConverted={onOpen} /> : null}

      {creating ? <CreateSalePanel fixedType="invoice" onClose={() => setCreating(false)} onCreated={onOpen} /> : null}

      <Card>
        <CardHeader title="Factures" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Client</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes</th>
                  <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 font-medium">Règlement</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {sales.length === 0 ? (
                  <tr><td colSpan={8} className="px-5 py-8 text-center text-muted">Aucune vente.</td></tr>
                ) : (
                  sales.map((s) => (
                    <tr key={s.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">
                        {s.reference}
                        {s.quote_id !== null ? <span className="ml-1 text-xs text-faint" title="Issue d'un devis">↩</span> : null}
                      </td>
                      <td className="px-5 py-3 text-muted">{s.created_at ?? '—'}</td>
                      <td className="px-5 py-3 text-ink">{s.customer}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{s.lines_count}</td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(s.total)}</td>
                      <td className="px-5 py-3">
                        {s.status === 'confirmed' ? <Badge tone="ok">Confirmé</Badge> : null}
                        {s.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
                        {s.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
                      </td>
                      <td className="px-5 py-3">
                        {s.status === 'confirmed' ? (
                          s.payment_status === 'paid' ? <Badge tone="ok">Payé</Badge>
                            : s.payment_status === 'partial' ? <Badge tone="warn">Partiel</Badge>
                              : <Badge tone="bad">Impayé</Badge>
                        ) : '—'}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => onOpen(s.id)} title="Consulter">
                            Ouvrir
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => downloadFile(`/sales/${s.id}/pdf`, `${s.reference}.pdf`)}
                            title="Facture PDF"
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                          {s.status === 'confirmed' ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => downloadFile(`/sales/${s.id}/exit-pdf`, `BS-${s.reference}.pdf`)}
                              title="Bon de sortie PDF"
                            >
                              <FileText className="h-4 w-4" />
                            </Button>
                          ) : null}
                        </div>
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

/**
 * Sélecteur de devis : liste des devis non convertis, un clic crée la
 * vente (facture brouillon) avec les mêmes lignes.
 */
function QuotePicker({ onClose, onConverted }: { onClose: () => void; onConverted: (invoiceId: number) => void }) {
  const qc = useQueryClient()
  const [error, setError] = useState<string | null>(null)

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: [...KEY, 'quotes-picker'],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { type: 'quote', per_page: 50 } })
      return r
    },
  })

  const convert = useMutation({
    mutationFn: async (quoteId: number) => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { id: number } }>(`/sales/${quoteId}/convert`)
      return r.data
    },
    onSuccess: (result) => {
      qc.invalidateQueries({ queryKey: KEY })
      onConverted(result.id)
    },
    onError: (e) => setError(errorMessage(e, 'Conversion impossible.')),
  })

  const quotes = (data?.data ?? []).filter((q) => q.status !== 'cancelled')

  return (
    <Card>
      <CardHeader title="Créer une vente à partir d'un devis" hint="Les lignes et le client du devis sont repris tels quels." />
      <CardBody className="space-y-3">
        {error ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{error}</p> : null}
        {isLoading ? (
          <p className="text-sm text-muted">Chargement…</p>
        ) : quotes.length === 0 ? (
          <p className="text-sm text-muted">Aucun devis disponible — créez-en un dans la page Devis.</p>
        ) : (
          <div className="max-h-80 overflow-auto rounded border border-line">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-4 py-2 font-medium">Référence</th>
                  <th className="px-4 py-2 font-medium">Date</th>
                  <th className="px-4 py-2 font-medium">Client</th>
                  <th className="px-4 py-2 text-right font-medium">Lignes</th>
                  <th className="px-4 py-2 text-right font-medium">Total (DH)</th>
                  <th className="px-4 py-2 text-right font-medium" />
                </tr>
              </thead>
              <tbody>
                {quotes.map((q) => (
                  <tr key={q.id} className={`border-b border-line last:border-0 ${q.converted ? 'opacity-50' : ''}`}>
                    <td className="mono px-4 py-2 text-muted">{q.reference}</td>
                    <td className="px-4 py-2 text-muted">{q.created_at ?? '—'}</td>
                    <td className="px-4 py-2 text-ink">{q.customer}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{q.lines_count}</td>
                    <td className="tabular px-4 py-2 text-right text-ink">{formatNumber(q.total)}</td>
                    <td className="px-4 py-2 text-right">
                      {q.converted ? (
                        <Badge tone="neutral">Déjà converti</Badge>
                      ) : (
                        <Button
                          size="sm"
                          onClick={() => convert.mutate(q.id)}
                          disabled={convert.isPending}
                        >
                          Convertir en vente
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Button variant="ghost" onClick={onClose}>Fermer</Button>
      </CardBody>
    </Card>
  )
}

export function CreateSalePanel({
  onClose,
  onCreated,
  fixedType = 'invoice',
}: {
  onClose: () => void
  onCreated: (id: number) => void
  fixedType?: 'invoice' | 'quote'
}) {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const type = fixedType
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
    queryKey: ['sale-product-search', productSearch, warehouseId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', {
        // warehouse_id ajoute current_stock (stock du lieu) à chaque article.
        params: { search: productSearch, warehouse_id: warehouseId || undefined, per_page: 20 },
      })
      return r.data
    },
    enabled: productSearch.trim().length >= 2,
  })

  const [priceWarning, setPriceWarning] = useState<string | null>(null)

  async function addProduct(p: ProductOption) {
    setPriceWarning(null)
    try {
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
        current_stock: p.current_stock ?? 0,
        manual: false,
      }])
    } catch (error) {
      // L'article est ajouté quand même, le prix se saisit manuellement.
      setLines((prev) => [...prev, {
        product_id: p.id,
        sku: p.sku,
        name: p.name,
        quantity: 1,
        unit_price: 0,
        price_type_code: null,
        floor_price: 0,
        current_stock: p.current_stock ?? 0,
        manual: true,
      }])
      const status =
        error && typeof error === 'object' && 'response' in error
          ? (error as { response?: { status?: number } }).response?.status
          : undefined
      // 422 = vraiment aucun tarif ; sinon (réseau, serveur) message honnête.
      setPriceWarning(
        status === 422
          ? `${p.sku} : aucun tarif défini — saisissez le prix manuellement.`
          : `${p.sku} : tarif non récupéré (serveur injoignable ?) — retirez la ligne et réessayez, ou saisissez le prix manuellement.`,
      )
    }
    setProductSearch('')
  }

  /**
   * Quantité modifiée : le prix se recalcule selon les paliers du client,
   * sauf si la ligne est en saisie manuelle (prix déverrouillé).
   */
  function changeQuantity(index: number, quantity: number) {
    const line = lines[index]
    if (!line) return
    const qty = Math.max(1, quantity)
    setLines((prev) => prev.map((x, j) => (j === index ? { ...x, quantity: qty } : x)))

    if (line.manual) return
    void api
      .get<{ data: { unit_price: number; price_type_code: string; floor_price: number } }>('/sales/price', {
        params: { product_id: line.product_id, quantity: qty, customer_id: customerId || undefined },
      })
      .then(({ data: r }) => {
        setLines((prev) =>
          prev.map((x) =>
            x.product_id === line.product_id && !x.manual
              ? { ...x, unit_price: r.data.unit_price, price_type_code: r.data.price_type_code, floor_price: r.data.floor_price }
              : x,
          ),
        )
      })
      .catch(() => {
        // Palier non recalculé (erreur réseau) : on garde le prix courant.
      })
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
      <CardHeader
        title={type === 'quote' ? 'Nouveau devis' : 'Nouvelle vente directe'}
        hint={`Total : ${formatNumber(total)} DH`}
      />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
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
                        className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-bg"
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
                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-bg"
                  >
                    <span className="mono text-muted">{o.sku}</span>
                    <span className="text-ink">{o.name}</span>
                  </button>
                </li>
              ))}
            </ul>
          ) : null}

          {priceWarning ? (
            <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">{priceWarning}</p>
          ) : null}

          {lines.length > 0 ? (
            <div className="grid grid-cols-[6rem_1fr_5.5rem_5.5rem_4.5rem_8rem_6rem_2rem_2rem] items-center gap-2 px-1 text-xs font-medium text-muted">
              <span>Référence</span>
              <span>Article</span>
              <span className="text-right">Stock dispo</span>
              <span className="text-right">Quantité</span>
              <span>Niveau</span>
              <span className="text-right">Prix unitaire (DH)</span>
              <span className="text-right">Total ligne</span>
              <span />
              <span />
            </div>
          ) : null}

          {lines.map((l, i) => {
            const overStock = type === 'invoice' && l.quantity > l.current_stock
            return (
              <div
                key={l.product_id}
                className="grid grid-cols-[6rem_1fr_5.5rem_5.5rem_4.5rem_8rem_6rem_2rem_2rem] items-center gap-2 rounded border border-line bg-card p-2"
              >
                <span className="mono text-sm text-muted">{l.sku}</span>
                <span className="truncate text-sm text-ink">{l.name}</span>
                <span className={`tabular text-right text-sm ${overStock ? 'font-medium text-bad' : 'text-muted'}`}>
                  {formatNumber(l.current_stock)}
                </span>
                <Input
                  type="number"
                  min={1}
                  value={l.quantity}
                  onChange={(e) => changeQuantity(i, Number(e.target.value))}
                  className={`w-full text-right ${overStock ? 'border-bad' : ''}`}
                  aria-label="Quantité"
                  title={overStock ? `Stock insuffisant (${l.current_stock} disponibles)` : undefined}
                />
                <span>{l.price_type_code ? <Badge tone="sky">{l.price_type_code}</Badge> : <Badge tone="warn">manuel</Badge>}</span>
                <Input
                  type="number"
                  min={0}
                  step="0.01"
                  value={l.unit_price}
                  disabled={!l.manual}
                  onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, unit_price: Number(e.target.value) } : x)))}
                  className={`w-full text-right ${l.unit_price < l.floor_price ? 'border-bad' : ''} ${!l.manual ? 'opacity-70' : ''}`}
                  aria-label="Prix unitaire"
                  title={
                    l.unit_price < l.floor_price
                      ? `Sous le plancher (${formatNumber(l.floor_price)} DH)`
                      : l.manual
                        ? 'Prix manuel'
                        : 'Prix appliqué automatiquement — déverrouillez pour modifier'
                  }
                />
                <span className="tabular text-right text-sm font-medium text-ink">{formatNumber(l.quantity * l.unit_price)}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  className={l.manual ? 'text-warn' : 'text-muted'}
                  onClick={() => {
                    const wasManual = l.manual
                    setLines((p) => p.map((x, j) => (j === i ? { ...x, manual: !x.manual } : x)))
                    // Re-verrouillage : on reprend le prix automatique du palier.
                    if (wasManual) changeQuantity(i, l.quantity)
                  }}
                  title={l.manual ? 'Reverrouiller (reprendre le prix automatique)' : 'Modifier le prix manuellement'}
                  aria-label={l.manual ? 'Reverrouiller le prix' : 'Modifier le prix'}
                >
                  {l.manual ? <LockOpen className="h-4 w-4" /> : <Lock className="h-4 w-4" />}
                </Button>
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
            )
          })}
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article — le prix s'applique automatiquement selon le client et la quantité (paliers détail / demi-gros / gros).</p> : null}
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

export function SaleDetailView({ id, onBack }: { id: number; onBack: () => void }) {
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
      qc.invalidateQueries({ queryKey: ['stock-exits'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
      // Bon de sortie généré automatiquement à la validation d'une facture.
      if (sale?.type === 'invoice') {
        void downloadFile(`/sales/${id}/exit-pdf`, `BS-${sale.reference}.pdf`)
      }
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
          {sale ? (
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={() => downloadFile(`/sales/${sale.id}/pdf`, `${sale.reference}.pdf`)}
                title={sale.type === 'quote' ? 'Devis PDF' : 'Facture PDF'}
              >
                <Download className="h-4 w-4" />
                {sale.type === 'quote' ? 'Devis PDF' : 'Facture PDF'}
              </Button>
              {sale.type === 'invoice' && sale.status === 'confirmed' ? (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => downloadFile(`/sales/${sale.id}/exit-pdf`, `BS-${sale.reference}.pdf`)}
                  title="Bon de sortie (quantités seules)"
                >
                  <FileText className="h-4 w-4" />
                  Bon de sortie
                </Button>
              ) : null}
            </>
          ) : null}
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
