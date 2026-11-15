import { useMutation } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { Badge, Button, Card, Erreur, Field, Input, cn } from '../components/ui'
import { api, dateHeure, dh, messageErreur } from '../lib/api'
import { ETATS_PAIEMENT, PAIEMENTS, STATUTS, type Devis, type SuiviCommande } from '../lib/types'

const ETAPES = ['new', 'confirmed', 'preparing', 'shipped', 'delivered']

export function Track() {
  const etat = useLocation().state as { number?: string; phone?: string } | null
  const navigate = useNavigate()
  const [numero, setNumero] = useState(etat?.number ?? '')
  const [tel, setTel] = useState(etat?.phone ?? '')
  // Un client qui a perdu son lien de devis a toujours son numéro : les deux
  // suivis partagent donc le même formulaire.
  const [objet, setObjet] = useState<'commande' | 'devis'>(
    (etat?.number ?? '').toUpperCase().startsWith('DEV-') ? 'devis' : 'commande',
  )

  const suivre = useMutation({
    mutationFn: async () => (await api<{ data: SuiviCommande }>('/store/orders/track', { method: 'POST', body: { number: numero, phone: tel } })).data,
  })

  const suivreDevis = useMutation({
    mutationFn: async () => (await api<{ data: Devis }>('/store/quotes/track', { method: 'POST', body: { number: numero, phone: tel } })).data,
    onSuccess: (d) => {
      // Le lien public porte tout : on y renvoie plutôt que de redessiner le
      // devis une seconde fois ici.
      if (d.public_token) navigate(`/devis/${d.public_token}`)
    },
  })

  const recherche = objet === 'devis' ? suivreDevis : suivre

  function envoyer(e: FormEvent) {
    e.preventDefault()
    recherche.mutate()
  }

  const c = suivre.data
  const indexEtape = c ? ETAPES.indexOf(c.status) : -1

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Suivre ma {objet}</h1>
        <p className="text-sm text-muted">Indiquez le numéro reçu et votre téléphone.</p>
      </div>

      <div className="inline-flex rounded-lg border border-line bg-white p-1 text-sm">
        {([['commande', 'Commande'], ['devis', 'Devis']] as const).map(([cle, libelle]) => (
          <button
            key={cle}
            type="button"
            onClick={() => setObjet(cle)}
            className={cn('rounded-md px-4 py-1.5', objet === cle ? 'bg-brand text-white' : 'text-muted hover:text-ink')}
          >
            {libelle}
          </button>
        ))}
      </div>

      <Card className="p-5">
        <form onSubmit={envoyer} className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
          <Field label={objet === 'devis' ? 'Numéro de devis' : 'Numéro de commande'} htmlFor="s-num">
            <Input id="s-num" value={numero} onChange={(e) => setNumero(e.target.value)} placeholder={objet === 'devis' ? 'DEV-261108-001' : 'WEB-261108-001'} required />
          </Field>
          <Field label="Téléphone" htmlFor="s-tel"><Input id="s-tel" value={tel} onChange={(e) => setTel(e.target.value)} placeholder="06 12 34 56 78" required /></Field>
          <Button type="submit" disabled={recherche.isPending}>{recherche.isPending ? 'Recherche…' : 'Rechercher'}</Button>
        </form>
        {recherche.isError ? <div className="mt-4"><Erreur message={messageErreur(recherche.error)} /></div> : null}
      </Card>

      {objet === 'commande' && c ? (
        <Card className="space-y-5 p-5">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <p className="font-mono text-lg font-semibold">{c.number}</p>
            <div className="flex gap-2">
              <Badge tone={STATUTS[c.status]?.tone ?? 'neutral'}>{STATUTS[c.status]?.label ?? c.status}</Badge>
              <Badge tone={ETATS_PAIEMENT[c.payment_status]?.tone ?? 'warn'}>{ETATS_PAIEMENT[c.payment_status]?.label ?? c.payment_status}</Badge>
            </div>
          </div>

          {c.status !== 'cancelled' ? (
            <ol className="grid grid-cols-5 gap-1 text-center text-[11px] text-muted">
              {ETAPES.map((e, i) => (
                <li key={e} className="space-y-1">
                  <div className={`mx-auto h-2 rounded-full ${i <= indexEtape ? 'bg-brand' : 'bg-line'}`} />
                  <span className={i <= indexEtape ? 'font-medium text-ink' : ''}>{STATUTS[e].label}</span>
                </li>
              ))}
            </ol>
          ) : null}

          <dl className="grid gap-2 text-sm sm:grid-cols-2">
            <div><dt className="text-faint">Commandée le</dt><dd>{dateHeure(c.created_at)}</dd></div>
            <div><dt className="text-faint">Paiement</dt><dd>{PAIEMENTS[c.payment_method]}</dd></div>
            {c.shipped_at ? <div><dt className="text-faint">Expédiée le</dt><dd>{dateHeure(c.shipped_at)}</dd></div> : null}
            {c.delivered_at ? <div><dt className="text-faint">Livrée le</dt><dd>{dateHeure(c.delivered_at)}</dd></div> : null}
          </dl>

          <ul className="divide-y divide-line text-sm">
            {c.lines.map((l, i) => (
              <li key={i} className="flex justify-between gap-3 py-2"><span className="text-muted">{l.quantity} × {l.name}</span><span>{dh(l.line_total)}</span></li>
            ))}
          </ul>
          <div className="flex justify-between border-t border-line pt-3 font-bold"><span>Total</span><span>{dh(c.total)}</span></div>

          {c.payment_method === 'transfer' && c.payment_status !== 'paid' && c.bank_details ? (
            <div className="rounded-lg bg-paper p-3 text-sm">
              <p className="font-medium">Coordonnées pour le virement</p>
              <pre className="mt-1 whitespace-pre-wrap font-mono text-xs text-muted">{c.bank_details}</pre>
            </div>
          ) : null}
        </Card>
      ) : null}

      {objet === 'devis' ? (
        <p className="text-sm text-muted">
          Pas encore de devis ? <Link to="/catalogue" className="text-brand hover:underline">Composez votre demande depuis le catalogue.</Link>
        </p>
      ) : null}
    </div>
  )
}
