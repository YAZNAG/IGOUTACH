import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCompact, formatCurrency } from '@/lib/utils'
import type { MonthlyFlowPoint } from '../types'
import { axisProps, chartColors, tooltipStyle } from './chartTheme'

interface MonthlyFlowChartProps {
  data: MonthlyFlowPoint[]
}

/** Ventes facturées face aux achats réceptionnés, sur 6 mois glissants. */
export function MonthlyFlowChart({ data }: MonthlyFlowChartProps) {
  return (
    <ResponsiveContainer width="100%" height="100%">
      <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }} barGap={4}>
        <CartesianGrid stroke={chartColors.grid} strokeDasharray="3 3" vertical={false} />
        <XAxis dataKey="label" {...axisProps} />
        <YAxis tickFormatter={(value: number) => formatCompact(value)} width={56} {...axisProps} />
        <Tooltip
          cursor={{ fill: 'var(--sky-soft)', opacity: 0.5 }}
          contentStyle={tooltipStyle}
          formatter={(value, name) => [
            formatCurrency(Number(value)),
            name === 'sales' ? 'Ventes' : 'Achats',
          ]}
        />
        <Legend
          iconType="circle"
          iconSize={8}
          wrapperStyle={{ fontSize: '12px', color: 'var(--muted)' }}
          formatter={(value) => (value === 'sales' ? 'Ventes' : 'Achats')}
        />
        <Bar dataKey="sales" fill={chartColors.sales} radius={[4, 4, 0, 0]} maxBarSize={28} />
        <Bar dataKey="purchases" fill={chartColors.purchases} radius={[4, 4, 0, 0]} maxBarSize={28} />
      </BarChart>
    </ResponsiveContainer>
  )
}
