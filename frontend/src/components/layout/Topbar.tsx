import { LogOut, Moon, Sun } from 'lucide-react'
import { LanguageSwitcher } from '@/components/i18n/LanguageSwitcher'
import { Button } from '@/components/ui/Button'
import { useTheme } from '@/components/theme/ThemeProvider'
import { useCurrentUser, useLogout } from '@/features/auth'

export function Topbar() {
  const { data: user } = useCurrentUser()
  const logout = useLogout()
  const { theme, toggle } = useTheme()

  return (
    <header className="flex h-16 items-center justify-between border-b border-line bg-card px-6">
      <div />
      <div className="flex items-center gap-3">
        <LanguageSwitcher />
        <button
          type="button"
          onClick={toggle}
          aria-label={theme === 'dark' ? 'Passer en clair' : 'Passer en sombre'}
          className="flex h-9 w-9 items-center justify-center rounded-lg border border-line text-muted transition-colors hover:bg-bg hover:text-ink"
        >
          {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>

        <div className="flex items-center gap-3 border-l border-line pl-3">
          <div className="text-right">
            <p className="text-sm font-medium text-ink">{user?.name}</p>
            <p className="text-xs text-muted">{user?.roles.join(', ')}</p>
          </div>
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-sky-soft text-sm font-semibold text-navy">
            {user?.name?.charAt(0).toUpperCase() ?? '?'}
          </div>
          <Button variant="ghost" size="sm" onClick={() => logout.mutate()} disabled={logout.isPending}>
            <LogOut className="h-4 w-4" />
            Déconnexion
          </Button>
        </div>
      </div>
    </header>
  )
}
