import { useMemo } from 'react'
import { useCurrentUser } from '@/features/auth'

/**
 * Confort d'affichage uniquement : le backend reste la source de vérité.
 */
export function usePermission(): (permission: string) => boolean {
  const { data: user } = useCurrentUser()
  const permissions = user?.permissions

  return useMemo(() => {
    const granted = new Set(permissions ?? [])
    return (permission: string) => granted.has(permission)
  }, [permissions])
}
