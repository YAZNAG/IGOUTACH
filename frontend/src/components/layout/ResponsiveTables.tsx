import { useEffect } from 'react'

/**
 * Rend lisibles sur téléphone tous les tableaux de l'application.
 *
 * Un tableau de six colonnes ne tient pas dans 375 px : soit il déborde et
 * casse la page, soit il se comprime jusqu'à l'illisible. Sur petit écran on
 * l'empile donc en fiches — une ligne devient un bloc, chaque cellule une
 * paire « intitulé : valeur ».
 *
 * L'intitulé vient de l'en-tête de colonne, recopié ici dans `data-label` pour
 * que la feuille de style puisse l'afficher devant la valeur.
 *
 * Le travail est fait sur le DOM rendu plutôt que dans chaque page :
 *
 * - les 49 tableaux de l'application restent inchangés, et ceux à venir
 *   héritent du comportement sans qu'on y pense ;
 * - les colonnes conditionnelles (« Valeur » réservée à qui voit les prix
 *   d'achat, colonne par lieu de la matrice) sont traitées d'office, puisqu'on
 *   lit les en-têtes réellement affichés et non le code qui les produit.
 *
 * Un tableau qui doit rester un tableau porte la classe `table-garder`.
 */
export function ResponsiveTables() {
  useEffect(() => {
    const zone = document.querySelector('main')
    if (zone === null) return

    function etiqueter(): void {
      zone!.querySelectorAll('table').forEach((table) => {
        if (table.classList.contains('table-garder')) return

        const intitules = Array.from(table.querySelectorAll('thead th')).map(
          (th) => th.textContent?.trim() ?? '',
        )
        if (intitules.length === 0) return

        table.classList.add('table-fiches')

        table.querySelectorAll('tbody tr').forEach((ligne) => {
          Array.from(ligne.children).forEach((cellule, i) => {
            // Une cellule fusionnée (« Aucun résultat ») n'appartient à aucune
            // colonne : lui coller un intitulé serait faux.
            if (cellule.hasAttribute('colspan')) {
              cellule.setAttribute('data-pleine-largeur', '')
              return
            }
            const intitule = intitules[i] ?? ''
            if (intitule !== '') cellule.setAttribute('data-label', intitule)
          })
        })
      })
    }

    etiqueter()

    // Les tableaux se re-rendent à chaque chargement de données, changement de
    // page ou de tri : on suit le DOM plutôt que de deviner quand recommencer.
    const observateur = new MutationObserver(etiqueter)
    observateur.observe(zone, { childList: true, subtree: true })

    return () => observateur.disconnect()
  }, [])

  return null
}
