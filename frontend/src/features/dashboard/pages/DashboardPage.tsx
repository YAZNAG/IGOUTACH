import { Boxes, Building2, Layers, Package } from 'lucide-react'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatNumber } from '@/lib/utils'
import { StatTile } from '../components/StatTile'
import { useDashboard } from '../hooks'

export function DashboardPage() {
  const { data, isLoading, isError } = useDashboard()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Vue globale</h1>
        <p className="text-sm text-muted">Stock consolidé, tous lieux confondus.</p>
      </div>

      {isError ? (
        <Card className="p-5">
          <p className="text-sm text-bad">Impossible de charger la vue globale.</p>
        </Card>
      ) : null}

      {isLoading || !data ? (
        <p className="text-sm text-muted">Chargement…</p>
      ) : (
        <>
          <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatTile label="Lieux actifs" value={data.summary.warehouses} icon={Building2} tone="navy" />
            <StatTile label="Articles actifs" value={data.summary.products} icon={Package} tone="sky" />
            <StatTile label="Unités en stock" value={data.summary.total_units} icon={Boxes} tone="ok" />
            <StatTile
              label="Références en stock"
              value={data.summary.distinct_in_stock}
              icon={Layers}
              tone="warn"
            />
          </div>

          <Card>
            <CardHeader title="Stock consolidé par article" hint="Quantités additionnées sur tous les lieux." />
            <CardBody className="p-0">
              <table className="w-full text-sm">
                <thead>
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
                        <td className="tabular px-5 py-3 font-medium text-ink">
                          {formatNumber(row.total_quantity)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </CardBody>
          </Card>
        </>
      )}
    </div>
  )
}
