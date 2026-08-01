import { api, ensureCsrfCookie } from '@/lib/api'

export type SettingValue = string | number | boolean
export type SettingsByGroup = Record<string, Record<string, SettingValue>>

export interface SettingsResponse {
  data: SettingsByGroup
  groups: string[]
}

export async function fetchSettings(): Promise<SettingsResponse> {
  const { data } = await api.get<SettingsResponse>('/settings')
  return data
}

export async function updateSettings(values: Record<string, SettingValue>): Promise<SettingsByGroup> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: SettingsByGroup }>('/settings', values)
  return data.data
}

export interface PaymentMethod {
  id: number
  code: string
  name: string
  type: string
  is_active: boolean
  position: number
}

export type PaymentMethodInput = Omit<PaymentMethod, 'id'>

export async function fetchPaymentMethods(): Promise<PaymentMethod[]> {
  const { data } = await api.get<{ data: PaymentMethod[] }>('/payment-methods')
  return data.data
}

export async function createPaymentMethod(input: PaymentMethodInput): Promise<PaymentMethod> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: PaymentMethod }>('/payment-methods', input)
  return data.data
}

export async function updatePaymentMethod(id: number, input: PaymentMethodInput): Promise<PaymentMethod> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: PaymentMethod }>(`/payment-methods/${id}`, input)
  return data.data
}

export async function deletePaymentMethod(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/payment-methods/${id}`)
}

export interface DocumentSequence {
  id: number
  key: string
  prefix: string
  current: number
}

export async function fetchDocumentSequences(): Promise<DocumentSequence[]> {
  const { data } = await api.get<{ data: DocumentSequence[] }>('/document-sequences')
  return data.data
}

export async function updateDocumentSequence(id: number, input: { prefix: string; current: number }): Promise<DocumentSequence> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: DocumentSequence }>(`/document-sequences/${id}`, input)
  return data.data
}
