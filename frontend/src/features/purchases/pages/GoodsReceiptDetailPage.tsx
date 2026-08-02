import { ArrowLeft, Download } from 'lucide-react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import { useGoodsReceipt } from '../hooks'

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function GoodsReceiptDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const receiptId = id ? Number(id) : 0
  const { data: receipt, isLoading } = useGoodsReceipt(receiptId)

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
              <p className="text-sm text-ink">
                {receipt.received_at ? new Date(receipt.received_at).toLocaleDateString('fr-FR') : '—'}
              </p>
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

      <Card>
        <CardHeader title="Lignes reçues" />
        <CardBody className="p-0">
          {receipt.lines.length === 0 ? (
            <div className="p-5 text-center text-sm text-muted">Aucune ligne.</div>
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
                  {receipt.lines.map((line) => (
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
