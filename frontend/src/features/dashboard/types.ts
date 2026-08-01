export interface DashboardSummary {
  warehouses: number
  products: number
  total_units: number
  distinct_in_stock: number
}

export interface ConsolidatedStockRow {
  product_id: number
  sku: string
  name: string
  total_quantity: number
}

export interface DashboardData {
  summary: DashboardSummary
  stock: ConsolidatedStockRow[]
}
