import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { Paginated } from '@/types'
import {
  createRole,
  deleteRole,
  duplicateRole,
  fetchPermissionGroups,
  fetchRoles,
  setRolePermissions,
  updateRole,
  type PermissionGroup,
  type Role,
  type RoleInput,
} from './api/rolesApi'
import {
  assignRoles,
  createUser,
  fetchUsers,
  resendInvitation,
  toggleUser,
  updateUser,
  type AdminUser,
  type UserFilters,
  type UserInput,
} from './api/usersApi'

const USERS = ['users'] as const
const ROLES = ['roles'] as const
const PERMS = ['permissions'] as const

export interface WarehouseOption {
  id: number
  code: string
  name: string
}

export function useWarehouseOptions() {
  return useQuery<WarehouseOption[]>({
    queryKey: ['warehouse-options'],
    queryFn: async () => {
      const { data } = await api.get<Paginated<WarehouseOption>>('/warehouses')
      return data.data
    },
    staleTime: 5 * 60_000,
  })
}

// ---- Rôles & permissions ----

export function useRoles() {
  return useQuery<Role[]>({ queryKey: ROLES, queryFn: fetchRoles })
}

export function usePermissionGroups() {
  return useQuery<PermissionGroup[]>({ queryKey: PERMS, queryFn: fetchPermissionGroups, staleTime: 5 * 60_000 })
}

export function useCreateRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: RoleInput) => createRole(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ROLES }),
  })
}

export function useUpdateRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: RoleInput }) => updateRole(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ROLES }),
  })
}

export function useDeleteRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteRole(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ROLES }),
  })
}

export function useSetRolePermissions() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, permissionIds }: { id: number; permissionIds: number[] }) =>
      setRolePermissions(id, permissionIds),
    onSuccess: () => qc.invalidateQueries({ queryKey: ROLES }),
  })
}

export function useDuplicateRole() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, name, displayName }: { id: number; name: string; displayName: string }) =>
      duplicateRole(id, name, displayName),
    onSuccess: () => qc.invalidateQueries({ queryKey: ROLES }),
  })
}

// ---- Utilisateurs ----

export function useUsers(filters: UserFilters) {
  return useQuery<Paginated<AdminUser>>({
    queryKey: [...USERS, filters],
    queryFn: () => fetchUsers(filters),
  })
}

export function useCreateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: UserInput) => createUser(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS }),
  })
}

export function useUpdateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: Omit<UserInput, 'role_ids'> }) => updateUser(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS }),
  })
}

export function useToggleUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => toggleUser(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS }),
  })
}

export function useAssignRoles() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, roleIds }: { id: number; roleIds: number[] }) => assignRoles(id, roleIds),
    onSuccess: () => qc.invalidateQueries({ queryKey: USERS }),
  })
}

export function useResendInvitation() {
  return useMutation({ mutationFn: (id: number) => resendInvitation(id) })
}
