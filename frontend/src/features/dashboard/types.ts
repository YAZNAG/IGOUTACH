export interface DashboardSummary {
  warehouses: number
  products: number
  total_units: number
  distinct_in_stock: number
}

export interface FinancialSummary {
  revenue_month: number
  sales_month: number
  outstanding: number
  stock_value: number
}

export interface SalesTrendPoint {
  date: string
  label: string
  revenue: number
  count: number
}

export interface MonthlyFlowPoint {
  month: string
  label: string
  sales: number
  purchases: number
}

export interface WarehouseStockRow {
  warehouse: string
  name: string
  units: number
  value: number
}

export interface TopProductRow {
  name: string
  quantity: number
  revenue: number
}

export interface PaymentMixRow {
  status: 'paid' | 'partial' | 'unpaid'
  label: string
  count: number
  amount: number
}

export interface ConsolidatedStockRow {
  product_id: number
  sku: string
  name: string
  total_quantity: number
}

export interface DashboardData {
  summary: DashboardSummary
  financial: FinancialSummary
  sales_trend: SalesTrendPoint[]
  monthly_flow: MonthlyFlowPoint[]
  stock_by_warehouse: WarehouseStockRow[]
  top_products: TopProductRow[]
  payment_mix: PaymentMixRow[]
  stock: ConsolidatedStockRow[]
}
