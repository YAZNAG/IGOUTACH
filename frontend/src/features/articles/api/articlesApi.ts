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
