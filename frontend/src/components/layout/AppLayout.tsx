import { useEffect, useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { ResponsiveTables } from './ResponsiveTables'
import { Sidebar } from './Sidebar'
import { Topbar } from './Topbar'

export function AppLayout() {
  const [menuOpen, setMenuOpen] = useState(false)
  const location = useLocation()

  // Sur mobile le menu recouvre la page : naviguer doit le refermer, sinon
  // l'utilisateur atterrit sur un écran masqué par le menu qu'il vient d'utiliser.
  useEffect(() => {
    setMenuOpen(false)
  }, [location.pathname])

  return (
    <div className="flex h-screen bg-bg">
      <Sidebar open={menuOpen} onClose={() => setMenuOpen(false)} />
      <div className="flex flex-1 flex-col overflow-hidden">
        <Topbar onOpenMenu={() => setMenuOpen(true)} />
        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          <Outlet />
        </main>
        {/* Étiquette les cellules des tableaux pour leur affichage en fiches
            sur téléphone. N'affiche rien par lui-même. */}
        <ResponsiveTables />
      </div>
    </div>
  )
}
