import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { StockDetail } from '../api/articlesApi'

interface StockTabProps {
  stock: StockDetail
}

export function StockTab({ stock }: StockTabProps) {
  const maxQuantity = Math.max(...stock.locations.map((l) => l.quantity), 1)

  return (
    <div className="space-y-6">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Total</p>
            <p className="mt-2 text-2xl font-semibold text-ink">{stock.total_quantity}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Disponible</p>
            <p className="mt-2 text-2xl font-semibold text-ok">{stock.total_available}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">Réservé</p>
            <p className="mt-2 text-2xl font-semibold text-warn">{stock.total_reserved}</p>
          </CardBody>
        </Card>
        <Card>
          <CardBody>
            <p className="text-xs uppercase tracking-wide text-muted">En transit</p>
            <p className="mt-2 text-2xl font-semibold text-sky">{stock.in_transit}</p>
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader title="Détail par lieu" />
        <CardBody className="p-0">
          {stock.locations.length === 0 ? (
            <p className="p-5 text-sm text-muted">Aucun stock en lieu.</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium text-right">Quantité</th>
                  <th className="px-5 py-3 font-medium text-right">Disponible</th>
                  <th className="px-5 py-3 font-medium text-right">Réservé</th>
                  <th className="px-5 py-3 font-medium text-right">Valeur</th>
                </tr>
              </thead>
              <tbody>
                {stock.locations.map((loc) => (
                  <tr key={loc.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 font-medium text-ink">{loc.warehouse_name}</td>
                    <td className="px-5 py-3 text-right">
                      <div className="mb-1 flex justify-end">
                        <span className="font-medium text-ink">{loc.quantity}</span>
                      </div>
                      <div className="h-1.5 w-20 rounded-full bg-bg" style={{ marginLeft: 'auto' }}>
                        <div
                          className="h-1.5 rounded-full bg-sky"
                          style={{
                            width: `${(loc.quantity / maxQuantity) * 100}%`,
                          }}
                        />
                      </div>
                    </td>
                    <td className="px-5 py-3 text-right text-ok">{loc.available}</td>
                    <td className="px-5 py-3 text-right text-warn">{loc.reserved}</td>
                    <td className="px-5 py-3 text-right text-muted">
                      {Number(loc.valuation || 0).toLocaleString('fr-FR')} MAD
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Valeur du stock" />
        <CardBody>
          <p className="text-2xl font-semibold text-ink">
            {Number(stock.total_valuation || 0).toLocaleString('fr-FR')} MAD
          </p>
        </CardBody>
      </Card>
    </div>
  )
}
