import { useQuery } from '@tanstack/react-query'
import { fetchAuditFilterOptions, fetchAuditLogs, type AuditFilters } from './api/auditApi'

export function useAuditLogs(filters: AuditFilters) {
  return useQuery({
    queryKey: ['audit', filters],
    queryFn: () => fetchAuditLogs(filters),
  })
}

export function useAuditFilterOptions() {
  return useQuery({
    queryKey: ['audit-filters'],
    queryFn: fetchAuditFilterOptions,
    staleTime: 60_000,
  })
}
