import { ChevronDown, ChevronsUpDown, ChevronUp } from 'lucide-react'
import { cn } from '@/lib/utils'

export type SortDirection = 'asc' | 'desc'

export interface SortState {
  sort: string
  direction: SortDirection
}

/** Bascule l'état de tri : même colonne → inverse le sens, sinon nouvelle colonne en asc. */
export function toggleSort(current: SortState, field: string): SortState {
  if (current.sort === field) {
    return { sort: field, direction: current.direction === 'asc' ? 'desc' : 'asc' }
  }
  return { sort: field, direction: 'asc' }
}

interface SortableThProps {
  field: string
  current: SortState
  onSort: (next: SortState) => void
  children: React.ReactNode
  className?: string
  align?: 'left' | 'right'
}

export function SortableTh({ field, current, onSort, children, className, align = 'left' }: SortableThProps) {
  const active = current.sort === field

  return (
    <th className={cn('px-5 py-3 font-medium', className)}>
      <button
        type="button"
        onClick={() => onSort(toggleSort(current, field))}
        className={cn(
          'inline-flex items-center gap-1 transition-colors hover:text-ink',
          active ? 'text-ink' : 'text-muted',
          align === 'right' && 'flex-row-reverse',
        )}
        aria-label={`Trier par ${typeof children === 'string' ? children : field}`}
      >
        <span>{children}</span>
        {active ? (
          current.direction === 'asc' ? (
            <ChevronUp className="h-3.5 w-3.5" />
          ) : (
            <ChevronDown className="h-3.5 w-3.5" />
          )
        ) : (
          <ChevronsUpDown className="h-3.5 w-3.5 opacity-40" />
        )}
      </button>
    </th>
  )
}

/** Texte « Affichage de X à Y sur Z ». */
export function paginationInfo(meta?: { current_page: number; per_page: number; total: number }): string {
  if (!meta || meta.total === 0) {
    return 'Aucun élément'
  }
  const from = (meta.current_page - 1) * meta.per_page + 1
  const to = Math.min(meta.total, meta.current_page * meta.per_page)
  return `Affichage de ${from} à ${to} sur ${meta.total}`
}
