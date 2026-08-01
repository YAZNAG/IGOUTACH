import { api, ensureCsrfCookie } from '@/lib/api'
import type { Paginated } from '@/types'

export interface Supplier {
  id: number
  code: string
  name: string
  contact_name: string | null
  phone: string | null
  email: string | null
  address: string | null
  city: string | null
  ice: string | null
  rc: string | null
  payment_terms_days: number
  notes: string | null
  is_active: boolean
}

export interface SupplierInput {
  code: string
  name: string
  contact_name?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
  city?: string | null
  ice?: string | null
  rc?: string | null
  payment_terms_days?: number | null
  notes?: string | null
  is_active?: boolean
}

export interface SupplierFilters {
  q?: string
  is_active?: boolean
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
}

export async function fetchSuppliers(filters: SupplierFilters = {}): Promise<Paginated<Supplier>> {
  const { data } = await api.get<Paginated<Supplier>>('/suppliers', { params: filters })
  return data
}

export async function createSupplier(input: SupplierInput): Promise<Supplier> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Supplier }>('/suppliers', input)
  return data.data
}

export async function updateSupplier(id: number, input: SupplierInput): Promise<Supplier> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Supplier }>(`/suppliers/${id}`, input)
  return data.data
}

export async function deleteSupplier(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/suppliers/${id}`)
}
