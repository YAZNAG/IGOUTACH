import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCompact, formatCurrency, formatNumber } from '@/lib/utils'
import type { WarehouseStockRow } from '../types'
import { axisProps, chartColors, tooltipStyle } from './chartTheme'

interface StockByWarehouseChartProps {
  data: WarehouseStockRow[]
}

/** Valeur du stock détenue par chaque lieu, valorisée au coût d'achat. */
export function StockByWarehouseChart({ data }: StockByWarehouseChartProps) {
  return (
    <ResponsiveContainer width="100%" height="100%">
      <BarChart data={data} layout="vertical" margin={{ top: 4, right: 16, bottom: 0, left: 8 }}>
        <XAxis type="number" tickFormatter={(value: number) => formatCompact(value)} {...axisProps} />
        <YAxis type="category" dataKey="warehouse" width={72} {...axisProps} />
        <Tooltip
          cursor={{ fill: 'var(--sky-soft)', opacity: 0.5 }}
          contentStyle={tooltipStyle}
          labelFormatter={(label) => {
            const row = data.find((item) => item.warehouse === String(label))
            return row ? `${row.warehouse} · ${row.name}` : String(label)
          }}
          formatter={(value, _name, item) => {
            const units = (item?.payload as WarehouseStockRow | undefined)?.units ?? 0
            return [
              `${formatCurrency(Number(value))} — ${formatNumber(units)} unités`,
              'Valeur du stock',
            ]
          }}
        />
        {/* Une seule mesure comparee entre lieux : la longueur porte tout,
            la couleur ne distingue rien et reste donc unique. Peindre une
            teinte par rang faisait changer de couleur un lieu qui monte. */}
        <Bar dataKey="value" fill={chartColors.brand} radius={[0, 4, 4, 0]} maxBarSize={26} isAnimationActive={false} />
      </BarChart>
    </ResponsiveContainer>
  )
}
