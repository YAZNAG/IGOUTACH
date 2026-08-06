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
