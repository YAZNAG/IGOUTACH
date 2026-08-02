import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { ProductDetail, StockDetail } from '../api/articlesApi'

interface PricingTabProps {
  product: ProductDetail
  stock: StockDetail
}

export function PricingTab({ product, stock }: PricingTabProps) {
  const costPrice = Number(product.cost_price || 0)
  const salePrice = Number(product.sale_price || 0)
  const margin = costPrice > 0 ? ((salePrice - costPrice) / costPrice) * 100 : 0
  const markup = costPrice > 0 ? ((salePrice - costPrice) / salePrice) * 100 : 0

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Prix de revient</p>
            <p className="mt-2 text-2xl font-semibold text-ink">
              {costPrice.toLocaleString('fr-FR')} MAD
            </p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Prix de vente</p>
            <p className="mt-2 text-2xl font-semibold text-ink">
              {salePrice.toLocaleString('fr-FR')} MAD
            </p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Marge brute</p>
            <p className="mt-2 text-2xl font-semibold text-sky">{margin.toFixed(1)}%</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Valeur du stock</p>
            <p className="mt-2 text-2xl font-semibold text-ink">
              {Number(stock.total_valuation || 0).toLocaleString('fr-FR')} MAD
            </p>
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader title="Tarif en vigueur" />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Désignation</th>
                <th className="px-5 py-3 font-medium text-right">Montant</th>
                <th className="px-5 py-3 font-medium text-right">% sur vente</th>
              </tr>
            </thead>
            <tbody>
              <tr className="border-b border-line">
                <td className="px-5 py-3 text-ink">Prix de revient</td>
                <td className="px-5 py-3 text-right font-medium text-ink">
                  {costPrice.toLocaleString('fr-FR')} MAD
                </td>
                <td className="px-5 py-3 text-right text-muted">100.0%</td>
              </tr>
              <tr className="border-b border-line">
                <td className="px-5 py-3 text-ink">Marge brute</td>
                <td className="px-5 py-3 text-right font-medium text-sky">
                  {(salePrice - costPrice).toLocaleString('fr-FR')} MAD
                </td>
                <td className="px-5 py-3 text-right text-sky">{markup.toFixed(1)}%</td>
              </tr>
              <tr>
                <td className="px-5 py-3 font-semibold text-ink">Prix de vente</td>
                <td className="px-5 py-3 text-right font-semibold text-ink">
                  {salePrice.toLocaleString('fr-FR')} MAD
                </td>
                <td className="px-5 py-3 text-right font-semibold text-ink">100.0%</td>
              </tr>
            </tbody>
          </table>
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Informations fiscales" />
        <CardBody className="space-y-3 text-sm">
          <div>
            <p className="text-xs uppercase tracking-wide text-muted">Taux de TVA</p>
            <p className="mt-1 font-medium text-ink">{Number(product.tax_rate || 0)}%</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-wide text-muted">Montant TVA</p>
            <p className="mt-1 font-medium text-ink">
              {(salePrice * (Number(product.tax_rate || 0) / 100)).toLocaleString('fr-FR')} MAD
            </p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-wide text-muted">Prix TTC</p>
            <p className="mt-1 font-medium text-ink">
              {(salePrice * (1 + Number(product.tax_rate || 0) / 100)).toLocaleString('fr-FR')} MAD
            </p>
          </div>
        </CardBody>
      </Card>
    </div>
  )
}
