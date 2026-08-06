import type { LucideIcon } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { cn, formatCurrency, formatNumber } from '@/lib/utils'

type Tone = 'navy' | 'sky' | 'ok' | 'warn' | 'bad'

const toneClasses: Record<Tone, string> = {
  navy: 'bg-sky-soft text-navy',
  sky: 'bg-sky-soft text-sky',
  ok: 'bg-ok-bg text-ok',
  warn: 'bg-warn-bg text-warn',
  bad: 'bg-bad-bg text-bad',
}

interface StatTileProps {
  label: string
  value: number
  icon?: LucideIcon
  tone?: Tone
  /** Affiche la valeur en dirhams plutôt qu'en nombre brut. */
  currency?: boolean
  /** Précision sous la valeur (période couverte, détail du calcul…). */
  hint?: string
}

export function StatTile({
  label,
  value,
  icon: Icon,
  tone = 'navy',
  currency = false,
  hint,
}: StatTileProps) {
  return (
    <Card className="flex items-center gap-4 p-5 transition-shadow hover:shadow-[var(--shadow-pop)]">
      {Icon ? (
        <div
          className={cn(
            'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg',
            toneClasses[tone],
          )}
        >
          <Icon className="h-5 w-5" />
        </div>
      ) : null}
      <div className="min-w-0">
        <p className="text-sm text-muted">{label}</p>
        <p className="mono mt-1 truncate text-2xl font-semibold text-ink">
          {currency ? formatCurrency(value) : formatNumber(value)}
        </p>
        {hint ? <p className="mt-0.5 text-xs text-faint">{hint}</p> : null}
      </div>
    </Card>
  )
}
