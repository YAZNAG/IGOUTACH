import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Category, Paginated, Product } from '@/types'
import {
  bulkDeleteArticles,
  createArticle,
  deleteArticle,
  fetchArticles,
  fetchCategoryOptions,
  fetchProductDetail,
  fetchProductMovements,
  fetchProductStock,
  importArticles,
  updateArticle,
  type ArticleFilters,
  type ArticleInput,
  type Movement,
  type MovementFilters,
  type ProductDetail,
  type StockDetail,
} from './api/articlesApi'

const KEY = ['articles'] as const
const DETAIL_KEY = ['articles', 'detail'] as const
const STOCK_KEY = ['articles', 'stock'] as const
const MOVEMENTS_KEY = ['articles', 'movements'] as const

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

export function useProductDetail(id: number) {
  return useQuery<ProductDetail>({
    queryKey: [...DETAIL_KEY, id],
    queryFn: () => fetchProductDetail(id),
    enabled: !!id,
  })
}

export function useProductStock(id: number) {
  return useQuery<StockDetail>({
    queryKey: [...STOCK_KEY, id],
    queryFn: () => fetchProductStock(id),
    enabled: !!id,
  })
}

export function useProductMovements(id: number, filters?: MovementFilters) {
  return useQuery<Paginated<Movement>>({
    queryKey: [...MOVEMENTS_KEY, id, filters],
    queryFn: () => fetchProductMovements(id, filters),
    enabled: !!id,
  })
}
