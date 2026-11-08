import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge, Button, Card, Input, Select, Spinner } from '../components/ui'
import { api, dateHeure, dh } from '../lib/api'
import { ETATS_PAIEMENT, PAIEMENTS, STATUTS, type Pagine } from '../lib/types'

export interface CommandeResume {
  id: number
  number: string
  status: string
  customer_name: string
  customer_phone: string
  city: string
  payment_method: string
  payment_status: string
  subtotal: number
  shipping_fee: number
  total: number
  paid_amount: number
  lines_count: number
  warehouse: string | null
  warehouse_id: number | null
  sale_reference: string | null
  created_at: string
}

export function AdminOrders() {
  const [statut, setStatut] = useState('')
  const [paiement, setPaiement] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery<Pagine<CommandeResume>>({
    queryKey: ['store-admin-orders', { statut, paiement, q, page }],
    queryFn: () => api<Pagine<CommandeResume>>('/v1/store-admin/orders', { auth: true, params: { status: statut, payment_status: paiement, q, page } }),
    refetchInterval: 60_000,
  })

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-bold">Commandes</h1>
        <p className="text-sm text-muted">{data ? `${data.meta.total} commande(s)` : ''}</p>
      </div>

      <Card className="grid gap-3 p-4 sm:grid-cols-3">
        <Input placeholder="N°, client, téléphone, ville…" value={q} onChange={(e) => { setQ(e.target.value); setPage(1) }} />
        <Select value={statut} onChange={(e) => { setStatut(e.target.value); setPage(1) }}>
          <option value="">Tous les états</option>
          {Object.entries(STATUTS).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
        </Select>
        <Select value={paiement} onChange={(e) => { setPaiement(e.target.value); setPage(1) }}>
          <option value="">Tous les paiements</option>
          {Object.entries(ETATS_PAIEMENT).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
        </Select>
      </Card>

      <Card className="overflow-x-auto">
        {isLoading ? <Spinner /> : (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-muted">
                <th className="px-4 py-3">N°</th><th className="px-4 py-3">Client</th><th className="px-4 py-3">Articles</th><th className="px-4 py-3">Paiement</th><th className="px-4 py-3">État</th><th className="px-4 py-3">Lieu</th><th className="px-4 py-3 text-right">Total</th><th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {(data?.data ?? []).map((c) => (
                <tr key={c.id} className="border-t border-line hover:bg-paper">
                  <td className="px-4 py-3"><span className="font-mono font-medium">{c.number}</span><p className="text-xs text-faint">{dateHeure(c.created_at)}</p></td>
                  <td className="px-4 py-3">{c.customer_name}<p className="text-xs text-faint">{c.customer_phone} · {c.city}</p></td>
                  <td className="px-4 py-3 text-muted">{c.lines_count}</td>
                  <td className="px-4 py-3"><p className="text-xs text-muted">{PAIEMENTS[c.payment_method]}</p><Badge tone={ETATS_PAIEMENT[c.payment_status]?.tone ?? 'warn'}>{ETATS_PAIEMENT[c.payment_status]?.label}</Badge></td>
                  <td className="px-4 py-3"><Badge tone={STATUTS[c.status]?.tone ?? 'neutral'}>{STATUTS[c.status]?.label}</Badge></td>
                  <td className="px-4 py-3 font-mono text-xs text-muted">{c.warehouse ?? '—'}{c.sale_reference ? <p className="text-faint">{c.sale_reference}</p> : null}</td>
                  <td className="px-4 py-3 text-right font-medium">{dh(c.total)}</td>
                  <td className="px-4 py-3 text-right"><Link to={`/admin/commandes/${c.id}`}><Button variant="outline" className="h-8 px-3">Ouvrir</Button></Link></td>
                </tr>
              ))}
              {data && data.data.length === 0 ? <tr><td colSpan={8} className="px-4 py-10 text-center text-muted">Aucune commande.</td></tr> : null}
            </tbody>
          </table>
        )}
      </Card>

      {data && data.meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {data.meta.current_page} / {data.meta.last_page}</span>
          <Button variant="outline" className="h-8 px-3" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" className="h-8 px-3" disabled={page >= data.meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}
