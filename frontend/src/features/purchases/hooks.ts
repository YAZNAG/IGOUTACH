import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  fetchGoodsReceipt,
  fetchGoodsReceipts,
  type GoodsReceipt,
  type GoodsReceiptFilters,
  type GoodsReceiptList,
} from './api/goodsReceiptsApi'
import {
  cancelPurchaseOrder,
  createPurchaseOrder,
  fetchBelowThresholdProducts,
  fetchPurchaseOrder,
  fetchPurchaseOrders,
  receivePurchaseOrder,
  searchProducts,
  sendPurchaseOrder,
  updatePurchaseOrder,
  type BelowThresholdProduct,
  type CreatePurchaseOrderInput,
  type ProductOption,
  type PurchaseOrder,
  type PurchaseOrderFilters,
  type PurchaseOrderList,
  type ReceivePurchaseOrderInput,
  type UpdatePurchaseOrderInput,
} from './api/purchaseOrdersApi'

import {
  fetchPaymentMethods,
  fetchReceiptPayments,
  fetchSupplierCredits,
  paySupplierCredit,
  type PaymentMethodOption,
  type PaySupplierCreditInput,
  type SupplierCreditFilters,
  type SupplierCredits,
  type SupplierPaymentRow,
} from './api/supplierCreditsApi'

const KEY = ['purchase-orders'] as const
const CREDITS_KEY = ['supplier-credits'] as const
const DETAIL_KEY = ['purchase-orders', 'detail'] as const
const PRODUCTS_KEY = ['purchase-orders', 'products'] as const
const BELOW_THRESHOLD_KEY = ['purchase-orders', 'below-threshold'] as const
const RECEIPTS_KEY = ['goods-receipts'] as const
const RECEIPT_DETAIL_KEY = ['goods-receipts', 'detail'] as const

export function usePurchaseOrders(filters: PurchaseOrderFilters) {
  return useQuery<PurchaseOrderList>({
    queryKey: [...KEY, filters],
    queryFn: () => fetchPurchaseOrders(filters),
  })
}

export function usePurchaseOrder(id: number) {
  return useQuery<PurchaseOrder>({
    queryKey: [...DETAIL_KEY, id],
    queryFn: () => fetchPurchaseOrder(id),
    enabled: !!id,
  })
}

export function useCreatePurchaseOrder() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: CreatePurchaseOrderInput) => createPurchaseOrder(input),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: KEY })
    },
  })
}

export function useSendPurchaseOrder() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => sendPurchaseOrder(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: KEY })
      queryClient.invalidateQueries({ queryKey: DETAIL_KEY })
    },
  })
}

export function useCancelPurchaseOrder() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => cancelPurchaseOrder(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: KEY })
      queryClient.invalidateQueries({ queryKey: DETAIL_KEY })
    },
  })
}

export function useUpdatePurchaseOrder() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: UpdatePurchaseOrderInput }) =>
      updatePurchaseOrder(id, input),
    onSuccess: (_data, { id }) => {
      queryClient.invalidateQueries({ queryKey: KEY })
      queryClient.invalidateQueries({ queryKey: [...DETAIL_KEY, id] })
    },
  })
}

export function useReceivePurchaseOrder() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: ReceivePurchaseOrderInput }) =>
      receivePurchaseOrder(id, input),
    onSuccess: (_data, { id }) => {
      queryClient.invalidateQueries({ queryKey: KEY })
      queryClient.invalidateQueries({ queryKey: RECEIPTS_KEY })
      queryClient.invalidateQueries({ queryKey: [...DETAIL_KEY, id] })
    },
  })
}

export function useGoodsReceipts(filters: GoodsReceiptFilters) {
  return useQuery<GoodsReceiptList>({
    queryKey: [...RECEIPTS_KEY, filters],
    queryFn: () => fetchGoodsReceipts(filters),
  })
}

export function useGoodsReceipt(id: number) {
  return useQuery<GoodsReceipt>({
    queryKey: [...RECEIPT_DETAIL_KEY, id],
    queryFn: () => fetchGoodsReceipt(id),
    enabled: !!id,
  })
}

export function useSupplierCredits(filters: SupplierCreditFilters) {
  return useQuery<SupplierCredits>({
    queryKey: [...CREDITS_KEY, filters],
    queryFn: () => fetchSupplierCredits(filters),
  })
}

export function usePaymentMethods() {
  return useQuery<PaymentMethodOption[]>({
    queryKey: ['payment-method-options'],
    queryFn: fetchPaymentMethods,
    staleTime: 5 * 60_000,
  })
}

export function useReceiptPayments(receiptId: number | null) {
  return useQuery<SupplierPaymentRow[]>({
    queryKey: ['receipt-payments', receiptId],
    queryFn: () => fetchReceiptPayments(receiptId as number),
    enabled: receiptId !== null,
  })
}

export function usePaySupplierCredit() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ receiptId, input }: { receiptId: number; input: PaySupplierCreditInput }) =>
      paySupplierCredit(receiptId, input),
    onSuccess: (_result, { receiptId }) => {
      queryClient.invalidateQueries({ queryKey: CREDITS_KEY })
      queryClient.invalidateQueries({ queryKey: RECEIPTS_KEY })
      queryClient.invalidateQueries({ queryKey: RECEIPT_DETAIL_KEY })
      queryClient.invalidateQueries({ queryKey: ['receipt-payments', receiptId] })
    },
  })
}

export function useProductAutoComplete(search: string, warehouseId?: number) {
  return useQuery<ProductOption[]>({
    queryKey: [...PRODUCTS_KEY, search, warehouseId],
    queryFn: () => searchProducts(search, warehouseId),
    enabled: search.trim().length >= 1,
    // Garde les résultats précédents affichés pendant le chargement des suivants.
    placeholderData: keepPreviousData,
    staleTime: 60_000,
  })
}

export function useBelowThresholdProducts(warehouseId: number) {
  return useQuery<BelowThresholdProduct[]>({
    queryKey: [...BELOW_THRESHOLD_KEY, warehouseId],
    queryFn: () => fetchBelowThresholdProducts(warehouseId),
    enabled: !!warehouseId,
  })
}
