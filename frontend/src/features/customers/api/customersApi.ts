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
  price_type_id: number | null
  seller_id: number | null
  warehouse_id: number | null
  credit_limit: number
  balance: number
  available_credit: number
  is_blocked: boolean
  notes: string | null
  is_active: boolean
  price_type?: string | null
  seller?: string | null
  warehouse?: string | null
  created_by?: string | null
}

export interface CustomerInput {
  /** Optionnel : auto-généré (CL-0001) si vide. */
  code?: string
  name: string
  is_company?: boolean
  contact_name?: string | null
  phone?: string | null
  email?: string | null
  address?: string | null
  city?: string | null
  ice?: string | null
  credit_limit?: number | null
  price_type_id?: number | null
  seller_id?: number | null
  warehouse_id?: number | null
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

export async function fetchCustomer(id: number): Promise<Customer> {
  const { data } = await api.get<{ data: Customer }>(`/customers/${id}`)
  return data.data
}

export interface LedgerEntry {
  date: string | null
  type: string
  amount: number
  balance_after: number
  note: string | null
}

/** Part de la dette nee dans un point de vente donne. */
export interface DuParLieu {
  warehouse_id: number | null
  code: string
  name?: string | null
  due: number
  invoices: number
}

export interface CustomerStatement {
  customer: { id: number; code: string; name: string }
  /** Reste du sur les factures non soldees : le montant qui se reclame. */
  balance: number
  /** Solde du grand-livre. Il s'ecarte du precedent quand un reglement n'a
   *  ete impute a aucune facture — l'ecart signale une imputation a faire. */
  ledger_balance?: number
  /** Lieu sur lequel le releve est cadre, null pour qui voit tout. */
  scoped_warehouse_id: number | null
  by_warehouse?: DuParLieu[]
  credit_limit: number
  is_blocked: boolean
  entries: LedgerEntry[]
}

export async function fetchCustomerStatement(id: number): Promise<CustomerStatement> {
  const { data } = await api.get<{ data: CustomerStatement }>(`/customers/${id}/statement`)
  return data.data
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
