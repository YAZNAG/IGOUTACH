import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { ProductDetail, StockDetail } from '../api/articlesApi'

interface StatisticsTabProps {
  product: ProductDetail
  stock: StockDetail
}

function KpiCard({ label, value, tone }: { label: string; value: string | number; tone?: 'ok' | 'warn' | 'bad' | 'sky' }) {
  const colorClass =
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
        <p className={`mt-2 text-2xl font-semibold ${colorClass}`}>{value}</p>
      </CardBody>
    </Card>
  )
}

export function StatisticsTab({ product, stock }: StatisticsTabProps) {
  const costPrice = Number(product.cost_price || 0)
  const salePrice = Number(product.sale_price || 0)
  const stockValue = Number(stock.total_valuation || 0)
  const margin = costPrice > 0 ? ((salePrice - costPrice) / costPrice) * 100 : 0
  const stockCover = costPrice > 0 ? stockValue / costPrice : 0

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <KpiCard label="Stock total" value={stock.total_quantity} />
        <KpiCard label="Disponible" value={stock.total_available} tone="ok" />
        <KpiCard label="Réservé" value={stock.total_reserved} tone="warn" />
        <KpiCard label="En transit" value={stock.in_transit} tone="sky" />
        <KpiCard label="Valeur du stock" value={`${stockValue.toLocaleString('fr-FR')} MAD`} />
        <KpiCard label="Nombre de lieux" value={stock.locations.length} />
        <KpiCard label="Marge brute" value={`${margin.toFixed(1)}%`} tone="sky" />
        <KpiCard label="Couverture stock" value={`${stockCover.toFixed(1)}j`} />
        <KpiCard label="Taux TVA" value={`${Number(product.tax_rate || 0)}%`} />
      </div>

      <Card>
        <CardHeader title="Répartition par lieu" />
        <CardBody>
          <div className="space-y-3">
            {stock.locations.map((loc) => {
              const percentage = stock.total_quantity > 0
                ? (loc.quantity / stock.total_quantity) * 100
                : 0

              return (
                <div key={loc.id}>
                  <div className="mb-1 flex items-center justify-between text-sm">
                    <span className="text-ink">{loc.warehouse_name}</span>
                    <span className="font-medium text-ink">{percentage.toFixed(1)}%</span>
                  </div>
                  <div className="h-2 w-full rounded-full bg-bg">
                    <div
                      className="h-2 rounded-full bg-sky transition-all"
                      style={{ width: `${percentage}%` }}
                    />
                  </div>
                </div>
              )
            })}
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Analyse" />
        <CardBody className="space-y-2 text-sm">
          {stock.total_quantity === 0 && (
            <p className="text-warn">Attention : Aucun stock disponible.</p>
          )}
          {stock.total_reserved > stock.total_available && (
            <p className="text-bad">Alerte : Les réservations dépassent le stock disponible.</p>
          )}
          {product.min_stock && stock.total_quantity < product.min_stock && (
            <p className="text-bad">Alerte : Stock sous le seuil minimum ({product.min_stock}).</p>
          )}
          {margin < 0 && (
            <p className="text-bad">Attention : Marge négative ({margin.toFixed(1)}%).</p>
          )}
          {margin > 0 && margin < 20 && (
            <p className="text-warn">Attention : Marge faible ({margin.toFixed(1)}%).</p>
          )}
          {stock.locations.length === 1 && (
            <p className="text-sky">Article stocké sur un seul lieu.</p>
          )}
          {!product.is_active && (
            <p className="text-muted">Article inactif : Aucun mouvement ne peut être généré.</p>
          )}
          {product.is_serialized && (
            <p className="text-sky">Article sérialisé : Les numéros de série doivent être gérés.</p>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
