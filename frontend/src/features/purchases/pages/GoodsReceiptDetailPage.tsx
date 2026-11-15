import { ArrowLeft, Download, Eye, Paperclip, Trash2 } from 'lucide-react'
import { useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { SearchInput } from '@/components/ui/SearchInput'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { usePermission } from '@/hooks/usePermission'
import { downloadFile, openFile } from '@/lib/download'
import { formatDate, formatDateHeure, formatNumber } from '@/lib/utils'
import { useAttachDeliveryNote, useGoodsReceipt, useRemoveDeliveryNote } from '../hooks'

const TYPES_BON_LIVRAISON = 'application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif,.pdf,.jpg,.jpeg,.png,.webp,.heic,.heif'

function apiErrorMessage(error: unknown): string | null {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return null
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function GoodsReceiptDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const receiptId = id ? Number(id) : 0
  const { data: receipt, isLoading } = useGoodsReceipt(receiptId)
  const can = usePermission()

  // Bon de livraison du fournisseur : se joint, se remplace, se retire ici,
  // après coup — la réception n'a pas toujours le papier sous la main.
  const attachMutation = useAttachDeliveryNote()
  const removeMutation = useRemoveDeliveryNote()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [confirmRemove, setConfirmRemove] = useState(false)
  const [noteError, setNoteError] = useState<string | null>(null)

  const handleAttach = async (file: File | null) => {
    if (!file) return
    setNoteError(null)
    if (file.size > 15 * 1024 * 1024) {
      setNoteError('Le fichier dépasse 15 Mo : réduisez la photo ou le PDF.')
      return
    }
    try {
      await attachMutation.mutateAsync({ id: receiptId, file })
    } catch (error) {
      setNoteError(apiErrorMessage(error) ?? "Le bon de livraison n'a pas pu être joint.")
    } finally {
      if (fileInputRef.current) fileInputRef.current.value = ''
    }
  }

  const handleRemove = async () => {
    setNoteError(null)
    try {
      await removeMutation.mutateAsync(receiptId)
    } catch (error) {
      setNoteError(apiErrorMessage(error) ?? "Le bon de livraison n'a pas pu être retiré.")
    } finally {
      setConfirmRemove(false)
    }
  }

  // Un bon de reception peut porter des dizaines de lignes. Le hook est
  // appele ici, avant les retours anticipes : l'ordre des hooks ne doit pas
  // changer d'un rendu a l'autre.
  const { terme, setTerme, resultats, actif } = useRechercheLocale(
    receipt?.lines ?? [],
    (line) => [line.product.sku, line.product.name],
  )

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/goods-receipts')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!receipt) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/goods-receipts')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Bon de réception introuvable</h1>
      </div>
    )
  }

  const totalQuantity = receipt.lines.reduce((sum, l) => sum + Number(l.quantity), 0)
  const totalAmount = receipt.lines.reduce((sum, l) => sum + Number(l.line_total), 0)

  const handleDownloadPdf = async () => {
    try {
      await downloadFile(`/goods-receipts/${receipt.id}/pdf`, `${receipt.number}.pdf`)
    } catch (error) {
      console.error('Failed to download goods receipt PDF:', error)
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate('/goods-receipts')}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="mono text-xl font-semibold text-ink">{receipt.number}</h1>
            <p className="text-sm text-muted">
              {receipt.supplier.name ?? '—'} → {receipt.warehouse.code} · {receipt.warehouse.name}
            </p>
          </div>
        </div>

        <Button variant="outline" size="sm" onClick={handleDownloadPdf}>
          <Download className="h-4 w-4" />
          Télécharger le PDF
        </Button>
      </div>

      <Card>
        <CardHeader title="Informations" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Date de réception</p>
              <p className="text-sm text-ink">{formatDate(receipt.received_at)}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Saisie le</p>
              <p className="text-sm text-ink">{formatDateHeure(receipt.created_at)}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">BC d'origine</p>
              {receipt.purchase_order ? (
                <Link
                  to={`/purchase-orders/${receipt.purchase_order.id}`}
                  className="mono text-sm text-sky hover:underline"
                >
                  {receipt.purchase_order.number}
                </Link>
              ) : (
                <p className="text-sm text-ink">Directe</p>
              )}
            </div>
            <div>
              <p className="text-xs font-medium text-muted">N° facture fournisseur</p>
              <p className="text-sm text-ink">{receipt.invoice_number ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Références</p>
              <p className="text-sm text-ink">{receipt.lines.length}</p>
            </div>
          </div>

          {receipt.notes ? (
            <div className="rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Notes</p>
              <p className="whitespace-pre-wrap text-sm text-ink">{receipt.notes}</p>
            </div>
          ) : null}
        </CardBody>
      </Card>

      {/* Le bon de livraison du fournisseur : la pièce qui accompagne la
          marchandise. Gardée avec le BR, elle tranche un litige de quantités
          ou de prix des mois plus tard. */}
      <Card>
        <CardHeader
          title="Bon de livraison du fournisseur"
          hint={receipt.supplier_delivery_note ? (receipt.supplier_delivery_note.is_image ? 'Photo jointe' : 'PDF joint') : 'Aucun document joint'}
        />
        <CardBody className="space-y-3">
          <input
            ref={fileInputRef}
            type="file"
            accept={TYPES_BON_LIVRAISON}
            className="hidden"
            aria-label="Choisir le bon de livraison du fournisseur"
            onChange={(e) => handleAttach(e.target.files?.[0] ?? null)}
          />
          {receipt.supplier_delivery_note ? (
            <div className="flex flex-wrap items-center gap-2">
              <span className="flex items-center gap-2 rounded border border-line bg-bg px-3 py-1.5 text-sm text-ink">
                <Paperclip className="h-4 w-4 text-sky" />
                <span className="max-w-[22rem] truncate" title={receipt.supplier_delivery_note.name ?? ''}>
                  {receipt.supplier_delivery_note.name ?? 'bon-livraison'}
                </span>
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={() =>
                  openFile(
                    `/goods-receipts/${receipt.id}/delivery-note`,
                    receipt.supplier_delivery_note?.name ?? `bon-livraison-${receipt.number}`,
                  ).catch(() => setNoteError("Le bon de livraison n'a pas pu être ouvert."))
                }
              >
                <Eye className="h-4 w-4" />
                {receipt.supplier_delivery_note.is_image ? 'Afficher la photo' : 'Afficher le PDF'}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() =>
                  downloadFile(
                    `/goods-receipts/${receipt.id}/delivery-note`,
                    receipt.supplier_delivery_note?.name ?? `bon-livraison-${receipt.number}`,
                  ).catch(() => setNoteError("Le bon de livraison n'a pas pu être téléchargé."))
                }
              >
                <Download className="h-4 w-4" />
                Télécharger
              </Button>
              {can('receipt.create') ? (
                <>
                  <Button
                    variant="ghost"
                    size="sm"
                    disabled={attachMutation.isPending}
                    onClick={() => fileInputRef.current?.click()}
                  >
                    {attachMutation.isPending ? 'Envoi…' : 'Remplacer'}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-bad hover:bg-bad-bg"
                    disabled={removeMutation.isPending}
                    onClick={() => setConfirmRemove(true)}
                  >
                    <Trash2 className="h-4 w-4" />
                    Retirer
                  </Button>
                </>
              ) : null}
            </div>
          ) : can('receipt.create') ? (
            <div className="flex flex-wrap items-center gap-3">
              <Button
                variant="outline"
                size="sm"
                disabled={attachMutation.isPending}
                onClick={() => fileInputRef.current?.click()}
              >
                <Paperclip className="h-4 w-4" />
                {attachMutation.isPending ? 'Envoi…' : 'Joindre un PDF ou une photo'}
              </Button>
              <span className="text-xs text-muted">PDF ou image (JPG, PNG, HEIC), 15 Mo maximum.</span>
            </div>
          ) : (
            <p className="text-sm text-muted">Aucun bon de livraison n'a été joint à cette réception.</p>
          )}
          {noteError ? (
            <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{noteError}</p>
          ) : null}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={confirmRemove}
        title="Retirer le bon de livraison"
        message="Le fichier joint sera supprimé définitivement. Vous pourrez en joindre un autre ensuite."
        confirmLabel="Retirer"
        danger
        isPending={removeMutation.isPending}
        onConfirm={handleRemove}
        onCancel={() => setConfirmRemove(false)}
      />

      <Card>
        <CardHeader
          title="Lignes reçues"
          hint={
            actif
              ? `${resultats.length} sur ${receipt.lines.length}`
              : `${receipt.lines.length} ligne(s)`
          }
          action={<SearchInput value={terme} onChange={setTerme} placeholder="Référence ou article…" />}
        />
        <CardBody className="p-0">
          {resultats.length === 0 ? (
            <div className="p-5 text-center text-sm text-muted">
              {actif ? 'Aucune ligne ne correspond à cette recherche.' : 'Aucune ligne.'}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Référence</th>
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 text-right font-medium">Quantité</th>
                    <th className="px-5 py-3 text-right font-medium">Prix unitaire</th>
                    <th className="px-5 py-3 text-right font-medium">Total ligne</th>
                  </tr>
                </thead>
                <tbody>
                  {resultats.map((line) => (
                    <tr key={line.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{line.product.sku ?? '—'}</td>
                      <td className="px-5 py-3 text-ink">{line.product.name ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatNumber(Number(line.quantity))}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">
                        {formatMoney(Number(line.unit_price))} DH
                      </td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatMoney(Number(line.line_total))} DH
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Synthèse" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div>
              <p className="text-xs font-medium text-muted">Unités reçues</p>
              <p className="text-lg font-semibold text-ink">{formatNumber(totalQuantity)}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Montant total HT</p>
              <p className="text-lg font-semibold text-ink">{formatMoney(totalAmount)} DH</p>
            </div>
            {/* Réception dans un lieu soumis à TVA : c'est le TTC qui se règle
                au fournisseur, le HT ne fait que le coût de la marchandise. */}
            {Number(receipt.tax_amount ?? 0) > 0 ? (
              <>
                <div>
                  <p className="text-xs font-medium text-muted">
                    TVA ({formatNumber(Number(receipt.tax_rate ?? 0))} %)
                  </p>
                  <p className="text-lg font-semibold text-ink">
                    {formatMoney(Number(receipt.tax_amount))} DH
                  </p>
                </div>
                <div>
                  <p className="text-xs font-medium text-muted">Montant total TTC</p>
                  <p className="text-lg font-semibold text-ink">
                    {formatMoney(Number(receipt.total_ttc ?? totalAmount))} DH
                  </p>
                </div>
              </>
            ) : null}
            <div>
              <p className="text-xs font-medium text-muted">Règlement</p>
              <div className="mt-1">
                {receipt.payment_status === 'paid' ? (
                  <Badge tone="ok">Payé</Badge>
                ) : receipt.payment_status === 'partial' ? (
                  <Badge tone="warn">Partiel</Badge>
                ) : (
                  <Badge tone="bad">Non payé</Badge>
                )}
              </div>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Montant payé</p>
              <p className="text-lg font-semibold text-ok">{formatMoney(Number(receipt.amount_paid))} DH</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Crédit fournisseur (reste)</p>
              <p
                className={`text-lg font-semibold ${
                  Number(receipt.remaining_amount) > 0 ? 'text-bad' : 'text-ok'
                }`}
              >
                {formatMoney(Number(receipt.remaining_amount))} DH
              </p>
            </div>
          </div>
        </CardBody>
      </Card>
    </div>
  )
}
