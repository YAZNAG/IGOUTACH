import { Card, CardBody } from '@/components/ui/Card'
import { formatCurrency } from '@/lib/utils'
import { useCheques } from '../hooks'
import { ChequeDraftFields, chequeDraftVide, type ChequeDraft } from './ChequeDraftFields'

/** Provenance du chèque remis au fournisseur. */
export type SupplierChequeSource = 'own' | 'third_party'

export interface SupplierChequeValue {
  source: SupplierChequeSource
  /** Chèque déjà en portefeuille repris tel quel. null = on en crée un. */
  chequeId: number | null
  draft: ChequeDraft
}

export function supplierChequeVide(): SupplierChequeValue {
  return { source: 'own', chequeId: null, draft: chequeDraftVide() }
}

interface SupplierChequePanelProps {
  value: SupplierChequeValue
  onChange: (value: SupplierChequeValue) => void
}

/**
 * Choix du chèque servant à régler un fournisseur.
 *
 * Deux provenances : notre propre chéquier, ou le chèque d'un tiers. Dans ce
 * second cas on peut endosser un chèque déjà reçu d'un client — seuls ceux
 * encore en portefeuille sont proposés, un chèque remis ne peut pas resservir.
 */
export function SupplierChequePanel({ value, onChange }: SupplierChequePanelProps) {
  const tiers = value.source === 'third_party'
  // Le portefeuille n'est interrogé que s'il peut servir.
  const { data: portefeuille = [], isLoading } = useCheques({ endorsable: true }, tiers)

  function choisirSource(source: SupplierChequeSource) {
    // Changer de provenance remet le choix à zéro : garder un chèque
    // sélectionné sous « mon chèque » enverrait un chèque client au serveur.
    onChange({ source, chequeId: null, draft: chequeDraftVide() })
  }

  return (
    <Card className="border-dashed">
      <CardBody className="space-y-4">
        <fieldset className="space-y-2">
          <legend className="text-sm font-medium text-ink">Quel chèque remettez-vous ?</legend>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="radio"
              name="cheque-source"
              checked={value.source === 'own'}
              onChange={() => choisirSource('own')}
              className="h-4 w-4 accent-sky"
            />
            Mon chèque
          </label>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="radio"
              name="cheque-source"
              checked={tiers}
              onChange={() => choisirSource('third_party')}
              className="h-4 w-4 accent-sky"
            />
            Le chèque d'une autre personne
          </label>
        </fieldset>

        {tiers ? (
          <div className="space-y-3 border-t border-line pt-3">
            <p className="text-sm text-muted">Reprendre un chèque déjà encaissé d'un client :</p>

            {isLoading ? (
              <p className="text-sm text-muted">Chargement du portefeuille…</p>
            ) : portefeuille.length === 0 ? (
              <p className="rounded-lg border border-dashed border-line px-3 py-4 text-sm text-muted">
                Aucun chèque disponible en portefeuille. Saisissez-en un ci-dessous.
              </p>
            ) : (
              <ul className="max-h-56 space-y-1 overflow-y-auto">
                {portefeuille.map((cheque) => (
                  <li key={cheque.id}>
                    <label
                      className={`flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm transition-colors ${
                        value.chequeId === cheque.id
                          ? 'border-sky bg-sky-soft'
                          : 'border-line hover:bg-bg'
                      }`}
                    >
                      <input
                        type="radio"
                        name="cheque-portefeuille"
                        checked={value.chequeId === cheque.id}
                        onChange={() => onChange({ ...value, chequeId: cheque.id })}
                        className="h-4 w-4 accent-sky"
                      />
                      <span className="flex-1">
                        <span className="mono text-ink">{cheque.number}</span>
                        <span className="text-muted"> · {cheque.signataire}</span>
                        {cheque.bank ? <span className="text-faint"> · {cheque.bank}</span> : null}
                      </span>
                      <span className="text-right">
                        <span className="mono block text-ink">{formatCurrency(cheque.amount)}</span>
                        <span className="block text-xs text-faint">
                          {new Date(cheque.cheque_date).toLocaleDateString('fr-FR')}
                        </span>
                      </span>
                    </label>
                  </li>
                ))}
              </ul>
            )}

            {value.chequeId !== null ? (
              <button
                type="button"
                onClick={() => onChange({ ...value, chequeId: null })}
                className="text-xs text-sky hover:underline"
              >
                Saisir plutôt un nouveau chèque
              </button>
            ) : null}
          </div>
        ) : null}

        {value.chequeId === null ? (
          <div className="space-y-3 border-t border-line pt-3">
            <p className="text-sm font-medium text-ink">
              {tiers ? 'Nouveau chèque d’un tiers' : 'Détails de votre chèque'}
            </p>
            <ChequeDraftFields
              value={value.draft}
              onChange={(draft) => onChange({ ...value, draft })}
              hideDrawerName={!tiers}
              drawerNameLabel="Nom porté sur le chèque"
            />
            {tiers ? (
              <p className="text-xs text-faint">
                Le nom du signataire est obligatoire : c'est lui que la banque opposera en cas de rejet.
              </p>
            ) : null}
          </div>
        ) : null}
      </CardBody>
    </Card>
  )
}
