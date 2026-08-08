import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createCheque,
  deleteCheque,
  endorseCheque,
  fetchCheques,
  updateChequeStatus,
  type Cheque,
  type ChequeFilters,
  type ChequeStatus,
} from './api/chequesApi'

const KEY = ['cheques'] as const

export function useCheques(filters: ChequeFilters = {}, enabled = true) {
  return useQuery<Cheque[]>({
    queryKey: [...KEY, filters],
    queryFn: () => fetchCheques(filters),
    enabled,
  })
}

export function useCreateCheque() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: createCheque,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useEndorseCheque() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, supplierId }: { id: number; supplierId: number }) => endorseCheque(id, supplierId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateChequeStatus() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: ChequeStatus }) => updateChequeStatus(id, status),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteCheque() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: deleteCheque,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}
