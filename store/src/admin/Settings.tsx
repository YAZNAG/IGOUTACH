import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useEffect, useState, type FormEvent } from 'react'
import { Button, Card, Erreur, Field, Input, Spinner, Succes } from '../components/ui'
import { api, messageErreur } from '../lib/api'

type Reglages = Record<string, string>

const CHAMPS: { cle: string; label: string; hint?: string; long?: boolean }[] = [
  { cle: 'shop_name', label: 'Nom de la boutique' },
  { cle: 'phone', label: 'Téléphone affiché' },
  { cle: 'whatsapp', label: 'WhatsApp (indicatif sans +, ex. 212661341783)' },
  { cle: 'email', label: 'E-mail' },
  { cle: 'address', label: 'Adresse' },
  { cle: 'announcement', label: 'Bandeau en haut du site' },
  { cle: 'hero_title', label: 'Titre de la page d’accueil' },
  { cle: 'hero_subtitle', label: 'Sous-titre de la page d’accueil', long: true },
  { cle: 'shipping_fee', label: 'Frais de livraison (DH)' },
  { cle: 'free_shipping_from', label: 'Livraison offerte à partir de (DH, 0 = jamais)' },
  { cle: 'bank_details', label: 'Coordonnées bancaires (affichées pour le virement)', long: true },
]

export function AdminSettings() {
  const qc = useQueryClient()
  const [form, setForm] = useState<Reglages>({})
  const [message, setMessage] = useState<string | null>(null)

  const { data, isLoading } = useQuery<Reglages>({
    queryKey: ['store-admin-settings'],
    queryFn: async () => (await api<{ data: Reglages }>('/v1/store-admin/settings', { auth: true })).data,
  })

  useEffect(() => {
    if (data) setForm(data)
  }, [data])

  const enregistrer = useMutation({
    mutationFn: () => api('/v1/store-admin/settings', { method: 'PUT', auth: true, body: form }),
    onSuccess: () => {
      setMessage('Réglages enregistrés. Le site les applique immédiatement.')
      qc.invalidateQueries({ queryKey: ['store-admin-settings'] })
      qc.invalidateQueries({ queryKey: ['store-settings'] })
    },
  })

  if (isLoading) return <Spinner />

  function envoyer(e: FormEvent) {
    e.preventDefault()
    setMessage(null)
    enregistrer.mutate()
  }

  const set = (cle: string, v: string) => setForm((f) => ({ ...f, [cle]: v }))

  return (
    <form onSubmit={envoyer} className="max-w-3xl space-y-5">
      <div>
        <h1 className="text-xl font-bold">Réglages de la boutique</h1>
        <p className="text-sm text-muted">Coordonnées, textes d’accueil, livraison et paiement.</p>
      </div>

      <Card className="grid gap-4 p-5 sm:grid-cols-2">
        {CHAMPS.map((c) => (
          <div key={c.cle} className={c.long ? 'sm:col-span-2' : ''}>
            <Field label={c.label} htmlFor={`r-${c.cle}`} hint={c.hint}>
              {c.long ? (
                <textarea id={`r-${c.cle}`} value={form[c.cle] ?? ''} onChange={(e) => set(c.cle, e.target.value)} rows={3} className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" />
              ) : (
                <Input id={`r-${c.cle}`} value={form[c.cle] ?? ''} onChange={(e) => set(c.cle, e.target.value)} />
              )}
            </Field>
          </div>
        ))}
        <div className="sm:col-span-2">
          <p className="mb-2 text-xs font-medium text-muted">Modes de paiement proposés</p>
          <div className="flex flex-wrap gap-4 text-sm">
            <label className="inline-flex items-center gap-2"><input type="checkbox" className="h-4 w-4 accent-brand" checked={form.payment_cod === '1'} onChange={(e) => set('payment_cod', e.target.checked ? '1' : '0')} /> Paiement à la livraison</label>
            <label className="inline-flex items-center gap-2"><input type="checkbox" className="h-4 w-4 accent-brand" checked={form.payment_transfer === '1'} onChange={(e) => set('payment_transfer', e.target.checked ? '1' : '0')} /> Virement bancaire</label>
          </div>
        </div>
      </Card>

      {message ? <Succes message={message} /> : null}
      {enregistrer.isError ? <Erreur message={messageErreur(enregistrer.error, 'Enregistrement impossible.')} /> : null}

      <Button type="submit" disabled={enregistrer.isPending}>{enregistrer.isPending ? 'Enregistrement…' : 'Enregistrer'}</Button>
    </form>
  )
}
