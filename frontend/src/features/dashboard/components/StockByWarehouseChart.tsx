import { Bar, BarChart, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCompact, formatCurrency, formatNumber } from '@/lib/utils'
import type { WarehouseStockRow } from '../types'
import { axisProps, seriesPalette, tooltipStyle } from './chartTheme'

interface StockByWarehouseChartProps {
  data: WarehouseStockRow[]
}

/** Valeur du stock détenue par chaque lieu, valorisée au CMUP. */
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
        <Bar dataKey="value" radius={[0, 4, 4, 0]} maxBarSize={26}>
          {data.map((row, index) => (
            <Cell key={row.warehouse} fill={seriesPalette[index % seriesPalette.length]} />
          ))}
        </Bar>
      </BarChart>
    </ResponsiveContainer>
  )
}
