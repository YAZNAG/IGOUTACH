import { api, ensureCsrfCookie } from '@/lib/api'
import type { NamedRef, PaymentStatus, PurchaseOrderListMeta } from './purchaseOrdersApi'

/** Bon de livraison du fournisseur joint à la réception (PDF ou photo). */
export interface SupplierDeliveryNote {
  name: string | null
  mime: string | null
  is_image: boolean
}

export interface GoodsReceiptSummary {
  id: number
  number: string
  purchase_order: { id: number; number: string } | null
  supplier: NamedRef
  warehouse: NamedRef
  received_at: string | null
  invoice_number: string | null
  lines_count: number
  total_quantity: number
  total_amount: number
  /** TVA du lieu : 0 hors d'un depot soumis a TVA. */
  tax_rate?: number
  tax_amount?: number
  total_ttc?: number
  payment_status: PaymentStatus
  amount_paid: number
  remaining_amount: number
  /** Null tant qu'aucun bon de livraison n'a été joint. */
  supplier_delivery_note?: SupplierDeliveryNote | null
  created_at: string | null
}

export interface GoodsReceiptLine {
  id: number
  product: { sku: string | null; name: string | null }
  quantity: number
  unit_price: number
  line_total: number
}

export interface GoodsReceipt extends GoodsReceiptSummary {
  notes?: string | null
  lines: GoodsReceiptLine[]
}

export const PAYMENT_LABELS: Record<PaymentStatus, string> = {
  paid: 'Payé',
  partial: 'Paiement partiel',
  unpaid: 'Non payé',
}

export interface GoodsReceiptList {
  data: GoodsReceiptSummary[]
  meta: PurchaseOrderListMeta
}

export interface GoodsReceiptFilters {
  search?: string
  supplier_id?: number
  warehouse_id?: number
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export async function fetchGoodsReceipts(filters: GoodsReceiptFilters = {}): Promise<GoodsReceiptList> {
  const { data } = await api.get<GoodsReceiptList>('/goods-receipts', { params: filters })
  return data
}

export async function fetchGoodsReceipt(id: number): Promise<GoodsReceipt> {
  // Le détail est renvoyé sans wrapper `data` (même convention que purchase-orders).
  const { data } = await api.get<GoodsReceipt>(`/goods-receipts/${id}`)
  return data
}

/** Joint (ou remplace) le bon de livraison du fournisseur : PDF ou photo. */
export async function attachDeliveryNote(id: number, file: File): Promise<GoodsReceipt> {
  await ensureCsrfCookie()
  const form = new FormData()
  form.append('file', file)
  const { data } = await api.post<GoodsReceipt>(`/goods-receipts/${id}/delivery-note`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data
}

export async function removeDeliveryNote(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/goods-receipts/${id}/delivery-note`)
}
