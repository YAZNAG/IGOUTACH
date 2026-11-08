import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
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

  const valeur = useMemo<Panier>(
    () => ({
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
        setLignes((prev) => prev.map((l) => (l.article.id === id ? { ...l, quantite: Math.max(1, Math.min(500, quantite)) } : l))),
      vider: () => setLignes([]),
      nombre: lignes.reduce((n, l) => n + l.quantite, 0),
      sousTotal: lignes.reduce((s, l) => s + l.article.price * l.quantite, 0),
    }),
    [lignes],
  )

  return <Contexte.Provider value={valeur}>{children}</Contexte.Provider>
}

export function usePanier(): Panier {
  const p = useContext(Contexte)
  if (!p) throw new Error('PanierProvider manquant')
  return p
}
