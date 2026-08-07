import { useState } from 'react'
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatCompact, formatCurrency, formatNumber } from '@/lib/utils'
import { useProductStatistics } from '../hooks'
import type { ProductDetail, StockDetail } from '../api/articlesApi'

interface StatisticsTabProps {
  product: ProductDetail
  stock: StockDetail
}

const PERIODES = [
  { id: '1m', label: '1 mois' },
  { id: '3m', label: '3 mois' },
  { id: '6m', label: '6 mois' },
  { id: '12m', label: '12 mois' },
]

function KpiCard({
  label,
  value,
  hint,
  tone,
}: {
  label: string
  value: string
  hint?: string
  tone?: 'ok' | 'warn' | 'bad' | 'sky'
}) {
  const couleur =
    tone === 'ok'
      ? 'text-ok'
      : tone === 'warn'
        ? 'text-warn'
        : tone === 'bad'
          ? 'text-bad'
          : tone === 'sky'
            ? 'text-sky'
            : 'text-ink'

  return (
    <Card>
      <CardBody>
        <p className="text-xs uppercase tracking-wide text-muted">{label}</p>
        <p className={`mono mt-2 text-2xl font-semibold ${couleur}`}>{value}</p>
        {hint ? <p className="mt-1 text-xs text-faint">{hint}</p> : null}
      </CardBody>
    </Card>
  )
}

export function StatisticsTab({ product, stock }: StatisticsTabProps) {
  const [periode, setPeriode] = useState('12m')
  const { data: stats, isLoading } = useProductStatistics(product.id, periode)

  if (isLoading || !stats) {
    return <p className="py-10 text-center text-sm text-muted">Chargement des statistiques…</p>
  }

  const aVendu = stats.sales_volume > 0
  const valeurStock = Number(stock.total_valuation || 0)

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-muted">Activité commerciale de l'article sur la période choisie.</p>
        <div className="flex gap-1">
          {PERIODES.map((p) => (
            <button
              key={p.id}
              type="button"
              onClick={() => setPeriode(p.id)}
              className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                periode === p.id ? 'bg-sky-soft text-navy' : 'text-muted hover:bg-bg hover:text-ink'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <KpiCard
          label="Quantité vendue"
          value={formatNumber(stats.sales_volume)}
          hint={`${formatNumber(stats.purchased_quantity)} reçue(s)`}
          tone="sky"
        />
        <KpiCard
          label="Chiffre d'affaires"
          value={formatCurrency(stats.revenue)}
          hint={aVendu ? `Prix moyen ${formatCurrency(stats.average_sale_price)}` : undefined}
          tone="ok"
        />
        <KpiCard
          label="Marge brute"
          value={formatCurrency(stats.gross_margin)}
          hint={aVendu ? `${stats.margin_percent} % du chiffre d'affaires` : undefined}
          tone={stats.gross_margin < 0 ? 'bad' : 'ok'}
        />
        <KpiCard
          label="Valeur en stock"
          value={formatCurrency(valeurStock)}
          hint={`${formatNumber(stock.total_quantity)} unité(s)`}
        />
      </div>

      <Card>
        <CardHeader title="Ventes par mois" hint="Quantités vendues sur la période." />
        <CardBody>
          {!aVendu ? (
            <div className="flex h-[240px] items-center justify-center rounded-lg border border-dashed border-line">
              <p className="text-sm text-muted">Aucune vente sur la période.</p>
            </div>
          ) : (
            <div className="h-[240px] w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={stats.monthly} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
                  <CartesianGrid stroke="var(--line)" strokeDasharray="3 3" vertical={false} />
                  <XAxis dataKey="label" stroke="var(--faint)" fontSize={11} tickLine={false} axisLine={false} />
                  <YAxis
                    stroke="var(--faint)"
                    fontSize={11}
                    tickLine={false}
                    axisLine={false}
                    width={48}
                    tickFormatter={(v: number) => formatCompact(v)}
                  />
                  <Tooltip
                    cursor={{ fill: 'var(--sky-soft)', opacity: 0.5 }}
                    contentStyle={{
                      backgroundColor: 'var(--card)',
                      border: '1px solid var(--line-2)',
                      borderRadius: 'var(--radius)',
                      fontSize: '12px',
                      color: 'var(--ink)',
                    }}
                    formatter={(value, name) => [
                      name === 'revenue' ? formatCurrency(Number(value)) : formatNumber(Number(value)),
                      name === 'revenue' ? "Chiffre d'affaires" : 'Quantité',
                    ]}
                  />
                  <Bar dataKey="quantity" fill="var(--sky)" radius={[4, 4, 0, 0]} maxBarSize={32} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </CardBody>
      </Card>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Ventes par lieu" hint="Où l'article se vend le mieux." />
          <CardBody className="p-0">
            {stats.by_warehouse.length === 0 ? (
              <p className="py-10 text-center text-sm text-muted">Aucune vente sur la période.</p>
            ) : (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 text-right font-medium">Quantité</th>
                    <th className="px-5 py-3 text-right font-medium">Chiffre d'affaires</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.by_warehouse.map((row) => (
                    <tr key={row.warehouse} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        <span className="mono text-muted">{row.warehouse}</span>{' '}
                        <span className="text-ink">{row.name}</span>
                      </td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(row.quantity)}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatCurrency(row.revenue)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Meilleurs clients" hint="Les 5 premiers sur la période." />
          <CardBody className="p-0">
            {stats.top_customers.length === 0 ? (
              <p className="py-10 text-center text-sm text-muted">
                Aucun client identifié — ventes au comptoir uniquement.
              </p>
            ) : (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Client</th>
                    <th className="px-5 py-3 text-right font-medium">Quantité</th>
                    <th className="px-5 py-3 text-right font-medium">Chiffre d'affaires</th>
                  </tr>
                </thead>
                <tbody>
                  {stats.top_customers.map((row) => (
                    <tr key={row.customer} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-ink">{row.customer}</td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(row.quantity)}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatCurrency(row.revenue)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
