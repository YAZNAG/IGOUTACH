export interface AuthUser {
  id: number
  name: string
  email: string
  phone: string | null
  warehouse_id: number | null
  is_active: boolean
  roles: string[]
  permissions: string[]
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface Category {
  id: number
  name: string
  requires_serial: boolean
  is_active: boolean
  products_count?: number
}

export interface Product {
  id: number
  sku: string
  barcode: string | null
  name: string
  description: string | null
  category_id: number
  brand_id: number | null
  unit_id: number
  sale_price: string
  tax_rate: string
  cost_price?: string
  is_serialized: boolean
  min_stock: number | null
  is_active: boolean
}
