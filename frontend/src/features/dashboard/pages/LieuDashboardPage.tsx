import { useQuery } from '@tanstack/react-query'
import { AlertTriangle, Boxes, HandCoins, Receipt, TrendingUp, Wallet } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { api } from '@/lib/api'
import { formatCurrency, formatNumber } from '@/lib/utils'
import { ChartCard } from '../components/ChartCard'
import { RankedBarChart } from '../components/RankedBarChart'
import { SalesTrendChart } from '../components/SalesTrendChart'
import { StatTile } from '../components/StatTile'

interface Periode {
  count: number
  revenue: number
  collected: number
  on_credit: number
}

interface LieuOverview {
  warehouse: { id?: number; code: string; name: string } | null
  today: Periode
  month: Periode
  stock: { value: number; units: number; references: number; below_min: number; out_of_stock: number }
  receivables: { total: number; customers: number; over_limit: number }
  expenses_month: number
  expenses_today: number
  today_sales: {
    id: number
    reference: string
    customer: string | null
    total: number
    paid_amount: number
    payment_status: string
    time: string | null
  }[]
  today_expenses: { id: number; label: string; category: string | null; amount: number; status: string }[]
  cash: {
    session_open: boolean
    opened_at: string | null
    opening: number
    cash_in: number
    cash_expenses: number
    remitted: number
    expected: number
    pending_remittances: number
  } | null
  top_products: { name: string; quantity: number; revenue: number }[]
  daily: { date: string; label: string; revenue: number }[]
  pending: {
    transfer_requests: number
    incoming_transfers: number
    draft_inventories: number
    unpaid_sales: number
  }
}

/**
 * Tableau de bord d'un responsable de lieu.
 *
 * Jusqu'ici, un responsable qui ouvrait le site tombait sur une page
 * « Bienvenue » vide : le tableau de bord n'existait que pour la direction,
 * en consolidé. Celui-ci reprend les chiffres de l'accueil mobile — le meme
 * point d'acces /me/overview —, tous cadres sur SON lieu : il ne voit jamais
 * les chiffres d'un autre.
 */
export function LieuDashboardPage() {
  const { data, isLoading, isError } = useQuery<LieuOverview>({
    queryKey: ['me-overview'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: LieuOverview }>('/me/overview')
      return r.data
    },
    // Les ventes tombent toute la journee : on se tient a jour sans recharger.
    refetchInterval: 120_000,
  })

  if (isLoading) return <p className="text-sm text-muted">Chargement du tableau de bord…</p>
  if (isError || !data) {
    return (
      <p className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
        Le tableau de bord n’a pas pu se charger. Rechargez la page.
      </p>
    )
  }

  const { today, month, stock, receivables, cash, pending } = data
  const aTraiter =
    pending.incoming_transfers + pending.transfer_requests + pending.draft_inventories + pending.unpaid_sales
  // Resultat du mois hors achats : ce qui est vendu, moins ce qui a ete depense.
  const netMois = month.revenue - data.expenses_month

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">
          {data.warehouse ? `${data.warehouse.code} · ${data.warehouse.name}` : 'Mon lieu'}
        </h1>
        <p className="text-sm text-muted">
          Les chiffres de votre lieu uniquement — aujourd’hui et depuis le début du mois.
        </p>
      </div>

      {/* ── Aujourd'hui ─────────────────────────────────────────────────── */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatTile
          label="Ventes du jour"
          value={today.revenue}
          currency
          icon={TrendingUp}
          tone="ok"
          hint={`${formatNumber(today.count)} vente(s) · encaissé ${formatCurrency(today.collected)}`}
        />
        <StatTile
          label="Reste à encaisser (jour)"
          value={today.on_credit}
          currency
          icon={HandCoins}
          tone={today.on_credit > 0 ? 'warn' : 'navy'}
          hint="Vendu à crédit aujourd’hui"
        />
        <StatTile
          label="Charges du jour"
          value={data.expenses_today}
          currency
          icon={Receipt}
          tone="bad"
          hint={`${data.today_expenses.length} charge(s) saisie(s)`}
        />
        <StatTile
          label="Caisse attendue"
          value={cash?.expected ?? 0}
          currency
          icon={Wallet}
          tone="navy"
          hint={
            cash
              ? cash.session_open
                ? 'Session ouverte'
                : 'Aucune session ouverte'
              : 'Pas de caisse pour ce lieu'
          }
        />
      </div>

      {/* ── Le mois ─────────────────────────────────────────────────────── */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatTile
          label="Ventes du mois"
          value={month.revenue}
          currency
          icon={TrendingUp}
          tone="ok"
          hint={`${formatNumber(month.count)} vente(s)`}
        />
        <StatTile
          label="Charges du mois"
          value={data.expenses_month}
          currency
          icon={Receipt}
          tone="bad"
          hint={`Ventes − charges : ${formatCurrency(netMois)}`}
        />
        <StatTile
          label="Crédits clients"
          value={receivables.total}
          currency
          icon={HandCoins}
          tone={receivables.over_limit > 0 ? 'bad' : 'warn'}
          hint={`${formatNumber(receivables.customers)} client(s) débiteur(s)${
            receivables.over_limit > 0 ? ` · ${receivables.over_limit} hors plafond` : ''
          }`}
        />
        <StatTile
          label="Valeur du stock"
          value={stock.value}
          currency
          icon={Boxes}
          tone="navy"
          hint={`${formatNumber(stock.units)} unités · ${formatNumber(stock.references)} articles`}
        />
      </div>

      {/* ── À traiter ───────────────────────────────────────────────────── */}
      {aTraiter > 0 || stock.below_min > 0 || stock.out_of_stock > 0 ? (
        <Card>
          <CardHeader title="À traiter" />
          <CardBody className="flex flex-wrap gap-3">
            {pending.incoming_transfers > 0 ? (
              <Link to="/transferts">
                <Badge tone="sky">{pending.incoming_transfers} transfert(s) à réceptionner</Badge>
              </Link>
            ) : null}
            {pending.transfer_requests > 0 ? (
              <Link to="/transferts">
                <Badge tone="warn">{pending.transfer_requests} demande(s) de stock à traiter</Badge>
              </Link>
            ) : null}
            {pending.draft_inventories > 0 ? (
              <Link to="/inventaire">
                <Badge tone="warn">{pending.draft_inventories} inventaire(s) en brouillon</Badge>
              </Link>
            ) : null}
            {pending.unpaid_sales > 0 ? (
              <Link to="/credits-clients">
                <Badge tone="bad">{pending.unpaid_sales} vente(s) non soldée(s)</Badge>
              </Link>
            ) : null}
            {stock.below_min > 0 ? (
              <Link to="/stock">
                <Badge tone="warn">
                  <AlertTriangle className="mr-1 inline h-3 w-3" />
                  {stock.below_min} article(s) sous le seuil
                </Badge>
              </Link>
            ) : null}
            {stock.out_of_stock > 0 ? (
              <Link to="/stock">
                <Badge tone="bad">{stock.out_of_stock} article(s) en rupture</Badge>
              </Link>
            ) : null}
          </CardBody>
        </Card>
      ) : null}

      {/* ── Courbe et meilleures ventes ─────────────────────────────────── */}
      <div className="grid gap-4 lg:grid-cols-2">
        <ChartCard
          title="Ventes des 14 derniers jours"
          hint="Chiffre d’affaires confirmé, jour par jour."
          isEmpty={data.daily.every((d) => d.revenue === 0)}
          emptyLabel="Aucune vente sur les 14 derniers jours."
        >
          <SalesTrendChart data={data.daily.map((d) => ({ ...d, count: 0 }))} />
        </ChartCard>
        <ChartCard
          title="Meilleures ventes du mois"
          hint="Par chiffre d’affaires."
          isEmpty={data.top_products.length === 0}
          emptyLabel="Aucune vente ce mois-ci."
        >
          <RankedBarChart
            rows={data.top_products.map((p) => ({
              name: p.name,
              value: p.revenue,
              detail: `${formatNumber(p.quantity)} vendu(s)`,
            }))}
          />
        </ChartCard>
      </div>

      {/* ── La journée en détail ───────────────────────────────────────── */}
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Ventes du jour" hint={`${data.today_sales.length} vente(s)`} />
          <CardBody className="p-0">
            {data.today_sales.length === 0 ? (
              <p className="p-5 text-sm text-muted">Aucune vente aujourd’hui.</p>
            ) : (
              <table className="w-full text-sm">
                <tbody>
                  {data.today_sales.map((v) => (
                    <tr key={v.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-2 text-faint">{v.time ?? '—'}</td>
                      <td className="mono px-2 py-2 text-muted">{v.reference}</td>
                      <td className="px-2 py-2 text-ink">{v.customer ?? 'Comptoir'}</td>
                      <td className="tabular px-5 py-2 text-right text-ink">{formatCurrency(v.total)}</td>
                      <td className="px-5 py-2 text-right">
                        {v.payment_status === 'paid' ? (
                          <Badge tone="ok">Payé</Badge>
                        ) : v.payment_status === 'partial' ? (
                          <Badge tone="warn">Partiel</Badge>
                        ) : (
                          <Badge tone="bad">Non payé</Badge>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Charges du jour" hint={formatCurrency(data.expenses_today)} />
          <CardBody className="p-0">
            {data.today_expenses.length === 0 ? (
              <p className="p-5 text-sm text-muted">
                Aucune charge aujourd’hui.{' '}
                <Link to="/charges" className="text-sky hover:underline">
                  Saisir une charge
                </Link>
              </p>
            ) : (
              <table className="w-full text-sm">
                <tbody>
                  {data.today_expenses.map((c) => (
                    <tr key={c.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-2 text-ink">{c.label}</td>
                      <td className="px-2 py-2 text-muted">{c.category ?? '—'}</td>
                      <td className="tabular px-5 py-2 text-right text-ink">{formatCurrency(c.amount)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
