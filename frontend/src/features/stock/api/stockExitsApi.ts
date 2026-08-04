import { api } from '@/lib/api'
import type { StockEntryFilters, StockEntryList, StockEntryRow } from './stockEntriesApi'

// Les sorties partagent la forme des entrées (mêmes colonnes, valorisation au CMUP).
export type StockExitRow = StockEntryRow
export type StockExitList = StockEntryList
export interface StockExitFilters extends StockEntryFilters {
  customer_id?: number
}

export async function fetchStockExits(filters: StockExitFilters = {}): Promise<StockExitList> {
  const { data } = await api.get<StockExitList>('/stock/exits', { params: filters })
  return data
}

export async function fetchStockExit(id: number): Promise<StockExitRow> {
  const { data } = await api.get<{ data: StockExitRow }>(`/stock/exits/${id}`)
  return data.data
}

export function stockExitsExportUrl(format: 'xlsx' | 'pdf', filters: StockExitFilters): string {
  const params = new URLSearchParams({ format })
  if (filters.date_from) params.set('date_from', filters.date_from)
  if (filters.date_to) params.set('date_to', filters.date_to)
  if (filters.warehouse_id) params.set('warehouse_id', String(filters.warehouse_id))
  if (filters.customer_id) params.set('customer_id', String(filters.customer_id))
  if (filters.type) params.set('type', filters.type)
  if (filters.search) params.set('search', filters.search)
  return `/stock/exits/export?${params.toString()}`
}
