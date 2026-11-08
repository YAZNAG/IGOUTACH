import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Phone, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Badge, Button, Card, Erreur, Field, Input, Select, Spinner } from '../components/ui'
import { api, dateHeure, dh, messageErreur } from '../lib/api'
import { ETATS_PAIEMENT, PAIEMENTS, STATUTS } from '../lib/types'
import type { CommandeResume } from './Orders'

interface Commande extends CommandeResume {
  customer_email: string | null
  address: string
  note: string | null
  cancel_reason: string | null
  handled_by: string | null
  confirmed_at: string | null
  shipped_at: string | null
  delivered_at: string | null
  cancelled_at: string | null
  lines: { id: number; product_id: number; sku: string | null; name: string; quantity: number; unit_price: number; line_total: number }[]
  payments: { id: number; method: string; amount: number; reference: string | null; received_at: string | null; note: string | null; received_by: string | null }[]
}

interface Lieu {
  id: number
  code: string
  name: string
}

/** Les transitions proposées depuis chaque état. */
const SUIVANTS: Record<string, string[]> = {
  new: ['confirmed', 'cancelled'],
  confirmed: ['preparing', 'shipped', 'cancelled'],
  preparing: ['shipped', 'cancelled'],
  shipped: ['delivered', 'cancelled'],
  delivered: [],
  cancelled: [],
}

export function AdminOrderDetail() {
  const { id } = useParams<{ id: string }>()
  const qc = useQueryClient()
  const [lieu, setLieu] = useState<number>(0)
  const [motif, setMotif] = useState('')
  const [montant, setMontant] = useState('')
  const [methode, setMethode] = useState('cod')
  const [reference, setReference] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)

  const { data: c, isLoading } = useQuery<Commande>({
    queryKey: ['store-admin-order', id],
    queryFn: async () => (await api<{ data: Commande }>(`/v1/store-admin/orders/${id}`, { auth: true })).data,
  })
  const { data: lieux = [] } = useQuery<Lieu[]>({
    queryKey: ['store-admin-warehouses'],
    queryFn: async () => (await api<{ data: Lieu[] }>('/v1/warehouses', { auth: true })).data,
    staleTime: 10 * 60_000,
  })

  function rafraichir() {
    qc.invalidateQueries({ queryKey: ['store-admin-order', id] })
    qc.invalidateQueries({ queryKey: ['store-admin-orders'] })
    qc.invalidateQueries({ queryKey: ['store-admin-dashboard'] })
  }

  const changer = useMutation({
    mutationFn: async (statut: string) =>
      api(`/v1/store-admin/orders/${id}/status`, {
        method: 'PATCH', auth: true,
        body: { status: statut, warehouse_id: lieu || c?.warehouse_id || null, reason: statut === 'cancelled' ? motif || null : null },
      }),
    onMutate: () => setErreur(null),
    onSuccess: rafraichir,
    onError: (e) => setErreur(messageErreur(e, 'Changement d’état impossible.')),
  })

  const encaisser = useMutation({
    mutationFn: async () =>
      api(`/v1/store-admin/orders/${id}/payments`, {
        method: 'POST', auth: true,
        body: { method: methode, amount: Number(montant.replace(',', '.')), reference: reference || null, received_at: new Date().toISOString().slice(0, 10) },
      }),
    onMutate: () => setErreur(null),
    onSuccess: () => { setMontant(''); setReference(''); rafraichir() },
    onError: (e) => setErreur(messageErreur(e, 'Règlement impossible.')),
  })

  const supprimerPaiement = useMutation({
    mutationFn: async (pid: number) => api(`/v1/store-admin/orders/${id}/payments/${pid}`, { method: 'DELETE', auth: true }),
    onSuccess: rafraichir,
  })

  if (isLoading || !c) return <Spinner />

  const reste = Math.max(0, c.total - c.paid_amount)
  const lieuChoisi = lieu || c.warehouse_id || 0
  const besoinLieu = (statut: string) => statut === 'delivered' && !lieuChoisi

  return (
    <div className="space-y-5">
      <Link to="/admin/commandes" className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><ArrowLeft className="h-4 w-4" /> Commandes</Link>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-mono text-2xl font-bold">{c.number}</h1>
          <p className="text-sm text-muted">Passée le {dateHeure(c.created_at)}{c.handled_by ? ` · traitée par ${c.handled_by}` : ''}</p>
        </div>
        <div className="flex gap-2">
          <Badge tone={STATUTS[c.status]?.tone ?? 'neutral'}>{STATUTS[c.status]?.label}</Badge>
          <Badge tone={ETATS_PAIEMENT[c.payment_status]?.tone ?? 'warn'}>{ETATS_PAIEMENT[c.payment_status]?.label}</Badge>
        </div>
      </div>

      {erreur ? <Erreur message={erreur} /> : null}

      <div className="grid gap-5 lg:grid-cols-[1fr_360px]">
        <div className="space-y-5">
          <Card className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead><tr className="text-left text-xs text-muted"><th className="px-4 py-3">Article</th><th className="px-4 py-3 text-right">Qté</th><th className="px-4 py-3 text-right">P.U.</th><th className="px-4 py-3 text-right">Total</th></tr></thead>
              <tbody>
                {c.lines.map((l) => (
                  <tr key={l.id} className="border-t border-line">
                    <td className="px-4 py-2">{l.name}<p className="font-mono text-xs text-faint">{l.sku}</p></td>
                    <td className="px-4 py-2 text-right">{l.quantity}</td>
                    <td className="px-4 py-2 text-right">{dh(l.unit_price)}</td>
                    <td className="px-4 py-2 text-right font-medium">{dh(l.line_total)}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="border-t border-line text-sm">
                <tr><td colSpan={3} className="px-4 py-1.5 text-right text-muted">Sous-total</td><td className="px-4 py-1.5 text-right">{dh(c.subtotal)}</td></tr>
                <tr><td colSpan={3} className="px-4 py-1.5 text-right text-muted">Livraison</td><td className="px-4 py-1.5 text-right">{dh(c.shipping_fee)}</td></tr>
                <tr className="font-bold"><td colSpan={3} className="px-4 py-2 text-right">Total</td><td className="px-4 py-2 text-right">{dh(c.total)}</td></tr>
              </tfoot>
            </table>
          </Card>

          <Card className="p-5">
            <h2 className="font-semibold">Règlements</h2>
            <p className="text-sm text-muted">{PAIEMENTS[c.payment_method]} · payé {dh(c.paid_amount)} · reste {dh(reste)}</p>
            <ul className="mt-3 divide-y divide-line text-sm">
              {c.payments.map((p) => (
                <li key={p.id} className="flex items-center justify-between gap-3 py-2">
                  <span>{dh(p.amount)} <span className="text-muted">· {PAIEMENTS[p.method] ?? p.method}{p.reference ? ` · ${p.reference}` : ''} · {p.received_at}{p.received_by ? ` · ${p.received_by}` : ''}</span></span>
                  <button type="button" onClick={() => supprimerPaiement.mutate(p.id)} className="text-faint hover:text-bad" aria-label="Supprimer ce règlement"><Trash2 className="h-4 w-4" /></button>
                </li>
              ))}
              {c.payments.length === 0 ? <li className="py-2 text-muted">Aucun règlement enregistré.</li> : null}
            </ul>
            {c.status !== 'cancelled' && reste > 0 ? (
              <form className="mt-4 grid gap-3 sm:grid-cols-[140px_1fr_1fr_auto] sm:items-end" onSubmit={(e) => { e.preventDefault(); encaisser.mutate() }}>
                <Field label="Montant (DH)" htmlFor="p-montant"><Input id="p-montant" value={montant} onChange={(e) => setMontant(e.target.value)} placeholder={String(reste)} inputMode="decimal" required /></Field>
                <Field label="Mode" htmlFor="p-mode">
                  <Select id="p-mode" value={methode} onChange={(e) => setMethode(e.target.value)}>
                    <option value="cod">À la livraison (espèces)</option><option value="transfer">Virement</option><option value="cash">Espèces au magasin</option><option value="card">Carte</option><option value="other">Autre</option>
                  </Select>
                </Field>
                <Field label="Référence" htmlFor="p-ref"><Input id="p-ref" value={reference} onChange={(e) => setReference(e.target.value)} placeholder="N° virement, reçu…" /></Field>
                <Button type="submit" disabled={encaisser.isPending}>Encaisser</Button>
              </form>
            ) : null}
          </Card>
        </div>

        <div className="space-y-5">
          <Card className="p-5">
            <h2 className="font-semibold">Client</h2>
            <p className="mt-2 font-medium">{c.customer_name}</p>
            <p className="text-sm text-muted"><a href={`tel:${c.customer_phone}`} className="inline-flex items-center gap-1 hover:text-ink"><Phone className="h-3.5 w-3.5" />{c.customer_phone}</a></p>
            {c.customer_email ? <p className="text-sm text-muted">{c.customer_email}</p> : null}
            <p className="mt-2 text-sm">{c.address}</p>
            <p className="text-sm text-muted">{c.city}</p>
            {c.note ? <p className="mt-2 rounded-lg bg-paper p-2 text-sm text-muted">« {c.note} »</p> : null}
          </Card>

          <Card className="space-y-3 p-5">
            <h2 className="font-semibold">Traitement</h2>
            <Field label="Lieu qui expédie" htmlFor="t-lieu" hint="À la livraison, une vente est créée dans ce lieu : le stock en sort.">
              <Select id="t-lieu" value={lieuChoisi || ''} onChange={(e) => setLieu(Number(e.target.value))} disabled={c.status === 'delivered' || c.status === 'cancelled'}>
                <option value="">— Choisir —</option>
                {lieux.map((l) => <option key={l.id} value={l.id}>{l.code} · {l.name}</option>)}
              </Select>
            </Field>
            {SUIVANTS[c.status]?.includes('cancelled') ? (
              <Field label="Motif d’annulation (si annulation)" htmlFor="t-motif"><Input id="t-motif" value={motif} onChange={(e) => setMotif(e.target.value)} /></Field>
            ) : null}
            <div className="flex flex-wrap gap-2">
              {(SUIVANTS[c.status] ?? []).map((s) => (
                <Button
                  key={s}
                  variant={s === 'cancelled' ? 'danger' : s === 'delivered' ? 'primary' : 'outline'}
                  disabled={changer.isPending || besoinLieu(s)}
                  title={besoinLieu(s) ? 'Choisissez le lieu qui expédie' : undefined}
                  onClick={() => changer.mutate(s)}
                >
                  {s === 'confirmed' ? 'Confirmer' : s === 'preparing' ? 'En préparation' : s === 'shipped' ? 'Expédiée' : s === 'delivered' ? 'Livrée (créer la vente)' : 'Annuler'}
                </Button>
              ))}
              {c.status === 'delivered' ? <p className="text-sm text-ok">Livrée le {dateHeure(c.delivered_at)}{c.sale_reference ? ` · vente ${c.sale_reference}` : ''}</p> : null}
              {c.status === 'cancelled' ? <p className="text-sm text-bad">Annulée le {dateHeure(c.cancelled_at)}{c.cancel_reason ? ` · ${c.cancel_reason}` : ''}</p> : null}
            </div>
            <dl className="space-y-1 border-t border-line pt-3 text-xs text-muted">
              {c.confirmed_at ? <div className="flex justify-between"><dt>Confirmée</dt><dd>{dateHeure(c.confirmed_at)}</dd></div> : null}
              {c.shipped_at ? <div className="flex justify-between"><dt>Expédiée</dt><dd>{dateHeure(c.shipped_at)}</dd></div> : null}
            </dl>
          </Card>
        </div>
      </div>
    </div>
  )
}
