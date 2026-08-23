import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { formatCompact, formatCurrency, formatNumber } from '@/lib/utils'
import { axisProps, barRadius, chartColors, cursorStyle, seriesPalette, tooltipStyle } from './chartTheme'

export interface RevenueProfitPoint {
  month: string
  label: string
  revenue: number
  cost: number
  profit: number
  count: number
}

interface RevenueProfitChartProps {
  data: RevenueProfitPoint[]
  countLabel?: string
}

interface InfoBulleProps {
  active?: boolean
  payload?: { payload: RevenueProfitPoint }[]
}

/**
 * Chiffre d'affaires décomposé en coût et bénéfice, mois par mois.
 *
 * Empilé plutôt que côte à côte : le bénéfice n'est pas une grandeur
 * comparable au chiffre d'affaires, il en est une part. Deux barres voisines
 * inviteraient à les lire comme deux mesures indépendantes ; la pile montre
 * que leur somme fait le total, et où passe l'argent.
 *
 * Le coût est en gris neutre, le bénéfice en couleur : l'œil va d'abord à ce
 * qui reste, qui est la question qu'on se pose.
 */
export function RevenueProfitChart({ data, countLabel = 'vente' }: RevenueProfitChartProps) {
  function InfoBulle({ active, payload }: InfoBulleProps) {
    if (!active || !payload?.length) return null
    const p = payload[0].payload
    const taux = p.revenue > 0 ? Math.round((p.profit / p.revenue) * 1000) / 10 : null

    return (
      <div style={tooltipStyle} className="px-3 py-2">
        <p className="font-medium text-ink">{p.label}</p>
        <p className="mono text-ink">{formatCurrency(p.revenue)} de chiffre d’affaires</p>
        <p className="text-muted">Coût : {formatCurrency(p.cost)}</p>
        <p className="text-ok">
          Bénéfice : {formatCurrency(p.profit)}
          {taux === null ? '' : ` (${taux} %)`}
        </p>
        <p className="text-faint">
          {formatNumber(p.count)} {countLabel}
          {p.count > 1 ? 's' : ''}
        </p>
      </div>
    )
  }

  return (
    <figure className="h-full w-full" aria-label="Chiffre d’affaires, coût et bénéfice par mois">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
          <CartesianGrid stroke={chartColors.grid} strokeDasharray="3 3" vertical={false} />
          <XAxis dataKey="label" {...axisProps} />
          <YAxis tickFormatter={(v: number) => formatCompact(v)} width={56} {...axisProps} />
          <Tooltip content={<InfoBulle />} cursor={cursorStyle} />
          <Legend
            wrapperStyle={{ fontSize: 12, color: 'var(--muted)' }}
            iconType="square"
            iconSize={9}
          />
          {/* Un filet de la couleur du fond sépare les deux segments : sans
              lui, la frontière se perd quand le bénéfice est faible. */}
          <Bar
            dataKey="cost"
            name="Coût"
            stackId="ca"
            fill={chartColors.grid}
            stroke={chartColors.surface}
            strokeWidth={2}
            maxBarSize={38}
          />
          <Bar
            dataKey="profit"
            name="Bénéfice"
            stackId="ca"
            fill={seriesPalette[1]}
            stroke={chartColors.surface}
            strokeWidth={2}
            radius={barRadius}
            maxBarSize={38}
          />
        </BarChart>
      </ResponsiveContainer>
    </figure>
  )
}
