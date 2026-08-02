import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Paginated } from '@/types'
import {
  approveInventory,
  cancelInventory,
  createInventory,
  fetchInventories,
  fetchInventory,
  saveInventoryLines,
  type Inventory,
} from './api/inventoryApi'

const KEY = ['inventories'] as const

export function useInventories(warehouseId: number | undefined, page: number) {
  return useQuery<Paginated<Inventory>>({
    queryKey: [...KEY, warehouseId, page],
    queryFn: () => fetchInventories(warehouseId, page),
  })
}

export function useInventory(id: number | null) {
  return useQuery<Inventory>({
    queryKey: [...KEY, 'detail', id],
    queryFn: () => fetchInventory(id as number),
    enabled: id !== null,
  })
}

export function useCreateInventory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: createInventory,
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useSaveInventoryLines() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, lines }: { id: number; lines: { product_id: number; counted_quantity: number; reason?: string | null }[] }) =>
      saveInventoryLines(id, lines),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useCancelInventory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => cancelInventory(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useApproveInventory() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => approveInventory(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['stock-movements'] })
    },
  })
}
