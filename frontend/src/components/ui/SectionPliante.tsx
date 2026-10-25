import { ChevronDown, ChevronRight } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Card, CardBody, CardHeader } from './Card'

/**
 * Section repliable : un titre qu'on ouvre et qu'on ferme.
 *
 * Certaines pages empilent plusieurs tableaux longs — la balance âgée, le
 * crédit par lieu, l'historique des règlements. Déroulées ensemble, elles
 * obligent à faire défiler des centaines de lignes pour atteindre la
 * suivante. Chacune se replie donc, et l'état est retenu d'une visite à
 * l'autre : celui qui ne consulte jamais l'historique ne le rouvre pas
 * chaque matin.
 */
export function SectionPliante({
  titre,
  hint,
  action,
  children,
  cle,
  ouvertParDefaut = true,
  bodyClassName,
}: {
  titre: string
  hint?: string
  /** Boutons du titre : ils restent visibles section fermée. */
  action?: ReactNode
  children: ReactNode
  /** Identifiant de mémorisation ; sans lui, rien n'est retenu. */
  cle?: string
  ouvertParDefaut?: boolean
  bodyClassName?: string
}) {
  const [ouvert, setOuvert] = useState(() => {
    if (!cle) return ouvertParDefaut
    try {
      const garde = localStorage.getItem(`section:${cle}`)
      return garde === null ? ouvertParDefaut : garde === '1'
    } catch {
      // Navigation privée ou stockage refusé : on s'en tient au défaut.
      return ouvertParDefaut
    }
  })

  function basculer() {
    setOuvert((v) => {
      const suivant = !v
      if (cle) {
        try {
          localStorage.setItem(`section:${cle}`, suivant ? '1' : '0')
        } catch {
          // Le repli reste valable pour cette visite, faute de mieux.
        }
      }
      return suivant
    })
  }

  return (
    <Card>
      <CardHeader
        title={
          <button
            type="button"
            onClick={basculer}
            aria-expanded={ouvert}
            className="flex items-center gap-2 text-left text-ink hover:text-sky"
          >
            {ouvert ? (
              <ChevronDown className="h-4 w-4 text-muted" />
            ) : (
              <ChevronRight className="h-4 w-4 text-muted" />
            )}
            {titre}
          </button>
        }
        hint={hint}
        action={action}
      />
      {ouvert ? <CardBody className={bodyClassName}>{children}</CardBody> : null}
    </Card>
  )
}
