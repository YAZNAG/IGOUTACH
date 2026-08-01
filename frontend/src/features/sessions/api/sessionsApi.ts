import { api, ensureCsrfCookie } from '@/lib/api'

export interface ActiveSession {
  id: string
  user_id: number | null
  user_name: string | null
  user_email: string | null
  ip_address: string | null
  user_agent: string | null
  last_activity: string
  is_current: boolean
}

export async function fetchSessions(): Promise<ActiveSession[]> {
  const { data } = await api.get<{ data: ActiveSession[] }>('/sessions')
  return data.data
}

export async function revokeSession(id: string): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/sessions/${id}`)
}

export async function revokeUserSessions(userId: number): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/users/${userId}/sessions`)
}
