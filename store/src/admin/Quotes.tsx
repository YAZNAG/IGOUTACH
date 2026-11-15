import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge, Button, Card, Input, Select, Spinner } from '../components/ui'
import { api, dateHeure, dh } from '../lib/api'
import { STATUTS_DEVIS, type Pagine } from '../lib/types'

export interface DevisResume {
  id: number
  number: string
  status: string
  customer_name: string
  customer_phone: string
  customer_email: string | null
  company: string | null
  city: string
  subtotal: number
  shipping_fee: number
  total: number
  lines_count: number
  valid_until: string | null
  warehouse: string | null
  warehouse_id: number | null
  order_number: string | null
  sale_reference: string | null
  created_at: string
  quoted_at: string | null
  answered_at: string | null
}

/**
 * Les devis de la boutique. Les demandes sans réponse remontent en premier
 * par l'ordre naturel (les plus récentes d'abord) et se repèrent à leur
 * pastille : c'est le seul endroit où la boutique est en retard sur le
 * client.
 */
export function AdminQuotes() {
  const [statut, setStatut] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery<Pagine<DevisResume>>({
    queryKey: ['store-admin-quotes', { statut, q, page }],
    queryFn: () => api<Pagine<DevisResume>>('/v1/store-admin/quotes', { auth: true, params: { status: statut, q, page } }),
    refetchInterval: 60_000,
  })

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-bold">Devis</h1>
        <p className="text-sm text-muted">{data ? `${data.meta.total} devis` : ''}</p>
      </div>

      <Card className="grid gap-3 p-4 sm:grid-cols-2">
        <Input placeholder="N°, client, société, téléphone, ville…" value={q} onChange={(e) => { setQ(e.target.value); setPage(1) }} />
        <Select value={statut} onChange={(e) => { setStatut(e.target.value); setPage(1) }}>
          <option value="">Tous les états</option>
          {Object.entries(STATUTS_DEVIS).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
        </Select>
      </Card>

      <Card className="overflow-x-auto">
        {isLoading ? <Spinner /> : (
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left text-xs text-muted">
                <th className="px-4 py-3">N°</th>
                <th className="px-4 py-3">Client</th>
                <th className="px-4 py-3">Lignes</th>
                <th className="px-4 py-3">État</th>
                <th className="px-4 py-3">Validité</th>
                <th className="px-4 py-3 text-right">Total</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody>
              {(data?.data ?? []).map((d) => {
                const etat = STATUTS_DEVIS[d.status] ?? { label: d.status, tone: 'neutral' as const }
                return (
                  <tr key={d.id} className="border-t border-line hover:bg-paper">
                    <td className="px-4 py-3">
                      <span className="font-mono font-medium">{d.number}</span>
                      <p className="text-xs text-faint">{dateHeure(d.created_at)}</p>
                    </td>
                    <td className="px-4 py-3">
                      <span className="font-medium">{d.company || d.customer_name}</span>
                      <p className="text-xs text-faint">{d.customer_phone} · {d.city}</p>
                    </td>
                    <td className="px-4 py-3 text-muted">{d.lines_count}</td>
                    <td className="px-4 py-3">
                      <Badge tone={etat.tone}>{etat.label}</Badge>
                      {d.order_number ? <p className="mt-1 font-mono text-xs text-ok">{d.order_number}</p> : null}
                    </td>
                    <td className="px-4 py-3 text-xs text-muted">
                      {d.valid_until ? new Date(d.valid_until).toLocaleDateString('fr-FR') : '—'}
                    </td>
                    <td className="px-4 py-3 text-right font-medium">{d.total > 0 ? dh(d.total) : '—'}</td>
                    <td className="px-4 py-3 text-right">
                      <Link to={`/admin/devis/${d.id}`}>
                        <Button variant="outline" className="h-8 px-3 text-xs">
                          {d.status === 'requested' ? 'Chiffrer' : 'Ouvrir'}
                        </Button>
                      </Link>
                    </td>
                  </tr>
                )
              })}
              {(data?.data ?? []).length === 0 && !isLoading ? (
                <tr><td colSpan={7} className="px-4 py-10 text-center text-sm text-muted">Aucun devis.</td></tr>
              ) : null}
            </tbody>
          </table>
        )}
      </Card>

      {data && data.meta.last_page > 1 ? (
        <div className="flex items-center justify-center gap-3">
          <Button variant="outline" className="h-9" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <span className="text-sm text-muted">Page {data.meta.current_page} / {data.meta.last_page}</span>
          <Button variant="outline" className="h-9" disabled={page >= data.meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}
