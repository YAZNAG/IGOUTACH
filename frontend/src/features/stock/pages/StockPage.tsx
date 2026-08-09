import { useQuery } from '@tanstack/react-query'
import { Download, FileText, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { cn } from '@/lib/utils'
import { formatNumber } from '@/lib/utils'
import { exportMatrix, exportStock, ISSUE_REASONS, searchProducts, type IssueLine, type ProductLite } from '../api/stockApi'
import { useEntryStock, useIssueStock, useMatrix, useMovements, useMovementTypes, useStock } from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

type Tab = 'stock' | 'matrix' | 'movements' | 'entry' | 'issue'

const statusBadge: Record<string, { tone: 'ok' | 'warn' | 'bad'; label: string }> = {
  ok: { tone: 'ok', label: 'OK' },
  low: { tone: 'warn', label: 'Sous seuil' },
  rupture: { tone: 'bad', label: 'Rupture' },
}

export function StockPage() {
  const can = usePermission()
  const canIssue = can('stock.issue')
  const canEntry = can('stock.entry')
  // « Tous les lieux » (vue consolidee) : reserve a qui a la vue globale (admin).
  const canViewGlobal = can('stock.view_global')

  const { data: warehouses = [] } = useWarehouseOptions()
  const [warehouseId, setWarehouseId] = useState<number | null>(null)
  const effectiveWh = warehouseId ?? warehouses[0]?.id ?? null

  const [tab, setTab] = useState<Tab>('stock')

  const tabs: { key: Tab; label: string; show: boolean }[] = [
    { key: 'stock', label: 'Stock par lieu', show: true },
    { key: 'matrix', label: 'Tous les lieux', show: canViewGlobal },
    { key: 'movements', label: 'Journal des mouvements', show: true },
    { key: 'issue', label: 'Bon de sortie', show: canIssue },
  ]

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold text-ink">Stock</h1>
          <p className="text-sm text-muted">Consultation par lieu, journal des mouvements et bons de sortie.</p>
        </div>
        <Select
          value={effectiveWh ?? ''}
          onChange={(e) => setWarehouseId(Number(e.target.value))}
          className="w-56"
          aria-label="Lieu"
        >
          {warehouses.map((w) => (
            <option key={w.id} value={w.id}>
              {w.code} · {w.name}
            </option>
          ))}
        </Select>
      </div>

      <div className="flex gap-1 border-b border-line">
        {tabs.filter((t) => t.show).map((t) => (
          <button
            key={t.key}
            type="button"
            onClick={() => setTab(t.key)}
            className={cn(
              '-mb-px border-b-2 px-4 py-2 text-sm transition-colors',
              tab === t.key ? 'border-sky font-medium text-navy' : 'border-transparent text-muted hover:text-ink',
            )}
          >
            {t.label}
          </button>
        ))}
      </div>

      {tab === 'stock' ? <StockByWarehouse warehouseId={effectiveWh} /> : null}
      {tab === 'matrix' ? <AllWarehousesMatrix /> : null}
      {tab === 'movements' ? <MovementsJournal warehouseId={effectiveWh} /> : null}
      {tab === 'entry' && canEntry ? <EntryForm warehouseId={effectiveWh} onDone={() => setTab('stock')} /> : null}
      {tab === 'issue' && canIssue ? <IssueForm warehouseId={effectiveWh} onDone={() => setTab('movements')} /> : null}
    </div>
  )
}

function StockByWarehouse({ warehouseId }: { warehouseId: number | null }) {
  // Le coût moyen est le prix d'achat : la colonne « Valeur » n'existe que
  // pour qui a le droit de le consulter.
  const voitLesCouts = usePermission()('product.view_cost_price')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const { data, isLoading } = useStock(warehouseId, q, page)
  const rows = data?.data ?? []
  const meta = data?.meta

  return (
    <Card>
      <CardHeader
        title="Stock par lieu"
        hint={meta ? `${meta.total} référence(s)` : undefined}
        action={
          <div className="flex gap-2">
            {warehouseId !== null ? (
              <>
                <Button variant="outline" size="sm" onClick={() => exportStock(warehouseId, 'xlsx')}>
                  <Download className="h-4 w-4" />
                  Excel
                </Button>
                <Button variant="outline" size="sm" onClick={() => exportStock(warehouseId, 'pdf')}>
                  <FileText className="h-4 w-4" />
                  PDF
                </Button>
              </>
            ) : null}
            <Input
              placeholder="Rechercher réf / article…"
              value={q}
              onChange={(e) => {
                setQ(e.target.value)
                setPage(1)
              }}
              className="w-56"
            />
          </div>
        }
      />
      <CardBody className="p-0">
        {isLoading ? (
          <p className="p-5 text-sm text-muted">Chargement…</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Quantité</th>
                <th className="px-5 py-3 text-right font-medium">Seuil</th>
                {voitLesCouts ? (
                  <th className="px-5 py-3 text-right font-medium">Valeur (DH)</th>
                ) : null}
                <th className="px-5 py-3 font-medium">Statut</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={voitLesCouts ? 6 : 5} className="px-5 py-8 text-center text-muted">
                    Aucun article en stock dans ce lieu.
                  </td>
                </tr>
              ) : (
                rows.map((r) => {
                  const badge = statusBadge[r.status]
                  return (
                    <tr key={r.product_id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                      <td className="px-5 py-3 text-ink">{r.name}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">{r.quantity}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{r.min_stock || '—'}</td>
                      {voitLesCouts ? (
                        <td className="tabular px-5 py-3 text-right text-muted">
                          {r.value !== null ? formatNumber(r.value) : '—'}
                        </td>
                      ) : null}
                      <td className="px-5 py-3">
                        <Badge tone={badge.tone}>{badge.label}</Badge>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        )}
      </CardBody>
      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 border-t border-line px-5 py-3 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Précédent
          </Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
            Suivant
          </Button>
        </div>
      ) : null}
    </Card>
  )
}

function MovementsJournal({ warehouseId }: { warehouseId: number | null }) {
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)
  const { data: types = [] } = useMovementTypes()
  const { data, isLoading } = useMovements(
    { warehouse_id: warehouseId ?? undefined, type: type || undefined, page },
    warehouseId !== null,
  )
  const rows = data?.data ?? []
  const meta = data?.meta

  return (
    <Card>
      <CardHeader
        title="Journal des mouvements"
        hint={meta ? `${meta.total}` : undefined}
        action={
          <Select value={type} onChange={(e) => { setType(e.target.value); setPage(1) }} className="w-52">
            <option value="">Tous les types</option>
            {types.map((t) => (
              <option key={t.code} value={t.code}>
                {t.name}
              </option>
            ))}
          </Select>
        }
      />
      <CardBody className="p-0">
        {isLoading ? (
          <p className="p-5 text-sm text-muted">Chargement…</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Date</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 font-medium">Type</th>
                <th className="px-5 py-3 text-right font-medium">Qté</th>
                <th className="px-5 py-3 text-right font-medium">Solde</th>
                <th className="px-5 py-3 font-medium">Note</th>
                <th className="px-5 py-3 font-medium">Par</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-5 py-8 text-center text-muted">
                    Aucun mouvement.
                  </td>
                </tr>
              ) : (
                rows.map((m) => (
                  <tr key={m.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-muted">{new Date(m.created_at).toLocaleString('fr-FR')}</td>
                    <td className="px-5 py-3 text-ink">
                      <span className="mono text-xs text-faint">{m.sku}</span> {m.name}
                    </td>
                    <td className="px-5 py-3">
                      <Badge tone={m.quantity >= 0 ? 'ok' : 'bad'}>{m.type}</Badge>
                    </td>
                    <td className={cn('tabular px-5 py-3 text-right font-medium', m.quantity >= 0 ? 'text-ok' : 'text-bad')}>
                      {m.quantity > 0 ? `+${m.quantity}` : m.quantity}
                    </td>
                    <td className="tabular px-5 py-3 text-right text-muted">{m.balance_after}</td>
                    <td className="px-5 py-3 text-muted">{m.note ?? '—'}</td>
                    <td className="px-5 py-3 text-muted">{m.user ?? '—'}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        )}
      </CardBody>
      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 border-t border-line px-5 py-3 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Précédent
          </Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
            Suivant
          </Button>
        </div>
      ) : null}
    </Card>
  )
}

function AllWarehousesMatrix() {
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const { data, isLoading } = useMatrix(q, page)
  const warehouses = data?.warehouses ?? []
  const rows = data?.data ?? []
  const meta = data?.meta

  return (
    <Card>
      <CardHeader
        title="Stock — tous les lieux"
        hint={meta ? `${meta.total} référence(s)` : undefined}
        action={
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => exportMatrix('xlsx', q)}>
              <Download className="h-4 w-4" />
              Excel
            </Button>
            <Button variant="outline" size="sm" onClick={() => exportMatrix('pdf', q)}>
              <FileText className="h-4 w-4" />
              PDF
            </Button>
            <Input
              placeholder="Rechercher réf / article…"
              value={q}
              onChange={(e) => {
                setQ(e.target.value)
                setPage(1)
              }}
              className="w-56"
            />
          </div>
        }
      />
      <CardBody className="overflow-x-auto p-0">
        {isLoading ? (
          <p className="p-5 text-sm text-muted">Chargement…</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                {warehouses.map((w) => (
                  <th key={w.id} className="px-4 py-3 text-right font-medium" title={w.name}>
                    {w.code}
                  </th>
                ))}
                <th className="px-5 py-3 text-right font-medium">Total</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 ? (
                <tr>
                  <td colSpan={warehouses.length + 3} className="px-5 py-8 text-center text-muted">
                    Aucun stock.
                  </td>
                </tr>
              ) : (
                rows.map((r) => (
                  <tr key={r.product_id} className="border-b border-line last:border-0">
                    <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                    <td className="px-5 py-3 text-ink">{r.name}</td>
                    {warehouses.map((w) => {
                      const qty = r.quantities[String(w.id)] ?? 0
                      return (
                        <td key={w.id} className={cn('tabular px-4 py-3 text-right', qty <= 0 ? 'text-faint' : 'text-muted')}>
                          {qty}
                        </td>
                      )
                    })}
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">{r.total}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        )}
      </CardBody>
      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 border-t border-line px-5 py-3 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Précédent
          </Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
            Suivant
          </Button>
        </div>
      ) : null}
    </Card>
  )
}

interface Line extends IssueLine {
  sku: string
  name: string
}

interface EntryLineRow {
  product_id: number
  sku: string
  name: string
  quantity: number
  unit_cost: number
  note: string
}

function EntryForm({ warehouseId, onDone }: { warehouseId: number | null; onDone: () => void }) {
  const entryMutation = useEntryStock()
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10))
  const [term, setTerm] = useState('')
  const [lines, setLines] = useState<EntryLineRow[]>([])

  const { data: results = [] } = useQuery<ProductLite[]>({
    queryKey: ['product-search-entry', term],
    queryFn: () => searchProducts(term),
    enabled: term.trim().length >= 1,
  })

  function addLine(p: ProductLite) {
    if (lines.some((l) => l.product_id === p.id)) return
    setLines((prev) => [...prev, { product_id: p.id, sku: p.sku, name: p.name, quantity: 1, unit_cost: 0, note: '' }])
    setTerm('')
  }

  function submit() {
    if (warehouseId === null || lines.length === 0) return
    entryMutation.mutate(
      {
        warehouse_id: warehouseId,
        date,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity, unit_cost: l.unit_cost, note: l.note })),
      },
      {
        onSuccess: () => {
          setLines([])
          onDone()
        },
      },
    )
  }

  return (
    <Card>
      <CardHeader title="Nouveau bon d'entrée" hint="Ajoute du stock au lieu sélectionné" />
      <CardBody className="space-y-4">
        {entryMutation.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(entryMutation.error, 'Entrée impossible.')}
          </p>
        ) : null}

        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Date de l'entrée" htmlFor="entry-date">
            <Input id="entry-date" type="date" value={date} onChange={(e) => setDate(e.target.value)} />
          </Field>
          <Field label="Ajouter un article" htmlFor="entry-search">
            <Input
              id="entry-search"
              placeholder="Rechercher réf / nom…"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
            />
          </Field>
        </div>

        {term.trim().length >= 1 && results.length > 0 ? (
          <div className="max-h-48 overflow-y-auto rounded border border-line">
            {results.map((p) => (
              <button
                key={p.id}
                type="button"
                onClick={() => addLine(p)}
                className="flex w-full items-center justify-between border-b border-line px-3 py-2 text-left text-sm last:border-0 hover:bg-bg"
              >
                <span>{p.name}</span>
                <span className="mono text-xs text-faint">{p.sku}</span>
              </button>
            ))}
          </div>
        ) : null}

        {lines.length > 0 ? (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-3 py-2 font-medium">Article</th>
                <th className="px-3 py-2 font-medium">Quantité</th>
                <th className="px-3 py-2 font-medium">Coût unitaire (DH)</th>
                <th className="px-3 py-2 font-medium">Note</th>
                <th className="px-3 py-2" />
              </tr>
            </thead>
            <tbody>
              {lines.map((l, i) => (
                <tr key={l.product_id} className="border-b border-line last:border-0">
                  <td className="px-3 py-2 text-ink">
                    <span className="mono text-xs text-faint">{l.sku}</span> {l.name}
                  </td>
                  <td className="px-3 py-2">
                    <Input
                      type="number"
                      min={1}
                      value={l.quantity}
                      onChange={(e) => setLines((prev) => prev.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))}
                      className="w-24"
                    />
                  </td>
                  <td className="px-3 py-2">
                    <Input
                      type="number"
                      min={0}
                      step="0.01"
                      value={l.unit_cost}
                      onChange={(e) => setLines((prev) => prev.map((x, j) => (j === i ? { ...x, unit_cost: Number(e.target.value) } : x)))}
                      className="w-28"
                    />
                  </td>
                  <td className="px-3 py-2">
                    <Input
                      value={l.note}
                      onChange={(e) => setLines((prev) => prev.map((x, j) => (j === i ? { ...x, note: e.target.value } : x)))}
                    />
                  </td>
                  <td className="px-3 py-2 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-bad hover:bg-bad-bg"
                      onClick={() => setLines((prev) => prev.filter((_, j) => j !== i))}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <p className="text-sm text-muted">Aucune ligne. Recherchez un article ci-dessus pour l'ajouter.</p>
        )}

        <div className="flex gap-2">
          <Button onClick={submit} disabled={entryMutation.isPending || lines.length === 0 || warehouseId === null}>
            <Plus className="h-4 w-4" />
            {entryMutation.isPending ? 'Validation…' : "Valider le bon d'entrée"}
          </Button>
        </div>
      </CardBody>
    </Card>
  )
}

function IssueForm({ warehouseId, onDone }: { warehouseId: number | null; onDone: () => void }) {
  const issueMutation = useIssueStock()
  const [reason, setReason] = useState(ISSUE_REASONS[0].code)
  const [term, setTerm] = useState('')
  const [lines, setLines] = useState<Line[]>([])

  const { data: results = [] } = useQuery<ProductLite[]>({
    queryKey: ['product-search', term],
    queryFn: () => searchProducts(term),
    enabled: term.trim().length >= 1,
  })

  function addLine(p: ProductLite) {
    if (lines.some((l) => l.product_id === p.id)) return
    setLines((prev) => [...prev, { product_id: p.id, sku: p.sku, name: p.name, quantity: 1, note: '' }])
    setTerm('')
  }

  function submit() {
    if (warehouseId === null || lines.length === 0) return
    issueMutation.mutate(
      {
        warehouse_id: warehouseId,
        reason_code: reason,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity, note: l.note })),
      },
      {
        onSuccess: () => {
          setLines([])
          onDone()
        },
      },
    )
  }

  return (
    <Card>
      <CardHeader title="Nouveau bon de sortie" hint="Casse · Perte · Usage interne · SAV" />
      <CardBody className="space-y-4">
        {issueMutation.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(issueMutation.error, 'Sortie impossible (stock insuffisant ?).')}
          </p>
        ) : null}

        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Motif" htmlFor="reason">
            <Select id="reason" value={reason} onChange={(e) => setReason(e.target.value)}>
              {ISSUE_REASONS.map((r) => (
                <option key={r.code} value={r.code}>
                  {r.label}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Ajouter un article" htmlFor="search">
            <Input
              id="search"
              placeholder="Rechercher réf / nom…"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
            />
          </Field>
        </div>

        {term.trim().length >= 1 && results.length > 0 ? (
          <div className="max-h-48 overflow-y-auto rounded border border-line">
            {results.map((p) => (
              <button
                key={p.id}
                type="button"
                onClick={() => addLine(p)}
                className="flex w-full items-center justify-between border-b border-line px-3 py-2 text-left text-sm last:border-0 hover:bg-bg"
              >
                <span>{p.name}</span>
                <span className="mono text-xs text-faint">{p.sku}</span>
              </button>
            ))}
          </div>
        ) : null}

        {lines.length > 0 ? (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-3 py-2 font-medium">Article</th>
                <th className="px-3 py-2 font-medium">Quantité</th>
                <th className="px-3 py-2 font-medium">Note</th>
                <th className="px-3 py-2" />
              </tr>
            </thead>
            <tbody>
              {lines.map((l, i) => (
                <tr key={l.product_id} className="border-b border-line last:border-0">
                  <td className="px-3 py-2 text-ink">
                    <span className="mono text-xs text-faint">{l.sku}</span> {l.name}
                  </td>
                  <td className="px-3 py-2">
                    <Input
                      type="number"
                      min={1}
                      value={l.quantity}
                      onChange={(e) =>
                        setLines((prev) => prev.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))
                      }
                      className="w-24"
                    />
                  </td>
                  <td className="px-3 py-2">
                    <Input
                      value={l.note ?? ''}
                      onChange={(e) => setLines((prev) => prev.map((x, j) => (j === i ? { ...x, note: e.target.value } : x)))}
                    />
                  </td>
                  <td className="px-3 py-2 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-bad hover:bg-bad-bg"
                      onClick={() => setLines((prev) => prev.filter((_, j) => j !== i))}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <p className="text-sm text-muted">Aucune ligne. Recherchez un article ci-dessus pour l'ajouter.</p>
        )}

        <div className="flex gap-2">
          <Button onClick={submit} disabled={issueMutation.isPending || lines.length === 0 || warehouseId === null}>
            <Plus className="h-4 w-4" />
            {issueMutation.isPending ? 'Validation…' : 'Valider le bon de sortie'}
          </Button>
        </div>
      </CardBody>
    </Card>
  )
}
