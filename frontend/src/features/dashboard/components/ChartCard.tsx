import type { ReactNode } from 'react'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'

interface ChartCardProps {
  title: string
  hint?: string
  /** Affiché à la place du graphique quand il n'y a rien à tracer. */
  isEmpty?: boolean
  emptyLabel?: string
  action?: ReactNode
  /**
   * Laisse le graphique fixer sa propre hauteur.
   *
   * Un classement en barres horizontales dimensionne sa hauteur au nombre de
   * lignes : l'enfermer dans 260 px écraserait deux barres et laisserait du
   * vide sous huit.
   */
  fitContent?: boolean
  /**
   * Serie encore en cours de chargement.
   *
   * Distinct de [isEmpty] : « aucune donnee » est une reponse, pas une
   * attente. Les confondre fait annoncer un stock vide le temps d'un
   * aller-retour reseau.
   */
  isLoading?: boolean
  children: ReactNode
}

/**
 * Enveloppe commune des graphiques : titre, hauteur fixe et message explicite
 * quand la série est vide. Un graphique vide doit se lire comme « aucune
 * donnée », jamais comme un axe à zéro.
 */
export function ChartCard({
  title,
  hint,
  isEmpty = false,
  emptyLabel = 'Aucune donnée sur la période.',
  action,
  fitContent = false,
  isLoading = false,
  children,
}: ChartCardProps) {
  return (
    <Card className="flex flex-col">
      <CardHeader title={title} hint={hint} action={action} />
      <CardBody className="flex-1">
        {isLoading ? (
          <div className="h-[260px] w-full animate-pulse rounded-lg bg-line" />
        ) : isEmpty ? (
          <div className="flex h-[260px] items-center justify-center rounded-lg border border-dashed border-line">
            <p className="text-sm text-muted">{emptyLabel}</p>
          </div>
        ) : (
          <div className={fitContent ? 'w-full' : 'h-[260px] w-full'}>{children}</div>
        )}
      </CardBody>
    </Card>
  )
}
