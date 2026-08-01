import { api, ensureCsrfCookie } from '@/lib/api'

export interface Role {
  id: number
  name: string
  display_name: string
  description: string | null
  is_system: boolean
  level: number
  users_count?: number
  permissions_count?: number
  permission_ids?: number[]
}

export interface RoleInput {
  name: string
  display_name: string
  description?: string | null
  level?: number
}

export interface PermissionItem {
  id: number
  name: string
  display_name: string
}

export interface PermissionGroup {
  module: string
  permissions: PermissionItem[]
}

export async function fetchRoles(): Promise<Role[]> {
  const { data } = await api.get<{ data: Role[] }>('/roles')
  return data.data
}

export async function fetchPermissionGroups(): Promise<PermissionGroup[]> {
  const { data } = await api.get<{ data: PermissionGroup[] }>('/permissions')
  return data.data
}

export async function createRole(input: RoleInput): Promise<Role> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Role }>('/roles', input)
  return data.data
}

export async function updateRole(id: number, input: RoleInput): Promise<Role> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Role }>(`/roles/${id}`, input)
  return data.data
}

export async function deleteRole(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/roles/${id}`)
}

export async function setRolePermissions(id: number, permissionIds: number[]): Promise<Role> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Role }>(`/roles/${id}/permissions`, { permission_ids: permissionIds })
  return data.data
}

export async function duplicateRole(id: number, name: string, displayName: string): Promise<Role> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Role }>(`/roles/${id}/duplicate`, {
    name,
    display_name: displayName,
  })
  return data.data
}
