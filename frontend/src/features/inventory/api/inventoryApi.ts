import { api, ensureCsrfCookie } from '@/lib/api'
import type { Paginated } from '@/types'

export interface InventoryLine {
  product_id: number
  sku: string
  name: string
  system_quantity: number
  counted_quantity: number
  difference: number
  reason: string | null
  variance_value: number | null
}

export interface Inventory {
  id: number
  reference: string
  warehouse_id: number
  warehouse: { id: number; code: string; name: string } | null
  counted_at: string | null
  status: 'draft' | 'approved' | 'cancelled'
  note: string | null
  lines_count?: number
  lines?: InventoryLine[]
}

export async function fetchInventories(warehouseId?: number, page = 1): Promise<Paginated<Inventory>> {
  const { data } = await api.get<Paginated<Inventory>>('/inventories', {
    params: { warehouse_id: warehouseId || undefined, page },
  })
  return data
}

export async function createInventory(input: { warehouse_id: number; counted_at: string; note?: string | null }): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Inventory }>('/inventories', input)
  return data.data
}

export async function fetchInventory(id: number): Promise<Inventory> {
  const { data } = await api.get<{ data: Inventory }>(`/inventories/${id}`)
  return data.data
}

export async function saveInventoryLines(
  id: number,
  lines: { product_id: number; counted_quantity: number; reason?: string | null }[],
): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Inventory }>(`/inventories/${id}/lines`, { lines })
  return data.data
}

/** Reprise un autre jour : met à jour la date de comptage / la note. */
export async function updateInventory(
  id: number,
  input: { counted_at?: string; note?: string | null },
): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Inventory }>(`/inventories/${id}`, input)
  return data.data
}

/** Retire un comptage saisi par erreur (l'article redevient non compté). */
export async function removeInventoryLine(id: number, productId: number): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.delete<{ data: Inventory }>(`/inventories/${id}/lines/${productId}`)
  return data.data
}

export async function approveInventory(id: number): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Inventory }>(`/inventories/${id}/approve`)
  return data.data
}

export async function cancelInventory(id: number): Promise<Inventory> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Inventory }>(`/inventories/${id}/cancel`)
  return data.data
}
