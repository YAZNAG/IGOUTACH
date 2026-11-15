import type { Article, Palier } from './types'

/**
 * Les paliers côté vitrine.
 *
 * Le serveur reste seul juge du prix : il relit le barème à l'enregistrement
 * de la commande. Ce qui suit ne sert qu'à montrer juste — un panier qui
 * annonce un prix et une facture qui en donne un autre, c'est une vente
 * perdue. La règle est donc copiée à l'identique de la gestion : on retient
 * le palier du rang le plus élevé dont le seuil de quantité est atteint,
 * sinon le détail.
 */
export function palierPour(article: Article, quantite: number): Palier | null {
  const paliers = article.tiers ?? []
  if (paliers.length === 0) return null

  const parRangDecroissant = [...paliers].sort((a, b) => b.rank - a.rank)
  const atteint = parRangDecroissant.find((p) => quantite >= p.min_quantity)
  if (atteint) return atteint

  return paliers.find((p) => p.code === 'detail') ?? paliers[0]
}

/** Prix unitaire affiché pour cette quantité. */
export function prixPour(article: Article, quantite: number): number {
  return palierPour(article, quantite)?.price ?? article.price
}

export function totalPour(article: Article, quantite: number): number {
  return prixPour(article, quantite) * quantite
}

/** Cette quantité relève-t-elle du devis ? */
export function surDevis(article: Article, quantite: number): boolean {
  return palierPour(article, quantite)?.on_quote ?? false
}

/**
 * Le palier suivant et ce qu'il manque pour l'atteindre — de quoi proposer
 * « encore 1 et le prix baisse de 33 % ». On ne propose rien quand le palier
 * suivant passe par un devis : ce serait pousser le client vers une porte
 * qu'on vient de fermer.
 */
export function prochainPalier(
  article: Article,
  quantite: number,
): { palier: Palier; manque: number; remise: number } | null {
  const actuel = palierPour(article, quantite)
  if (!actuel) return null

  const suivants = (article.tiers ?? [])
    .filter((p) => p.min_quantity > quantite && p.price < actuel.price && !p.on_quote)
    .sort((a, b) => a.min_quantity - b.min_quantity)

  const suivant = suivants[0]
  if (!suivant || actuel.price <= 0) return null

  return {
    palier: suivant,
    manque: suivant.min_quantity - quantite,
    remise: Math.round(((actuel.price - suivant.price) / actuel.price) * 100),
  }
}

/** Les paliers à montrer publiquement, prix décroissant de gauche à droite. */
export function grille(article: Article): Palier[] {
  return [...(article.tiers ?? [])].sort((a, b) => a.min_quantity - b.min_quantity)
}

/** « 3+ » ou « 1 » : l'étiquette courte d'un palier. */
export function seuilCourt(palier: Palier): string {
  return palier.min_quantity <= 1 ? '1' : `${palier.min_quantity}+`
}
