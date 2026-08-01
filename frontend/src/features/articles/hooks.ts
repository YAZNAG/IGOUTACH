import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Category, Paginated, Product } from '@/types'
import {
  bulkDeleteArticles,
  createArticle,
  deleteArticle,
  fetchArticles,
  fetchCategoryOptions,
  importArticles,
  updateArticle,
  type ArticleFilters,
  type ArticleInput,
} from './api/articlesApi'

const KEY = ['articles'] as const

export function useDeleteArticle() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteArticle(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useBulkDeleteArticles() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (ids: number[]) => bulkDeleteArticles(ids),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useImportArticles() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (file: File) => importArticles(file),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useArticles(filters: ArticleFilters) {
  return useQuery<Paginated<Product>>({
    queryKey: [...KEY, filters],
    queryFn: () => fetchArticles(filters),
  })
}

export function useCategoryOptions() {
  return useQuery<Category[]>({
    queryKey: ['articles', 'category-options'],
    queryFn: fetchCategoryOptions,
    staleTime: 5 * 60_000,
  })
}

export function useCreateArticle() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: ArticleInput) => createArticle(input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateArticle() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: ArticleInput }) => updateArticle(id, input),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}
