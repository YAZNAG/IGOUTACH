import { api } from '@/lib/api'

export interface StockEntryRow {
  id: number
  date: string | null
  type: { code: string | null; name: string | null }
  source: { type: string; id: number | null; label: string } | null
  warehouse: { id: number | null; code: string | null; name: string | null }
  product: { id: number | null; sku: string | null; name: string | null; unit: string | null }
  quantity: number
  unit_cost: number
  line_value: number
  balance_after: number
  note: string | null
  author: string | null
}

export interface StockEntryTotals {
  lines_count: number
  total_quantity: number
  total_value: number
}

export interface StockEntryList {
  data: StockEntryRow[]
  totals: StockEntryTotals
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface StockEntryFilters {
  date_from?: string
  date_to?: string
  warehouse_id?: number
  supplier_id?: number
  type?: string
  search?: string
  page?: number
  per_page?: number
}

export async function fetchStockEntries(filters: StockEntryFilters = {}): Promise<StockEntryList> {
  const { data } = await api.get<StockEntryList>('/stock/entries', { params: filters })
  return data
}

export async function fetchStockEntry(id: number): Promise<StockEntryRow> {
  const { data } = await api.get<{ data: StockEntryRow }>(`/stock/entries/${id}`)
  return data.data
}

export function stockEntriesExportUrl(format: 'xlsx' | 'pdf', filters: StockEntryFilters): string {
  const params = new URLSearchParams({ format })
  if (filters.date_from) params.set('date_from', filters.date_from)
  if (filters.date_to) params.set('date_to', filters.date_to)
  if (filters.warehouse_id) params.set('warehouse_id', String(filters.warehouse_id))
  if (filters.type) params.set('type', filters.type)
  if (filters.search) params.set('search', filters.search)
  return `/stock/entries/export?${params.toString()}`
}
