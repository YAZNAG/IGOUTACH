import { api, ensureCsrfCookie } from '@/lib/api'
import type { Paginated } from '@/types'
import type { Warehouse, WarehouseInput, WarehouseType } from '../types'

export async function fetchWarehouses(): Promise<Paginated<Warehouse>> {
  const { data } = await api.get<Paginated<Warehouse>>('/warehouses')
  return data
}

export async function fetchWarehouseTypes(): Promise<WarehouseType[]> {
  const { data } = await api.get<{ data: WarehouseType[] }>('/warehouse-types')
  return data.data
}

export async function createWarehouse(input: WarehouseInput): Promise<Warehouse> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Warehouse }>('/warehouses', input)
  return data.data
}

export async function updateWarehouse(id: number, input: WarehouseInput): Promise<Warehouse> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Warehouse }>(`/warehouses/${id}`, input)
  return data.data
}

export interface WarehouseSummary {
  stock_value: number
  references: number
  below_threshold: number
  ruptures: number
  last_movement_at: string | null
  seller_missing: boolean
}

export interface WarehouseUser {
  id: number
  name: string
  email: string
  is_active: boolean
}

export async function fetchWarehouse(id: number): Promise<Warehouse> {
  const { data } = await api.get<{ data: Warehouse }>(`/warehouses/${id}`)
  return data.data
}

export async function fetchWarehouseSummary(id: number): Promise<WarehouseSummary> {
  const { data } = await api.get<{ data: WarehouseSummary }>(`/warehouses/${id}/summary`)
  return data.data
}

export async function fetchWarehouseUsers(id: number): Promise<WarehouseUser[]> {
  const { data } = await api.get<{ data: WarehouseUser[] }>(`/warehouses/${id}/users`)
  return data.data
}

export async function assignWarehouseUsers(id: number, userIds: number[]): Promise<WarehouseUser[]> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: WarehouseUser[] }>(`/warehouses/${id}/assign-users`, { user_ids: userIds })
  return data.data
}
