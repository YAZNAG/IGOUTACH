import { FileText, TrendingDown } from 'lucide-react'
import { dh } from '../lib/api'
import { grille, palierPour, prochainPalier, seuilCourt } from '../lib/paliers'
import type { Article } from '../lib/types'
import { cn } from './ui'

/**
 * La grille des prix d'un article : détail, demi-gros, gros.
 *
 * Elle est le cœur de la vente en volume — un professionnel qui ne voit pas
 * le prix à dix pièces repart. Le palier qui s'applique à la quantité en
 * cours est mis en évidence, et ceux qui passent par un devis le disent.
 */
/**
 * La plage couverte par un palier, lue sur le seuil du palier suivant et
 * non sur une supposition : un article dont le demi-gros démarre à 2 ne se
 * vend au détail que pour une pièce, pas « 1 à 2 ».
 */
function plage(paliers: ReturnType<typeof grille>, index: number): string {
  const courant = paliers[index]
  const suivant = paliers[index + 1]

  if (!suivant) return `${courant.min_quantity} et +`

  const dernier = suivant.min_quantity - 1
  if (dernier <= courant.min_quantity) return String(courant.min_quantity)

  return `${courant.min_quantity} à ${dernier}`
}

export function GrillePaliers({ article, quantite }: { article: Article; quantite: number }) {
  const paliers = grille(article)
  if (paliers.length <= 1) return null

  const actuel = palierPour(article, quantite)

  return (
    <div className="overflow-hidden rounded-xl border border-line">
      <table className="w-full table-fixed text-sm">
        <thead>
          <tr className="bg-paper text-left text-xs text-muted">
            <th className="w-[30%] px-3 py-2 font-medium">Quantité</th>
            <th className="px-3 py-2 font-medium">Tarif</th>
            <th className="w-[32%] px-3 py-2 text-right font-medium">Prix unitaire</th>
          </tr>
        </thead>
        <tbody>
          {paliers.map((p, index) => {
            const applique = actuel?.code === p.code
            return (
              <tr
                key={p.code}
                className={cn('border-t border-line', applique ? 'bg-brand-soft/50' : '')}
              >
                <td className="whitespace-nowrap px-3 py-2 font-medium">{plage(paliers, index)}</td>
                <td className="px-3 py-2 text-muted">
                  <span className="block truncate">{p.label}</span>
                  {p.on_quote ? (
                    <span className="mt-0.5 inline-flex items-center gap-1 rounded-full bg-warn-soft px-2 py-0.5 text-[11px] font-medium text-warn">
                      <FileText className="h-3 w-3 shrink-0" /> sur devis
                    </span>
                  ) : null}
                </td>
                <td className={cn('whitespace-nowrap px-3 py-2 text-right font-semibold', applique ? 'text-brand' : '')}>
                  {dh(p.price)}
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

/**
 * L'incitation au palier suivant. Elle ne s'affiche que si le palier
 * d'après se commande en ligne : pousser vers une porte fermée agace.
 */
export function ProchainPalier({ article, quantite }: { article: Article; quantite: number }) {
  const suivant = prochainPalier(article, quantite)
  if (!suivant || suivant.remise <= 0) return null

  return (
    <p className="inline-flex items-center gap-2 rounded-lg bg-ok-soft px-3 py-2 text-sm text-ok">
      <TrendingDown className="h-4 w-4 shrink-0" />
      <span>
        Encore <strong>{suivant.manque}</strong> {suivant.manque > 1 ? 'unités' : 'unité'} et le prix passe à{' '}
        <strong>{dh(suivant.palier.price)}</strong> l’unité — <strong>−{suivant.remise} %</strong>.
      </span>
    </p>
  )
}

/** Résumé compact pour une carte de grille : « 3+ : 20 DH · 10+ : 13 DH ». */
export function PaliersResume({ article }: { article: Article }) {
  const autres = grille(article).filter((p) => p.min_quantity > 1)
  if (autres.length === 0) return null

  return (
    <p className="text-[11px] text-muted">
      {autres.map((p) => `${seuilCourt(p)} : ${dh(p.price)}`).join(' · ')}
    </p>
  )
}
