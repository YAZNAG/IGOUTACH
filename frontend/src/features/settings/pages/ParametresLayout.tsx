import { Bookmark, Building2, CreditCard, Database, Hash, Percent, Ruler, Tags, type LucideIcon } from 'lucide-react'
import { NavLink, Outlet } from 'react-router-dom'
import { usePermission } from '@/hooks/usePermission'
import { cn } from '@/lib/utils'

interface SubLink {
  label: string
  to: string
  icon: LucideIcon
  permission?: string
}

const SUB_LINKS: SubLink[] = [
  { label: 'Général', to: '/parametres/general', icon: Building2, permission: 'settings.view' },
  { label: 'Taux de TVA', to: '/parametres/taux-tva', icon: Percent, permission: 'tax_rate.view' },
  { label: 'Unités', to: '/parametres/unites', icon: Ruler, permission: 'unit.view' },
  { label: 'Marques', to: '/parametres/marques', icon: Bookmark, permission: 'brand.view' },
  { label: 'Modes de paiement', to: '/parametres/paiements', icon: CreditCard, permission: 'payment_method.view' },
  { label: 'Types de charge', to: '/parametres/types-charge', icon: Tags, permission: 'expense.create' },
  { label: 'Numérotation', to: '/parametres/numerotation', icon: Hash, permission: 'settings.view' },
  { label: 'Sauvegarde', to: '/parametres/sauvegarde', icon: Database, permission: 'system.backup' },
]

export function ParametresLayout() {
  const can = usePermission()
  const links = SUB_LINKS.filter((link) => !link.permission || can(link.permission))

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Paramètres</h1>
        <p className="text-sm text-muted">Référentiels et réglages de l'application.</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-[220px_1fr]">
        <nav aria-label="Sous-sections des paramètres" className="space-y-1">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                cn(
                  'flex items-center gap-2 rounded px-3 py-2 text-sm transition-colors',
                  isActive ? 'bg-sky-soft font-medium text-navy' : 'text-muted hover:bg-bg hover:text-ink',
                )
              }
            >
              <link.icon className="h-4 w-4" />
              {link.label}
            </NavLink>
          ))}
        </nav>

        <div className="min-w-0">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
