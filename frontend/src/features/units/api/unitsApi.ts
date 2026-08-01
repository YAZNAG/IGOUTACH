import { api, ensureCsrfCookie } from '@/lib/api'

export interface Unit {
  id: number
  code: string
  name: string
  is_decimal: boolean
  position: number
  is_active: boolean
  products_count?: number
}

export interface UnitInput {
  code: string
  name: string
  is_decimal: boolean
  position?: number
  is_active?: boolean
}

export async function fetchUnits(): Promise<Unit[]> {
  const { data } = await api.get<{ data: Unit[] }>('/units')
  return data.data
}

export async function createUnit(input: UnitInput): Promise<Unit> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Unit }>('/units', input)
  return data.data
}

export async function updateUnit(id: number, input: UnitInput): Promise<Unit> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Unit }>(`/units/${id}`, input)
  return data.data
}

export async function deleteUnit(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/units/${id}`)
}
