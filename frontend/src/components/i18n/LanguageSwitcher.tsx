import { Languages } from 'lucide-react'
import { cn } from '@/lib/utils'
import { useLanguage } from './LanguageProvider'

/**
 * Bascule de langue FR / AR. L'arabe passe l'interface en RTL.
 */
export function LanguageSwitcher() {
  const { lang, setLang } = useLanguage()

  return (
    <div className="flex items-center gap-1 rounded-md border border-line px-1 py-0.5 text-xs">
      <Languages className="h-4 w-4 text-muted" />
      <button
        type="button"
        onClick={() => setLang('fr')}
        className={cn('rounded px-2 py-1 transition-colors', lang === 'fr' ? 'bg-sky-soft font-medium text-navy' : 'text-muted hover:text-ink')}
      >
        FR
      </button>
      <button
        type="button"
        onClick={() => setLang('ar')}
        className={cn('rounded px-2 py-1 transition-colors', lang === 'ar' ? 'bg-sky-soft font-medium text-navy' : 'text-muted hover:text-ink')}
      >
        ع
      </button>
    </div>
  )
}
