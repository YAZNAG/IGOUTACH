import { Navigate, Outlet } from 'react-router-dom'
import { useCurrentUser } from '@/features/auth'

export function PublicOnlyRoute() {
  const { data: user, isLoading } = useCurrentUser()

  if (isLoading) {
    return null
  }

  if (user) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}
