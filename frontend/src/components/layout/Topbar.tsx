import { LogOut, Menu, Moon, Sun } from 'lucide-react'
import { LanguageSwitcher } from '@/components/i18n/LanguageSwitcher'
import { Button } from '@/components/ui/Button'
import { useTheme } from '@/components/theme/ThemeProvider'
import { useCurrentUser, useLogout } from '@/features/auth'

interface TopbarProps {
  /** Ouvre le menu latéral sur petit écran. */
  onOpenMenu?: () => void
}

export function Topbar({ onOpenMenu }: TopbarProps) {
  const { data: user } = useCurrentUser()
  const logout = useLogout()
  const { theme, toggle } = useTheme()

  return (
    <header className="flex h-16 items-center justify-between border-b border-line bg-card px-4 lg:px-6">
      <button
        type="button"
        onClick={onOpenMenu}
        aria-label="Ouvrir le menu"
        className="flex h-9 w-9 items-center justify-center rounded-lg border border-line text-muted transition-colors hover:bg-bg hover:text-ink lg:hidden"
      >
        <Menu className="h-4 w-4" />
      </button>
      <div className="hidden lg:block" />
      <div className="flex items-center gap-2 lg:gap-3">
        <LanguageSwitcher />
        <button
          type="button"
          onClick={toggle}
          aria-label={theme === 'dark' ? 'Passer en clair' : 'Passer en sombre'}
          className="flex h-9 w-9 items-center justify-center rounded-lg border border-line text-muted transition-colors hover:bg-bg hover:text-ink"
        >
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>

        {/* Sous « lg » seuls l'initiale et l'icône subsistent : le nom complet
            et le libellé du bouton feraient déborder la barre. */}
        <div className="flex items-center gap-2 border-l border-line pl-2 lg:gap-3 lg:pl-3">
          <div className="hidden text-right lg:block">
            <p className="text-sm font-medium text-ink">{user?.name}</p>
            <p className="text-xs text-muted">{user?.roles.join(', ')}</p>
          </div>
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-soft text-sm font-semibold text-navy">
            {user?.name?.charAt(0).toUpperCase() ?? '?'}
          </div>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => logout.mutate()}
            disabled={logout.isPending}
            aria-label="Déconnexion"
          >
            <LogOut className="h-4 w-4" />
            <span className="hidden lg:inline">Déconnexion</span>
          </Button>
        </div>
      </div>
    </header>
  )
}
