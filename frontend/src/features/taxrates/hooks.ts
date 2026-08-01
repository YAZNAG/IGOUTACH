import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createTaxRate,
  deleteTaxRate,
  fetchTaxRates,
  updateTaxRate,
  type TaxRate,
  type TaxRateInput,
} from './api/taxRatesApi'

const KEY = ['tax-rates'] as const

export function useTaxRates() {
  return useQuery<TaxRate[]>({ queryKey: KEY, queryFn: fetchTaxRates, staleTime: 5 * 60_000 })
}

export function useCreateTaxRate() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: TaxRateInput) => createTaxRate(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateTaxRate() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: TaxRateInput }) => updateTaxRate(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteTaxRate() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteTaxRate(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
