/**
 * Palette et réglages communs aux graphiques du tableau de bord.
 *
 * Les couleurs pointent vers les variables du thème : les graphiques suivent
 * donc le mode clair comme le mode sombre sans duplication de palette.
 */
export const chartColors = {
  sales: 'var(--sky)',
  purchases: 'var(--navy-3)',
  ok: 'var(--ok)',
  warn: 'var(--warn)',
  bad: 'var(--bad)',
  grid: 'var(--line)',
  axis: 'var(--faint)',
} as const

/** Teintes successives pour les séries catégorielles (lieux, articles…). */
export const seriesPalette = [
  'var(--sky)',
  'var(--navy-3)',
  'var(--ok)',
  'var(--warn)',
  'var(--navy)',
  'var(--sky-2)',
] as const

export const axisProps = {
  stroke: chartColors.axis,
  fontSize: 11,
  tickLine: false,
  axisLine: false,
} as const

/** Style du panneau d'info-bulle, aligné sur les cartes de l'application. */
export const tooltipStyle = {
  backgroundColor: 'var(--card)',
  border: '1px solid var(--line-2)',
  borderRadius: 'var(--radius)',
  boxShadow: 'var(--shadow-pop)',
  fontSize: '12px',
  color: 'var(--ink)',
} as const
