import { Clock, Copy, FileText } from 'lucide-react'
import { useState } from 'react'
import { Link, Navigate, useLocation } from 'react-router-dom'
import { Card } from '../components/ui'
import { dh } from '../lib/api'
import type { Devis } from '../lib/types'

/**
 * Accusé de réception d'une demande de devis.
 *
 * Le lien de suivi est donné tout de suite, et le numéro aussi : si le
 * client perd l'un, l'autre le retrouve.
 */
export function QuoteDone() {
  const etat = useLocation().state as { devis?: Devis } | null
  const devis = etat?.devis
  const [copie, setCopie] = useState(false)

  if (!devis) return <Navigate to="/" replace />

  const lien = devis.public_token ? `${window.location.origin}/devis/${devis.public_token}` : null

  return (
    <div className="mx-auto max-w-2xl space-y-6 py-6">
      <div className="text-center">
        <FileText className="mx-auto h-14 w-14 text-brand" />
        <h1 className="mt-4 text-2xl font-bold">Votre demande est partie</h1>
        <p className="mt-2 text-muted">
          Référence <span className="font-mono font-semibold text-ink">{devis.number}</span>. Nous revenons vers vous
          avec un prix ferme, en général sous 24 h ouvrées.
        </p>
      </div>

      <Card className="flex items-start gap-3 p-5 text-sm">
        <Clock className="mt-0.5 h-5 w-5 shrink-0 text-muted" />
        <div>
          <p className="font-medium">Ce qui se passe maintenant</p>
          <ol className="mt-2 list-decimal space-y-1 pl-4 text-muted">
            <li>Nous vérifions la disponibilité des quantités demandées.</li>
            <li>Vous recevez un devis chiffré, avec sa date de validité.</li>
            <li>Vous l’acceptez ou le refusez — la commande ne part qu’après votre accord.</li>
          </ol>
        </div>
      </Card>

      <Card className="p-5">
        <h2 className="font-semibold">Votre demande</h2>
        <ul className="mt-3 divide-y divide-line text-sm">
          {devis.lines.map((l, i) => (
            <li key={i} className="flex justify-between gap-3 py-2">
              <span className="clamp-2 text-muted">{l.quantity} × {l.name}</span>
              <span className="shrink-0 text-faint">{dh(l.line_total)}</span>
            </li>
          ))}
        </ul>
        <p className="mt-3 border-t border-line pt-3 text-xs text-faint">
          Montants indicatifs, au barème public. Le devis fera foi.
        </p>
      </Card>

      {lien ? (
        <Card className="space-y-3 p-5">
          <h2 className="font-semibold">Votre lien de suivi</h2>
          <p className="text-sm text-muted">Gardez-le : c’est par là que vous verrez le prix et répondrez.</p>
          <div className="flex gap-2">
            <code className="min-w-0 flex-1 truncate rounded-lg bg-paper px-3 py-2 text-xs">{lien}</code>
            <button
              type="button"
              className="inline-flex h-9 shrink-0 items-center gap-1 rounded-lg border border-line px-3 text-sm hover:border-brand hover:text-brand"
              onClick={() => {
                navigator.clipboard?.writeText(lien).then(
                  () => setCopie(true),
                  () => setCopie(false),
                )
              }}
            >
              <Copy className="h-3.5 w-3.5" /> {copie ? 'Copié' : 'Copier'}
            </button>
          </div>
          <Link to={`/devis/${devis.public_token}`} className="block text-sm text-brand hover:underline">
            Ouvrir mon devis
          </Link>
        </Card>
      ) : null}

      <p className="text-center text-sm text-muted">
        <Link to="/catalogue" className="text-brand hover:underline">Continuer vos achats</Link>
      </p>
    </div>
  )
}
