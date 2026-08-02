import { Badge } from '@/components/ui/Badge'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { ProductDetail, StockDetail } from '../api/articlesApi'

interface OverviewTabProps {
  product: ProductDetail
  stock: StockDetail
}

export function OverviewTab({ product, stock }: OverviewTabProps) {
  return (
    <div className="grid gap-6 lg:grid-cols-3">
      {/* Galerie images */}
      <div className="lg:col-span-1">
        <Card>
          <CardBody className="flex flex-col items-center justify-center space-y-4 py-12">
            <div className="flex h-40 w-40 items-center justify-center rounded-lg bg-bg text-muted">
              <svg
                className="h-16 w-16 text-line"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fillRule="evenodd"
                  d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <p className="text-sm text-muted">Aucune image</p>
          </CardBody>
        </Card>
      </div>

      {/* Informations produit */}
      <div className="lg:col-span-1">
        <Card>
          <CardHeader title="Informations" />
          <CardBody className="space-y-3 text-sm">
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Description</p>
              <p className="mt-1 text-ink">{product.description || '—'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Catégorie</p>
              <p className="mt-1 text-ink">{product.category?.name || '—'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Marque</p>
              <p className="mt-1 text-ink">{product.brand?.name || '—'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Unité</p>
              <p className="mt-1 text-ink">
                {product.unit?.symbol || product.unit?.name || '—'}
              </p>
            </div>
            {product.is_serialized ? (
              <div className="mt-3 rounded border border-line bg-sky-soft px-2 py-1">
                <Badge tone="sky" className="text-xs">
                  Sérialisé
                </Badge>
              </div>
            ) : null}
          </CardBody>
        </Card>
      </div>

      {/* Résumé stock et prix */}
      <div className="lg:col-span-1 space-y-4">
        <Card>
          <CardHeader title="Stock par lieu" />
          <CardBody className="space-y-3 text-sm">
            {stock.locations && stock.locations.length > 0 ? (
              stock.locations.map((loc) => (
                <div key={loc.id}>
                  <p className="mb-1 font-medium text-ink">{loc.warehouse_name}</p>
                  <div className="space-y-1">
                    <div className="flex items-center justify-between text-xs text-muted">
                      <span>Total</span>
                      <span className="font-medium text-ink">{loc.quantity}</span>
                    </div>
                    <div className="flex items-center justify-between text-xs text-muted">
                      <span>Disponible</span>
                      <span className="font-medium text-ok">{loc.available}</span>
                    </div>
                    <div className="flex items-center justify-between text-xs text-muted">
                      <span>Réservé</span>
                      <span className="font-medium text-warn">{loc.reserved}</span>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <p className="text-muted">Aucun stock.</p>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Tarification" />
          <CardBody className="space-y-3 text-sm">
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Prix de revient</p>
              <p className="mt-1 font-semibold text-ink">
                {Number(product.cost_price || 0).toLocaleString('fr-FR')} MAD
              </p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Prix de vente</p>
              <p className="mt-1 font-semibold text-ink">
                {Number(product.sale_price || 0).toLocaleString('fr-FR')} MAD
              </p>
            </div>
            <div className="border-t border-line pt-3">
              <p className="text-xs uppercase tracking-wide text-muted">Marge</p>
              <p className="mt-1 font-semibold text-sky">
                {((Number(product.sale_price) - Number(product.cost_price)) / Number(product.cost_price) * 100).toFixed(1)}%
              </p>
            </div>
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
