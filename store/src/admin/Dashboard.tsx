import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { Badge, Card, Spinner } from '../components/ui'
import { api, dateHeure, dh } from '../lib/api'
import { ETATS_PAIEMENT, STATUTS } from '../lib/types'
import type { CommandeResume } from './Orders'

interface TableauDeBord {
  by_status: Record<string, number>
  today: { orders: number; revenue: number }
  month: { orders: number; revenue: number; unpaid: number }
  top_products: { product_id: number; name: string; qty: number; revenue: number }[]
  catalog: { online: number; without_image: number; out_of_stock: number }
  recent_orders: CommandeResume[]
}

export function AdminDashboard() {
  const { data, isLoading } = useQuery<TableauDeBord>({
    queryKey: ['store-admin-dashboard'],
    queryFn: async () => (await api<{ data: TableauDeBord }>('/v1/store-admin/dashboard', { auth: true })).data,
    refetchInterval: 60_000,
  })

  if (isLoading || !data) return <Spinner />

  const aTraiter = (data.by_status.new ?? 0) + (data.by_status.confirmed ?? 0) + (data.by_status.preparing ?? 0)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold">Tableau de bord</h1>
        <p className="text-sm text-muted">La boutique en ligne, en un coup d’œil.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {[
          ['Nouvelles commandes', data.by_status.new ?? 0, 'à confirmer', 'text-warn'],
          ['À traiter', aTraiter, 'nouvelles, confirmées, en préparation', 'text-brand'],
          ['CA du mois (livré)', dh(data.month.revenue), `${data.month.orders} commande(s) ce mois`, 'text-ok'],
          ['Reste à encaisser', dh(data.month.unpaid), 'commandes non soldées', data.month.unpaid > 0 ? 'text-bad' : 'text-muted'],
        ].map(([l, v, h, c]) => (
          <Card key={l as string} className="p-5">
            <p className="text-xs font-medium text-muted">{l as string}</p>
            <p className={`mt-1 text-2xl font-bold ${c as string}`}>{v as string}</p>
            <p className="text-xs text-faint">{h as string}</p>
          </Card>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        <Card>
          <div className="flex items-center justify-between border-b border-line px-5 py-3">
            <h2 className="font-semibold">Dernières commandes</h2>
            <Link to="/admin/commandes" className="text-sm text-brand hover:underline">Toutes les commandes</Link>
          </div>
          <table className="w-full text-sm">
            <thead><tr className="text-left text-xs text-muted"><th className="px-5 py-2">N°</th><th className="px-5 py-2">Client</th><th className="px-5 py-2">État</th><th className="px-5 py-2 text-right">Total</th></tr></thead>
            <tbody>
              {data.recent_orders.map((c) => (
                <tr key={c.id} className="border-t border-line hover:bg-paper">
                  <td className="px-5 py-2"><Link to={`/admin/commandes/${c.id}`} className="font-mono text-brand hover:underline">{c.number}</Link><p className="text-xs text-faint">{dateHeure(c.created_at)}</p></td>
                  <td className="px-5 py-2">{c.customer_name}<p className="text-xs text-faint">{c.city}</p></td>
                  <td className="px-5 py-2"><Badge tone={STATUTS[c.status]?.tone ?? 'neutral'}>{STATUTS[c.status]?.label}</Badge> <Badge tone={ETATS_PAIEMENT[c.payment_status]?.tone ?? 'warn'}>{ETATS_PAIEMENT[c.payment_status]?.label}</Badge></td>
                  <td className="px-5 py-2 text-right font-medium">{dh(c.total)}</td>
                </tr>
              ))}
              {data.recent_orders.length === 0 ? <tr><td colSpan={4} className="px-5 py-8 text-center text-muted">Aucune commande pour l’instant.</td></tr> : null}
            </tbody>
          </table>
        </Card>

        <div className="space-y-6">
          <Card className="p-5">
            <h2 className="font-semibold">Catalogue en ligne</h2>
            <dl className="mt-3 space-y-2 text-sm">
              <div className="flex justify-between"><dt className="text-muted">Articles visibles</dt><dd className="font-medium">{data.catalog.online}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Sans photo</dt><dd className={data.catalog.without_image > 0 ? 'font-medium text-warn' : ''}>{data.catalog.without_image}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">En rupture</dt><dd className={data.catalog.out_of_stock > 0 ? 'font-medium text-bad' : ''}>{data.catalog.out_of_stock}</dd></div>
            </dl>
            <Link to="/admin/articles" className="mt-3 block text-sm text-brand hover:underline">Gérer les articles</Link>
          </Card>
          <Card className="p-5">
            <h2 className="font-semibold">Meilleures ventes (30 j)</h2>
            <ul className="mt-3 space-y-2 text-sm">
              {data.top_products.map((p) => (
                <li key={p.product_id} className="flex justify-between gap-2"><span className="clamp-2 text-muted">{p.name}</span><span className="shrink-0 font-medium">{p.qty} · {dh(p.revenue)}</span></li>
              ))}
              {data.top_products.length === 0 ? <li className="text-muted">Pas encore de vente en ligne.</li> : null}
            </ul>
          </Card>
        </div>
      </div>
    </div>
  )
}
