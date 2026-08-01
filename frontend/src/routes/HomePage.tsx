import { PlaceholderPage } from '@/components/layout/PlaceholderPage'
import { DashboardPage } from '@/features/dashboard'
import { usePermission } from '@/hooks/usePermission'

export function HomePage() {
  const can = usePermission()

  if (can('stock.view_global')) {
    return <DashboardPage />
  }

  return <PlaceholderPage title="Bienvenue" />
}
