import type { LucideIcon } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { cn, formatNumber } from '@/lib/utils'

type Tone = 'navy' | 'sky' | 'ok' | 'warn'

const toneClasses: Record<Tone, string> = {
  navy: 'bg-sky-soft text-navy',
  sky: 'bg-sky-soft text-sky',
  ok: 'bg-ok-bg text-ok',
  warn: 'bg-warn-bg text-warn',
}

interface StatTileProps {
  label: string
  value: number
  icon?: LucideIcon
  tone?: Tone
}

export function StatTile({ label, value, icon: Icon, tone = 'navy' }: StatTileProps) {
  return (
    <Card className="flex items-center gap-4 p-5">
      {Icon ? (
        <div className={cn('flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', toneClasses[tone])}>
          <Icon className="h-5 w-5" />
        </div>
      ) : null}
      <div>
        <p className="text-sm text-muted">{label}</p>
        <p className="mono mt-1 text-2xl font-semibold text-ink">{formatNumber(value)}</p>
      </div>
    </Card>
  )
}
