import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { chartColors, tooltipStyle } from '@/features/dashboard/components/chartTheme'
import { formatCompact, formatCurrency } from '@/lib/utils'
import type { ProductStatistics } from '../api/articlesApi'

interface PriceHistoryChartProps {
  data: ProductStatistics['price_history']
  /** Le coût n'est tracé que s'il est visible pour cet utilisateur. */
  showCost: boolean
}

interface InfoBulleProps {
  active?: boolean
  payload?: { payload: ProductStatistics['price_history'][number] }[]
}

/**
 * Évolution du prix de vente appliqué, face au coût de revient.
 *
 * Une courbe et non des barres : le prix est une grandeur continue, qui existe
 * entre deux ventes. Trois traits — le plus bas, le moyen, le plus haut — car
 * avec trois tarifs et la possibilité de descendre au cas par cas, la moyenne
 * seule ne dit pas si l'on a bradé. L'écart entre le plancher et le plafond,
 * lui, le montre.
 *
 * Une seule échelle, en dirhams : prix et coût s'y comparent directement.
 * C'est tout l'intérêt — voir d'un coup d'œil quand la vente est passée sous
 * le coût.
 */
export function PriceHistoryChart({ data, showCost }: PriceHistoryChartProps) {
  function InfoBulle({ active, payload }: InfoBulleProps) {
    if (!active || !payload?.length) return null
    const point = payload[0].payload

    if (point.avg === null) {
      return (
        <div style={tooltipStyle} className="px-3 py-2">
          <p className="font-medium text-ink">{point.label}</p>
          <p className="text-muted">Aucune vente ce mois-ci.</p>
        </div>
      )
    }

    return (
      <div style={tooltipStyle} className="px-3 py-2">
        <p className="font-medium text-ink">{point.label}</p>
        <p className="mono text-ink">Prix moyen {formatCurrency(point.avg)}</p>
        <p className="text-muted">
          de {formatCurrency(point.min ?? 0)} à {formatCurrency(point.max ?? 0)}
        </p>
        {showCost && point.cost !== undefined ? (
          <p className={point.avg < point.cost ? 'text-bad' : 'text-muted'}>
            Coût {formatCurrency(point.cost)}
            {point.avg < point.cost ? ' — vendu à perte' : ''}
          </p>
        ) : null}
      </div>
    )
  }

  return (
    <ResponsiveContainer width="100%" height="100%">
      <LineChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
        <CartesianGrid stroke={chartColors.grid} strokeDasharray="3 3" vertical={false} />
        <XAxis dataKey="label" stroke={chartColors.axis} fontSize={11} tickLine={false} axisLine={false} />
        <YAxis
          stroke={chartColors.axis}
          fontSize={11}
          tickLine={false}
          axisLine={false}
          width={52}
          tickFormatter={(v: number) => formatCompact(v)}
        />
        <Tooltip content={<InfoBulle />} />
        {/* Quatre traits : l'identité ne peut pas reposer sur la couleur seule. */}
        <Legend
          verticalAlign="top"
          height={28}
          iconType="plainline"
          wrapperStyle={{ fontSize: 11, color: 'var(--muted)' }}
        />
        {/* Le plancher et le plafond encadrent, en trait fin ; la moyenne
            porte la lecture, en trait plein. `connectNulls` est laissé à faux :
            relier deux mois séparés par un mois sans vente inventerait une
            continuité qui n'a pas eu lieu. */}
        <Line
          type="monotone"
          dataKey="max"
          name="Prix le plus haut"
          stroke={chartColors.sales}
          strokeWidth={1}
          strokeDasharray="4 3"
          dot={false}
          isAnimationActive={false}
        />
        <Line
          type="monotone"
          dataKey="avg"
          name="Prix moyen"
          stroke={chartColors.brand}
          strokeWidth={2}
          dot={{ r: 3 }}
          isAnimationActive={false}
        />
        <Line
          type="monotone"
          dataKey="min"
          name="Prix le plus bas"
          stroke={chartColors.sales}
          strokeWidth={1}
          strokeDasharray="4 3"
          dot={false}
          isAnimationActive={false}
        />
        {showCost ? (
          <Line
            type="monotone"
            dataKey="cost"
            name="Coût de revient"
            stroke={chartColors.warn}
            strokeWidth={1.5}
            strokeDasharray="2 4"
            dot={false}
            isAnimationActive={false}
          />
        ) : null}
      </LineChart>
    </ResponsiveContainer>
  )
}
