import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchSessions, revokeSession, revokeUserSessions } from './api/sessionsApi'

const KEY = ['sessions'] as const

export function useSessions() {
  return useQuery({ queryKey: KEY, queryFn: fetchSessions })
}

export function useRevokeSession() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: string) => revokeSession(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useRevokeUserSessions() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (userId: number) => revokeUserSessions(userId),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
