import { Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { formatCurrency } from '@/lib/utils'
import type { PaymentMixRow } from '../types'
import { chartColors, tooltipStyle } from './chartTheme'

interface PaymentMixChartProps {
  data: PaymentMixRow[]
}

const TONES: Record<PaymentMixRow['status'], string> = {
  paid: chartColors.ok,
  partial: chartColors.warn,
  unpaid: chartColors.bad,
}

/** Répartition du chiffre d'affaires selon l'état de règlement. */
export function PaymentMixChart({ data }: PaymentMixChartProps) {
  // Une part à zéro n'apporte rien au camembert : on ne garde que le réel.
  const slices = data.filter((row) => row.amount > 0)

  return (
    <ResponsiveContainer width="100%" height="100%">
      <PieChart>
        <Pie
          data={slices}
          dataKey="amount"
          nameKey="label"
          innerRadius="55%"
          outerRadius="80%"
          paddingAngle={2}
          strokeWidth={0}
        >
          {slices.map((row) => (
            <Cell key={row.status} fill={TONES[row.status]} />
          ))}
        </Pie>
        <Tooltip
          contentStyle={tooltipStyle}
          formatter={(value, name) => [formatCurrency(Number(value)), String(name)]}
        />
        <Legend
          iconType="circle"
          iconSize={8}
          wrapperStyle={{ fontSize: '12px', color: 'var(--muted)' }}
        />
      </PieChart>
    </ResponsiveContainer>
  )
}
