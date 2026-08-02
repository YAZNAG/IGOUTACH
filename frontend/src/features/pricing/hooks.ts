import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Category, Paginated } from '@/types'
import {
  bulkMarginPrices,
  bulkUpdatePrices,
  fetchBelowFloor,
  fetchCategoryOptions,
  fetchPriceList,
  fetchProductPrices,
  updateProductPrices,
  type BelowFloorRow,
  type PriceLevelInput,
  type PriceListItem,
  type PricingFilters,
  type ProductPrices,
} from './api/pricingApi'

const KEY = ['pricing'] as const

export function usePriceList(filters: PricingFilters) {
  return useQuery<Paginated<PriceListItem>>({
    queryKey: [...KEY, 'list', filters],
    queryFn: () => fetchPriceList(filters),
  })
}

export function usePricingCategories() {
  return useQuery<Category[]>({
    queryKey: ['pricing', 'category-options'],
    queryFn: fetchCategoryOptions,
    staleTime: 5 * 60_000,
  })
}

export function useProductPrices(productId: number | null) {
  return useQuery<ProductPrices>({
    queryKey: ['pricing', 'product', productId],
    queryFn: () => fetchProductPrices(productId as number),
    enabled: productId !== null,
  })
}

export function useUpdateProductPrices() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ productId, prices }: { productId: number; prices: PriceLevelInput[] }) =>
      updateProductPrices(productId, prices),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  })
}

export function useBulkUpdatePrices() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: bulkUpdatePrices,
    onSuccess: (result) => {
      if (result.applied) queryClient.invalidateQueries({ queryKey: KEY })
    },
  })
}

export function useBulkMarginPrices() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: bulkMarginPrices,
    onSuccess: (result) => {
      if (result.applied) queryClient.invalidateQueries({ queryKey: KEY })
    },
  })
}

export function useBelowFloor(enabled: boolean) {
  return useQuery<BelowFloorRow[]>({
    queryKey: ['pricing', 'below-floor'],
    queryFn: fetchBelowFloor,
    enabled,
  })
}
