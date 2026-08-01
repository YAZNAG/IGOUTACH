import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createPaymentMethod,
  deletePaymentMethod,
  fetchDocumentSequences,
  fetchPaymentMethods,
  fetchSettings,
  updateDocumentSequence,
  updatePaymentMethod,
  updateSettings,
  type PaymentMethodInput,
  type SettingValue,
} from './api/settingsApi'

const SETTINGS = ['settings'] as const
const PAYMENT_METHODS = ['payment-methods'] as const
const DOCUMENT_SEQUENCES = ['document-sequences'] as const

export function useSettings() {
  return useQuery({ queryKey: SETTINGS, queryFn: fetchSettings })
}

export function useUpdateSettings() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (values: Record<string, SettingValue>) => updateSettings(values),
    onSuccess: () => qc.invalidateQueries({ queryKey: SETTINGS }),
  })
}

export function usePaymentMethods() {
  return useQuery({ queryKey: PAYMENT_METHODS, queryFn: fetchPaymentMethods })
}

export function useCreatePaymentMethod() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: PaymentMethodInput) => createPaymentMethod(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: PAYMENT_METHODS }),
  })
}

export function useUpdatePaymentMethod() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: PaymentMethodInput }) => updatePaymentMethod(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: PAYMENT_METHODS }),
  })
}

export function useDeletePaymentMethod() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deletePaymentMethod(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: PAYMENT_METHODS }),
  })
}

export function useDocumentSequences() {
  return useQuery({ queryKey: DOCUMENT_SEQUENCES, queryFn: fetchDocumentSequences })
}

export function useUpdateDocumentSequence() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: { prefix: string; current: number } }) =>
      updateDocumentSequence(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: DOCUMENT_SEQUENCES }),
  })
}
