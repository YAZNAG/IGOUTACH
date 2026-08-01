import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Paginated } from '@/types'
import {
  createSupplier,
  deleteSupplier,
  fetchSuppliers,
  updateSupplier,
  type Supplier,
  type SupplierFilters,
  type SupplierInput,
} from './api/suppliersApi'

const KEY = ['suppliers'] as const

export function useSuppliers(filters: SupplierFilters) {
  return useQuery<Paginated<Supplier>>({
    queryKey: [...KEY, filters],
    queryFn: () => fetchSuppliers(filters),
  })
}

export function useCreateSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: SupplierInput) => createSupplier(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: SupplierInput }) => updateSupplier(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteSupplier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteSupplier(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
