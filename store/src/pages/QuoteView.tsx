import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CalendarClock, CheckCircle2, FileText, XCircle } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Badge, Button, Card, Erreur, Field, Input, Spinner, Succes } from '../components/ui'
import { api, dh, messageErreur } from '../lib/api'
import { PAIEMENTS, STATUTS_DEVIS, type Devis } from '../lib/types'

/**
 * Le devis vu par le client, par le lien qu'il a reçu.
 *
 * Le lien vaut authentification : rien d'autre n'est demandé. On n'y fait
 * que deux choses, accepter ou refuser, et une seule fois.
 */
export function QuoteView() {
  const { token = '' } = useParams<{ token: string }>()
  const queryClient = useQueryClient()
  const [paiement, setPaiement] = useState<'cod' | 'transfer' | ''>('')
  const [adresse, setAdresse] = useState('')
  const [motif, setMotif] = useState('')
  const [refuser, setRefuser] = useState(false)
  const [commande, setCommande] = useState<string | null>(null)

  const { data: devis, isLoading, error } = useQuery<Devis>({
    queryKey: ['store-quote', token],
    queryFn: async () => (await api<{ data: Devis }>(`/store/quotes/${token}`)).data,
  })

  const accepter = useMutation({
    mutationFn: async () =>
      (
        await api<{ data: { order_number: string; quote: Devis } }>(`/store/quotes/${token}/accept`, {
          method: 'POST',
          body: { payment_method: paiement || devis?.payment_methods[0], address: adresse || null },
        })
      ).data,
    onSuccess: (r) => {
      setCommande(r.order_number)
      queryClient.setQueryData(['store-quote', token], r.quote)
    },
  })

  const decliner = useMutation({
    mutationFn: async () =>
      (await api<{ data: Devis }>(`/store/quotes/${token}/decline`, { method: 'POST', body: { reason: motif || null } })).data,
    onSuccess: (d) => queryClient.setQueryData(['store-quote', token], d),
  })

  if (isLoading) return <Spinner />
  if (error || !devis) return <Erreur message={messageErreur(error, 'Ce devis est introuvable.')} />

  const etat = STATUTS_DEVIS[devis.status] ?? { label: devis.status, tone: 'neutral' as const, aide: '' }
  const repondable = devis.status === 'quoted'
  const methodes: ('cod' | 'transfer')[] = devis.payment_methods.length > 0 ? devis.payment_methods : ['cod']

  return (
    <div className="mx-auto max-w-3xl space-y-6 py-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="inline-flex items-center gap-2 text-xs uppercase tracking-wide text-faint">
            <FileText className="h-3.5 w-3.5" /> Devis
          </p>
          <h1 className="mt-1 text-2xl font-bold">{devis.number}</h1>
          <p className="mt-1 text-sm text-muted">
            {devis.company || devis.customer_name} · {devis.city}
          </p>
        </div>
        <div className="text-right">
          <Badge tone={etat.tone}>{etat.label}</Badge>
          <p className="mt-1 text-xs text-faint">{etat.aide}</p>
        </div>
      </div>

      {commande ? (
        <Succes message={`Devis accepté. Votre commande ${commande} est enregistrée : nous vous appelons pour la livraison.`} />
      ) : null}

      {devis.status === 'requested' ? (
        <Card className="p-5 text-sm text-muted">
          Nous préparons votre prix. Vous recevrez une réponse sur ce même lien — gardez-le.
        </Card>
      ) : null}

      {devis.reply ? (
        <Card className="p-5">
          <h2 className="text-sm font-semibold">Notre réponse</h2>
          <p className="mt-2 whitespace-pre-wrap text-sm text-muted">{devis.reply}</p>
        </Card>
      ) : null}

      <Card className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-muted">
              <th className="px-4 py-3">Article</th>
              <th className="px-4 py-3 text-right">Quantité</th>
              <th className="px-4 py-3 text-right">Prix unitaire</th>
              <th className="px-4 py-3 text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            {devis.lines.map((l, i) => (
              <tr key={i} className="border-t border-line">
                <td className="px-4 py-3">
                  <span className="font-medium">{l.name}</span>
                  {l.sku ? <p className="text-xs text-faint">Réf. {l.sku}</p> : null}
                </td>
                <td className="px-4 py-3 text-right">{l.quantity}</td>
                <td className="px-4 py-3 text-right">{l.unit_price > 0 ? dh(l.unit_price) : '—'}</td>
                <td className="px-4 py-3 text-right font-medium">{l.line_total > 0 ? dh(l.line_total) : '—'}</td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t border-line text-sm">
              <td className="px-4 py-2" colSpan={3}>Sous-total</td>
              <td className="px-4 py-2 text-right">{dh(devis.subtotal)}</td>
            </tr>
            <tr className="text-sm">
              <td className="px-4 py-2 text-muted" colSpan={3}>Livraison</td>
              <td className="px-4 py-2 text-right">{devis.shipping_fee === 0 ? 'Offerte' : dh(devis.shipping_fee)}</td>
            </tr>
            <tr className="border-t border-line text-base font-bold">
              <td className="px-4 py-3" colSpan={3}>Total</td>
              <td className="px-4 py-3 text-right">{dh(devis.total)}</td>
            </tr>
          </tfoot>
        </table>
      </Card>

      {devis.valid_until ? (
        <p className="inline-flex items-center gap-2 text-sm text-muted">
          <CalendarClock className="h-4 w-4" />
          {repondable ? 'Valable jusqu’au' : 'Validité'} {new Date(devis.valid_until).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}
        </p>
      ) : null}

      {repondable && !commande ? (
        <Card className="space-y-4 p-5">
          <h2 className="font-semibold">Votre réponse</h2>

          {!refuser ? (
            <>
              <div className="space-y-2">
                {methodes.includes('cod') ? (
                  <label className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 ${paiement === 'cod' ? 'border-brand bg-brand-soft/40' : 'border-line'}`}>
                    <input type="radio" className="mt-1 accent-brand" checked={paiement === 'cod'} onChange={() => setPaiement('cod')} />
                    <span><span className="block font-medium">{PAIEMENTS.cod}</span><span className="text-sm text-muted">Vous réglez à réception.</span></span>
                  </label>
                ) : null}
                {methodes.includes('transfer') ? (
                  <label className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 ${paiement === 'transfer' ? 'border-brand bg-brand-soft/40' : 'border-line'}`}>
                    <input type="radio" className="mt-1 accent-brand" checked={paiement === 'transfer'} onChange={() => setPaiement('transfer')} />
                    <span><span className="block font-medium">{PAIEMENTS.transfer}</span><span className="text-sm text-muted">Les coordonnées bancaires s’affichent après votre accord.</span></span>
                  </label>
                ) : null}
              </div>

              <Field label="Adresse de livraison (facultatif)" htmlFor="q-adresse" hint="Si elle diffère de celle indiquée à la demande.">
                <Input id="q-adresse" value={adresse} onChange={(e) => setAdresse(e.target.value)} />
              </Field>

              {accepter.isError ? <Erreur message={messageErreur(accepter.error, 'L’acceptation a échoué.')} /> : null}

              <div className="flex flex-wrap gap-3">
                <Button className="h-11" disabled={!paiement || accepter.isPending} onClick={() => accepter.mutate()}>
                  <CheckCircle2 className="h-4 w-4" /> {accepter.isPending ? 'Enregistrement…' : 'Accepter ce devis'}
                </Button>
                <Button variant="outline" className="h-11" onClick={() => setRefuser(true)}>
                  <XCircle className="h-4 w-4" /> Refuser
                </Button>
              </div>
              {!paiement ? <p className="text-xs text-faint">Choisissez un mode de paiement pour accepter.</p> : null}
            </>
          ) : (
            <>
              <Field label="Pourquoi ? (facultatif)" htmlFor="q-motif" hint="Votre retour nous aide à mieux chiffrer la prochaine fois.">
                <Input id="q-motif" value={motif} onChange={(e) => setMotif(e.target.value)} placeholder="Prix, délai, besoin reporté…" />
              </Field>
              {decliner.isError ? <Erreur message={messageErreur(decliner.error, 'Le refus n’a pas pu être enregistré.')} /> : null}
              <div className="flex flex-wrap gap-3">
                <Button variant="danger" className="h-11" disabled={decliner.isPending} onClick={() => decliner.mutate()}>
                  {decliner.isPending ? 'Envoi…' : 'Confirmer le refus'}
                </Button>
                <Button variant="ghost" className="h-11" onClick={() => setRefuser(false)}>Revenir</Button>
              </div>
            </>
          )}
        </Card>
      ) : null}

      {commande && devis.bank_details && paiement === 'transfer' ? (
        <Card className="space-y-2 p-5">
          <h2 className="font-semibold">Virement bancaire</h2>
          <p className="text-sm text-muted">Merci de virer {dh(devis.total)} en indiquant {commande} en motif.</p>
          <pre className="whitespace-pre-wrap rounded-lg bg-paper p-3 font-mono text-sm">{devis.bank_details}</pre>
        </Card>
      ) : null}

      <p className="text-center text-sm text-muted">
        <Link to="/catalogue" className="text-brand hover:underline">Retour au catalogue</Link>
      </p>
    </div>
  )
}
