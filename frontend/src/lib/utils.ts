import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs))
}

export function formatNumber(value: number): string {
  return new Intl.NumberFormat('fr-FR').format(value)
}

/** Montant en dirhams, deux décimales. */
export function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)+' MAD'
}

/**
 * Montant abrégé pour les axes de graphique : 1 250 000 → « 1,3 M ».
 * Les axes doivent rester lisibles, la valeur exacte est dans l'info-bulle.
 */
export function formatCompact(value: number): string {
  const abs = Math.abs(value)

  if (abs >= 1_000_000) {
    return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(value / 1_000_000)} M`
  }

  if (abs >= 1_000) {
    return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(value / 1_000)} k`
  }

  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(value)
}

/**
 * Découpe une date-heure du serveur sans passer par `Date`.
 *
 * L'API renvoie l'heure déjà exprimée dans le fuseau de l'entreprise
 * (Africa/Casablanca). La reconstruire avec `new Date()` la ferait
 * réinterpréter dans le fuseau du navigateur : une vente saisie à 17 h
 * s'afficherait à 18 h pour qui consulte depuis l'Europe. On lit donc les
 * chiffres tels quels.
 */
function decouperHorodatage(valeur: string): { j: string; m: string; a: string; h: string; min: string } | null {
  const m = valeur.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/)
  if (!m) return null
  return { a: m[1], m: m[2], j: m[3], h: m[4] ?? '', min: m[5] ?? '' }
}

/** « 02/09/2026 ». Rend un tiret cadratin quand la valeur manque. */
export function formatDate(valeur: string | null | undefined): string {
  if (!valeur) return '—'
  const p = decouperHorodatage(valeur)
  if (!p) return valeur
  return `${p.j}/${p.m}/${p.a}`
}

/**
 * « 02/09/2026 à 17:08 ».
 *
 * Retombe sur la date seule quand la valeur ne porte pas d'heure : inventer
 * « 00:00 » laisserait croire à une saisie en pleine nuit.
 */
export function formatDateHeure(valeur: string | null | undefined): string {
  if (!valeur) return '—'
  const p = decouperHorodatage(valeur)
  if (!p) return valeur
  if (p.h === '') return `${p.j}/${p.m}/${p.a}`
  return `${p.j}/${p.m}/${p.a} à ${p.h}:${p.min}`
}

/** « 17:08 », ou une chaîne vide si la valeur ne porte pas d'heure. */
export function formatHeure(valeur: string | null | undefined): string {
  if (!valeur) return ''
  const p = decouperHorodatage(valeur)
  return p && p.h !== '' ? `${p.h}:${p.min}` : ''
}
