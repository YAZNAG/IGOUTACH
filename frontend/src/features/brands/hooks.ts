import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createBrand,
  deleteBrand,
  fetchBrands,
  updateBrand,
  uploadBrandLogo,
  type Brand,
  type BrandInput,
} from './api/brandsApi'

const KEY = ['brands'] as const

export function useBrands() {
  return useQuery<Brand[]>({ queryKey: KEY, queryFn: fetchBrands })
}

export function useCreateBrand() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: BrandInput) => createBrand(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateBrand() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: BrandInput }) => updateBrand(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteBrand() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteBrand(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUploadBrandLogo() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, file }: { id: number; file: File }) => uploadBrandLogo(id, file),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
