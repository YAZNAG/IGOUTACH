import type { ReactNode } from 'react'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'

interface ChartCardProps {
  title: string
  hint?: string
  /** Affiché à la place du graphique quand il n'y a rien à tracer. */
  isEmpty?: boolean
  emptyLabel?: string
  action?: ReactNode
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
  children,
}: ChartCardProps) {
  return (
    <Card className="flex flex-col">
      <CardHeader title={title} hint={hint} action={action} />
      <CardBody className="flex-1">
        {isEmpty ? (
          <div className="flex h-[260px] items-center justify-center rounded-lg border border-dashed border-line">
            <p className="text-sm text-muted">{emptyLabel}</p>
          </div>
        ) : (
          <div className="h-[260px] w-full">{children}</div>
        )}
      </CardBody>
    </Card>
  )
}
