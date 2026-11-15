import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { prixPour, surDevis } from './paliers'
import type { Article } from './types'

export interface LignePanier {
  article: Article
  quantite: number
}

interface Panier {
  lignes: LignePanier[]
  ajouter: (article: Article, quantite?: number) => void
  retirer: (id: number) => void
  changerQuantite: (id: number, quantite: number) => void
  vider: () => void
  nombre: number
  sousTotal: number
  /** Prix unitaire d'une ligne, au palier atteint par sa quantité. */
  prixUnitaire: (ligne: LignePanier) => number
  /**
   * Au moins une ligne dépasse le palier vendu sur devis : le panier ne se
   * commande plus, il se transforme en demande de devis.
   */
  surDevis: boolean
  lignesSurDevis: LignePanier[]
}

const CLE = 'store:panier'
const Contexte = createContext<Panier | null>(null)

function lire(): LignePanier[] {
  try {
    const brut = localStorage.getItem(CLE)
    const liste = brut ? (JSON.parse(brut) as LignePanier[]) : []
    return Array.isArray(liste) ? liste.filter((l) => l && l.article && l.quantite > 0) : []
  } catch {
    return []
  }
}

/** Le panier vit dans le navigateur ; la commande relit prix et stock en base. */
export function PanierProvider({ children }: { children: ReactNode }) {
  const [lignes, setLignes] = useState<LignePanier[]>(lire)

  useEffect(() => {
    try {
      localStorage.setItem(CLE, JSON.stringify(lignes))
    } catch {
      /* ignorer */
    }
  }, [lignes])

  const valeur = useMemo<Panier>(() => {
    const prixUnitaire = (l: LignePanier) => prixPour(l.article, l.quantite)
    const auDevis = lignes.filter((l) => surDevis(l.article, l.quantite))

    return {
      lignes,
      ajouter: (article, quantite = 1) =>
        setLignes((prev) => {
          const existante = prev.find((l) => l.article.id === article.id)
          if (existante) {
            return prev.map((l) => (l.article.id === article.id ? { ...l, quantite: l.quantite + quantite, article } : l))
          }
          return [...prev, { article, quantite }]
        }),
      retirer: (id) => setLignes((prev) => prev.filter((l) => l.article.id !== id)),
      changerQuantite: (id, quantite) =>
        setLignes((prev) => prev.map((l) => (l.article.id === id ? { ...l, quantite: Math.max(1, Math.min(100000, quantite)) } : l))),
      vider: () => setLignes([]),
      nombre: lignes.reduce((n, l) => n + l.quantite, 0),
      // Le sous-total suit les paliers : à 3 pièces, la ligne passe au
      // demi-gros et le récapitulatif doit le refléter tout de suite.
      sousTotal: lignes.reduce((s, l) => s + prixUnitaire(l) * l.quantite, 0),
      prixUnitaire,
      surDevis: auDevis.length > 0,
      lignesSurDevis: auDevis,
    }
  }, [lignes])

  return <Contexte.Provider value={valeur}>{children}</Contexte.Provider>
}

export function usePanier(): Panier {
  const p = useContext(Contexte)
  if (!p) throw new Error('PanierProvider manquant')
  return p
}
