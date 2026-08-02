import { ChevronLeft, ChevronRight, Copy, Edit, MoreVertical, Tag } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { usePermission } from '@/hooks/usePermission'
import type { ProductDetail, StockDetail } from '../api/articlesApi'

interface ProductDetailHeaderProps {
  product: ProductDetail
  stock: StockDetail
  onPrev?: () => void
  onNext?: () => void
  onEdit?: () => void
  onDuplicate?: () => void
  onLabel?: () => void
  hasPrev?: boolean
  hasNext?: boolean
}

function AnimatedKpi({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="flex flex-col items-center justify-center px-4">
      <p className="text-2xl font-semibold text-ink">{value}</p>
      <p className="text-xs uppercase tracking-wide text-muted">{label}</p>
    </div>
  )
}

export function ProductDetailHeader({
  product,
  stock,
  onPrev,
  onNext,
  onEdit,
  onDuplicate,
  onLabel,
  hasPrev,
  hasNext,
}: ProductDetailHeaderProps) {
  const can = usePermission()
  const canEdit = can('product.update')
  const canDuplicate = can('product.create')
  const canLabel = can('product.labels_print')
  const [menuOpen, setMenuOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false)
      }
    }
    if (menuOpen) document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [menuOpen])

  return (
    <div className="sticky top-0 z-40 space-y-3 border-b border-line bg-card px-5 py-4 shadow-sm">
      {/* Breadcrumb + Actions */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onPrev} disabled={!hasPrev}>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <div className="flex flex-col">
            <p className="text-xs text-muted">Référence · {product.category?.name}</p>
            <h1 className="text-lg font-semibold text-ink">{product.name}</h1>
          </div>
          <Button variant="ghost" size="sm" onClick={onNext} disabled={!hasNext}>
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>

        <div className="flex items-center gap-2">
          {canEdit && onEdit ? (
            <Button variant="outline" size="sm" onClick={onEdit}>
              <Edit className="h-4 w-4" />
              Modifier
            </Button>
          ) : null}
          {canLabel && onLabel ? (
            <Button variant="outline" size="sm" onClick={onLabel}>
              <Tag className="h-4 w-4" />
              Étiquette
            </Button>
          ) : null}
          <div className="relative" ref={menuRef}>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setMenuOpen(!menuOpen)}
            >
              <MoreVertical className="h-4 w-4" />
            </Button>
            {menuOpen ? (
              <div className="absolute right-0 mt-1 w-44 rounded-lg border border-line bg-card shadow-lg">
                {canDuplicate && onDuplicate ? (
                  <button
                    onClick={() => {
                      onDuplicate()
                      setMenuOpen(false)
                    }}
                    className="flex w-full items-center gap-2 px-4 py-2 text-sm text-ink hover:bg-bg"
                  >
                    <Copy className="h-4 w-4" />
                    Dupliquer
                  </button>
                ) : null}
              </div>
            ) : null}
          </div>
        </div>
      </div>

      {/* Badges + Indicateurs */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Badge tone="sky">{product.category?.name}</Badge>
          {product.brand ? <Badge tone="neutral">{product.brand.name}</Badge> : null}
          {product.is_active ? (
            <Badge tone="ok">Actif</Badge>
          ) : (
            <Badge tone="bad">Inactif</Badge>
          )}
        </div>

        {/* KPIs animés */}
        <div className="flex items-center divide-x divide-line rounded-lg border border-line bg-bg">
          <AnimatedKpi label="Stock global" value={stock.total_quantity} />
          <AnimatedKpi
            label="Valeur stock"
            value={`${Number(stock.total_valuation || 0).toLocaleString('fr-FR')} MAD`}
          />
          <AnimatedKpi
            label="Prix détail"
            value={`${Number(product.sale_price || 0).toLocaleString('fr-FR')} MAD`}
          />
          <AnimatedKpi
            label="Marge"
            value={`${(((Number(product.sale_price) - Number(product.cost_price)) / Number(product.cost_price)) * 100).toFixed(1)}%`}
          />
        </div>
      </div>
    </div>
  )
}
