import { useEffect, useState } from 'react'
import { useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { Card, CardBody } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import { useProductDetail, useProductMovements, useProductStock } from '../hooks'
import { MovementsTab } from '../components/MovementsTab'
import { OtherTabs } from '../components/OtherTabs'
import { OverviewTab } from '../components/OverviewTab'
import { PricingTab } from '../components/PricingTab'
import { ProductDetailHeader } from '../components/ProductDetailHeader'
import { StatisticsTab } from '../components/StatisticsTab'
import { StockTab } from '../components/StockTab'

export function ProductDetailPage() {
  const { id } = useParams()
  const productId = Number(id)
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()
  const can = usePermission()

  const activeTab = searchParams.get('tab') || 'overview'
  const [allProducts, setAllProducts] = useState<Array<{ id: number }>>([])

  const { data: product, isLoading: productLoading } = useProductDetail(productId)
  const { data: stock } = useProductStock(productId)
  const { data: movements, isLoading: movementsLoading } = useProductMovements(productId)

  // Fetch all products for navigation
  useEffect(() => {
    // In a real app, this would come from a more optimized query
    // For now, we'll just disable prev/next
    setAllProducts([])
  }, [])

  function handleTabChange(tab: string) {
    setSearchParams({ tab })
  }

  function handlePrev() {
    const currentIndex = allProducts.findIndex((p) => p.id === productId)
    if (currentIndex > 0) {
      navigate(`/articles/${allProducts[currentIndex - 1].id}?tab=${activeTab}`)
    }
  }

  function handleNext() {
    const currentIndex = allProducts.findIndex((p) => p.id === productId)
    if (currentIndex < allProducts.length - 1) {
      navigate(`/articles/${allProducts[currentIndex + 1].id}?tab=${activeTab}`)
    }
  }

  if (productLoading || !product || !stock) {
    return (
      <div className="space-y-6">
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  const tabs = [
    { id: 'overview', label: 'Vue d\'ensemble' },
    { id: 'stock', label: 'Stock' },
    { id: 'movements', label: 'Mouvements' },
    { id: 'pricing', label: 'Tarification' },
    { id: 'statistics', label: 'Statistiques' },
    { id: 'specs', label: 'Caractéristiques' },
    { id: 'media', label: 'Médias' },
    { id: 'serials', label: 'Numéros de série' },
    { id: 'suppliers', label: 'Fournisseurs' },
  ]

  return (
    <div className="flex flex-col">
      <ProductDetailHeader
        product={product}
        stock={stock}
        onPrev={handlePrev}
        onNext={handleNext}
        onEdit={can('product.update') ? () => {} : undefined}
        onDuplicate={can('product.create') ? () => {} : undefined}
        onLabel={can('product.labels_print') ? () => {} : undefined}
        hasPrev={allProducts.length > 0 && allProducts.findIndex((p) => p.id === productId) > 0}
        hasNext={allProducts.length > 0 && allProducts.findIndex((p) => p.id === productId) < allProducts.length - 1}
      />

      <div className="flex-1 overflow-x-auto border-b border-line">
        <div className="flex gap-0">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => handleTabChange(tab.id)}
              className={`whitespace-nowrap border-b-2 px-5 py-3 text-sm font-medium transition-colors ${
                activeTab === tab.id
                  ? 'border-sky text-ink'
                  : 'border-transparent text-muted hover:text-ink'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      <div className="space-y-6 p-5">
        {activeTab === 'overview' && <OverviewTab product={product} stock={stock} />}
        {activeTab === 'stock' && <StockTab stock={stock} />}
        {activeTab === 'movements' && (
          <MovementsTab
            movements={movements?.data || []}
            isLoading={movementsLoading}
          />
        )}
        {activeTab === 'pricing' && <PricingTab product={product} stock={stock} />}
        {activeTab === 'statistics' && <StatisticsTab product={product} stock={stock} />}
        {['specs', 'media', 'serials', 'suppliers'].includes(activeTab) && (
          <OtherTabs product={product} activeTab={activeTab} />
        )}

        {!product.is_active && (
          <Card>
            <CardBody className="py-3 text-sm text-warn">
              Attention : Cet article est inactif et ne peut pas être utilisé dans les mouvements de stock.
            </CardBody>
          </Card>
        )}
      </div>
    </div>
  )
}
