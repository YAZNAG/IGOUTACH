import { api } from '@/lib/api'
import type { Paginated } from '@/types'

export interface AuditLog {
  id: number
  action: string
  module: string | null
  description: string | null
  entity_type: string | null
  entity_id: number | null
  changes: Record<string, unknown> | null
  ip_address: string | null
  user: { id: number | null; name: string | null; email: string | null } | null
  created_at: string | null
}

export interface AuditFilters {
  action?: string
  module?: string
  from?: string
  to?: string
  page?: number
  per_page?: number
}

export async function fetchAuditLogs(filters: AuditFilters): Promise<Paginated<AuditLog>> {
  const { data } = await api.get<Paginated<AuditLog>>('/audit', { params: filters })
  return data
}

export async function fetchAuditFilterOptions(): Promise<{ actions: string[]; modules: string[] }> {
  const { data } = await api.get<{ actions: string[]; modules: string[] }>('/audit/filters')
  return data
}
