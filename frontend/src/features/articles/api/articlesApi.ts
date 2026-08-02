import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import type { Category, Paginated, Product } from '@/types'

export interface ImportResult {
  created: number
  updated: number
  skipped: number
}

export interface ArticleInput {
  sku: string
  barcode: string | null
  name: string
  description: string | null
  category_id: number
  brand_id: number | null
  unit_id: number | null
  tax_rate: number | null
  is_serialized: boolean
  min_stock: number | null
  is_active: boolean
}

export interface ArticleFilters {
  search?: string
  category_id?: number
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
}

export async function fetchArticles(filters: ArticleFilters = {}): Promise<Paginated<Product>> {
  const { data } = await api.get<Paginated<Product>>('/products', { params: filters })
  return data
}

export async function fetchCategoryOptions(): Promise<Category[]> {
  const { data } = await api.get<Paginated<Category>>('/categories')
  return data.data
}

export async function createArticle(input: ArticleInput): Promise<Product> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Product }>('/products', input)
  return data.data
}

export async function updateArticle(id: number, input: ArticleInput): Promise<Product> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Product }>(`/products/${id}`, input)
  return data.data
}

export async function deleteArticle(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/products/${id}`)
}

export interface BulkDeleteResult {
  deleted: number
  blocked: { id: number; name: string; reason: string }[]
}

export async function bulkDeleteArticles(ids: number[]): Promise<BulkDeleteResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: BulkDeleteResult }>('/products/bulk-delete', { ids })
  return data.data
}

export async function exportArticles(
  format: 'xlsx' | 'pdf',
  filters: ArticleFilters = {},
): Promise<void> {
  await downloadFile('/products/export', `articles.${format}`, { ...filters, format })
}

export async function importArticles(file: File): Promise<ImportResult> {
  await ensureCsrfCookie()
  const form = new FormData()
  form.append('file', file)
  const { data } = await api.post<{ data: ImportResult }>('/products/import', form)
  return data.data
}

export interface ProductDetail extends Product {
  brand?: { id: number; name: string }
  unit?: { id: number; name: string; symbol: string }
  category?: { id: number; name: string }
  cost_price: string
  created_at: string
  updated_at: string
}

export interface StockLocation {
  id: number
  warehouse_id: number
  warehouse_name: string
  quantity: number
  reserved: number
  available: number
  valuation: string
}

export interface StockDetail {
  product_id: number
  total_quantity: number
  total_reserved: number
  total_available: number
  total_valuation: string
  in_transit: number
  locations: StockLocation[]
}

export interface Movement {
  id: number
  product_id: number
  warehouse_id: number
  warehouse_name: string
  type: string
  quantity: number
  reference: string | null
  notes: string | null
  created_at: string
  user: { id: number; name: string }
}

export interface PriceInfo {
  cost_price: string
  sale_price: string
  quantity_on_hand: number
  turnover_rate: number
}

export async function fetchProductDetail(id: number): Promise<ProductDetail> {
  const { data } = await api.get<{ data: ProductDetail }>(`/products/${id}`)
  return data.data
}

export async function fetchProductStock(id: number): Promise<StockDetail> {
  const { data } = await api.get<{ data: StockDetail }>(`/products/${id}/stock`)
  return data.data
}

export interface MovementFilters {
  type?: string
  warehouse_id?: number
  date_from?: string
  date_to?: string
  limit?: number
}

export async function fetchProductMovements(
  id: number,
  filters?: MovementFilters,
): Promise<Paginated<Movement>> {
  const { data } = await api.get<Paginated<Movement>>(`/products/${id}/movements`, { params: filters })
  return data
}
