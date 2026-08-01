import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Category } from '@/types'
import {
  bulkDeleteCategories,
  createCategory,
  deleteCategory,
  fetchCategories,
  updateCategory,
  type CategoryInput,
} from './api/categoriesApi'

const KEY = ['categories'] as const

export function useDeleteCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteCategory(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useBulkDeleteCategories() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (ids: number[]) => bulkDeleteCategories(ids),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useCategories(search = '') {
  return useQuery<Category[]>({
    queryKey: [...KEY, search],
    queryFn: () => fetchCategories(search),
  })
}

export function useCreateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: CategoryInput) => createCategory(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateCategory() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: CategoryInput }) => updateCategory(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}
