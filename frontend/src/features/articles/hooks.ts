import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Category, Paginated, Product } from '@/types'
import {
  bulkDeleteArticles,
  createArticle,
  deleteArticle,
  fetchArticles,
  fetchCategoryOptions,
  fetchProductDetail,
  fetchProductHistory,
  fetchProductImages,
  fetchProductMovements,
  fetchProductStatistics,
  fetchProductStock,
  importArticles,
  setMainProductImage,
  updateArticle,
  uploadProductImage,
  deleteProductImage,
  type ArticleFilters,
  type ArticleInput,
  type Movement,
  type MovementFilters,
  type ProductDetail,
  type ProductHistoryEntry,
  type ProductImage,
  type ProductStatistics,
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

// ─── Médias, statistiques et historique ────────────────────────────────────

const IMAGES_KEY = ['articles', 'images'] as const
const STATS_KEY = ['articles', 'statistics'] as const
const HISTORY_KEY = ['articles', 'history'] as const

export function useProductImages(id: number) {
  return useQuery<ProductImage[]>({
    queryKey: [...IMAGES_KEY, id],
    queryFn: () => fetchProductImages(id),
    enabled: id > 0,
  })
}

/**
 * Les trois mutations d'image renvoient la galerie complète : on écrit le
 * résultat directement dans le cache plutôt que de relancer une requête.
 */
function useImageMutation<TArgs>(fn: (id: number, args: TArgs) => Promise<ProductImage[]>, id: number) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (args: TArgs) => fn(id, args),
    onSuccess: (images) => {
      queryClient.setQueryData([...IMAGES_KEY, id], images)
      queryClient.invalidateQueries({ queryKey: [...DETAIL_KEY, id] })
    },
  })
}

export function useUploadProductImage(id: number) {
  return useImageMutation<File>((productId, file) => uploadProductImage(productId, file), id)
}

export function useSetMainProductImage(id: number) {
  return useImageMutation<number>((productId, imageId) => setMainProductImage(productId, imageId), id)
}

export function useDeleteProductImage(id: number) {
  return useImageMutation<number>((productId, imageId) => deleteProductImage(productId, imageId), id)
}

export function useProductStatistics(id: number, period = '12m') {
  return useQuery<ProductStatistics>({
    queryKey: [...STATS_KEY, id, period],
    queryFn: () => fetchProductStatistics(id, period),
    enabled: id > 0,
  })
}

export function useProductHistory(id: number) {
  return useQuery<ProductHistoryEntry[]>({
    queryKey: [...HISTORY_KEY, id],
    queryFn: () => fetchProductHistory(id),
    enabled: id > 0,
  })
}
