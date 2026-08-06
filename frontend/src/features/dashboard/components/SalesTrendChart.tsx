import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCurrency, formatCompact } from '@/lib/utils'
import type { SalesTrendPoint } from '../types'
import { axisProps, chartColors, tooltipStyle } from './chartTheme'

interface SalesTrendChartProps {
  data: SalesTrendPoint[]
}

/** Chiffre d'affaires jour par jour sur 30 jours. */
export function SalesTrendChart({ data }: SalesTrendChartProps) {
  // Un jour sur cinq suffit en abscisse : au-delà, les dates se chevauchent.
  const tickInterval = Math.max(0, Math.floor(data.length / 6) - 1)

  return (
    <ResponsiveContainer width="100%" height="100%">
      <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
        <defs>
          <linearGradient id="gradientCa" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={chartColors.sales} stopOpacity={0.28} />
            <stop offset="100%" stopColor={chartColors.sales} stopOpacity={0.02} />
          </linearGradient>
        </defs>
        <CartesianGrid stroke={chartColors.grid} strokeDasharray="3 3" vertical={false} />
        <XAxis dataKey="label" interval={tickInterval} {...axisProps} />
        <YAxis tickFormatter={(value: number) => formatCompact(value)} width={56} {...axisProps} />
        <Tooltip
          contentStyle={tooltipStyle}
          labelFormatter={(label) => `Le ${String(label)}`}
          formatter={(value) => [formatCurrency(Number(value)), 'Chiffre d’affaires']}
        />
        <Area
          type="monotone"
          dataKey="revenue"
          stroke={chartColors.sales}
          strokeWidth={2}
          fill="url(#gradientCa)"
        />
      </AreaChart>
    </ResponsiveContainer>
  )
}
