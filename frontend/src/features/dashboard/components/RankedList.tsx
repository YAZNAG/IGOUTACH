import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatCurrency } from '@/lib/utils'

export interface RankedRow {
  /** Clé de rendu et libellé principal. */
  name: string
  /** Valeur qui classe la ligne, et qui dimensionne la barre. */
  value: number
  /** Précision affichée sous le nom : nombre de documents, quantité… */
  detail?: string
  /** Seconde valeur, à droite du détail : encours, reste dû… */
  note?: string
  /** Ton de la note : gris par défaut, rouge quand elle signale une dette. */
  noteTone?: 'muted' | 'bad'
}

interface RankedListProps {
  title: string
  hint?: string
  rows: RankedRow[]
  emptyLabel?: string
  /** Couleur de la barre. Distinguer les trois listes aide à les lire. */
  barClassName?: string
}

/**
 * Un classement en barres proportionnelles.
 *
 * Le chiffre seul ne situe rien : 40 000 DH est beaucoup ou peu selon le
 * premier de la liste. La barre donne cette échelle d'un coup d'œil, ce qu'un
 * tableau de nombres ne fait pas.
 */
export function RankedList({
  title,
  hint,
  rows,
  emptyLabel = 'Aucune donnée sur la période.',
  barClassName = 'bg-sky',
}: RankedListProps) {
  const max = rows[0]?.value ?? 0

  return (
    <Card>
      <CardHeader title={title} hint={hint} />
      <CardBody className="space-y-3">
        {rows.length === 0 ? (
          <p className="py-8 text-center text-sm text-muted">{emptyLabel}</p>
        ) : (
          rows.map((row) => (
            <div key={row.name} className="space-y-1.5">
              <div className="flex items-baseline justify-between gap-4">
                <p className="truncate text-sm text-ink">{row.name}</p>
                <p className="mono shrink-0 text-sm font-medium text-ink">
                  {formatCurrency(row.value)}
                </p>
              </div>
              <div className="h-1.5 overflow-hidden rounded-full bg-bg">
                <div
                  className={`h-full rounded-full ${barClassName}`}
                  style={{ width: `${max > 0 ? (row.value / max) * 100 : 0}%` }}
                />
              </div>
              {row.detail || row.note ? (
                <div className="flex items-baseline justify-between gap-4 text-xs">
                  <span className="text-faint">{row.detail ?? ''}</span>
                  {row.note ? (
                    <span className={row.noteTone === 'bad' ? 'text-bad' : 'text-muted'}>
                      {row.note}
                    </span>
                  ) : null}
                </div>
              ) : null}
            </div>
          ))
        )}
      </CardBody>
    </Card>
  )
}
