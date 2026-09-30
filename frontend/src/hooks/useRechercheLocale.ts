import { useMemo, useState } from 'react'

/**
 * Normalise pour comparer : minuscules et accents retirés.
 *
 * Sans cela, « depot » ne trouverait pas « DÉPÔT », ce qui est précisément
 * ce qu'un utilisateur tape quand il cherche vite.
 */
function normaliser(valeur: string): string {
  return valeur
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
}

/**
 * Recherche dans des lignes déjà chargées.
 *
 * À réserver aux tableaux qui tiennent en mémoire — listes de paramétrage,
 * onglets d'une fiche, résultats d'un rapport. Sur une liste paginée par le
 * serveur, filtrer localement ne fouillerait que la page affichée et
 * laisserait croire qu'un enregistrement n'existe pas : ces listes-là passent
 * leur terme à l'API.
 *
 * Chaque terme séparé par une espace doit être trouvé, dans n'importe quel
 * champ : « tarrast cable » ramène les lignes qui portent les deux.
 */
export function useRechercheLocale<T>(
  lignes: T[],
  champs: (ligne: T) => Array<string | number | null | undefined>,
): {
  terme: string
  setTerme: (valeur: string) => void
  resultats: T[]
  /** Vrai quand un terme est saisi : distingue « rien » de « rien trouvé ». */
  actif: boolean
} {
  const [terme, setTerme] = useState('')

  const resultats = useMemo(() => {
    const mots = normaliser(terme).split(/\s+/).filter(Boolean)
    if (mots.length === 0) return lignes

    return lignes.filter((ligne) => {
      const foin = normaliser(
        champs(ligne)
          .filter((v) => v !== null && v !== undefined)
          .join(' '),
      )
      return mots.every((mot) => foin.includes(mot))
    })
    // `champs` est redéfini à chaque rendu par l'appelant : le mettre en
    // dépendance relancerait le filtre en boucle.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [lignes, terme])

  return { terme, setTerme, resultats, actif: terme.trim() !== '' }
}
