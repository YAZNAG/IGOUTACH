import { Bar, BarChart, LabelList, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { formatCompact, formatCurrency } from '@/lib/utils'
import { axisProps, barRadiusHorizontal, chartColors, cursorStyle, tooltipStyle } from './chartTheme'

export interface RankedBarRow {
  /** Libellé de l'entité — porte l'identité, jamais la couleur seule. */
  name: string
  value: number
  /** Précision affichée dans l'info-bulle : documents, quantité… */
  detail?: string
  /** Seconde valeur affichée dans l'info-bulle : encours, reste dû… */
  note?: string
}

/** Au-dela, le nom mange la piste ; l'info-bulle porte le nom entier. */
const NOM_MAX = 20

function abreger(nom: string): string {
  return nom.length > NOM_MAX ? `${nom.slice(0, NOM_MAX - 1)}…` : nom
}

interface RankedBarChartProps {
  rows: RankedBarRow[]
  /** Unité affichée en info-bulle. */
  valueLabel?: string
  /** Couleur de la série. Une seule teinte : c'est une série unique. */
  color?: string
  /** Hauteur d'une barre, marges comprises. */
  rowHeight?: number
}

/**
 * Classement en barres horizontales.
 *
 * Une seule série : la longueur porte la comparaison, la couleur ne distingue
 * rien et reste donc unique. Les noms sont longs et de longueur inégale — les
 * mettre en ordonnée évite la rotation à 45° qui rend les libellés illisibles.
 *
 * La valeur est écrite au bout de chaque barre : le lecteur n'a pas à revenir
 * à l'axe, et l'information ne dépend jamais de la seule couleur.
 */
export function RankedBarChart({
  rows,
  valueLabel = 'Chiffre d’affaires',
  color = chartColors.brand,
  rowHeight = 34,
}: RankedBarChartProps) {
  // La hauteur suit le nombre de lignes : une grille fixe écraserait deux
  // barres et laisserait du vide sous huit.
  const height = Math.max(120, rows.length * rowHeight + 16)

  // Le nom complet reste dans la donnée : l'info-bulle le lit, l'axe affiche
  // sa version courte.
  const lignes = rows.map((row) => ({ ...row, court: abreger(row.name) }))

  return (
    <figure style={{ height }} aria-label={valueLabel}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart
          data={lignes}
          layout="vertical"
          margin={{ top: 4, right: 56, bottom: 4, left: 4 }}
        >
          {/* La valeur est ecrite au bout de chaque barre : un axe en plus
              ferait lire deux fois la meme chose. */}
          <XAxis type="number" tick={false} height={0} {...axisProps} />
          <YAxis
            type="category"
            dataKey="court"
            width={112}
            {...axisProps}
          />
          <Tooltip
            cursor={cursorStyle}
            contentStyle={tooltipStyle}
            labelFormatter={(label) => {
              const row = lignes.find((l) => l.court === String(label))
              return row?.name ?? String(label)
            }}
            formatter={(value, _nom, item) => {
              const row = item?.payload as RankedBarRow | undefined
              return [
                [formatCurrency(Number(value)), row?.detail, row?.note].filter(Boolean).join(' — '),
                valueLabel,
              ]
            }}
          />
          <Bar
            dataKey="value"
            fill={color}
            radius={barRadiusHorizontal}
            maxBarSize={18}
            // Recharts 3.10 avec React 19 ne demarre jamais l'animation
            // d'entree : sans cela, chaque barre reste large de zero.
            isAnimationActive={false}
          >
            <LabelList
              dataKey="value"
              position="right"
              formatter={(value) => formatCompact(Number(value ?? 0))}
              style={{ fill: 'var(--muted)', fontSize: 11 }}
            />
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </figure>
  )
}
