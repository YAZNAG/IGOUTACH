import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import type { Category, Paginated } from '@/types'

export interface CategoryInput {
  name: string
  requires_serial: boolean
  is_active: boolean
}

export async function fetchCategories(search = ''): Promise<Category[]> {
  const { data } = await api.get<Paginated<Category>>('/categories', {
    params: search ? { search } : {},
  })
  return data.data
}

export async function createCategory(input: CategoryInput): Promise<Category> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Category }>('/categories', input)
  return data.data
}

export async function updateCategory(id: number, input: CategoryInput): Promise<Category> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Category }>(`/categories/${id}`, input)
  return data.data
}

export async function deleteCategory(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/categories/${id}`)
}

export interface BulkDeleteResult {
  deleted: number
  blocked: { id: number; name: string; reason: string }[]
}

export async function bulkDeleteCategories(ids: number[]): Promise<BulkDeleteResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: BulkDeleteResult }>('/categories/bulk-delete', { ids })
  return data.data
}

export async function exportCategories(format: 'xlsx' | 'pdf'): Promise<void> {
  await downloadFile('/categories/export', `categories.${format}`, { format })
}
