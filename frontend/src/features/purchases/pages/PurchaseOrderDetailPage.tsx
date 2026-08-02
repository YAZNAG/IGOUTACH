import { ArrowLeft, Download, PackageCheck, Pencil, Send, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { downloadFile } from '@/lib/download'
import { usePermission } from '@/hooks/usePermission'
import { formatNumber } from '@/lib/utils'
import { PurchaseOrderLineTable } from '../components/PurchaseOrderLineTable'
import { useCancelPurchaseOrder, usePurchaseOrder, useSendPurchaseOrder } from '../hooks'

const STATUS_BADGES: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  draft: { label: 'Brouillon', tone: 'neutral' },
  pending_approval: { label: 'En attente d’approbation', tone: 'warn' },
  sent: { label: 'Envoyé', tone: 'sky' },
  partially_received: { label: 'Partiellement reçu', tone: 'warn' },
  received: { label: 'Reçu', tone: 'ok' },
  cancelled: { label: 'Annulé', tone: 'bad' },
}

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

export function PurchaseOrderDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const can = usePermission()

  const orderId = id ? Number(id) : 0
  const { data: order, isLoading } = usePurchaseOrder(orderId)

  const [confirmAction, setConfirmAction] = useState<'send' | 'cancel' | null>(null)

  const sendMutation = useSendPurchaseOrder()
  const cancelMutation = useCancelPurchaseOrder()

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!order) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Bon de commande introuvable</h1>
      </div>
    )
  }

  const badge = STATUS_BADGES[order.status.code] ?? { label: order.status.name, tone: 'neutral' as const }
  const canEditOrder = order.status.code === 'draft' && can('purchase.create')
  const canSendOrder = order.can_send && can('purchase.create')
  const canCancelOrder = order.can_cancel && can('purchase.create')
  const canReceiveOrder = order.can_receive && can('receipt.create')
  const canDownloadPdf = order.status.code !== 'draft' && order.status.code !== 'cancelled'

  const mutationError = apiErrorMessage(sendMutation.error) ?? apiErrorMessage(cancelMutation.error)

  const handleSend = async () => {
    await sendMutation.mutateAsync(orderId)
    setConfirmAction(null)
  }

  const handleCancel = async () => {
    await cancelMutation.mutateAsync(orderId)
    setConfirmAction(null)
  }

  const handleDownloadPdf = async () => {
    try {
      await downloadFile(`/purchase-orders/${order.id}/pdf`, `${order.number}.pdf`)
    } catch (error) {
      console.error('Failed to download purchase order PDF:', error)
    }
  }

  const totalOrdered = order.lines.reduce((sum, l) => sum + l.quantity, 0)
  const totalReceived = order.lines.reduce((sum, l) => sum + l.received_quantity, 0)

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate('/purchase-orders')}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="mono text-xl font-semibold text-ink">{order.number}</h1>
              <Badge tone={badge.tone}>{badge.label}</Badge>
            </div>
            <p className="text-sm text-muted">
              {order.supplier.name ?? '—'} → {order.warehouse.code} · {order.warehouse.name}
            </p>
          </div>
        </div>

        <div className="flex gap-2">
          {canEditOrder ? (
            <Button
              variant="outline"
              size="sm"
              onClick={() => navigate(`/purchase-orders/${orderId}/edit`)}
            >
              <Pencil className="h-4 w-4" />
              Éditer
            </Button>
          ) : null}
          {canDownloadPdf ? (
            <Button variant="outline" size="sm" onClick={handleDownloadPdf}>
              <Download className="h-4 w-4" />
              PDF
            </Button>
          ) : null}
          {canReceiveOrder ? (
            <Button size="sm" onClick={() => navigate(`/purchase-orders/${orderId}/receive`)}>
              <PackageCheck className="h-4 w-4" />
              Réceptionner
            </Button>
          ) : null}
          {canSendOrder ? (
            <Button size="sm" onClick={() => setConfirmAction('send')}>
              <Send className="h-4 w-4" />
              Envoyer
            </Button>
          ) : null}
          {canCancelOrder ? (
            <Button
              variant="outline"
              size="sm"
              onClick={() => setConfirmAction('cancel')}
              className="text-bad hover:bg-bad-bg"
            >
              <Trash2 className="h-4 w-4" />
              Annuler
            </Button>
          ) : null}
        </div>
      </div>

      <Card>
        <CardHeader title="Informations" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Date de commande</p>
              <p className="text-sm text-ink">
                {order.ordered_at ? new Date(order.ordered_at).toLocaleDateString('fr-FR') : '—'}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Livraison prévue</p>
              <p className="text-sm text-ink">
                {order.expected_at ? new Date(order.expected_at).toLocaleDateString('fr-FR') : '—'}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Créé par</p>
              <p className="text-sm text-ink">{order.created_by.name ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Références</p>
              <p className="text-sm text-ink">{order.lines.length}</p>
            </div>
          </div>

          {order.notes ? (
            <div className="rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Notes</p>
              <p className="whitespace-pre-wrap text-sm text-ink">{order.notes}</p>
            </div>
          ) : null}

          {mutationError ? (
            <div className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{mutationError}</div>
          ) : null}
        </CardBody>
      </Card>

      <PurchaseOrderLineTable lines={order.lines} />

      <Card>
        <CardHeader title="Synthèse" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <p className="text-xs font-medium text-muted">Commandé</p>
              <p className="text-lg font-semibold text-ink">{formatNumber(totalOrdered)}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Reçu</p>
              <p className="text-lg font-semibold text-ink">{formatNumber(totalReceived)}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Reliquat</p>
              <p
                className={`text-lg font-semibold ${totalOrdered - totalReceived > 0 ? 'text-warn' : 'text-ok'}`}
              >
                {formatNumber(totalOrdered - totalReceived)}
              </p>
            </div>
          </div>
        </CardBody>
      </Card>

      <ConfirmDialog
        open={confirmAction === 'send'}
        title="Envoyer la commande"
        message="Confirmer l'envoi du bon de commande ? Il sera figé et transmis au fournisseur."
        onConfirm={handleSend}
        onCancel={() => setConfirmAction(null)}
        confirmLabel="Envoyer"
        isPending={sendMutation.isPending}
        danger={false}
      />

      <ConfirmDialog
        open={confirmAction === 'cancel'}
        title="Annuler la commande"
        message="Confirmer l'annulation du bon de commande ? Cette action est définitive."
        onConfirm={handleCancel}
        onCancel={() => setConfirmAction(null)}
        confirmLabel="Annuler la commande"
        isPending={cancelMutation.isPending}
        danger={true}
      />
    </div>
  )
}
