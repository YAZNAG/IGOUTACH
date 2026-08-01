import { api, ensureCsrfCookie } from '@/lib/api'
import type { Paginated } from '@/types'

export interface AdminUserRole {
  id: number
  name: string
  display_name: string
  level: number
}

export interface AdminUser {
  id: number
  name: string
  email: string
  phone: string | null
  warehouse_id: number | null
  warehouse: { id: number; code: string; name: string } | null
  is_active: boolean
  last_login_at: string | null
  invited: boolean
  roles: AdminUserRole[]
}

export interface UserInput {
  name: string
  email: string
  phone?: string | null
  warehouse_id?: number | null
  role_ids: number[]
  is_active?: boolean
}

export interface UserFilters {
  q?: string
  role_id?: number
  warehouse_id?: number
  is_active?: boolean
  page?: number
  per_page?: number
  sort?: string
  direction?: 'asc' | 'desc'
}

export async function fetchUsers(filters: UserFilters = {}): Promise<Paginated<AdminUser>> {
  const { data } = await api.get<Paginated<AdminUser>>('/users', { params: filters })
  return data
}

export async function createUser(input: UserInput): Promise<AdminUser> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: AdminUser }>('/users', input)
  return data.data
}

export async function updateUser(id: number, input: Omit<UserInput, 'role_ids'>): Promise<AdminUser> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: AdminUser }>(`/users/${id}`, input)
  return data.data
}

export async function toggleUser(id: number): Promise<AdminUser> {
  await ensureCsrfCookie()
  const { data } = await api.patch<{ data: AdminUser }>(`/users/${id}/toggle`)
  return data.data
}

export async function assignRoles(id: number, roleIds: number[]): Promise<AdminUser> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: AdminUser }>(`/users/${id}/roles`, { role_ids: roleIds })
  return data.data
}

export async function resendInvitation(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.post(`/users/${id}/resend-invitation`)
}
