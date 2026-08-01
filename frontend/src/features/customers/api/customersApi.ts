import { api, ensureCsrfCookie } from '@/lib/api'
import type { Paginated } from '@/types'

export interface Customer {
  id: number
  code: string
  name: string
  is_company: boolean
  contact_name: string | null
  phone: string | null
  email: string | null
  address: string | null
  city: string | null
  ice: string | null
  credit_limit: number
  balance: number
  available_credit: number
  is_blocked: boolean
  notes: string | null
  is_active: boolean
}

export interface CustomerInput {
  code: string
  name: string
  is_company?: boolean
  contact_name?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
  city?: string | null
  ice?: string | null
  notes?: string | null
  is_active?: boolean
}

export interface CustomerFilters {
  q?: string
  is_active?: boolean
  is_blocked?: boolean
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
}

export async function fetchCustomers(filters: CustomerFilters = {}): Promise<Paginated<Customer>> {
  const { data } = await api.get<Paginated<Customer>>('/customers', { params: filters })
  return data
}

export async function createCustomer(input: CustomerInput): Promise<Customer> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Customer }>('/customers', input)
  return data.data
}

export async function updateCustomer(id: number, input: CustomerInput): Promise<Customer> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Customer }>(`/customers/${id}`, input)
  return data.data
}

export async function deleteCustomer(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/customers/${id}`)
}

export async function setCreditLimit(id: number, creditLimit: number): Promise<Customer> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Customer }>(`/customers/${id}/credit`, { credit_limit: creditLimit })
  return data.data
}

export async function toggleBlock(id: number): Promise<Customer> {
  await ensureCsrfCookie()
  const { data } = await api.patch<{ data: Customer }>(`/customers/${id}/block`)
  return data.data
}
