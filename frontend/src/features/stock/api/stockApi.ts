import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'

export interface StockRow {
  product_id: number
  sku: string
  name: string
  quantity: number
  average_cost: number
  value: number
  min_stock: number
  status: 'ok' | 'low' | 'rupture'
}

export interface StockMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface StockMovement {
  id: number
  created_at: string
  sku: string
  name: string
  type: string
  type_code: string
  quantity: number
  balance_after: number
  note: string | null
  user: string | null
}

export interface MovementType {
  code: string
  name: string
  sign: number
}

export interface IssueLine {
  product_id: number
  quantity: number
  note?: string | null
}

export interface ProductLite {
  id: number
  sku: string
  name: string
}

export async function fetchStock(warehouseId: number, q?: string, page = 1): Promise<{ data: StockRow[]; meta: StockMeta }> {
  const { data } = await api.get<{ data: StockRow[]; meta: StockMeta }>('/stock', {
    params: { warehouse_id: warehouseId, q: q || undefined, page, per_page: 50 },
  })
  return data
}

export interface MovementFilters {
  warehouse_id?: number
  product_id?: number
  type?: string
  page?: number
}

export async function fetchMovements(filters: MovementFilters): Promise<{ data: StockMovement[]; meta: StockMeta }> {
  const { data } = await api.get<{ data: StockMovement[]; meta: StockMeta }>('/stock/movements', { params: filters })
  return data
}

export async function fetchMovementTypes(): Promise<MovementType[]> {
  const { data } = await api.get<{ data: MovementType[] }>('/stock/movement-types')
  return data.data
}

export async function searchProducts(term: string): Promise<ProductLite[]> {
  const { data } = await api.get<{ data: ProductLite[] }>('/products', {
    params: { search: term || undefined, per_page: 20 },
  })
  return data.data
}

export async function issueStock(payload: {
  warehouse_id: number
  reason_code: string
  lines: IssueLine[]
}): Promise<void> {
  await ensureCsrfCookie()
  await api.post('/stock/issue', payload)
}

export interface EntryLine {
  product_id: number
  quantity: number
  unit_cost?: number | null
  note?: string | null
}

export async function entryStock(payload: {
  warehouse_id: number
  date: string
  lines: EntryLine[]
}): Promise<void> {
  await ensureCsrfCookie()
  await api.post('/stock/entry', payload)
}

export interface MatrixWarehouse {
  id: number
  code: string
  name: string
}

export interface MatrixRow {
  product_id: number
  sku: string
  name: string
  quantities: Record<string, number>
  total: number
}

export async function fetchMatrix(q: string, page = 1): Promise<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }> {
  const { data } = await api.get<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }>('/stock/matrix', {
    params: { q: q || undefined, page, per_page: 50 },
  })
  return data
}

export async function exportStock(warehouseId: number, format: 'xlsx' | 'pdf'): Promise<void> {
  await downloadFile('/stock/export', `stock.${format}`, { warehouse_id: warehouseId, format })
}

export async function exportMatrix(format: 'xlsx' | 'pdf', q?: string): Promise<void> {
  await downloadFile('/stock/matrix/export', `stock-tous-lieux.${format}`, { q: q || undefined, format })
}

export const ISSUE_REASONS: { code: string; label: string }[] = [
  { code: 'breakage', label: 'Casse' },
  { code: 'loss', label: 'Perte' },
  { code: 'internal_use', label: 'Usage interne' },
  { code: 'sav', label: 'SAV' },
]
