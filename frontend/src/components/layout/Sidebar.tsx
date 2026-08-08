import { X } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import logoFull from '@/assets/logo/igoutech-full.svg'
import { usePermission } from '@/hooks/usePermission'
import { cn } from '@/lib/utils'
import { navGroups } from './nav'

interface SidebarProps {
  /** Ouverture du tiroir sur petit écran. Sans effet à partir de `lg`. */
  open?: boolean
  onClose?: () => void
}

export function Sidebar({ open = false, onClose }: SidebarProps) {
  const can = usePermission()

  return (
    <>
      {/* Voile cliquable : refermer le menu doit rester à portée de pouce. */}
      {open ? (
        <div
          className="fixed inset-0 z-30 bg-[rgba(20,20,20,0.5)] lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      ) : null}

      <aside
        className={cn(
          'flex h-full w-64 shrink-0 flex-col border-r border-line bg-card',
          // Hors « lg » la barre sort du flux : elle recouvre la page au lieu
          // de lui voler 256 px, largeur qui rendait le contenu illisible.
          'fixed inset-y-0 left-0 z-40 transition-transform duration-200 lg:static lg:translate-x-0',
          open ? 'translate-x-0' : '-translate-x-full',
        )}
      >
        <div className="flex h-16 items-center justify-between border-b border-line px-5">
          <img src={logoFull} alt="IGOUTECH" className="h-8 w-auto" />
          <button
            type="button"
            onClick={onClose}
            aria-label="Fermer le menu"
            className="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-bg hover:text-ink lg:hidden"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-4">
          {navGroups.map((group) => {
            const items = group.items.filter((item) => !item.permission || can(item.permission))
            if (items.length === 0) return null

            return (
              <div key={group.title} className="mb-6">
                <p className="px-2 pb-2 text-xs font-medium uppercase tracking-wide text-faint">
                  {group.title}
                </p>
                <ul className="space-y-0.5">
                  {items.map((item) => (
                    <li key={item.to}>
                      <NavLink
                        to={item.to}
                        end={item.to === '/'}
                        className={({ isActive }) =>
                          cn(
                            'relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                            isActive
                              ? 'bg-sky-soft font-medium text-navy before:absolute before:left-0 before:top-1.5 before:bottom-1.5 before:w-1 before:rounded-full before:bg-sky'
                              : 'text-muted hover:bg-bg hover:text-ink',
                          )
                        }
                      >
                        <item.icon className="h-4 w-4 shrink-0" />
                        {item.label}
                      </NavLink>
                    </li>
                  ))}
                </ul>
              </div>
            )
          })}
        </nav>
      </aside>
    </>
  )
}
