import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Paginated } from '@/types'
import {
  assignWarehouseUsers,
  createWarehouse,
  fetchWarehouse,
  fetchWarehouses,
  fetchWarehouseSummary,
  fetchWarehouseTypes,
  fetchWarehouseUsers,
  updateWarehouse,
} from './api/warehousesApi'
import type { Warehouse, WarehouseInput, WarehouseType } from './types'

const WAREHOUSES_KEY = ['warehouses'] as const
const TYPES_KEY = ['warehouse-types'] as const

export function useWarehouses() {
  return useQuery<Paginated<Warehouse>>({
    queryKey: WAREHOUSES_KEY,
    queryFn: fetchWarehouses,
  })
}

export function useWarehouseTypes() {
  return useQuery<WarehouseType[]>({
    queryKey: TYPES_KEY,
    queryFn: fetchWarehouseTypes,
    staleTime: 5 * 60_000,
  })
}

export function useCreateWarehouse() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: WarehouseInput) => createWarehouse(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: WAREHOUSES_KEY }),
  })
}

export function useUpdateWarehouse() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: WarehouseInput }) => updateWarehouse(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: WAREHOUSES_KEY }),
  })
}

export function useWarehouse(id: number) {
  return useQuery({ queryKey: [...WAREHOUSES_KEY, id], queryFn: () => fetchWarehouse(id) })
}

export function useWarehouseSummary(id: number) {
  return useQuery({ queryKey: [...WAREHOUSES_KEY, id, 'summary'], queryFn: () => fetchWarehouseSummary(id) })
}

export function useWarehouseUsers(id: number) {
  return useQuery({ queryKey: [...WAREHOUSES_KEY, id, 'users'], queryFn: () => fetchWarehouseUsers(id) })
}

export function useAssignWarehouseUsers(id: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (userIds: number[]) => assignWarehouseUsers(id, userIds),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: [...WAREHOUSES_KEY, id, 'users'] }),
  })
}
