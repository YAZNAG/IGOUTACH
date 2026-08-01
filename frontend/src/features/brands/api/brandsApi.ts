import { api, ensureCsrfCookie } from '@/lib/api'

export interface Brand {
  id: number
  code: string | null
  name: string
  website: string | null
  logo_path: string | null
  logo_url: string | null
  position: number
  is_active: boolean
  products_count?: number
}

export interface BrandInput {
  code?: string | null
  name: string
  website?: string | null
  is_active?: boolean
}

export async function fetchBrands(): Promise<Brand[]> {
  const { data } = await api.get<{ data: Brand[] }>('/brands')
  return data.data
}

export async function createBrand(input: BrandInput): Promise<Brand> {
  await ensureCsrfCookie()
  const { data } = await api.post<{ data: Brand }>('/brands', input)
  return data.data
}

export async function updateBrand(id: number, input: BrandInput): Promise<Brand> {
  await ensureCsrfCookie()
  const { data } = await api.put<{ data: Brand }>(`/brands/${id}`, input)
  return data.data
}

export async function deleteBrand(id: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/brands/${id}`)
}

export async function uploadBrandLogo(id: number, file: File): Promise<Brand> {
  await ensureCsrfCookie()
  const form = new FormData()
  form.append('logo', file)
  const { data } = await api.post<{ data: Brand }>(`/brands/${id}/logo`, form)
  return data.data
}
