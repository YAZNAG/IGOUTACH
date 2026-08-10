import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'

export interface StockRow {
  product_id: number
  sku: string
  name: string
  quantity: number
  /** null quand l'utilisateur n'a pas « product.view_cost_price ». */
  average_cost: number | null
  value: number | null
  min_stock: number
  status: 'ok' | 'low' | 'rupture'
}

export interface StockMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  /** Tri réellement appliqué : le serveur refuse les colonnes inconnues. */
  sort?: string
  direction?: 'asc' | 'desc'
}

/** Paramètres communs aux tableaux paginés du module stock. */
export interface TableParams {
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
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

export interface StockFilters extends TableParams {
  q?: string
  /** Filtre d'état : rupture, sous seuil, ou disponible. */
  status?: 'rupture' | 'low' | 'ok' | ''
}

export async function fetchStock(
  warehouseId: number,
  filters: StockFilters = {},
): Promise<{ data: StockRow[]; meta: StockMeta }> {
  const { data } = await api.get<{ data: StockRow[]; meta: StockMeta }>('/stock', {
    params: {
      warehouse_id: warehouseId,
      q: filters.q || undefined,
      status: filters.status || undefined,
      page: filters.page ?? 1,
      per_page: filters.per_page ?? 50,
      sort: filters.sort,
      direction: filters.direction,
    },
  })
  return data
}

export interface MovementFilters extends TableParams {
  warehouse_id?: number
  product_id?: number
  type?: string
  /** Bornes de date incluses, au format AAAA-MM-JJ. */
  from?: string
  to?: string
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

export async function fetchMatrix(
  q: string,
  params: TableParams = {},
): Promise<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }> {
  const { data } = await api.get<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }>('/stock/matrix', {
    params: {
      q: q || undefined,
      page: params.page ?? 1,
      per_page: params.per_page ?? 50,
      sort: params.sort,
      direction: params.direction,
    },
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
