import { api, ensureCsrfCookie } from '@/lib/api'

export interface TaxRate {
  id: number
  rate: number
  label: string
  is_default: boolean
  position: number
  is_active: boolean
}

export interface TaxRateInput {
  rate: number
  label: string
  is_default?: boolean
  position?: number
  is_active?: boolean
}

export async function fetchTaxRates(): Promise<TaxRate[]> {
  const { data } = await api.get<{ data: TaxRate[] }>('/tax-rates')
  return data.data
}

export async function createTaxRate(input: TaxRateInput): Promise<TaxRate> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: TaxRate }>('/tax-rates', input)
  return data.data
}

export async function updateTaxRate(id: number, input: TaxRateInput): Promise<TaxRate> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: TaxRate }>(`/tax-rates/${id}`, input)
  return data.data
}

export async function deleteTaxRate(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/tax-rates/${id}`)
}
