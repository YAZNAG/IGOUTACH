import { ChevronLeft, ChevronRight, Download, Eye, X } from 'lucide-react'
import { useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useSupplierOptions, useWarehouseOptions } from '@/features/access/hooks'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import { useGoodsReceipts } from '../hooks'
import type { GoodsReceiptFilters } from '../api/goodsReceiptsApi'

export function GoodsReceiptsListPage() {
  const navigate = useNavigate()
  const location = useLocation()

  // Message de succès transmis par l'écran de réception (numéro du BR créé).
  const createdReceipt = (location.state as { createdReceipt?: string } | null)?.createdReceipt
  const [successDismissed, setSuccessDismissed] = useState(false)

  const [search, setSearch] = useState('')
  const [supplierId, setSupplierId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')
  const [page, setPage] = useState(1)

  const filters: GoodsReceiptFilters = {
    search: search || undefined,
    supplier_id: supplierId || undefined,
    warehouse_id: warehouseId || undefined,
    date_from: dateFrom || undefined,
    date_to: dateTo || undefined,
    page,
  }

  const { data: list, isLoading } = useGoodsReceipts(filters)
  const { data: suppliers = [] } = useSupplierOptions()
  const { data: warehouses = [] } = useWarehouseOptions()

  const receipts = list?.data ?? []
  const meta = list?.meta

  const handleResetFilters = () => {
    setSearch('')
    setSupplierId(0)
    setWarehouseId(0)
    setDateFrom('')
    setDateTo('')
    setPage(1)
  }

  const handleDownloadPdf = async (id: number, number: string) => {
    try {
      await downloadFile(`/goods-receipts/${id}/pdf`, `${number}.pdf`)
    } catch (error) {
      console.error('Failed to download goods receipt PDF:', error)
    }
  }

  const hasActiveFilters = search || supplierId || warehouseId || dateFrom || dateTo

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Réceptions</h1>
        <p className="text-sm text-muted">Bons de réception fournisseurs et entrées en stock.</p>
      </div>

      {createdReceipt && !successDismissed ? (
        <div className="flex items-center justify-between rounded border border-line bg-ok-bg px-4 py-3 text-sm text-ok">
          <span>
            Réception validée : le bon de réception <span className="mono font-semibold">{createdReceipt}</span> a été créé.
          </span>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setSuccessDismissed(true)}
            title="Fermer"
          >
            <X className="h-4 w-4" />
          </Button>
        </div>
      ) : null}

      <Card>
        <CardHeader title="Filtres" />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <Field label="N° de réception" htmlFor="gr-search">
              <Input
                id="gr-search"
                placeholder="BR-2026-…"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                }}
              />
            </Field>
            <Field label="Fournisseur" htmlFor="gr-supplier">
              <Select
                id="gr-supplier"
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
            <Field label="Lieu" htmlFor="gr-warehouse">
              <Select
                id="gr-warehouse"
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
            <Field label="Du" htmlFor="gr-date-from">
              <Input
                id="gr-date-from"
                type="date"
                value={dateFrom}
                onChange={(e) => {
                  setDateFrom(e.target.value)
                  setPage(1)
                }}
              />
            </Field>
            <Field label="Au" htmlFor="gr-date-to">
              <Input
                id="gr-date-to"
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
        <CardHeader
          title="Bons de réception"
          hint={meta ? `${meta.total} résultat${meta.total > 1 ? 's' : ''}` : undefined}
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : receipts.length === 0 ? (
            <div className="p-8 text-center text-sm text-muted">Aucun bon de réception trouvé.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">N°</th>
                    <th className="px-5 py-3 font-medium">Date de réception</th>
                    <th className="px-5 py-3 font-medium">BC d'origine</th>
                    <th className="px-5 py-3 font-medium">Fournisseur</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 text-right font-medium">Références</th>
                    <th className="px-5 py-3 text-right font-medium">Unités</th>
                    <th className="px-5 py-3 text-right font-medium">Montant HT</th>
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {receipts.map((receipt) => (
                    <tr key={receipt.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 font-medium text-ink">{receipt.number}</td>
                      <td className="px-5 py-3 text-muted">
                        {receipt.received_at
                          ? new Date(receipt.received_at).toLocaleDateString('fr-FR')
                          : '—'}
                      </td>
                      <td className="px-5 py-3">
                        {receipt.purchase_order ? (
                          <Link
                            to={`/purchase-orders/${receipt.purchase_order.id}`}
                            className="mono text-sky hover:underline"
                          >
                            {receipt.purchase_order.number}
                          </Link>
                        ) : (
                          <span className="text-muted">Directe</span>
                        )}
                      </td>
                      <td className="px-5 py-3 text-ink">{receipt.supplier.name ?? '—'}</td>
                      <td className="px-5 py-3 text-muted">
                        {receipt.warehouse.code
                          ? `${receipt.warehouse.code} · ${receipt.warehouse.name}`
                          : '—'}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{receipt.lines_count}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatNumber(Number(receipt.total_quantity))}
                      </td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {formatNumber(Number(receipt.total_amount))} DH
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigate(`/goods-receipts/${receipt.id}`)}
                            title="Afficher"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDownloadPdf(receipt.id, receipt.number)}
                            title="Télécharger le PDF"
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
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
    </div>
  )
}
