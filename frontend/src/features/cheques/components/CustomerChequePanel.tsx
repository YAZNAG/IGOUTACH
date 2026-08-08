import { Card, CardBody } from '@/components/ui/Card'
import { ChequeDraftFields, type ChequeDraft } from './ChequeDraftFields'

export interface CustomerChequeValue {
  draft: ChequeDraft
  /** false : le chèque est au nom du client ; true : au nom de quelqu'un d'autre. */
  autreSignataire: boolean
}

interface CustomerChequePanelProps {
  value: CustomerChequeValue
  onChange: (value: CustomerChequeValue) => void
  /** Nom du client réglant la facture, affiché comme choix par défaut. */
  customerName?: string | null
}

/**
 * Saisie d'un chèque remis par un client.
 *
 * Le nom du tireur est demandé séparément du client : c'est fréquent qu'un
 * client règle avec le chèque d'un tiers, et c'est ce nom-là que la banque
 * opposera en cas de rejet.
 */
export function CustomerChequePanel({ value, onChange, customerName }: CustomerChequePanelProps) {
  const nomClient = customerName?.trim() ? customerName : 'le client'

  function choisirSignataire(autre: boolean) {
    onChange({
      autreSignataire: autre,
      // Repasser sur « au nom du client » efface le nom saisi : le laisser
      // traîner ferait enregistrer un tireur que l'écran n'affiche plus.
      draft: autre ? value.draft : { ...value.draft, drawer_name: '' },
    })
  }

  return (
    <Card className="border-dashed">
      <CardBody className="space-y-4">
        <p className="text-sm font-medium text-ink">Détails du chèque</p>

        <ChequeDraftFields
          value={value.draft}
          onChange={(draft) => onChange({ ...value, draft })}
          hideDrawerName
        />

        <fieldset className="space-y-2">
          <legend className="text-sm text-muted">Le chèque est au nom de :</legend>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="radio"
              name="cheque-signataire"
              checked={!value.autreSignataire}
              onChange={() => choisirSignataire(false)}
              className="h-4 w-4 accent-sky"
            />
            {nomClient}
          </label>

          <label className="flex items-center gap-2 text-sm text-ink">
            <input
              type="radio"
              name="cheque-signataire"
              checked={value.autreSignataire}
              onChange={() => choisirSignataire(true)}
              className="h-4 w-4 accent-sky"
            />
            Une autre personne
          </label>

          {value.autreSignataire ? (
            <input
              type="text"
              value={value.draft.drawer_name}
              onChange={(e) => onChange({ ...value, draft: { ...value.draft, drawer_name: e.target.value } })}
              placeholder="Nom porté sur le chèque"
              className="w-full rounded-lg border border-line bg-card px-3 py-2 text-sm text-ink outline-none focus:border-sky"
            />
          ) : null}
        </fieldset>
      </CardBody>
    </Card>
  )
}
