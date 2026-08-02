import { api, ensureCsrfCookie } from '@/lib/api'

export type PurchaseOrderStatusCode =
  | 'draft'
  | 'pending_approval'
  | 'sent'
  | 'partially_received'
  | 'received'
  | 'cancelled'

export interface PurchaseOrderStatus {
  id: number
  code: PurchaseOrderStatusCode
  name: string
}

export interface NamedRef {
  id: number | null
  name: string | null
  code: string | null
}

export interface PurchaseOrderLine {
  id: number
  product: {
    id: number | null
    sku: string | null
    name: string | null
    current_stock: number | null
    min_stock: number | null
  }
  quantity: number
  received_quantity: number
  remaining: number
  position: number
  last_price_known: string | null
}

export interface PurchaseOrderSummary {
  id: number
  number: string
  supplier: NamedRef
  warehouse: NamedRef
  ordered_at: string | null
  expected_at: string | null
  status: PurchaseOrderStatus
  notes: string | null
  lines_count: number
  total_quantity: number
  total_received: number
  can_send: boolean
  can_approve: boolean
  can_receive: boolean
  can_cancel: boolean
  created_at: string | null
}

export interface PurchaseOrder extends Omit<PurchaseOrderSummary, 'total_quantity' | 'total_received'> {
  lines: PurchaseOrderLine[]
  created_by: { id: number | null; name: string | null; email: string | null }
  updated_at: string | null
}

export interface PurchaseOrderListMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PurchaseOrderList {
  data: PurchaseOrderSummary[]
  meta: PurchaseOrderListMeta
}

export interface PurchaseOrderFilters {
  search?: string
  supplier_id?: number
  warehouse_id?: number
  status?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export interface CreatePurchaseOrderInput {
  supplier_id: number
  warehouse_id: number
  expected_at?: string | null
  notes?: string | null
  lines: {
    product_id: number
    quantity: number
  }[]
}

export type UpdatePurchaseOrderInput = CreatePurchaseOrderInput

export interface ReceivePurchaseOrderLineInput {
  purchase_order_line_id: number
  quantity: number
  unit_price: number
  over_receipt_reason?: string
}

export type PaymentStatus = 'unpaid' | 'partial' | 'paid'

export interface ReceivePurchaseOrderInput {
  received_at: string
  invoice_number?: string | null
  notes?: string | null
  payment_status?: PaymentStatus
  amount_paid?: number
  lines: ReceivePurchaseOrderLineInput[]
}

export interface ReceivePurchaseOrderResult {
  id: number
  number: string
}

export interface ProductOption {
  id: number
  sku: string
  name: string
  barcode: string | null
  current_stock: number
  min_stock_alert: number
  cost_price?: number
}

export interface BelowThresholdProduct extends ProductOption {
  suggested_quantity: number
}

interface RawProduct {
  id: number
  sku: string
  name: string
  barcode: string | null
  min_stock: number | null
  current_stock?: number
  cost_price?: string | number
}

function toProductOption(p: RawProduct): ProductOption {
  return {
    id: p.id,
    sku: p.sku,
    name: p.name,
    barcode: p.barcode,
    current_stock: p.current_stock ?? 0,
    min_stock_alert: p.min_stock ?? 0,
    cost_price: p.cost_price !== undefined ? Number(p.cost_price) : undefined,
  }
}

export async function fetchPurchaseOrders(filters: PurchaseOrderFilters = {}): Promise<PurchaseOrderList> {
  const { data } = await api.get<PurchaseOrderList>('/purchase-orders', { params: filters })
  return data
}

export async function fetchPurchaseOrder(id: number): Promise<PurchaseOrder> {
  const { data } = await api.get<PurchaseOrder>(`/purchase-orders/${id}`)
  return data
}

export async function createPurchaseOrder(input: CreatePurchaseOrderInput): Promise<PurchaseOrder> {
  await ensureCsrfCookie()
  const { data } = await api.post<PurchaseOrder>('/purchase-orders', input)
  return data
}

export async function sendPurchaseOrder(id: number): Promise<PurchaseOrder> {
  await ensureCsrfCookie()
  const { data } = await api.post<PurchaseOrder>(`/purchase-orders/${id}/send`)
  return data
}

export async function cancelPurchaseOrder(id: number): Promise<PurchaseOrder> {
  await ensureCsrfCookie()
  const { data } = await api.post<PurchaseOrder>(`/purchase-orders/${id}/cancel`)
  return data
}

export async function updatePurchaseOrder(id: number, input: UpdatePurchaseOrderInput): Promise<PurchaseOrder> {
  await ensureCsrfCookie()
  const { data } = await api.put<PurchaseOrder>(`/purchase-orders/${id}`, input)
  return data
}

export async function receivePurchaseOrder(
  id: number,
  input: ReceivePurchaseOrderInput,
): Promise<ReceivePurchaseOrderResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<ReceivePurchaseOrderResult>(`/purchase-orders/${id}/receive`, input)
  return data
}

export async function searchProducts(search: string, warehouseId?: number): Promise<ProductOption[]> {
  const { data } = await api.get<{ data: RawProduct[] }>('/products', {
    params: { search, warehouse_id: warehouseId || undefined, per_page: 20 },
  })
  return data.data.map(toProductOption)
}

export async function fetchBelowThresholdProducts(warehouseId: number): Promise<BelowThresholdProduct[]> {
  const { data } = await api.get<{ data: RawProduct[] }>('/products', {
    params: { warehouse_id: warehouseId, below_threshold: true, per_page: 100 },
  })
  return data.data.map((p) => {
    const option = toProductOption(p)
    return {
      ...option,
      // Quantité conseillée : (seuil × 2) − stock actuel, minimum 1.
      suggested_quantity: Math.max(1, option.min_stock_alert * 2 - option.current_stock),
    }
  })
}
