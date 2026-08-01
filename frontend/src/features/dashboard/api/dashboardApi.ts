import { api } from '@/lib/api'
import type { DashboardData } from '../types'

export async function fetchDashboard(): Promise<DashboardData> {
  const { data } = await api.get<{ data: DashboardData }>('/dashboard')
  return data.data
}
