import { api, ensureCsrfCookie } from '@/lib/api'
import type { AuthUser } from '@/types'
import type { LoginCredentials } from '../types'

export async function login(credentials: LoginCredentials): Promise<void> {
  await ensureCsrfCookie()
  await api.post('/login', credentials)
}

export async function logout(): Promise<void> {
  await api.post('/logout')
}

export async function fetchCurrentUser(): Promise<AuthUser> {
  const { data } = await api.get<{ data: AuthUser }>('/user')
  return data.data
}
