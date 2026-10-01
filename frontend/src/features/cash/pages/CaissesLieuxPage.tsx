import { CaissesEtTransferts } from '../components/CaissesEtTransferts'

/**
 * L'argent des lieux, sur sa propre page.
 *
 * Ces deux blocs vivaient sur le tableau de bord, qui sert à prendre la
 * mesure d'une journée — pas à traiter des dossiers un par un. Confirmer ou
 * refuser un transfert demande d'ouvrir un justificatif et de compter : cela
 * mérite un écran qu'on ouvre exprès, et qu'on retrouve dans le menu.
 */
export function CaissesLieuxPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Caisses et transferts</h1>
        <p className="text-sm text-muted">
          Ce que chaque lieu détient en espèces, et les sommes annoncées qui attendent votre
          confirmation.
        </p>
      </div>

      <CaissesEtTransferts />
    </div>
  )
}
