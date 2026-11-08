import { useMutation } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { useReglages } from '../components/Layout'
import { Button, Card, Erreur, Field, Input } from '../components/ui'
import { api, dh, messageErreur } from '../lib/api'
import { usePanier } from '../lib/cart'
import type { SuiviCommande } from '../lib/types'

export function Checkout() {
  const { lignes, sousTotal, vider } = usePanier()
  const { data: reglages } = useReglages()
  const navigate = useNavigate()

  const [nom, setNom] = useState('')
  const [tel, setTel] = useState('')
  const [email, setEmail] = useState('')
  const [ville, setVille] = useState('')
  const [adresse, setAdresse] = useState('')
  const [note, setNote] = useState('')
  const [paiement, setPaiement] = useState<'cod' | 'transfer'>('cod')

  const methodes = reglages?.payment_methods ?? ['cod']
  const port = reglages ? (reglages.free_shipping_from > 0 && sousTotal >= reglages.free_shipping_from ? 0 : reglages.shipping_fee) : 0

  const commander = useMutation({
    mutationFn: async () =>
      (
        await api<{ data: SuiviCommande }>('/store/orders', {
          method: 'POST',
          body: {
            customer_name: nom,
            customer_phone: tel,
            customer_email: email || null,
            city: ville,
            address: adresse,
            note: note || null,
            payment_method: methodes.includes(paiement) ? paiement : methodes[0],
            lines: lignes.map((l) => ({ product_id: l.article.id, quantity: l.quantite })),
          },
        })
      ).data,
    onSuccess: (commande) => {
      vider()
      navigate('/commande/confirmee', { state: { commande, phone: tel }, replace: true })
    },
  })

  if (lignes.length === 0 && !commander.isPending && !commander.isSuccess) return <Navigate to="/panier" replace />

  function envoyer(e: FormEvent) {
    e.preventDefault()
    commander.mutate()
  }

  return (
    <form onSubmit={envoyer} className="grid gap-6 lg:grid-cols-[1fr_380px]">
      <div className="space-y-5">
        <h1 className="text-2xl font-bold">Finaliser la commande</h1>

        <Card className="space-y-4 p-5">
          <h2 className="font-semibold">Vos coordonnées</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nom complet *" htmlFor="c-nom"><Input id="c-nom" value={nom} onChange={(e) => setNom(e.target.value)} required minLength={2} autoComplete="name" /></Field>
            <Field label="Téléphone *" htmlFor="c-tel" hint="Nous vous appelons pour confirmer la commande."><Input id="c-tel" value={tel} onChange={(e) => setTel(e.target.value)} required inputMode="tel" autoComplete="tel" placeholder="06 12 34 56 78" /></Field>
            <Field label="E-mail (facultatif)" htmlFor="c-email"><Input id="c-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" /></Field>
            <Field label="Ville *" htmlFor="c-ville"><Input id="c-ville" value={ville} onChange={(e) => setVille(e.target.value)} required autoComplete="address-level2" /></Field>
            <div className="sm:col-span-2">
              <Field label="Adresse de livraison *" htmlFor="c-adresse"><Input id="c-adresse" value={adresse} onChange={(e) => setAdresse(e.target.value)} required minLength={5} autoComplete="street-address" /></Field>
            </div>
            <div className="sm:col-span-2">
              <Field label="Remarque (facultatif)" htmlFor="c-note"><Input id="c-note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Horaires, point de repère…" /></Field>
            </div>
          </div>
        </Card>

        <Card className="space-y-3 p-5">
          <h2 className="font-semibold">Mode de paiement</h2>
          {methodes.includes('cod') ? (
            <label className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 ${paiement === 'cod' ? 'border-brand bg-brand-soft/40' : 'border-line'}`}>
              <input type="radio" className="mt-1 accent-brand" checked={paiement === 'cod'} onChange={() => setPaiement('cod')} />
              <span><span className="block font-medium">Paiement à la livraison</span><span className="text-sm text-muted">Vous réglez en espèces au livreur.</span></span>
            </label>
          ) : null}
          {methodes.includes('transfer') ? (
            <label className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 ${paiement === 'transfer' ? 'border-brand bg-brand-soft/40' : 'border-line'}`}>
              <input type="radio" className="mt-1 accent-brand" checked={paiement === 'transfer'} onChange={() => setPaiement('transfer')} />
              <span><span className="block font-medium">Virement bancaire</span><span className="text-sm text-muted">Les coordonnées bancaires s’affichent après la commande ; l’envoi part à réception.</span></span>
            </label>
          ) : null}
        </Card>

        {commander.isError ? <Erreur message={messageErreur(commander.error, 'La commande n’a pas pu être enregistrée.')} /> : null}
      </div>

      <Card className="h-fit p-5">
        <h2 className="font-semibold">Votre commande</h2>
        <ul className="mt-3 divide-y divide-line text-sm">
          {lignes.map(({ article, quantite }) => (
            <li key={article.id} className="flex justify-between gap-3 py-2">
              <span className="clamp-2 text-muted">{quantite} × {article.name}</span>
              <span className="shrink-0">{dh(article.price * quantite)}</span>
            </li>
          ))}
        </ul>
        <dl className="mt-3 space-y-2 border-t border-line pt-3 text-sm">
          <div className="flex justify-between"><dt className="text-muted">Sous-total</dt><dd>{dh(sousTotal)}</dd></div>
          <div className="flex justify-between"><dt className="text-muted">Livraison</dt><dd>{port === 0 ? 'Offerte' : dh(port)}</dd></div>
          <div className="flex justify-between text-base font-bold"><dt>Total</dt><dd>{dh(sousTotal + port)}</dd></div>
        </dl>
        <Button type="submit" className="mt-5 h-11 w-full" disabled={commander.isPending}>
          {commander.isPending ? 'Enregistrement…' : 'Confirmer la commande'}
        </Button>
        <p className="mt-3 text-center text-xs text-faint">Les prix et le stock sont vérifiés à l’enregistrement.</p>
      </Card>
    </form>
  )
}
