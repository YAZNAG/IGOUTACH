import { CheckCircle2 } from 'lucide-react'
import { Link, Navigate, useLocation } from 'react-router-dom'
import { Card } from '../components/ui'
import { dh } from '../lib/api'
import type { SuiviCommande } from '../lib/types'

export function OrderDone() {
  const etat = useLocation().state as { commande?: SuiviCommande; phone?: string } | null
  const commande = etat?.commande

  if (!commande) return <Navigate to="/" replace />

  return (
    <div className="mx-auto max-w-2xl space-y-6 py-6">
      <div className="text-center">
        <CheckCircle2 className="mx-auto h-14 w-14 text-ok" />
        <h1 className="mt-4 text-2xl font-bold">Merci, votre commande est enregistrée</h1>
        <p className="mt-2 text-muted">
          Numéro <span className="font-mono font-semibold text-ink">{commande.number}</span>. Nous vous appelons au plus vite pour la confirmer.
        </p>
      </div>

      <Card className="p-5">
        <ul className="divide-y divide-line text-sm">
          {commande.lines.map((l, i) => (
            <li key={i} className="flex justify-between gap-3 py-2">
              <span className="text-muted">{l.quantity} × {l.name}</span>
              <span>{dh(l.line_total)}</span>
            </li>
          ))}
        </ul>
        <dl className="mt-3 space-y-1 border-t border-line pt-3 text-sm">
          <div className="flex justify-between"><dt className="text-muted">Livraison</dt><dd>{commande.shipping_fee === 0 ? 'Offerte' : dh(commande.shipping_fee)}</dd></div>
          <div className="flex justify-between text-base font-bold"><dt>Total</dt><dd>{dh(commande.total)}</dd></div>
        </dl>
      </Card>

      {commande.payment_method === 'transfer' ? (
        <Card className="space-y-2 p-5">
          <h2 className="font-semibold">Virement bancaire</h2>
          <p className="text-sm text-muted">Merci de virer {dh(commande.total)} en indiquant le numéro {commande.number} en motif. L’envoi part à réception.</p>
          {commande.bank_details ? <pre className="whitespace-pre-wrap rounded-lg bg-paper p-3 font-mono text-sm">{commande.bank_details}</pre> : null}
        </Card>
      ) : (
        <Card className="p-5 text-sm text-muted">Vous réglerez {dh(commande.total)} au livreur, en espèces.</Card>
      )}

      <p className="text-center text-sm text-muted">
        Gardez votre numéro : <Link to="/suivi" state={{ number: commande.number, phone: etat?.phone }} className="text-brand hover:underline">suivre ma commande</Link>
      </p>
    </div>
  )
}
