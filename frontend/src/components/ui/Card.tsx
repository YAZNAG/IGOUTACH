import type { HTMLAttributes, ReactNode } from 'react'
import { cn } from '@/lib/utils'

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn('rounded-lg border border-line bg-card shadow-[var(--shadow-card)]', className)}
      {...props}
    />
  )
}

interface CardHeaderProps {
  title: string
  hint?: string
  action?: ReactNode
}

export function CardHeader({ title, hint, action }: CardHeaderProps) {
  return (
    // Sur téléphone les actions passent sous le titre : côte à côte, une barre
    // de recherche et deux boutons débordent de l'écran.
    <div className="flex flex-col gap-3 border-b border-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 className="text-sm font-semibold text-ink">{title}</h2>
        {hint ? <p className="mt-0.5 text-sm text-muted">{hint}</p> : null}
      </div>
      {action}
    </div>
  )
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('p-5', className)} {...props} />
}
