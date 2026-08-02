import { ChevronDown, ChevronUp, Package } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { useBelowThresholdProducts } from '../hooks'
import type { ProductOption } from '../api/purchaseOrdersApi'

interface BelowThresholdSuggestionsProps {
  warehouseId: number
  onAddProduct: (product: ProductOption & { suggested_quantity: number }) => void
  exclude?: number[]
  isOpen?: boolean
  onToggle?: () => void
}

export function BelowThresholdSuggestions({
  warehouseId,
  onAddProduct,
  exclude = [],
  isOpen = false,
  onToggle,
}: BelowThresholdSuggestionsProps) {
  const { data: products = [], isLoading } = useBelowThresholdProducts(warehouseId)
  const [open, setOpen] = useState(isOpen)

  const visibleProducts = products.filter((p) => !exclude.includes(p.id))

  const handleToggle = () => {
    setOpen(!open)
    onToggle?.()
  }

  if (visibleProducts.length === 0) {
    return null
  }

  return (
    <div className="space-y-2">
      <button
        type="button"
        onClick={handleToggle}
        className="flex items-center justify-between w-full rounded-lg border border-line bg-card px-3 py-2 text-sm font-medium text-ink hover:bg-bg transition-colors"
      >
        <div className="flex items-center gap-2">
          <Package className="h-4 w-4" />
          Articles sous seuil ({visibleProducts.length})
        </div>
        {open ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
      </button>

      {open ? (
        <Card className="border-sky-200 dark:border-sky-800">
          <CardHeader title="Suggestions de réapprovisionnement" />
          <CardBody className="space-y-2 p-3">
            {isLoading ? (
              <p className="text-sm text-muted">Chargement…</p>
            ) : visibleProducts.length === 0 ? (
              <p className="text-sm text-muted">Aucun article sous seuil.</p>
            ) : (
              <div className="grid gap-2">
                {visibleProducts.map((product) => (
                  <div key={product.id} className="flex items-start justify-between gap-3 rounded border border-line p-2">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="mono text-xs font-medium text-muted">{product.sku}</span>
                        <span className="text-xs text-muted">
                          {product.current_stock} / {product.min_stock_alert}
                        </span>
                      </div>
                      <p className="text-sm text-ink truncate">{product.name}</p>
                      <p className="text-xs text-muted">
                        À commander: {product.suggested_quantity} unités
                      </p>
                    </div>
                    <Button
                      size="sm"
                      onClick={() =>
                        onAddProduct({
                          id: product.id,
                          sku: product.sku,
                          name: product.name,
                          barcode: null,
                          current_stock: product.current_stock,
                          min_stock_alert: product.min_stock_alert,
                          suggested_quantity: product.suggested_quantity,
                        })
                      }
                    >
                      Ajouter
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </CardBody>
        </Card>
      ) : null}
    </div>
  )
}
