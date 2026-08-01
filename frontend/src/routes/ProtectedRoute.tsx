import { Navigate, Outlet } from 'react-router-dom'
import { useCurrentUser } from '@/features/auth'

export function ProtectedRoute() {
  const { data: user, isLoading, isError } = useCurrentUser()

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center bg-bg">
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (isError || !user) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}
