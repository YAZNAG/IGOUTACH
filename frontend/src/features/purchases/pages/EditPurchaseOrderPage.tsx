import { ArrowLeft } from 'lucide-react'
import { useNavigate, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { usePermission } from '@/hooks/usePermission'
import { PurchaseOrderForm } from '../components/PurchaseOrderForm'
import { useUpdatePurchaseOrder, usePurchaseOrder } from '../hooks'
import type { CreatePurchaseOrderInput } from '../api/purchaseOrdersApi'

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

function PageShell({ title, children }: { title: string; children?: React.ReactNode }) {
  const navigate = useNavigate()
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">{title}</h1>
      </div>
      {children}
    </div>
  )
}

export function EditPurchaseOrderPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const can = usePermission()

  const orderId = id ? Number(id) : 0
  const { data: order, isLoading } = usePurchaseOrder(orderId)
  const updateMutation = useUpdatePurchaseOrder()

  if (!can('purchase.create')) {
    return (
      <PageShell title="Éditer le bon de commande">
        <div className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
          Vous n'avez pas la permission de modifier un bon de commande.
        </div>
      </PageShell>
    )
  }

  if (isLoading) {
    return <PageShell title="Éditer le bon de commande">
      <p className="text-sm text-muted">Chargement…</p>
    </PageShell>
  }

  if (!order) {
    return <PageShell title="Bon de commande introuvable" />
  }

  if (order.status.code !== 'draft') {
    return (
      <PageShell title={`Éditer ${order.number}`}>
        <div className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
          Seul un bon de commande en brouillon peut être modifié.
        </div>
      </PageShell>
    )
  }

  const handleSubmit = async (input: CreatePurchaseOrderInput) => {
    try {
      await updateMutation.mutateAsync({ id: orderId, input })
      navigate(`/purchase-orders/${orderId}`)
    } catch (error) {
      console.error('Failed to update purchase order:', error)
    }
  }

  return (
    <PurchaseOrderForm
      // key : force la ré-initialisation du formulaire si on navigue vers un autre BC.
      key={order.id}
      title={`Éditer ${order.number}`}
      subtitle="Modification du brouillon — quantités seules, pas de montants"
      initialValues={{
        supplierId: order.supplier.id ?? 0,
        warehouseId: order.warehouse.id ?? 0,
        expectedAt: order.expected_at ? order.expected_at.slice(0, 10) : '',
        notes: order.notes ?? '',
        lines: order.lines.map((l) => ({
          product_id: l.product.id ?? 0,
          sku: l.product.sku ?? '—',
          name: l.product.name ?? '—',
          current_stock: l.product.current_stock ?? 0,
          min_stock_alert: l.product.min_stock ?? 0,
          quantity: l.quantity,
          cost_price: l.last_price_known != null ? Number(l.last_price_known) : undefined,
        })),
      }}
      submitLabel="Enregistrer les modifications"
      isPending={updateMutation.isPending}
      errorMessage={
        updateMutation.isError
          ? `Erreur lors de la modification: ${apiErrorMessage(updateMutation.error) ?? 'Veuillez réessayer.'}`
          : null
      }
      onSubmit={handleSubmit}
    />
  )
}
