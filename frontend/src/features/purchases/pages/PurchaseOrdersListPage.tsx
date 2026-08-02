import { ChevronLeft, ChevronRight, Download, Eye, Pencil, Plus, Send, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { downloadFile } from '@/lib/download'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useSupplierOptions, useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { formatNumber } from '@/lib/utils'
import { useCancelPurchaseOrder, usePurchaseOrders, useSendPurchaseOrder } from '../hooks'
import type { PurchaseOrderFilters } from '../api/purchaseOrdersApi'

const STATUS_BADGES: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  draft: { label: 'Brouillon', tone: 'neutral' },
  pending_approval: { label: 'En attente d’approbation', tone: 'warn' },
  sent: { label: 'Envoyé', tone: 'sky' },
  partially_received: { label: 'Partiellement reçu', tone: 'warn' },
  received: { label: 'Reçu', tone: 'ok' },
  cancelled: { label: 'Annulé', tone: 'bad' },
}

export function PurchaseOrdersListPage() {
  const navigate = useNavigate()
  const can = usePermission()

  const [search, setSearch] = useState('')
  const [supplierId, setSupplierId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [status, setStatus] = useState('')
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [page, setPage] = useState(1)

  const [confirmId, setConfirmId] = useState<number | null>(null)
  const [confirmAction, setConfirmAction] = useState<'send' | 'cancel' | null>(null)

  const filters: PurchaseOrderFilters = {
    search: search || undefined,
    supplier_id: supplierId || undefined,
    warehouse_id: warehouseId || undefined,
    status: status || undefined,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    page,
  }

  const { data: list, isLoading } = usePurchaseOrders(filters)
  const { data: suppliers = [] } = useSupplierOptions()
  const { data: warehouses = [] } = useWarehouseOptions()

  const orders = list?.data ?? []
  const meta = list?.meta

  const sendMutation = useSendPurchaseOrder()
  const cancelMutation = useCancelPurchaseOrder()

  const handleResetFilters = () => {
    setSearch('')
    setSupplierId(0)
    setWarehouseId(0)
    setStatus('')
    setDateFrom('')
    setDateTo('')
    setPage(1)
  }

  const handleSendConfirm = async () => {
    if (confirmId && confirmAction === 'send') {
      await sendMutation.mutateAsync(confirmId)
      setConfirmId(null)
      setConfirmAction(null)
    }
  }

  const handleCancelConfirm = async () => {
    if (confirmId && confirmAction === 'cancel') {
      await cancelMutation.mutateAsync(confirmId)
      setConfirmId(null)
      setConfirmAction(null)
    }
  }

  const handleDownloadPdf = async (id: number, number: string) => {
    try {
      await downloadFile(`/purchase-orders/${id}/pdf`, `${number}.pdf`)
    } catch (error) {
      console.error('Failed to download purchase order PDF:', error)
    }
  }

  const hasActiveFilters = search || supplierId || warehouseId || status || dateFrom || dateTo

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Bons de commande</h1>
          <p className="text-sm text-muted">Gestion des commandes fournisseurs et réceptions.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" disabled title="Exports PDF / Excel — bientôt disponibles">
            <Download className="h-4 w-4" />
            Exporter
          </Button>
          {can('purchase.create') ? (
            <Button size="sm" onClick={() => navigate('/purchase-orders/create')}>
              <Plus className="h-4 w-4" />
              Nouvelle commande
            </Button>
          ) : null}
        </div>
      </div>

      <Card>
        <CardHeader title="Filtres" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <Field label="N° de commande" htmlFor="po-search">
              <Input
                id="po-search"
                placeholder="BC-2026-…"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                }}
              />
            </Field>
            <Field label="Fournisseur" htmlFor="po-supplier">
              <Select
                id="po-supplier"
                value={supplierId || ''}
                onChange={(e) => {
                  setSupplierId(Number(e.target.value))
                  setPage(1)
                }}
              >
                <option value="">Tous</option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.code} · {s.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Lieu" htmlFor="po-warehouse">
              <Select
                id="po-warehouse"
                value={warehouseId || ''}
                onChange={(e) => {
                  setWarehouseId(Number(e.target.value))
                  setPage(1)
                }}
              >
                <option value="">Tous</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.code} · {w.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Statut" htmlFor="po-status">
              <Select
                id="po-status"
                value={status}
                onChange={(e) => {
                  setStatus(e.target.value)
                  setPage(1)
                }}
              >
                <option value="">Tous</option>
                {Object.entries(STATUS_BADGES).map(([key, { label }]) => (
                  <option key={key} value={key}>
                    {label}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label="Du" htmlFor="po-date-from">
              <Input
                id="po-date-from"
                type="date"
                value={dateFrom}
                onChange={(e) => {
                  setDateFrom(e.target.value)
                  setPage(1)
                }}
              />
            </Field>
            <Field label="Au" htmlFor="po-date-to">
              <Input
                id="po-date-to"
                type="date"
                value={dateTo}
                onChange={(e) => {
                  setDateTo(e.target.value)
                  setPage(1)
                }}
              />
            </Field>
          </div>

          {hasActiveFilters ? (
            <Button variant="outline" size="sm" onClick={handleResetFilters} className="text-muted">
              <X className="h-4 w-4" />
              Réinitialiser les filtres
            </Button>
          ) : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Commandes" hint={meta ? `${meta.total} résultat${meta.total > 1 ? 's' : ''}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : orders.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted">
              Aucun bon de commande trouvé.
              {can('purchase.create') ? (
                <div className="mt-3">
                  <Button size="sm" onClick={() => navigate('/purchase-orders/create')}>
                    <Plus className="h-4 w-4" />
                    Créer la première commande
                  </Button>
                </div>
              ) : null}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">N°</th>
                    <th className="px-5 py-3 font-medium">Date</th>
                    <th className="px-5 py-3 font-medium">Fournisseur</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 text-right font-medium">Références</th>
                    <th className="px-5 py-3 text-right font-medium">Unités</th>
                    <th className="px-5 py-3 font-medium">Avancement</th>
                    <th className="px-5 py-3 font-medium">Statut</th>
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {orders.map((order) => {
                    const badge = STATUS_BADGES[order.status.code] ?? {
                      label: order.status.name,
                      tone: 'neutral' as const,
                    }
                    const progress =
                      order.total_quantity > 0
                        ? Math.round((order.total_received / order.total_quantity) * 100)
                        : 0
                    return (
                      <tr key={order.id} className="border-b border-line last:border-0">
                        <td className="mono px-5 py-3 font-medium text-ink">{order.number}</td>
                        <td className="px-5 py-3 text-muted">
                          {order.ordered_at ? new Date(order.ordered_at).toLocaleDateString('fr-FR') : '—'}
                        </td>
                        <td className="px-5 py-3 text-ink">{order.supplier.name ?? '—'}</td>
                        <td className="px-5 py-3 text-muted">
                          {order.warehouse.code ? `${order.warehouse.code} · ${order.warehouse.name}` : '—'}
                        </td>
                        <td className="tabular px-5 py-3 text-right text-muted">{order.lines_count}</td>
                        <td className="tabular px-5 py-3 text-right font-medium text-ink">
                          {formatNumber(order.total_quantity)}
                        </td>
                        <td className="px-5 py-3">
                          {order.status.code === 'draft' || order.status.code === 'cancelled' ? (
                            <span className="text-muted">—</span>
                          ) : (
                            <div className="flex items-center gap-2">
                              <div className="h-1.5 w-20 overflow-hidden rounded-full bg-bg">
                                <div
                                  className={`h-full rounded-full ${progress >= 100 ? 'bg-ok' : 'bg-sky'}`}
                                  style={{ width: `${Math.min(100, progress)}%` }}
                                />
                              </div>
                              <span className="tabular text-xs text-muted">
                                {formatNumber(order.total_received)}/{formatNumber(order.total_quantity)}
                              </span>
                            </div>
                          )}
                        </td>
                        <td className="px-5 py-3">
                          <Badge tone={badge.tone}>{badge.label}</Badge>
                        </td>
                        <td className="px-5 py-3 text-right">
                          <div className="flex items-center justify-end gap-1">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => navigate(`/purchase-orders/${order.id}`)}
                              title="Afficher"
                            >
                              <Eye className="h-4 w-4" />
                            </Button>
                            {order.status.code === 'draft' && can('purchase.create') ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => navigate(`/purchase-orders/${order.id}/edit`)}
                                title="Éditer"
                              >
                                <Pencil className="h-4 w-4" />
                              </Button>
                            ) : null}
                            {order.status.code !== 'draft' && order.status.code !== 'cancelled' ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleDownloadPdf(order.id, order.number)}
                                title="Télécharger le PDF"
                              >
                                <Download className="h-4 w-4" />
                              </Button>
                            ) : null}
                            {order.can_send && can('purchase.create') ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                  setConfirmId(order.id)
                                  setConfirmAction('send')
                                }}
                                title="Envoyer"
                              >
                                <Send className="h-4 w-4" />
                              </Button>
                            ) : null}
                            {order.can_cancel && can('purchase.create') ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-bad hover:bg-bad-bg"
                                onClick={() => {
                                  setConfirmId(order.id)
                                  setConfirmAction('cancel')
                                }}
                                title="Annuler"
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            ) : null}
                          </div>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <div>
            Page {meta.current_page} / {meta.last_page}
          </div>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={meta.current_page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
            >
              <ChevronLeft className="h-4 w-4" />
              Précédent
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={meta.current_page >= meta.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Suivant
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      ) : null}

      <ConfirmDialog
        open={confirmAction === 'send' && confirmId !== null}
        title="Envoyer la commande"
        message="Confirmer l'envoi du bon de commande ? Il sera figé et transmis au fournisseur."
        onConfirm={handleSendConfirm}
        onCancel={() => {
          setConfirmId(null)
          setConfirmAction(null)
        }}
        confirmLabel="Envoyer"
        isPending={sendMutation.isPending}
        danger={false}
      />

      <ConfirmDialog
        open={confirmAction === 'cancel' && confirmId !== null}
        title="Annuler la commande"
        message="Confirmer l'annulation du bon de commande ? Cette action est définitive."
        onConfirm={handleCancelConfirm}
        onCancel={() => {
          setConfirmId(null)
          setConfirmAction(null)
        }}
        confirmLabel="Annuler la commande"
        isPending={cancelMutation.isPending}
        danger={true}
      />
    </div>
  )
}
