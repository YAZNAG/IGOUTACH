import { useMutation } from '@tanstack/react-query'
import { FileText, Minus, Plus, Trash2 } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { useReglages } from '../components/Layout'
import { Button, Card, Erreur, Field, Input } from '../components/ui'
import { api, dh, messageErreur } from '../lib/api'
import { usePanier } from '../lib/cart'
import { palierPour } from '../lib/paliers'
import type { Devis } from '../lib/types'

/**
 * Demande de devis : le panier devient une demande de prix.
 *
 * Volontairement sans adresse obligatoire ni mode de paiement — on n'engage
 * rien, on demande un prix. Le stock n'est pas contrôlé non plus : c'est
 * justement ce qu'on vient faire préciser.
 */
export function QuoteRequest() {
  const { lignes, retirer, changerQuantite, sousTotal, vider, prixUnitaire } = usePanier()
  const { data: reglages } = useReglages()
  const navigate = useNavigate()

  const [nom, setNom] = useState('')
  const [societe, setSociete] = useState('')
  const [tel, setTel] = useState('')
  const [email, setEmail] = useState('')
  const [ville, setVille] = useState('')
  const [adresse, setAdresse] = useState('')
  const [note, setNote] = useState('')

  const demander = useMutation({
    mutationFn: async () =>
      (
        await api<{ data: Devis }>('/store/quotes', {
          method: 'POST',
          body: {
            customer_name: nom,
            company: societe || null,
            customer_phone: tel,
            customer_email: email || null,
            city: ville,
            address: adresse || null,
            note: note || null,
            lines: lignes.map((l) => ({ product_id: l.article.id, quantity: l.quantite })),
          },
        })
      ).data,
    onSuccess: (devis) => {
      vider()
      navigate('/devis/envoye', { state: { devis }, replace: true })
    },
  })

  if (lignes.length === 0 && !demander.isPending && !demander.isSuccess) return <Navigate to="/panier" replace />

  function envoyer(e: FormEvent) {
    e.preventDefault()
    demander.mutate()
  }

  return (
    <form onSubmit={envoyer} className="grid gap-6 lg:grid-cols-[1fr_380px]">
      <div className="space-y-5">
        <div>
          <h1 className="text-2xl font-bold">Demande de devis</h1>
          <p className="mt-1 text-sm text-muted">
            {reglages?.quote_intro || 'Dites-nous les quantités souhaitées : nous revenons vers vous avec un prix ferme.'}
          </p>
        </div>

        <Card className="space-y-4 p-5">
          <h2 className="font-semibold">Vos coordonnées</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Nom complet *" htmlFor="d-nom"><Input id="d-nom" value={nom} onChange={(e) => setNom(e.target.value)} required minLength={2} autoComplete="name" /></Field>
            <Field label="Société (facultatif)" htmlFor="d-societe" hint="Pour établir le devis au bon nom."><Input id="d-societe" value={societe} onChange={(e) => setSociete(e.target.value)} autoComplete="organization" /></Field>
            <Field label="Téléphone *" htmlFor="d-tel"><Input id="d-tel" value={tel} onChange={(e) => setTel(e.target.value)} required inputMode="tel" autoComplete="tel" placeholder="06 12 34 56 78" /></Field>
            <Field label="E-mail (facultatif)" htmlFor="d-email"><Input id="d-email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" /></Field>
            <Field label="Ville *" htmlFor="d-ville"><Input id="d-ville" value={ville} onChange={(e) => setVille(e.target.value)} required autoComplete="address-level2" /></Field>
            <Field label="Adresse de livraison (facultatif)" htmlFor="d-adresse"><Input id="d-adresse" value={adresse} onChange={(e) => setAdresse(e.target.value)} autoComplete="street-address" /></Field>
            <div className="sm:col-span-2">
              <Field label="Votre demande (facultatif)" htmlFor="d-note" hint="Délai souhaité, usage, quantités à venir…">
                <Input id="d-note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Besoin pour un chantier début décembre…" />
              </Field>
            </div>
          </div>
        </Card>

        <Card className="p-5">
          <h2 className="font-semibold">Articles de la demande</h2>
          <ul className="mt-3 divide-y divide-line">
            {lignes.map((ligne) => {
              const { article, quantite } = ligne
              const palier = palierPour(article, quantite)
              return (
                <li key={article.id} className="flex items-center gap-3 py-3">
                  <div className="min-w-0 flex-1">
                    <Link to={`/article/${article.id}`} className="clamp-2 text-sm font-medium hover:text-brand">{article.name}</Link>
                    <p className="text-xs text-faint">
                      Réf. {article.sku} · indicatif {dh(prixUnitaire(ligne))}
                      {palier && palier.min_quantity > 1 ? ` · ${palier.label.toLowerCase()}` : ''}
                    </p>
                  </div>
                  <div className="flex h-9 shrink-0 items-center rounded-lg border border-line">
                    <button type="button" className="px-2.5 text-muted" onClick={() => changerQuantite(article.id, quantite - 1)} aria-label="Moins"><Minus className="h-4 w-4" /></button>
                    <input
                      type="number"
                      min={1}
                      max={100000}
                      value={quantite}
                      onChange={(e) => changerQuantite(article.id, Number(e.target.value) || 1)}
                      className="w-16 border-0 bg-transparent text-center text-sm focus:outline-none"
                      aria-label={`Quantité pour ${article.name}`}
                    />
                    <button type="button" className="px-2.5 text-muted" onClick={() => changerQuantite(article.id, quantite + 1)} aria-label="Plus"><Plus className="h-4 w-4" /></button>
                  </div>
                  <button type="button" onClick={() => retirer(article.id)} className="shrink-0 text-muted hover:text-bad" aria-label={`Retirer ${article.name}`}><Trash2 className="h-4 w-4" /></button>
                </li>
              )
            })}
          </ul>
        </Card>

        {demander.isError ? <Erreur message={messageErreur(demander.error, 'La demande n’a pas pu être envoyée.')} /> : null}
      </div>

      <Card className="h-fit p-5">
        <h2 className="font-semibold">Estimation</h2>
        <p className="mt-1 text-xs text-faint">Au barème public, avant remise.</p>
        <p className="mt-3 text-2xl font-bold">{dh(sousTotal)}</p>
        <p className="mt-3 text-sm text-muted">
          Ce montant n’est pas un engagement. Nous vérifions la disponibilité, calculons le prix ferme et le délai,
          puis vous recevez un devis à accepter ou à refuser en un clic.
        </p>
        <Button type="submit" className="mt-5 h-11 w-full" disabled={demander.isPending}>
          <FileText className="h-4 w-4" /> {demander.isPending ? 'Envoi…' : 'Envoyer la demande'}
        </Button>
        {reglages?.whatsapp ? (
          <a
            href={`https://wa.me/${reglages.whatsapp.replace(/\D/g, '')}`}
            target="_blank"
            rel="noreferrer"
            className="mt-3 block text-center text-xs text-muted hover:text-brand"
          >
            Ou écrivez-nous directement sur WhatsApp
          </a>
        ) : null}
      </Card>
    </form>
  )
}
