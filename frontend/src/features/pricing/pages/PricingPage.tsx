import { AlertTriangle, Calculator, Download, FileText, Percent } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { usePermission } from '@/hooks/usePermission'
import { formatNumber } from '@/lib/utils'
import { exportPrices, type PriceCell, type PriceLevelInput, type PriceListItem } from '../api/pricingApi'
import { BulkMarginPanel } from '../components/BulkMarginPanel'
import { BulkUpdatePanel } from '../components/BulkUpdatePanel'
import { PriceLevelsForm } from '../components/PriceLevelsForm'
import { useBelowFloor, usePriceList, usePricingCategories, useProductPrices, useUpdateProductPrices } from '../hooks'

function PriceValue({ cell }: { cell: PriceCell }) {
  if (cell.amount === null) {
    return <span className="text-xs text-faint">non défini</span>
  }
  return (
    <div>
      <span className="tabular font-medium text-ink">{formatNumber(cell.amount)} DH</span>
      <span className="ml-1 text-xs text-muted">≥ {cell.min_quantity}</span>
    </div>
  )
}

export function PricingPage() {
  const can = usePermission()
  const canManage = can('price.manage')

  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState(0)
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(20)
  const [sort, setSort] = useState<SortState>({ sort: 'name', direction: 'asc' })
  const [editing, setEditing] = useState<PriceListItem | null>(null)
  const [showBulk, setShowBulk] = useState(false)
  const [showMargin, setShowMargin] = useState(false)
  const [showAlerts, setShowAlerts] = useState(false)

  const { data: categories = [] } = usePricingCategories()
  const { data, isLoading } = usePriceList({
    search: search || undefined,
    category_id: categoryId || undefined,
    page,
    per_page: perPage,
    sort: sort.sort,
    direction: sort.direction,
  })
  const pricesQuery = useProductPrices(editing?.id ?? null)
  const updateMutation = useUpdateProductPrices()

  const items = data?.data ?? []
  const meta = data?.meta
  const categoryName = (id: number) => categories.find((c) => c.id === id)?.name ?? '—'
  const belowFloor = useBelowFloor(showAlerts)

  function handleSubmit(prices: PriceLevelInput[]) {
    if (!editing) return
    updateMutation.mutate({ productId: editing.id, prices }, { onSuccess: () => setEditing(null) })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Tarifs de vente</h1>
          <p className="text-sm text-muted">Trois niveaux : détail, demi-gros, gros — quantités configurables par article.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => setShowAlerts((v) => !v)}>
            <AlertTriangle className="h-4 w-4" />
            Alertes marge
          </Button>
          {can('price.bulk_update') ? (
            <Button variant="outline" size="sm" onClick={() => { setShowBulk((v) => !v); setShowMargin(false) }}>
              <Percent className="h-4 w-4" />
              MAJ en masse
            </Button>
          ) : null}
          {can('price.bulk_update') ? (
            <Button variant="outline" size="sm" onClick={() => { setShowMargin((v) => !v); setShowBulk(false) }}>
              <Calculator className="h-4 w-4" />
              Marges sur achat
            </Button>
          ) : null}
          <Button
            variant="outline"
            size="sm"
            onClick={() => exportPrices('xlsx', { search: search || undefined, category_id: categoryId || undefined })}
          >
            <Download className="h-4 w-4" />
            Excel
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => exportPrices('pdf', { search: search || undefined, category_id: categoryId || undefined })}
          >
            <FileText className="h-4 w-4" />
            PDF
          </Button>
        </div>
      </div>

      {showBulk ? <BulkUpdatePanel categories={categories} onClose={() => setShowBulk(false)} /> : null}

      {showMargin ? <BulkMarginPanel categories={categories} onClose={() => setShowMargin(false)} /> : null}

      {showAlerts ? (
        <Card>
          <CardHeader title="Prix sous le plancher de marge" hint={belowFloor.data ? `${belowFloor.data.length} alerte(s)` : undefined} />
          <CardBody className="p-0">
            {belowFloor.isLoading ? (
              <p className="p-5 text-sm text-muted">Chargement…</p>
            ) : (belowFloor.data ?? []).length === 0 ? (
              <p className="p-5 text-sm text-muted">Aucun prix sous le plancher — marges respectées.</p>
            ) : (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Référence</th>
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 font-medium">Niveau</th>
                    <th className="px-5 py-3 text-right font-medium">Prix actuel</th>
                  </tr>
                </thead>
                <tbody>
                  {(belowFloor.data ?? []).map((r) => (
                    <tr key={`${r.product_id}-${r.price_type}`} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                      <td className="px-5 py-3 text-ink">{r.name}</td>
                      <td className="px-5 py-3"><Badge tone="warn">{r.price_type}</Badge></td>
                      <td className="tabular px-5 py-3 text-right font-medium text-bad">{formatNumber(Number(r.amount))} DH</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>
      ) : null}

      {editing ? (
        <Card>
          <CardHeader title={`Tarifs — ${editing.sku} · ${editing.name}`} />
          <CardBody>
            {updateMutation.isError && (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                Enregistrement impossible (ordre des prix invalide).
              </p>
            )}
            {pricesQuery.isLoading || !pricesQuery.data ? (
              <p className="text-sm text-muted">Chargement des tarifs…</p>
            ) : (
              <PriceLevelsForm
                data={pricesQuery.data}
                isPending={updateMutation.isPending}
                onSubmit={handleSubmit}
                onCancel={() => {
                  setEditing(null)
                  updateMutation.reset()
                }}
              />
            )}
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader
          title="Articles"
          hint={meta ? `${meta.total} article(s)` : undefined}
          action={
            <div className="flex gap-2">
              <Select
                value={perPage}
                onChange={(e) => {
                  setPerPage(Number(e.target.value))
                  setPage(1)
                }}
                className="w-28"
                aria-label="Articles par page"
              >
                <option value={20}>20 / page</option>
                <option value={50}>50 / page</option>
                <option value={100}>100 / page</option>
              </Select>
              <Select
                value={categoryId}
                onChange={(e) => {
                  setCategoryId(Number(e.target.value))
                  setPage(1)
                }}
                className="w-44"
              >
                <option value={0}>Toutes catégories</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </Select>
              <Input
                placeholder="Rechercher…"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
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
                  <SortableTh field="sku" current={sort} onSort={setSort}>Référence</SortableTh>
                  <SortableTh field="name" current={sort} onSort={setSort}>Article</SortableTh>
                  <th className="px-5 py-3 font-medium">Catégorie</th>
                  <th className="px-5 py-3 font-medium">Détail</th>
                  <th className="px-5 py-3 font-medium">Demi-gros</th>
                  <th className="px-5 py-3 font-medium">Gros</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-5 py-8 text-center text-muted">
                      Aucun article.
                    </td>
                  </tr>
                ) : (
                  items.map((item) => (
                    <tr key={item.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{item.sku}</td>
                      <td className="px-5 py-3 text-ink">{item.name}</td>
                      <td className="px-5 py-3">
                        <Badge tone="sky">{categoryName(item.category_id)}</Badge>
                      </td>
                      <td className="px-5 py-3">
                        <PriceValue cell={item.prices.detail} />
                      </td>
                      <td className="px-5 py-3">
                        <PriceValue cell={item.prices.semi_gros} />
                      </td>
                      <td className="px-5 py-3">
                        <PriceValue cell={item.prices.gros} />
                      </td>
                      {canManage ? (
                        <td className="px-5 py-3 text-right">
                          <Button variant="ghost" size="sm" onClick={() => setEditing(item)}>
                            Modifier
                          </Button>
                        </td>
                      ) : null}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      {meta ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <span>{paginationInfo(meta)}</span>
          {meta.last_page > 1 ? (
            <div className="flex items-center gap-2">
              <span>
                Page {meta.current_page} / {meta.last_page}
              </span>
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                Précédent
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  )
}
