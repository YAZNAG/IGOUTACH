import { api, ensureCsrfCookie } from '@/lib/api'
import type { NamedRef, PaymentStatus } from './purchaseOrdersApi'

export interface SupplierCreditRow {
  id: number
  number: string
  purchase_order: { id: number; number: string } | null
  supplier: NamedRef
  warehouse: NamedRef
  received_at: string | null
  invoice_number: string | null
  payment_status: PaymentStatus
  total_amount: number
  amount_paid: number
  remaining_amount: number
}

export interface SupplierCreditSummary {
  supplier: NamedRef
  receipts_count: number
  total_due: number
}

export interface SupplierCredits {
  rows: SupplierCreditRow[]
  suppliers: SupplierCreditSummary[]
  total_due: number
  receipts_count: number
}

export interface SupplierCreditFilters {
  supplier_id?: number
  search?: string
}

export interface PaymentMethodOption {
  id: number
  name: string
}

export interface PaySupplierCreditInput {
  amount: number
  payment_method_id?: number | null
  paid_at?: string
  notes?: string | null
}

export interface PaySupplierCreditResult {
  payment_id: number
  payment_status: PaymentStatus
  amount_paid: number
  remaining_amount: number
}

export interface SupplierPaymentRow {
  id: number
  amount: number
  paid_at: string
  payment_method: string | null
  notes: string | null
  created_by: string | null
}

export async function fetchSupplierCredits(filters: SupplierCreditFilters = {}): Promise<SupplierCredits> {
  const { data } = await api.get<{ data: SupplierCredits }>('/supplier-credits', { params: filters })
  return data.data
}

export async function fetchPaymentMethods(): Promise<PaymentMethodOption[]> {
  const { data } = await api.get<{ data: PaymentMethodOption[] }>('/payment-methods')
  return data.data
}

export async function fetchReceiptPayments(receiptId: number): Promise<SupplierPaymentRow[]> {
  const { data } = await api.get<{ data: SupplierPaymentRow[] }>(`/goods-receipts/${receiptId}/payments`)
  return data.data
}

export async function paySupplierCredit(receiptId: number, input: PaySupplierCreditInput): Promise<PaySupplierCreditResult> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: PaySupplierCreditResult }>(`/goods-receipts/${receiptId}/pay`, input)
  return data.data
}
