import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ChevronRight, Download, FileText, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { api } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import type { Category } from '@/types'
import { usePricingCategories } from '../hooks'

interface CostRow {
  id: number
  sku: string
  name: string
  category: string | null
  total_quantity: number
  cmup: number
  stock_value: number
  last_purchase_price: number | null
  last_purchase_at: string | null
  detail_price: number | null
  margin_percent: number | null
  below_cost: boolean
}

interface CostList {
  data: CostRow[]
  totals: { total_quantity: number; total_value: number }
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/**
 * Coûts des articles : CMUP global (moyenne pondérée de tous les lieux),
 * valeur de stock, dernier prix d'achat et marge du prix détail.
 */
export function ProductCostsPage() {
  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState(0)
  const [page, setPage] = useState(1)
  const [exporting, setExporting] = useState<'xlsx' | 'pdf' | null>(null)

  const { data: categories = [] } = usePricingCategories()

  const filters = {
    search: search || undefined,
    category_id: categoryId || undefined,
    page,
  }

  const { data: list, isLoading } = useQuery<CostList>({
    queryKey: ['product-costs', filters],
    queryFn: async () => {
      const { data } = await api.get<CostList>('/product-costs', { params: filters })
      return data
    },
  })

  const rows = list?.data ?? []
  const totals = list?.totals
  const meta = list?.meta

  async function handleExport(format: 'xlsx' | 'pdf') {
    setExporting(format)
    try {
      const params = new URLSearchParams({ format })
      if (search) params.set('search', search)
      if (categoryId) params.set('category_id', String(categoryId))
      await downloadFile(`/product-costs/export?${params.toString()}`, `IGOUTECH_couts-articles.${format}`)
    } finally {
      setExporting(null)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Coûts des articles</h1>
          <p className="text-sm text-muted">
            CMUP global (moyenne pondérée de tous les lieux), recalculé automatiquement à chaque réception.
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => handleExport('xlsx')} disabled={exporting !== null}>
            <Download className="h-4 w-4" />
            {exporting === 'xlsx' ? 'Export…' : 'Excel'}
          </Button>
          <Button variant="outline" size="sm" onClick={() => handleExport('pdf')} disabled={exporting !== null}>
            <FileText className="h-4 w-4" />
            {exporting === 'pdf' ? 'Export…' : 'PDF'}
          </Button>
        </div>
      </div>

      {/* Synthèse */}
      <div className="grid gap-4 sm:grid-cols-2">
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Unités en stock (ensemble filtré)</p>
            <p className="text-2xl font-semibold text-ink">{formatNumber(totals?.total_quantity ?? 0)}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Valeur totale du stock (au CMUP)</p>
            <p className="text-2xl font-semibold text-ink">{formatMoney(totals?.total_value ?? 0)} DH</p>
          </CardBody>
        </Card>
      </div>

      {/* Filtres */}
      <Card>
        <CardHeader title="Filtres" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Field label="Article" htmlFor="cost-search">
              <Input
                id="cost-search"
                placeholder="Référence ou nom…"
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1) }}
              />
            </Field>
            <Field label="Catégorie" htmlFor="cost-category">
              <Select id="cost-category" value={categoryId} onChange={(e) => { setCategoryId(Number(e.target.value)); setPage(1) }}>
                <option value={0}>Toutes</option>
                {categories.map((c: Category) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </Select>
            </Field>
          </div>
          {search || categoryId ? (
            <Button
              variant="outline"
              size="sm"
              className="mt-4 text-muted"
              onClick={() => { setSearch(''); setCategoryId(0); setPage(1) }}
            >
              <X className="h-4 w-4" />
              Réinitialiser
            </Button>
          ) : null}
        </CardBody>
      </Card>

      {/* Tableau */}
      <Card>
        <CardHeader title="Articles" hint={meta ? `${meta.total} article(s)` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : rows.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted">Aucun article trouvé.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-3 font-medium">Référence</th>
                    <th className="px-4 py-3 font-medium">Article</th>
                    <th className="px-4 py-3 font-medium">Catégorie</th>
                    <th className="px-4 py-3 text-right font-medium">Stock total</th>
                    <th className="px-4 py-3 text-right font-medium">CMUP (DH)</th>
                    <th className="px-4 py-3 text-right font-medium">Valeur stock</th>
                    <th className="px-4 py-3 text-right font-medium">Dernier achat</th>
                    <th className="px-4 py-3 text-right font-medium">Prix détail</th>
                    <th className="px-4 py-3 text-right font-medium">Marge</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.id} className="border-b border-line last:border-0">
                      <td className="mono px-4 py-3 text-muted">
                        <Link to={`/articles/${row.id}`} className="hover:text-sky hover:underline">{row.sku}</Link>
                      </td>
                      <td className="px-4 py-3 text-ink">{row.name}</td>
                      <td className="px-4 py-3 text-muted">{row.category ?? '—'}</td>
                      <td className="tabular px-4 py-3 text-right text-muted">{formatNumber(row.total_quantity)}</td>
                      <td className="tabular px-4 py-3 text-right font-semibold text-ink">{formatMoney(row.cmup)}</td>
                      <td className="tabular px-4 py-3 text-right text-ink">{formatMoney(row.stock_value)}</td>
                      <td className="tabular px-4 py-3 text-right text-muted">
                        {row.last_purchase_price !== null ? (
                          <>
                            {formatMoney(row.last_purchase_price)}
                            {row.last_purchase_at ? (
                              <span className="block text-xs text-faint">
                                {new Date(row.last_purchase_at).toLocaleDateString('fr-FR')}
                              </span>
                            ) : null}
                          </>
                        ) : (
                          '—'
                        )}
                      </td>
                      <td className="tabular px-4 py-3 text-right text-muted">
                        {row.detail_price !== null ? formatMoney(row.detail_price) : '—'}
                      </td>
                      <td className="px-4 py-3 text-right">
                        {row.margin_percent === null ? (
                          <span className="text-muted">—</span>
                        ) : row.below_cost ? (
                          <Badge tone="bad">{row.margin_percent} % — à perte !</Badge>
                        ) : row.margin_percent < 10 ? (
                          <Badge tone="warn">{row.margin_percent} %</Badge>
                        ) : (
                          <Badge tone="ok">{row.margin_percent} %</Badge>
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

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <div>Page {meta.current_page} / {meta.last_page}</div>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
              <ChevronLeft className="h-4 w-4" />
              Précédent
            </Button>
            <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
              Suivant
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  )
}
