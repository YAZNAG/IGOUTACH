import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import type { Category, Paginated } from '@/types'

export interface PriceCell {
  amount: number | null
  min_quantity: number
}

export interface PriceListItem {
  id: number
  sku: string
  name: string
  category_id: number
  prices: {
    detail: PriceCell
    semi_gros: PriceCell
    gros: PriceCell
  }
}

export interface PriceLevel {
  price_type_id: number
  code: string
  name: string
  min_quantity: number
  amount: number | null
  min_margin_percent: number
  margin_percent: number | null
  floor_price: number
  below_floor: boolean
}

export interface ProductPrices {
  product: { id: number; sku: string; name: string }
  unit_cost: number | null
  levels: PriceLevel[]
}

export interface PriceLevelInput {
  price_type_code: string
  amount: number
  min_margin_percent?: number
  min_quantity?: number
}

export interface PricingFilters {
  search?: string
  category_id?: number
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
}

export async function fetchPriceList(filters: PricingFilters = {}): Promise<Paginated<PriceListItem>> {
  const { data } = await api.get<Paginated<PriceListItem>>('/prices', { params: filters })
  return data
}

export async function fetchCategoryOptions(): Promise<Category[]> {
  const { data } = await api.get<{ data: Category[] }>('/categories')
  return data.data
}

export async function exportPrices(
  format: 'xlsx' | 'pdf',
  filters: Omit<PricingFilters, 'page' | 'per_page'> = {},
): Promise<void> {
  await downloadFile('/prices/export', `tarifs.${format}`, { ...filters, format })
}

export async function fetchProductPrices(productId: number): Promise<ProductPrices> {
  const { data } = await api.get<{ data: ProductPrices }>(`/products/${productId}/prices`)
  return data.data
}

export async function updateProductPrices(
  productId: number,
  prices: PriceLevelInput[],
): Promise<ProductPrices> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: ProductPrices }>(`/products/${productId}/prices`, { prices })
  return data.data
}

export interface BulkPreviewRow {
  product_id: number
  sku: string | null
  name: string | null
  current: number
  next: number
}

export interface BulkUpdateResult {
  count: number
  rows: BulkPreviewRow[]
  applied: boolean
}

export async function bulkUpdatePrices(input: {
  price_type_code: string
  percent: number
  category_id?: number
  apply: boolean
}): Promise<BulkUpdateResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: BulkUpdateResult }>('/prices/bulk-update', input)
  return data.data
}

export interface BulkMarginLevel {
  current: number | null
  next: number
}

export interface BulkMarginRow {
  product_id: number
  sku: string
  name: string
  cost: number
  levels: Partial<Record<'detail' | 'semi_gros' | 'gros', BulkMarginLevel>>
}

export interface BulkMarginResult {
  count: number
  skipped: number
  errors?: number
  rows: BulkMarginRow[]
  applied: boolean
}

export async function bulkMarginPrices(input: {
  margins: { detail?: number; semi_gros?: number; gros?: number }
  category_id?: number
  apply: boolean
}): Promise<BulkMarginResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: BulkMarginResult }>('/prices/bulk-margin', input)
  return data.data
}

export interface BelowFloorRow {
  product_id: number
  sku: string
  name: string
  price_type: string
  amount: string
}

export async function fetchBelowFloor(): Promise<BelowFloorRow[]> {
  const { data } = await api.get<{ data: BelowFloorRow[] }>('/prices/below-floor')
  return data.data
}
