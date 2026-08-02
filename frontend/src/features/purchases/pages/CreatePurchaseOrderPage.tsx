import { ArrowLeft } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { usePermission } from '@/hooks/usePermission'
import { PurchaseOrderForm } from '../components/PurchaseOrderForm'
import { useCreatePurchaseOrder } from '../hooks'
import type { CreatePurchaseOrderInput } from '../api/purchaseOrdersApi'

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

export function CreatePurchaseOrderPage() {
  const navigate = useNavigate()
  const can = usePermission()
  const createMutation = useCreatePurchaseOrder()

  if (!can('purchase.create')) {
    return (
      <div className="space-y-6">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate(-1)}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">Nouveau bon de commande</h1>
          </div>
        </div>
        <div className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
          Vous n'avez pas la permission de créer un bon de commande.
        </div>
      </div>
    )
  }

  const handleSubmit = async (input: CreatePurchaseOrderInput) => {
    try {
      await createMutation.mutateAsync(input)
      navigate('/purchase-orders')
    } catch (error) {
      console.error('Failed to create purchase order:', error)
    }
  }

  return (
    <PurchaseOrderForm
      title="Nouveau bon de commande"
      subtitle="Saisie des quantités seules, pas de montants"
      submitLabel="Enregistrer"
      isPending={createMutation.isPending}
      errorMessage={
        createMutation.isError
          ? `Erreur lors de la création: ${apiErrorMessage(createMutation.error) ?? 'Veuillez réessayer.'}`
          : null
      }
      onSubmit={handleSubmit}
    />
  )
}
