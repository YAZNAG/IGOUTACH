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
  stock_value_purchase: number
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

/** Chiffre d'affaires realise par un lieu. */
export interface WarehouseRevenueRow {
  warehouse: string
  name: string
  count: number
  revenue: number
}

/** Un client, ce qu'il a achete et ce qu'il doit encore. */
export interface TopCustomerRow {
  name: string
  count: number
  revenue: number
  balance: number
}

/** Un fournisseur, ce qu'on lui a achete et ce qu'on lui doit. */
export interface TopSupplierRow {
  name: string
  count: number
  purchases: number
  due: number
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

/** Encours par tranche d'anciennete. */
export interface AgingBucketRow {
  bucket: string
  amount: number
}

/** Charges de la periode, par categorie. */
export interface ExpenseCategoryRow {
  name: string
  amount: number
  count: number
}

export interface DashboardData {
  summary: DashboardSummary
  financial: FinancialSummary
  sales_trend: SalesTrendPoint[]
  monthly_flow: MonthlyFlowPoint[]
  stock_by_warehouse: WarehouseStockRow[]
  top_products: TopProductRow[]
  revenue_by_warehouse: WarehouseRevenueRow[]
  top_customers: TopCustomerRow[]
  top_suppliers: TopSupplierRow[]
  payment_mix: PaymentMixRow[]
  aging: AgingBucketRow[]
  expenses_by_category: ExpenseCategoryRow[]
  stock: ConsolidatedStockRow[]
}
