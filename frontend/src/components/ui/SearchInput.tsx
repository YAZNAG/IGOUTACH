import { Search, X } from 'lucide-react'
import { cn } from '@/lib/utils'

interface SearchInputProps {
  /** Relie le champ à son <label> : sans lui, `htmlFor` ne pointe sur rien. */
  id?: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  className?: string
  /** Décrit ce qui est cherché quand le champ n'est pas visuellement étiqueté. */
  'aria-label'?: string
}

/**
 * Champ de recherche d'un tableau.
 *
 * Le bouton d'effacement compte : sans lui, revenir à la liste complète
 * demande de vider le champ caractère par caractère, et l'on croit la liste
 * vide alors qu'elle est seulement filtrée.
 */
export function SearchInput({
  id,
  value,
  onChange,
  placeholder = 'Rechercher…',
  className,
  'aria-label': ariaLabel,
}: SearchInputProps) {
  return (
    <div className={cn('relative', className ?? 'w-64')}>
      <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
      <input
        id={id}
        type="search"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        aria-label={ariaLabel ?? placeholder}
        className={cn(
          'h-10 w-full rounded border border-line-2 bg-card pl-9 pr-9 text-sm text-ink placeholder:text-faint',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky focus-visible:border-sky',
          // Chrome ajoute sa propre croix sur input[type=search] : deux
          // boutons d'effacement côte à côte, on masque le sien.
          '[&::-webkit-search-cancel-button]:appearance-none',
        )}
      />
      {value ? (
        <button
          type="button"
          onClick={() => onChange('')}
          aria-label="Effacer la recherche"
          className="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-faint transition-colors hover:text-ink"
        >
          <X className="h-4 w-4" />
        </button>
      ) : null}
    </div>
  )
}
