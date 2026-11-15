import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Copy, Phone, Send, Trash2, XCircle } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Badge, Button, Card, Erreur, Field, Input, Select, Spinner, Succes } from '../components/ui'
import { api, dateHeure, dh, messageErreur } from '../lib/api'
import { STATUTS_DEVIS } from '../lib/types'
import type { DevisResume } from './Quotes'

interface LigneDevis {
  id: number
  product_id: number
  name: string
  sku: string | null
  quantity: number
  unit_price: number
  price_type_code: string | null
  line_total: number
  stock_at_request: number | null
}

interface DevisComplet extends DevisResume {
  address: string | null
  note: string | null
  reply: string | null
  decline_reason: string | null
  public_token: string
  handled_by: string | null
  lines: LigneDevis[]
}

interface Lieu {
  id: number
  code: string
  name: string
}

/** Brouillon local d'une ligne pendant le chiffrage. */
interface Brouillon {
  id: number
  quantity: string
  unit_price: string
  retiree: boolean
}

/**
 * Chiffrage d'un devis : prix ferme ligne à ligne, port, validité, lieu
 * d'expédition. Rien n'est envoyé au client tant que le bouton « Envoyer »
 * n'a pas été pressé — on peut donc reprendre un chiffrage à tête reposée.
 */
export function AdminQuoteDetail() {
  const { id } = useParams<{ id: string }>()
  const qc = useQueryClient()
  const [brouillons, setBrouillons] = useState<Brouillon[]>([])
  const [port, setPort] = useState('')
  const [validite, setValidite] = useState('')
  const [reponse, setReponse] = useState('')
  const [lieu, setLieu] = useState('')
  const [motif, setMotif] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [copie, setCopie] = useState(false)

  const { data: d, isLoading } = useQuery<DevisComplet>({
    queryKey: ['store-admin-quote', id],
    queryFn: async () => (await api<{ data: DevisComplet }>(`/v1/store-admin/quotes/${id}`, { auth: true })).data,
  })

  const { data: lieux = [] } = useQuery<Lieu[]>({
    queryKey: ['store-admin-warehouses'],
    queryFn: async () => (await api<{ data: Lieu[] }>('/v1/warehouses', { auth: true })).data,
    staleTime: 10 * 60_000,
  })

  // Le formulaire part de ce que le devis contient déjà : on corrige, on ne
  // ressaisit pas.
  useEffect(() => {
    if (!d) return
    setBrouillons(d.lines.map((l) => ({
      id: l.id,
      quantity: String(l.quantity),
      unit_price: l.unit_price > 0 ? String(l.unit_price) : '',
      retiree: false,
    })))
    setPort(String(d.shipping_fee ?? 0))
    setValidite(d.valid_until ?? '')
    setReponse(d.reply ?? '')
    setLieu(d.warehouse_id ? String(d.warehouse_id) : '')
  }, [d])

  function rafraichir() {
    qc.invalidateQueries({ queryKey: ['store-admin-quote', id] })
    qc.invalidateQueries({ queryKey: ['store-admin-quotes'] })
    qc.invalidateQueries({ queryKey: ['store-admin-dashboard'] })
  }

  const corps = () => ({
    lines: brouillons
      .filter((b) => !b.retiree)
      .map((b) => ({ id: b.id, quantity: Number(b.quantity) || 1, unit_price: Number(b.unit_price) || 0 })),
    shipping_fee: Number(port) || 0,
    valid_until: validite || null,
    reply: reponse || null,
    warehouse_id: lieu ? Number(lieu) : null,
  })

  const enregistrer = useMutation({
    mutationFn: async () => api(`/v1/store-admin/quotes/${id}`, { method: 'PUT', auth: true, body: corps() }),
    onMutate: () => { setErreur(null); setMessage(null) },
    onSuccess: () => { rafraichir(); setMessage('Chiffrage enregistré.') },
    onError: (e) => setErreur(messageErreur(e, 'Le chiffrage n’a pas pu être enregistré.')),
  })

  const envoyer = useMutation({
    // On enregistre avant d'envoyer : sinon le client recevrait l'état
    // d'avant la dernière correction à l'écran.
    mutationFn: async () => {
      await api(`/v1/store-admin/quotes/${id}`, { method: 'PUT', auth: true, body: corps() })
      return api(`/v1/store-admin/quotes/${id}/send`, { method: 'POST', auth: true })
    },
    onMutate: () => { setErreur(null); setMessage(null) },
    onSuccess: () => { rafraichir(); setMessage('Devis envoyé. Transmettez le lien au client.') },
    onError: (e) => setErreur(messageErreur(e, 'L’envoi a échoué.')),
  })

  const annuler = useMutation({
    mutationFn: async () => api(`/v1/store-admin/quotes/${id}/cancel`, { method: 'POST', auth: true, body: { reason: motif || null } }),
    onMutate: () => { setErreur(null); setMessage(null) },
    onSuccess: () => { rafraichir(); setMessage('Devis annulé.') },
    onError: (e) => setErreur(messageErreur(e, 'L’annulation a échoué.')),
  })

  if (isLoading || !d) return <Spinner />

  const etat = STATUTS_DEVIS[d.status] ?? { label: d.status, tone: 'neutral' as const, aide: '' }
  const modifiable = d.status === 'requested' || d.status === 'quoted' || d.status === 'expired'
  const actives = brouillons.filter((b) => !b.retiree)
  const sousTotal = actives.reduce((s, b) => s + (Number(b.unit_price) || 0) * (Number(b.quantity) || 0), 0)
  const total = sousTotal + (Number(port) || 0)
  const lien = `${window.location.origin}/devis/${d.public_token}`
  const chiffrageIncomplet = actives.some((b) => !(Number(b.unit_price) > 0))

  return (
    <div className="space-y-5">
      <Link to="/admin/devis" className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
        <ArrowLeft className="h-4 w-4" /> Tous les devis
      </Link>

      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="font-mono text-xl font-bold">{d.number}</h1>
          <p className="text-sm text-muted">Demandé le {dateHeure(d.created_at)}</p>
        </div>
        <div className="text-right">
          <Badge tone={etat.tone}>{etat.label}</Badge>
          {d.order_number ? <p className="mt-1 font-mono text-xs text-ok">Commande {d.order_number}</p> : null}
          {d.sale_reference ? <p className="mt-1 font-mono text-xs text-muted">Devis gestion {d.sale_reference}</p> : null}
        </div>
      </div>

      {erreur ? <Erreur message={erreur} /> : null}
      {message ? <Succes message={message} /> : null}

      <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
        <div className="space-y-5">
          <Card className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-xs text-muted">
                  <th className="px-4 py-3">Article</th>
                  <th className="px-4 py-3 w-28">Quantité</th>
                  <th className="px-4 py-3 w-32">Prix unitaire</th>
                  <th className="px-4 py-3 text-right">Total</th>
                  <th className="px-4 py-3" />
                </tr>
              </thead>
              <tbody>
                {d.lines.map((l) => {
                  const b = brouillons.find((x) => x.id === l.id)
                  if (!b) return null
                  const ligneTotal = (Number(b.unit_price) || 0) * (Number(b.quantity) || 0)

                  return (
                    <tr key={l.id} className={`border-t border-line ${b.retiree ? 'opacity-40' : ''}`}>
                      <td className="px-4 py-3">
                        <span className="font-medium">{l.name}</span>
                        <p className="text-xs text-faint">
                          Réf. {l.sku ?? '—'}
                          {l.price_type_code ? ` · barème ${l.price_type_code}` : ''}
                          {l.stock_at_request !== null ? ` · ${l.stock_at_request} en stock à la demande` : ''}
                        </p>
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number" min={1} value={b.quantity} disabled={!modifiable || b.retiree}
                          onChange={(e) => setBrouillons((p) => p.map((x) => (x.id === l.id ? { ...x, quantity: e.target.value } : x)))}
                        />
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number" min={0} step="0.01" value={b.unit_price} disabled={!modifiable || b.retiree}
                          placeholder="0,00"
                          onChange={(e) => setBrouillons((p) => p.map((x) => (x.id === l.id ? { ...x, unit_price: e.target.value } : x)))}
                        />
                      </td>
                      <td className="px-4 py-3 text-right font-medium">{ligneTotal > 0 ? dh(ligneTotal) : '—'}</td>
                      <td className="px-4 py-3 text-right">
                        {modifiable ? (
                          <button
                            type="button"
                            className="text-muted hover:text-bad"
                            title={b.retiree ? 'Remettre cette ligne' : 'Retirer cette ligne du devis'}
                            onClick={() => setBrouillons((p) => p.map((x) => (x.id === l.id ? { ...x, retiree: !x.retiree } : x)))}
                          >
                            {b.retiree ? <span className="text-xs">Remettre</span> : <Trash2 className="h-4 w-4" />}
                          </button>
                        ) : null}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </Card>

          {modifiable ? (
            <Card className="grid gap-4 p-5 sm:grid-cols-3">
              <Field label="Frais de livraison" htmlFor="q-port">
                <Input id="q-port" type="number" min={0} step="0.01" value={port} onChange={(e) => setPort(e.target.value)} />
              </Field>
              <Field label="Valable jusqu’au" htmlFor="q-validite" hint="Vide = délai par défaut.">
                <Input id="q-validite" type="date" value={validite} onChange={(e) => setValidite(e.target.value)} />
              </Field>
              <Field label="Lieu d’expédition" htmlFor="q-lieu" hint="Nécessaire pour créer le devis dans la gestion.">
                <Select id="q-lieu" value={lieu} onChange={(e) => setLieu(e.target.value)}>
                  <option value="">Choisir…</option>
                  {lieux.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}
                </Select>
              </Field>
              <div className="sm:col-span-3">
                <Field label="Mot au client" htmlFor="q-reponse" hint="Affiché sur le devis : délai, conditions, contrepartie d’une remise.">
                  <Input id="q-reponse" value={reponse} onChange={(e) => setReponse(e.target.value)} placeholder="Prix ferme pour 200 pièces, livrables sous 5 jours." />
                </Field>
              </div>
            </Card>
          ) : null}

          <Card className="space-y-2 p-5 text-sm">
            <h2 className="font-semibold">Le client</h2>
            <p className="font-medium">{d.company || d.customer_name}{d.company ? ` · ${d.customer_name}` : ''}</p>
            <p className="text-muted">
              <a href={`tel:${d.customer_phone}`} className="inline-flex items-center gap-1 hover:text-brand">
                <Phone className="h-3.5 w-3.5" /> {d.customer_phone}
              </a>
              {d.customer_email ? <> · {d.customer_email}</> : null}
            </p>
            <p className="text-muted">{[d.address, d.city].filter(Boolean).join(', ')}</p>
            {d.note ? <p className="rounded-lg bg-paper p-3 text-muted">« {d.note} »</p> : null}
            {d.decline_reason ? <p className="text-bad">Refus : {d.decline_reason}</p> : null}
            {d.handled_by ? <p className="text-xs text-faint">Suivi par {d.handled_by}</p> : null}
          </Card>
        </div>

        <div className="space-y-5">
          <Card className="p-5">
            <h2 className="font-semibold">Montant</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between"><dt className="text-muted">Sous-total</dt><dd>{dh(sousTotal)}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Livraison</dt><dd>{(Number(port) || 0) === 0 ? 'Offerte' : dh(Number(port))}</dd></div>
              <div className="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Total</dt><dd>{dh(total)}</dd></div>
            </dl>

            {modifiable ? (
              <div className="mt-4 space-y-2">
                <Button variant="outline" className="h-10 w-full" disabled={enregistrer.isPending} onClick={() => enregistrer.mutate()}>
                  {enregistrer.isPending ? 'Enregistrement…' : 'Enregistrer sans envoyer'}
                </Button>
                <Button className="h-10 w-full" disabled={envoyer.isPending || chiffrageIncomplet || actives.length === 0} onClick={() => envoyer.mutate()}>
                  <Send className="h-4 w-4" /> {envoyer.isPending ? 'Envoi…' : d.status === 'requested' ? 'Chiffrer et envoyer' : 'Renvoyer au client'}
                </Button>
                {chiffrageIncomplet ? (
                  <p className="text-xs text-warn">Toutes les lignes gardées doivent avoir un prix.</p>
                ) : null}
                {!lieu ? (
                  <p className="text-xs text-faint">Sans lieu d’expédition, le devis reste propre à la boutique et n’apparaît pas dans la gestion.</p>
                ) : null}
              </div>
            ) : null}
          </Card>

          {d.status !== 'requested' ? (
            <Card className="space-y-3 p-5">
              <h2 className="text-sm font-semibold">Lien du client</h2>
              <code className="block truncate rounded-lg bg-paper px-3 py-2 text-xs">{lien}</code>
              <div className="flex flex-wrap gap-2">
                <Button
                  variant="outline" className="h-9 flex-1 text-xs"
                  onClick={() => navigator.clipboard?.writeText(lien).then(() => setCopie(true), () => setCopie(false))}
                >
                  <Copy className="h-3.5 w-3.5" /> {copie ? 'Copié' : 'Copier'}
                </Button>
                <a
                  href={`https://wa.me/${d.customer_phone.replace(/\D/g, '').replace(/^0/, '212')}?text=${encodeURIComponent(`Bonjour, votre devis ${d.number} est prêt : ${lien}`)}`}
                  target="_blank" rel="noreferrer"
                  className="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-line px-3 text-xs hover:border-brand hover:text-brand"
                >
                  WhatsApp
                </a>
              </div>
              <p className="text-xs text-faint">
                {d.quoted_at ? `Envoyé le ${dateHeure(d.quoted_at)}.` : ''}
                {d.answered_at ? ` Réponse le ${dateHeure(d.answered_at)}.` : ''}
              </p>
            </Card>
          ) : null}

          {d.status !== 'accepted' && d.status !== 'cancelled' ? (
            <Card className="space-y-3 p-5">
              <h2 className="text-sm font-semibold">Annuler ce devis</h2>
              <Input value={motif} onChange={(e) => setMotif(e.target.value)} placeholder="Motif (facultatif)" />
              <Button variant="danger" className="h-9 w-full text-xs" disabled={annuler.isPending} onClick={() => annuler.mutate()}>
                <XCircle className="h-3.5 w-3.5" /> {annuler.isPending ? 'Annulation…' : 'Annuler le devis'}
              </Button>
            </Card>
          ) : null}
        </div>
      </div>
    </div>
  )
}
