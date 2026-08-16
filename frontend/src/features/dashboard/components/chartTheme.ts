/**
 * Palette et réglages communs aux graphiques.
 *
 * Les couleurs pointent vers les variables du thème : les graphiques suivent
 * donc le mode clair comme le mode sombre sans duplication de palette.
 */
export const chartColors = {
  /** Série unique : le rouge de la marque. Une seule couleur n'a personne à
   *  qui se confondre, et le graphique reste identifiable comme le nôtre. */
  brand: 'var(--brand)',
  sales: 'var(--chart-1)',
  purchases: 'var(--chart-3)',
  ok: 'var(--ok)',
  warn: 'var(--warn)',
  bad: 'var(--bad)',
  grid: 'var(--line)',
  axis: 'var(--faint)',
  surface: 'var(--card)',
} as const

/**
 * Teintes successives des séries catégorielles, dans l'ordre d'attribution.
 *
 * Elles sont attribuées dans cet ordre et ne se recyclent pas : au-delà de
 * cinq séries, on regroupe le reste sous « Autres » plutôt que d'inventer une
 * sixième teinte qui se confondrait avec une existante.
 *
 * La couleur suit l'entité, jamais son rang : un filtre qui change le nombre
 * de séries ne doit pas repeindre les survivantes.
 */
export const seriesPalette = [
  'var(--chart-1)',
  'var(--chart-2)',
  'var(--chart-3)',
  'var(--chart-4)',
  'var(--chart-5)',
] as const

/** Nombre de séries au-delà duquel le reste est regroupé. */
export const MAX_SERIES = seriesPalette.length

/**
 * Couleur d'une entité, stable quel que soit le nombre de séries affichées.
 *
 * On passe l'index de l'entité dans la liste complète, pas sa position à
 * l'écran : sinon masquer une série repeindrait toutes les suivantes.
 */
export function seriesColor(index: number): string {
  return seriesPalette[Math.min(index, MAX_SERIES - 1)]
}

/** Couleurs d'état — réservées, jamais réutilisées comme « série 4 ». */
export const statusColors = {
  paid: 'var(--ok)',
  partial: 'var(--warn)',
  unpaid: 'var(--bad)',
} as const

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

/** Curseur de survol discret : il situe sans masquer la marque. */
export const cursorStyle = { fill: 'var(--line)', fillOpacity: 0.35 } as const

/** Extrémités arrondies des barres, côté valeur uniquement. */
export const barRadius = [4, 4, 0, 0] as [number, number, number, number]

export const barRadiusHorizontal = [0, 4, 4, 0] as [number, number, number, number]
