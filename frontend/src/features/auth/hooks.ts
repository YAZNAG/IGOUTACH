import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AuthUser } from '@/types'
import { fetchCurrentUser, login, logout } from './api/authApi'
import type { LoginCredentials } from './types'

const ME_KEY = ['auth', 'me'] as const

export function useCurrentUser() {
  return useQuery<AuthUser>({
    queryKey: ME_KEY,
    queryFn: fetchCurrentUser,
    retry: false,
    staleTime: 60_000,
  })
}

export function useLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (credentials: LoginCredentials) => login(credentials),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ME_KEY })
    },
  })
}

export function useLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => logout(),
    onSuccess: () => {
      queryClient.setQueryData(ME_KEY, null)
      queryClient.clear()
    },
  })
}
