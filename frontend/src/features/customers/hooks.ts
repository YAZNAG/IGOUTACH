import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Paginated } from '@/types'
import {
  createCustomer,
  deleteCustomer,
  fetchCustomers,
  setCreditLimit,
  toggleBlock,
  updateCustomer,
  type Customer,
  type CustomerFilters,
  type CustomerInput,
} from './api/customersApi'

const KEY = ['customers'] as const

export function useCustomers(filters: CustomerFilters) {
  return useQuery<Paginated<Customer>>({
    queryKey: [...KEY, filters],
    queryFn: () => fetchCustomers(filters),
  })
}

export function useCreateCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: CustomerInput) => createCustomer(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: CustomerInput }) => updateCustomer(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteCustomer() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteCustomer(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useSetCreditLimit() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, creditLimit }: { id: number; creditLimit: number }) => setCreditLimit(id, creditLimit),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useToggleBlock() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => toggleBlock(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
