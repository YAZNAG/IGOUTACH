import { DashboardPage } from '@/features/dashboard'
import { LieuDashboardPage } from '@/features/dashboard/pages/LieuDashboardPage'
import { usePermission } from '@/hooks/usePermission'

export function HomePage() {
  const can = usePermission()

  // La direction voit le consolide de tous les lieux.
  if (can('stock.view_global')) {
    return <DashboardPage />
  }

  // Un responsable voit les chiffres de son lieu. Il tombait auparavant sur
  // une page « Bienvenue » vide.
  return <LieuDashboardPage />
}
