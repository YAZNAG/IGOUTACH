import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCompact, formatCurrency, formatNumber } from '@/lib/utils'
import { axisProps, barRadius, chartColors, cursorStyle, tooltipStyle } from './chartTheme'

export interface MonthlyPoint {
  month: string
  label: string
  /** Chiffre d'affaires (ventes) ou montant acheté (fournisseur). */
  value: number
  count: number
}

interface MonthlySeriesChartProps {
  data: MonthlyPoint[]
  /** Nom de la mesure, affiché en info-bulle. Le titre de la carte le répète. */
  measureLabel?: string
  /** Nom de l'unité comptée : « vente » ou « réception ». */
  countLabel?: string
  color?: string
}

interface InfoBulleProps {
  active?: boolean
  payload?: { payload: MonthlyPoint }[]
}

/**
 * Une mesure, mois par mois, sur douze mois glissants.
 *
 * Des barres et non une courbe : chaque mois est une quantité close et
 * comparable, pas un point sur un continuum. Une courbe suggérerait une
 * valeur entre deux mois, qui n'existe pas.
 *
 * Série unique, donc pas de légende : le titre de la carte la nomme.
 */
export function MonthlySeriesChart({
  data,
  measureLabel = 'Chiffre d’affaires',
  countLabel = 'vente',
  color = chartColors.brand,
}: MonthlySeriesChartProps) {
  function InfoBulle({ active, payload }: InfoBulleProps) {
    if (!active || !payload?.length) return null
    const point = payload[0].payload

    return (
      <div style={tooltipStyle} className="px-3 py-2">
        <p className="font-medium text-ink">{point.label}</p>
        <p className="mono text-ink">{formatCurrency(point.value)}</p>
        <p className="text-muted">
          {formatNumber(point.count)} {countLabel}
          {point.count > 1 ? 's' : ''}
        </p>
      </div>
    )
  }

  return (
    // Le libellé de la mesure vit dans le titre de la carte ; répété ici pour
    // les lecteurs d'écran, hors du conteneur que Recharts veut seul enfant.
    <figure className="h-full w-full" aria-label={measureLabel}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
          <CartesianGrid stroke={chartColors.grid} strokeDasharray="3 3" vertical={false} />
          <XAxis dataKey="label" {...axisProps} />
          <YAxis tickFormatter={(value: number) => formatCompact(value)} width={56} {...axisProps} />
          <Tooltip content={<InfoBulle />} cursor={cursorStyle} />
          <Bar dataKey="value" fill={color} radius={barRadius} maxBarSize={38} isAnimationActive={false} />
        </BarChart>
      </ResponsiveContainer>
    </figure>
  )
}
