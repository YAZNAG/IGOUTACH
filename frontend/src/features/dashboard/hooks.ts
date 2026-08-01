import { useQuery } from '@tanstack/react-query'
import type { DashboardData } from './types'
import { fetchDashboard } from './api/dashboardApi'

export function useDashboard() {
  return useQuery<DashboardData>({
    queryKey: ['dashboard'],
    queryFn: fetchDashboard,
  })
}
