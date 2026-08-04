import { ChevronLeft, ChevronRight, Download, Eye, FileText, X } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import { fetchStockExits, stockExitsExportUrl, type StockExitFilters, type StockExitList } from '../api/stockExitsApi'

const TYPE_BADGES: Record<string, { label: string; tone: 'ok' | 'sky' | 'warn' | 'bad' | 'neutral' }> = {
  out: { label: 'Vente / Sortie', tone: 'bad' },
  transfer_out: { label: 'Transfert expédié', tone: 'sky' },
  adjustment: { label: 'Régularisation', tone: 'warn' },
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function StockExitsPage() {
  const navigate = useNavigate()

  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [warehouseId, setWarehouseId] = useState(0)
  const [type, setType] = useState('')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [exporting, setExporting] = useState<'xlsx' | 'pdf' | null>(null)

  const filters: StockExitFilters = {
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    warehouse_id: warehouseId || undefined,
    type: type || undefined,
    search: search || undefined,
    page,
  }

  const { data: list, isLoading } = useQuery<StockExitList>({
    queryKey: ['stock-exits', filters],
    queryFn: () => fetchStockExits(filters),
  })
  const { data: warehouses = [] } = useWarehouseOptions()

  const rows = list?.data ?? []
  const totals = list?.totals
  const meta = list?.meta

  const hasActiveFilters = dateFrom || dateTo || warehouseId || type || search

  async function handleExport(format: 'xlsx' | 'pdf') {
    setExporting(format)
    try {
      const suffix = dateFrom || dateTo ? `_${dateFrom || 'debut'}_${dateTo || 'fin'}` : ''
      await downloadFile(stockExitsExportUrl(format, filters), `IGOUTECH_sorties-stock${suffix}.${format}`)
    } finally {
      setExporting(null)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Sorties de stock</h1>
          <p className="text-sm text-muted">
            Un mouvement par ligne : ventes, transferts expédiés, régularisations négatives — valorisés au CMUP.
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

      <Card>
        <CardHeader title="Filtres" hint="La période porte sur la date du mouvement, pas la date de saisie." />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <Field label="Du" htmlFor="sx-from">
              <Input id="sx-from" type="date" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1) }} />
            </Field>
            <Field label="Au" htmlFor="sx-to">
              <Input id="sx-to" type="date" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1) }} />
            </Field>
            <Field label="Lieu" htmlFor="sx-warehouse">
              <Select id="sx-warehouse" value={warehouseId} onChange={(e) => { setWarehouseId(Number(e.target.value)); setPage(1) }}>
                <option value={0}>Tous</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
                ))}
              </Select>
            </Field>
            <Field label="Type de sortie" htmlFor="sx-type">
              <Select id="sx-type" value={type} onChange={(e) => { setType(e.target.value); setPage(1) }}>
                <option value="">Tous</option>
                {Object.entries(TYPE_BADGES).map(([code, { label }]) => (
                  <option key={code} value={code}>{label}</option>
                ))}
              </Select>
            </Field>
            <Field label="Article" htmlFor="sx-search">
              <Input
                id="sx-search"
                placeholder="Référence ou nom…"
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1) }}
              />
            </Field>
          </div>

          {hasActiveFilters ? (
            <Button
              variant="outline"
              size="sm"
              className="text-muted"
              onClick={() => { setDateFrom(''); setDateTo(''); setWarehouseId(0); setType(''); setSearch(''); setPage(1) }}
            >
              <X className="h-4 w-4" />
              Réinitialiser les filtres
            </Button>
          ) : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Sorties"
          hint={totals ? `${totals.lines_count} ligne(s) · ${formatNumber(totals.total_quantity)} unités · ${formatMoney(totals.total_value)} DH` : undefined}
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : rows.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted">Aucune sortie de stock sur la période.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-3 font-medium">Date</th>
                    <th className="px-4 py-3 font-medium">Type</th>
                    <th className="px-4 py-3 font-medium">Document</th>
                    <th className="px-4 py-3 font-medium">Lieu</th>
                    <th className="px-4 py-3 font-medium">Article</th>
                    <th className="px-4 py-3 text-right font-medium">Qté</th>
                    <th className="px-4 py-3 text-right font-medium">CMUP (DH)</th>
                    <th className="px-4 py-3 text-right font-medium">Valeur</th>
                    <th className="px-4 py-3 text-right font-medium">Solde après</th>
                    <th className="px-4 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => {
                    const badge = TYPE_BADGES[row.type.code ?? ''] ?? {
                      label: row.type.name ?? '—',
                      tone: 'neutral' as const,
                    }
                    return (
                      <tr key={row.id} className="border-b border-line last:border-0">
                        <td className="px-4 py-3 text-muted">
                          {row.date
                            ? new Date(row.date).toLocaleDateString('fr-FR', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                              })
                            : '—'}
                        </td>
                        <td className="px-4 py-3">
                          <Badge tone={badge.tone}>{badge.label}</Badge>
                        </td>
                        <td className="px-4 py-3">
                          {row.source?.type === 'sale' && row.source.id ? (
                            <span className="mono text-sky">{row.source.label}</span>
                          ) : (
                            <span className="text-muted">{row.source?.label ?? '—'}</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-muted">{row.warehouse.code ?? '—'}</td>
                        <td className="px-4 py-3">
                          {row.product.id ? (
                            <Link to={`/articles/${row.product.id}`} className="hover:underline">
                              <span className="mono text-xs text-muted">{row.product.sku}</span>{' '}
                              <span className="text-ink">{row.product.name}</span>
                            </Link>
                          ) : (
                            '—'
                          )}
                        </td>
                        <td className="tabular px-4 py-3 text-right font-medium text-bad">
                          −{formatNumber(row.quantity)}
                          {row.product.unit ? <span className="ml-1 text-xs text-muted">{row.product.unit}</span> : null}
                        </td>
                        <td className="tabular px-4 py-3 text-right text-muted">{formatMoney(row.unit_cost)}</td>
                        <td className="tabular px-4 py-3 text-right font-medium text-ink">{formatMoney(row.line_value)}</td>
                        <td className="tabular px-4 py-3 text-right text-muted">{formatNumber(row.balance_after)}</td>
                        <td className="px-4 py-3 text-right">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate(`/stock-exits/${row.id}`)}
                            title="Détail"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
                {totals ? (
                  <tfoot>
                    <tr className="border-t border-line-2 font-medium text-ink">
                      <td colSpan={5} className="px-4 py-3">
                        Total ({formatNumber(totals.lines_count)} lignes filtrées)
                      </td>
                      <td className="tabular px-4 py-3 text-right text-bad">−{formatNumber(totals.total_quantity)}</td>
                      <td />
                      <td className="tabular px-4 py-3 text-right">{formatMoney(totals.total_value)} DH</td>
                      <td colSpan={2} />
                    </tr>
                  </tfoot>
                ) : null}
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
