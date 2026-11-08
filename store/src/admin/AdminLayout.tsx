import { useQuery } from '@tanstack/react-query'
import { ExternalLink, LayoutDashboard, LogOut, Package, Settings, ShoppingBag } from 'lucide-react'
import { Navigate, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { Spinner } from '../components/ui'
import { api, jeton, memoriserJeton } from '../lib/api'

interface Moi {
  id: number
  name: string
  permissions: string[]
}

/** Le compte connecté, lu avec le jeton ; null si le jeton est absent ou mort. */
export function useMoi() {
  return useQuery<Moi | null>({
    queryKey: ['store-admin-me', jeton()],
    queryFn: async () => {
      if (!jeton()) return null
      try {
        const r = await api<{ data: Moi } | Moi>('/v1/user', { auth: true })
        return ('data' in r ? r.data : r) as Moi
      } catch {
        return null
      }
    },
    staleTime: 5 * 60_000,
  })
}

const MENU = [
  { to: '/admin', label: 'Tableau de bord', icon: LayoutDashboard, end: true },
  { to: '/admin/commandes', label: 'Commandes', icon: ShoppingBag },
  { to: '/admin/articles', label: 'Articles en ligne', icon: Package },
  { to: '/admin/reglages', label: 'Réglages', icon: Settings },
]

/**
 * Gabarit du tableau de bord : menu latéral, compte connecté, déconnexion.
 * Sans jeton valide ou sans la permission « store.manage », on renvoie à la
 * connexion.
 */
export function AdminLayout() {
  const navigate = useNavigate()
  const { data: moi, isLoading } = useMoi()

  if (isLoading) return <Spinner />
  if (!moi) return <Navigate to="/admin/connexion" replace />
  if (!moi.permissions.includes('store.manage')) {
    return (
      <div className="mx-auto max-w-md py-20 text-center">
        <p className="font-semibold">Accès réservé</p>
        <p className="mt-2 text-sm text-muted">Votre compte n’a pas la permission « Gérer la boutique en ligne ».</p>
        <button type="button" className="mt-4 text-sm text-brand hover:underline" onClick={() => { memoriserJeton(null); navigate('/admin/connexion') }}>Changer de compte</button>
      </div>
    )
  }

  return (
    <div className="flex min-h-screen bg-paper">
      <aside className="hidden w-60 shrink-0 flex-col border-r border-line bg-white md:flex">
        <div className="flex items-center gap-2 px-5 py-5">
          <span className="flex h-9 w-9 items-center justify-center rounded-full border-2 border-brand text-sm font-black text-brand">iG</span>
          <div>
            <p className="text-sm font-bold leading-tight">iGouTech Store</p>
            <p className="text-xs text-muted">Tableau de bord</p>
          </div>
        </div>
        <nav className="flex-1 space-y-1 px-3">
          {MENU.map((m) => (
            <NavLink
              key={m.to}
              to={m.to}
              end={m.end}
              className={({ isActive }) => `flex items-center gap-3 rounded-lg px-3 py-2 text-sm ${isActive ? 'bg-brand-soft font-medium text-brand-strong' : 'text-muted hover:bg-paper hover:text-ink'}`}
            >
              <m.icon className="h-4 w-4" />
              {m.label}
            </NavLink>
          ))}
          <a href="/" target="_blank" rel="noreferrer" className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted hover:bg-paper hover:text-ink">
            <ExternalLink className="h-4 w-4" /> Voir la boutique
          </a>
        </nav>
        <div className="border-t border-line p-4 text-sm">
          <p className="truncate font-medium">{moi.name}</p>
          <button type="button" className="mt-1 inline-flex items-center gap-1 text-xs text-muted hover:text-bad" onClick={() => { memoriserJeton(null); navigate('/admin/connexion') }}>
            <LogOut className="h-3.5 w-3.5" /> Se déconnecter
          </button>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center gap-2 overflow-x-auto border-b border-line bg-white px-4 py-2 md:hidden">
          {MENU.map((m) => (
            <NavLink key={m.to} to={m.to} end={m.end} className={({ isActive }) => `whitespace-nowrap rounded-lg px-3 py-1.5 text-sm ${isActive ? 'bg-brand-soft text-brand-strong' : 'text-muted'}`}>
              {m.label}
            </NavLink>
          ))}
        </header>
        <main className="flex-1 p-4 sm:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
