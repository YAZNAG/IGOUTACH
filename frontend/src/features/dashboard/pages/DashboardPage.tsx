import { Boxes, HandCoins, TrendingUp, Wallet } from 'lucide-react'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatCurrency, formatNumber } from '@/lib/utils'
import { ChartCard } from '../components/ChartCard'
import { MonthlyFlowChart } from '../components/MonthlyFlowChart'
import { PaymentMixChart } from '../components/PaymentMixChart'
import { SalesTrendChart } from '../components/SalesTrendChart'
import { StatTile } from '../components/StatTile'
import { StockByWarehouseChart } from '../components/StockByWarehouseChart'
import { useDashboard } from '../hooks'

function SkeletonBlock({ className }: { className?: string }) {
  return <div className={`animate-pulse rounded-lg bg-line ${className ?? ''}`} />
}

export function DashboardPage() {
  const { data, isLoading, isError } = useDashboard()

  if (isError) {
    return (
      <Card className="p-5">
        <p className="text-sm text-bad">Impossible de charger la vue globale.</p>
      </Card>
    )
  }

  if (isLoading || !data) {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          {Array.from({ length: 4 }, (_, i) => (
            <SkeletonBlock key={i} className="h-[92px]" />
          ))}
        </div>
        <div className="grid gap-4 lg:grid-cols-3">
          <SkeletonBlock className="h-[340px] lg:col-span-2" />
          <SkeletonBlock className="h-[340px]" />
        </div>
      </div>
    )
  }

  const revenue30 = data.sales_trend.reduce((sum, point) => sum + point.revenue, 0)
  const sales30 = data.sales_trend.reduce((sum, point) => sum + point.count, 0)
  const hasTrend = revenue30 > 0
  const hasFlow = data.monthly_flow.some((point) => point.sales > 0 || point.purchases > 0)
  const hasMix = data.payment_mix.some((row) => row.amount > 0)
  const topRevenue = data.top_products[0]?.revenue ?? 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Vue globale</h1>
        <p className="text-sm text-muted">Activité consolidée, tous lieux confondus.</p>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile
          label="CA du mois"
          value={data.financial.revenue_month}
          icon={TrendingUp}
          tone="ok"
          currency
          hint={`${formatNumber(data.financial.sales_month)} vente(s) facturée(s)`}
        />
        <StatTile
          label="Valeur du stock"
          value={data.financial.stock_value}
          icon={Wallet}
          tone="navy"
          currency
          hint="Valorisée au coût moyen"
        />
        <StatTile
          label="Encours clients"
          value={data.financial.outstanding}
          icon={HandCoins}
          tone={data.financial.outstanding > 0 ? 'warn' : 'ok'}
          currency
          hint="Crédits restant à recouvrer"
        />
        <StatTile
          label="Unités en stock"
          value={data.summary.total_units}
          icon={Boxes}
          tone="sky"
          hint={`${formatNumber(data.summary.distinct_in_stock)} référence(s)`}
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <ChartCard
            title="Chiffre d'affaires"
            hint="30 derniers jours, ventes facturées."
            isEmpty={!hasTrend}
            emptyLabel="Aucune vente facturée sur les 30 derniers jours."
            action={
              hasTrend ? (
                <div className="text-right">
                  <p className="mono text-sm font-semibold text-ink">{formatCurrency(revenue30)}</p>
                  <p className="text-xs text-muted">{formatNumber(sales30)} vente(s)</p>
                </div>
              ) : null
            }
          >
            <SalesTrendChart data={data.sales_trend} />
          </ChartCard>
        </div>

        <ChartCard
          title="État des règlements"
          hint="Ventes des 30 derniers jours."
          isEmpty={!hasMix}
        >
          <PaymentMixChart data={data.payment_mix} />
        </ChartCard>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <ChartCard
          title="Ventes et achats"
          hint="6 mois glissants, en dirhams."
          isEmpty={!hasFlow}
        >
          <MonthlyFlowChart data={data.monthly_flow} />
        </ChartCard>

        <ChartCard
          title="Stock par lieu"
          hint="Valeur détenue par dépôt, point de vente ou véhicule."
          isEmpty={data.stock_by_warehouse.length === 0}
          emptyLabel="Aucun stock enregistré."
        >
          <StockByWarehouseChart data={data.stock_by_warehouse} />
        </ChartCard>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Meilleures ventes" hint="30 derniers jours, en chiffre d'affaires." />
          <CardBody className="space-y-3">
            {data.top_products.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted">Aucune vente sur la période.</p>
            ) : (
              data.top_products.map((product) => (
                <div key={product.name} className="space-y-1.5">
                  <div className="flex items-baseline justify-between gap-4">
                    <p className="truncate text-sm text-ink">{product.name}</p>
                    <p className="mono shrink-0 text-sm font-medium text-ink">
                      {formatCurrency(product.revenue)}
                    </p>
                  </div>
                  {/* La barre situe chaque article par rapport au meilleur vendeur. */}
                  <div className="h-1.5 overflow-hidden rounded-full bg-bg">
                    <div
                      className="h-full rounded-full bg-sky"
                      style={{
                        width: `${topRevenue > 0 ? (product.revenue / topRevenue) * 100 : 0}%`,
                      }}
                    />
                  </div>
                  <p className="text-xs text-faint">{formatNumber(product.quantity)} unité(s) vendue(s)</p>
                </div>
              ))
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            title="Stock consolidé par article"
            hint="20 premières références, tous lieux confondus."
          />
          <CardBody className="max-h-[360px] overflow-y-auto p-0">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-card">
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Article</th>
                  <th className="px-5 py-3 text-right font-medium">Quantité</th>
                </tr>
              </thead>
              <tbody>
                {data.stock.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-5 py-8 text-center text-muted">
                      Aucun stock enregistré pour le moment.
                    </td>
                  </tr>
                ) : (
                  data.stock.map((row) => (
                    <tr key={row.product_id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{row.sku}</td>
                      <td className="px-5 py-3 text-ink">{row.name}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatNumber(row.total_quantity)}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
