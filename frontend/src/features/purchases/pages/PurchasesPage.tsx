import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, PackageSearch, Plus, Trash2, Undo2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface PoRow {
  id: number
  reference: string
  supplier: string | null
  warehouse: string | null
  status: string
  expected_at: string | null
  lines_count: number
  created_at: string | null
}

interface PoDetail {
  id: number
  reference: string
  supplier: string | null
  warehouse: string | null
  status: string
  expected_at: string | null
  note: string | null
  lines: {
    id: number
    sku: string | null
    name: string | null
    quantity_ordered: number
    quantity_received: number
    remaining: number
    unit_price: number
  }[]
}

interface SupplierOption {
  id: number
  code: string
  name: string
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
}

interface ReplenishmentRow {
  product_id: number
  sku: string
  name: string
  min_stock: number
  current_stock: number
}

const KEY = ['purchase-orders'] as const

const STATUS_BADGES: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  draft: { label: 'Brouillon', tone: 'warn' },
  ordered: { label: 'Commandé', tone: 'sky' },
  partial: { label: 'Reçu partiel', tone: 'warn' },
  received: { label: 'Reçu', tone: 'ok' },
  cancelled: { label: 'Annulé', tone: 'bad' },
}

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Achats : bons de commande, réception partielle/totale (reliquats),
 * suggestions de réapprovisionnement et retours fournisseur.
 */
export function PurchasesPage() {
  const [detailId, setDetailId] = useState<number | null>(null)

  if (detailId !== null) {
    return <PoDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  return <PoList onOpen={setDetailId} />
}

function PoList({ onOpen }: { onOpen: (id: number) => void }) {
  const can = usePermission()
  const [page, setPage] = useState(1)
  const [panel, setPanel] = useState<'none' | 'create' | 'replenishment' | 'return'>('none')

  const { data, isLoading } = useQuery<Paginated<PoRow>>({
    queryKey: [...KEY, page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<PoRow>>('/purchase-orders', { params: { page } })
      return r
    },
  })

  const orders = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Achats</h1>
          <p className="text-sm text-muted">Bons de commande fournisseurs, réceptions et reliquats.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => setPanel(panel === 'replenishment' ? 'none' : 'replenishment')}>
            <PackageSearch className="h-4 w-4" />
            Réappro
          </Button>
          {can('receipt.create') ? (
            <Button variant="outline" size="sm" onClick={() => setPanel(panel === 'return' ? 'none' : 'return')}>
              <Undo2 className="h-4 w-4" />
              Retour fournisseur
            </Button>
          ) : null}
          {can('purchase.create') ? (
            <Button size="sm" onClick={() => setPanel(panel === 'create' ? 'none' : 'create')}>
              <Plus className="h-4 w-4" />
              Nouveau bon de commande
            </Button>
          ) : null}
        </div>
      </div>

      {panel === 'create' ? <CreatePoPanel onClose={() => setPanel('none')} /> : null}
      {panel === 'replenishment' ? <ReplenishmentPanel /> : null}
      {panel === 'return' ? <SupplierReturnPanel onClose={() => setPanel('none')} /> : null}

      <Card>
        <CardHeader title="Bons de commande" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Fournisseur</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium">Attendu le</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {orders.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-8 text-center text-muted">Aucun bon de commande.</td></tr>
                ) : (
                  orders.map((o) => {
                    const badge = STATUS_BADGES[o.status] ?? { label: o.status, tone: 'neutral' as const }
                    return (
                      <tr key={o.id} className="border-b border-line last:border-0">
                        <td className="mono px-5 py-3 text-muted">{o.reference}</td>
                        <td className="px-5 py-3 text-ink">{o.supplier}</td>
                        <td className="px-5 py-3 text-muted">{o.warehouse}</td>
                        <td className="px-5 py-3 text-muted">{o.expected_at ?? '—'}</td>
                        <td className="px-5 py-3"><Badge tone={badge.tone}>{badge.label}</Badge></td>
                        <td className="px-5 py-3 text-right">
                          <Button variant="ghost" size="sm" onClick={() => onOpen(o.id)}>
                            {['ordered', 'partial'].includes(o.status) ? 'Réceptionner' : 'Consulter'}
                          </Button>
                        </td>
                      </tr>
                    )
                  })
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

function useSupplierOptions() {
  return useQuery<SupplierOption[]>({
    queryKey: ['supplier-options'],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SupplierOption>>('/suppliers', { params: { per_page: 100 } })
      return r.data
    },
    staleTime: 5 * 60_000,
  })
}

function ProductPicker({ onPick, exclude }: { onPick: (p: ProductOption) => void; exclude: number[] }) {
  const [search, setSearch] = useState('')

  const { data: options = [] } = useQuery<ProductOption[]>({
    queryKey: ['po-product-search', search],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', { params: { search, per_page: 20 } })
      return r.data
    },
    enabled: search.trim().length >= 2,
  })

  return (
    <div className="space-y-2">
      <Input placeholder="Rechercher un article à ajouter…" value={search} onChange={(e) => setSearch(e.target.value)} />
      {options.length > 0 && search.trim().length >= 2 ? (
        <ul className="max-h-40 overflow-auto rounded border border-line">
          {options.filter((o) => !exclude.includes(o.id)).map((o) => (
            <li key={o.id}>
              <button
                type="button"
                onClick={() => {
                  onPick(o)
                  setSearch('')
                }}
                className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
              >
                <span className="mono text-muted">{o.sku}</span>
                <span className="text-ink">{o.name}</span>
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  )
}

function CreatePoPanel({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient()
  const { data: suppliers = [] } = useSupplierOptions()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [supplierId, setSupplierId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [expectedAt, setExpectedAt] = useState('')
  const [lines, setLines] = useState<DraftLine[]>([])

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post('/purchase-orders', {
        supplier_id: supplierId,
        warehouse_id: warehouseId,
        expected_at: expectedAt || null,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity, unit_price: l.unit_price })),
      })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      onClose()
    },
  })

  const total = lines.reduce((sum, l) => sum + l.quantity * l.unit_price, 0)

  return (
    <Card>
      <CardHeader title="Nouveau bon de commande" hint={`Total : ${formatNumber(total)} DH`} />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Fournisseur" htmlFor="po-supplier">
            <Select id="po-supplier" value={supplierId || ''} onChange={(e) => setSupplierId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {suppliers.map((s) => <option key={s.id} value={s.id}>{s.code} · {s.name}</option>)}
            </Select>
          </Field>
          <Field label="Lieu de réception" htmlFor="po-warehouse">
            <Select id="po-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Date attendue" htmlFor="po-expected">
            <Input id="po-expected" type="date" value={expectedAt} onChange={(e) => setExpectedAt(e.target.value)} />
          </Field>
        </div>

        <div className="space-y-2 rounded-lg border border-line p-3">
          <ProductPicker
            exclude={lines.map((l) => l.product_id)}
            onPick={(p) => setLines((prev) => [...prev, { product_id: p.id, sku: p.sku, name: p.name, quantity: 1, unit_price: 0 }])}
          />
          {lines.map((l, i) => (
            <div key={l.product_id} className="flex items-center gap-2">
              <span className="mono w-28 text-sm text-muted">{l.sku}</span>
              <span className="flex-1 truncate text-sm text-ink">{l.name}</span>
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
                className="w-28"
                aria-label="Prix unitaire"
              />
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
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article — quantité et prix d'achat par ligne.</p> : null}
        </div>

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Création impossible.')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button onClick={() => create.mutate()} disabled={create.isPending || !supplierId || !warehouseId || lines.length === 0}>
            {create.isPending ? 'Création…' : 'Passer la commande'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

function ReplenishmentPanel() {
  const { data: rows = [], isLoading } = useQuery<ReplenishmentRow[]>({
    queryKey: ['replenishment'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ReplenishmentRow[] }>('/purchase-orders/replenishment')
      return r.data
    },
  })

  return (
    <Card>
      <CardHeader title="Suggestions de réapprovisionnement" hint={`${rows.length} article(s) sous le seuil`} />
      <CardBody className="p-0">
        {isLoading ? (
          <p className="p-5 text-sm text-muted">Chargement…</p>
        ) : rows.length === 0 ? (
          <p className="p-5 text-sm text-muted">Aucun article sous son seuil minimal.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Stock actuel</th>
                <th className="px-5 py-3 text-right font-medium">Seuil</th>
                <th className="px-5 py-3 text-right font-medium">À commander</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => (
                <tr key={r.product_id} className="border-b border-line last:border-0">
                  <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                  <td className="px-5 py-3 text-ink">{r.name}</td>
                  <td className="tabular px-5 py-3 text-right text-bad">{r.current_stock}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{r.min_stock}</td>
                  <td className="tabular px-5 py-3 text-right font-medium text-ink">{Math.max(0, r.min_stock - r.current_stock)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </CardBody>
    </Card>
  )
}

function SupplierReturnPanel({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient()
  const { data: suppliers = [] } = useSupplierOptions()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [supplierId, setSupplierId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [note, setNote] = useState('')
  const [lines, setLines] = useState<DraftLine[]>([])

  const send = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post('/supplier-returns', {
        supplier_id: supplierId,
        warehouse_id: warehouseId,
        note: note || null,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
      })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stock'] })
      onClose()
    },
  })

  return (
    <Card>
      <CardHeader title="Retour fournisseur" hint="Sortie de stock tracée (mouvement return_out)" />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Fournisseur" htmlFor="ret-supplier">
            <Select id="ret-supplier" value={supplierId || ''} onChange={(e) => setSupplierId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {suppliers.map((s) => <option key={s.id} value={s.id}>{s.code} · {s.name}</option>)}
            </Select>
          </Field>
          <Field label="Lieu" htmlFor="ret-warehouse">
            <Select id="ret-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Motif / note" htmlFor="ret-note">
            <Input id="ret-note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Défectueux, erreur de commande…" />
          </Field>
        </div>

        <div className="space-y-2 rounded-lg border border-line p-3">
          <ProductPicker
            exclude={lines.map((l) => l.product_id)}
            onPick={(p) => setLines((prev) => [...prev, { product_id: p.id, sku: p.sku, name: p.name, quantity: 1, unit_price: 0 }])}
          />
          {lines.map((l, i) => (
            <div key={l.product_id} className="flex items-center gap-2">
              <span className="mono w-28 text-sm text-muted">{l.sku}</span>
              <span className="flex-1 truncate text-sm text-ink">{l.name}</span>
              <Input
                type="number"
                min={1}
                value={l.quantity}
                onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))}
                className="w-24"
              />
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
        </div>

        {send.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(send.error, 'Retour impossible (stock insuffisant ?).')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button onClick={() => send.mutate()} disabled={send.isPending || !supplierId || !warehouseId || lines.length === 0}>
            Enregistrer le retour
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

function PoDetailView({ id, onBack }: { id: number; onBack: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()

  const { data: order } = useQuery<PoDetail>({
    queryKey: [...KEY, 'detail', id],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: PoDetail }>(`/purchase-orders/${id}`)
      return r.data
    },
  })

  const [received, setReceived] = useState<Record<number, number>>({})

  const receive = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/purchase-orders/${id}/receive`, { quantities: received })
    },
    onSuccess: () => {
      setReceived({})
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
    },
  })

  const receivable = order !== undefined && ['ordered', 'partial'].includes(order.status)
  const badge = STATUS_BADGES[order?.status ?? ''] ?? { label: order?.status ?? '', tone: 'neutral' as const }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">Commande {order?.reference}</h1>
            <p className="text-sm text-muted">
              {order?.supplier} → {order?.warehouse} <Badge tone={badge.tone}>{badge.label}</Badge>
            </p>
          </div>
        </div>
        {receivable && can('receipt.create') ? (
          <Button onClick={() => receive.mutate()} disabled={receive.isPending || Object.values(received).every((q) => !q)}>
            {receive.isPending ? 'Réception…' : 'Valider la réception'}
          </Button>
        ) : null}
      </div>

      {receive.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(receive.error, 'Réception impossible.')}
        </p>
      ) : null}

      <Card>
        <CardHeader title={receivable ? 'Réception — saisir les quantités reçues' : 'Lignes'} />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Commandé</th>
                <th className="px-5 py-3 text-right font-medium">Déjà reçu</th>
                <th className="px-5 py-3 text-right font-medium">Reliquat</th>
                <th className="px-5 py-3 text-right font-medium">PU (DH)</th>
                {receivable ? <th className="px-5 py-3 text-right font-medium">Recevoir</th> : null}
              </tr>
            </thead>
            <tbody>
              {(order?.lines ?? []).map((l) => (
                <tr key={l.id} className="border-b border-line last:border-0">
                  <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                  <td className="px-5 py-3 text-ink">{l.name}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.quantity_ordered}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.quantity_received}</td>
                  <td className="tabular px-5 py-3 text-right font-medium text-ink">{l.remaining}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(l.unit_price)}</td>
                  {receivable ? (
                    <td className="px-5 py-3 text-right">
                      <Input
                        type="number"
                        min={0}
                        max={l.remaining}
                        value={received[l.id] ?? 0}
                        onChange={(e) => setReceived((p) => ({ ...p, [l.id]: Math.min(l.remaining, Number(e.target.value)) }))}
                        className="ml-auto w-24"
                        disabled={l.remaining === 0}
                      />
                    </td>
                  ) : null}
                </tr>
              ))}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
