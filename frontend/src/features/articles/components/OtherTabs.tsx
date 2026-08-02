import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { ProductDetail } from '../api/articlesApi'

interface OtherTabsProps {
  product: ProductDetail
  activeTab: string
}

export function OtherTabs({ product, activeTab }: OtherTabsProps) {
  if (activeTab === 'specs') {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader title="Caractéristiques techniques" />
          <CardBody className="space-y-4 text-sm">
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Référence</p>
              <p className="mt-1 font-medium text-ink mono">{product.sku}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Code-barres</p>
              <p className="mt-1 font-medium text-ink mono">{product.barcode || '—'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Sérialisé</p>
              <p className="mt-1 text-ink">{product.is_serialized ? 'Oui' : 'Non'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Seuil minimum</p>
              <p className="mt-1 text-ink">{product.min_stock ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">Actif</p>
              <p className="mt-1 text-ink">{product.is_active ? 'Oui' : 'Non'}</p>
            </div>
          </CardBody>
        </Card>
      </div>
    )
  }

  if (activeTab === 'media') {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader title="Médias" />
          <CardBody className="py-12 text-center text-muted">
            Aucun média associé.
          </CardBody>
        </Card>
      </div>
    )
  }

  if (activeTab === 'serials') {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader title="Numéros de série" />
          <CardBody className={product.is_serialized ? "space-y-4" : "py-12 text-center text-muted"}>
            {product.is_serialized ? (
              <p className="text-sm text-muted">Liste des numéros de série en stock.</p>
            ) : (
              "Cet article n'est pas sérialisé."
            )}
          </CardBody>
        </Card>
      </div>
    )
  }

  if (activeTab === 'suppliers') {
    return (
      <div className="space-y-4">
        <Card>
          <CardHeader title="Fournisseurs" />
          <CardBody className="py-12 text-center text-muted">
            Aucun fournisseur lié.
          </CardBody>
        </Card>
      </div>
    )
  }

  return null
}
