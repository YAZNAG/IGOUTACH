import { useQuery } from '@tanstack/react-query'
import { Coins, Download, Percent, TrendingUp, Wallet } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { ChartCard } from '@/features/dashboard/components/ChartCard'
import { RankedBarChart } from '@/features/dashboard/components/RankedBarChart'
import { StatTile } from '@/features/dashboard/components/StatTile'
import { chartColors } from '@/features/dashboard/components/chartTheme'
import { api } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { cn, formatCurrency, formatNumber } from '@/lib/utils'

interface SalesRow {
  label: string
  documents: number
  revenue: number
  collected: number
}

interface ValuationRow {
  code: string
  name: string
  units: number
  value: number
}

interface MarginRow {
  sku: string
  name: string
  quantity: number
  revenue: number
  cost: number
  margin: number
}

interface DormantRow {
  sku: string
  name: string
  quantity: number
  immobilized_value: number
}

/** Une ligne de bénéfice, quelle que soit la dimension observée. */
interface ProfitRow {
  name: string
  documents: number
  revenue: number
  cost: number
  profit: number
  margin_percent: number | null
}

interface ProfitPayload {
  from: string
  to: string
  totals: {
    documents: number
    revenue: number
    cost: number
    profit: number
    margin_percent: number | null
    revenue_without_cost: number
    products_without_cost: number
    share_without_cost: number
  }
  by_warehouse: ProfitRow[]
  by_customer: ProfitRow[]
  by_supplier: ProfitRow[]
}

function premierDuMois(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

function aujourdhui(): string {
  return new Date().toISOString().slice(0, 10)
}

/**
 * Tableau du bénéfice sur une dimension.
 *
 * Le graphique classe, le tableau chiffre. Les deux vont ensemble : la barre
 * situe d'un coup d'œil, le taux de marge dit si le premier de la liste est
 * vraiment le plus profitable — un gros chiffre d'affaires à faible marge
 * rapporte parfois moins qu'un petit bien vendu.
 */
function TableauBenefice({ rows }: { rows: ProfitRow[] }) {
  if (rows.length === 0) {
    return <p className="py-6 text-center text-sm text-muted">Aucune vente sur la période.</p>
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-line text-left text-muted">
            <th className="py-2 pr-4 font-medium">Nom</th>
            <th className="py-2 pr-4 text-right font-medium">CA</th>
            <th className="py-2 pr-4 text-right font-medium">Coût</th>
            <th className="py-2 pr-4 text-right font-medium">Bénéfice</th>
            <th className="py-2 text-right font-medium">Marge</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r) => (
            <tr key={r.name} className="border-b border-line last:border-0">
              <td className="py-2 pr-4 text-ink">{r.name}</td>
              <td className="tabular py-2 pr-4 text-right text-muted">{formatNumber(r.revenue)}</td>
              <td className="tabular py-2 pr-4 text-right text-muted">{formatNumber(r.cost)}</td>
              <td
                className={cn(
                  'tabular py-2 pr-4 text-right font-medium',
                  r.profit >= 0 ? 'text-ok' : 'text-bad',
                )}
              >
                {formatNumber(r.profit)}
              </td>
              <td className="tabular py-2 text-right text-muted">
                {r.margin_percent === null ? '—' : `${r.margin_percent} %`}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

/** Un bloc « graphique + tableau » pour une dimension du bénéfice. */
function BlocBenefice({
  titre,
  hint,
  rows,
  couleur,
}: {
  titre: string
  hint: string
  rows: ProfitRow[]
  couleur: string
}) {
  return (
    <div className="space-y-4">
      <ChartCard
        title={titre}
        hint={hint}
        isEmpty={rows.length === 0}
        emptyLabel="Aucune vente sur la période."
        fitContent
      >
        <RankedBarChart
          color={couleur}
          valueLabel="Bénéfice"
          rows={rows.map((r) => ({
            name: r.name,
            value: r.profit,
            detail: `${formatCurrency(r.revenue)} de chiffre d'affaires`,
            note: r.margin_percent === null ? undefined : `marge ${r.margin_percent} %`,
          }))}
        />
      </ChartCard>
      <Card>
        <CardBody>
          <TableauBenefice rows={rows} />
        </CardBody>
      </Card>
    </div>
  )
}

export function ReportsPage() {
  const [from, setFrom] = useState(premierDuMois())
  const [to, setTo] = useState(aujourdhui())
  const [group, setGroup] = useState<'warehouse' | 'seller' | 'product'>('warehouse')
  const [vue, setVue] = useState<'warehouse' | 'customer' | 'supplier'>('warehouse')
  const [exporting, setExporting] = useState(false)

  const { data: profit } = useQuery<ProfitPayload>({
    queryKey: ['report-profit', from, to],
    queryFn: async () => {
      const { data } = await api.get<{ data: ProfitPayload }>('/reports/profit', {
        params: { from, to },
      })
      return data.data
    },
  })

  const { data: sales } = useQuery<{ rows: SalesRow[] }>({
    queryKey: ['report-sales', from, to, group],
    queryFn: async () => {
      const { data } = await api.get<{ data: { rows: SalesRow[] } }>('/reports/sales', {
        params: { from, to, group },
      })
      return data.data
    },
  })

  const { data: valuation } = useQuery<{ warehouses: ValuationRow[]; total_value: number }>({
    queryKey: ['report-valuation'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { warehouses: ValuationRow[]; total_value: number } }>(
        '/reports/stock-valuation',
      )
      return data.data
    },
  })

  const { data: margins } = useQuery<{ rows: MarginRow[] }>({
    queryKey: ['report-margins', from, to],
    queryFn: async () => {
      const { data } = await api.get<{ data: { rows: MarginRow[] } }>('/reports/margins', {
        params: { from, to },
      })
      return data.data
    },
  })

  const { data: dormant } = useQuery<{ rows: DormantRow[] }>({
    queryKey: ['report-dormant'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { rows: DormantRow[] } }>('/reports/dormant-products')
      return data.data
    },
  })

  async function exportSales() {
    setExporting(true)
    try {
      await downloadFile(
        `/reports/sales?from=${from}&to=${to}&group=${group}&format=xlsx`,
        `ventes-${group}-${from}-${to}.xlsx`,
      )
    } finally {
      setExporting(false)
    }
  }

  const groupLabel = group === 'warehouse' ? 'Lieu' : group === 'seller' ? 'Vendeur' : 'Article'
  const totaux = profit?.totals
  const dormantTotal = (dormant?.rows ?? []).reduce((s, r) => s + r.immobilized_value, 0)

  const vues = {
    warehouse: {
      titre: 'Bénéfice par lieu',
      hint: 'Ce que rapporte chaque dépôt, point de vente ou véhicule.',
      rows: profit?.by_warehouse ?? [],
      couleur: chartColors.brand,
    },
    customer: {
      titre: 'Bénéfice par client',
      hint: 'Les meilleurs clients, et le total des ventes de passage.',
      rows: profit?.by_customer ?? [],
      couleur: chartColors.sales,
    },
    supplier: {
      titre: 'Bénéfice par fournisseur',
      hint: 'Marge dégagée sur les articles que chacun livre.',
      rows: profit?.by_supplier ?? [],
      couleur: chartColors.purchases,
    },
  }[vue]

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold text-ink">Rapports</h1>
          <p className="text-sm text-muted">
            Ce que l'on vend, ce que cela rapporte, et ce qui dort en stock.
          </p>
        </div>
        <Button variant="outline" size="sm" onClick={exportSales} disabled={exporting}>
          <Download className="h-4 w-4" />
          {exporting ? 'Export…' : 'Exporter les ventes'}
        </Button>
      </div>

      {/* La période commande toute la page : elle vit en haut, pas enfouie
          dans la première carte. */}
      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-3">
          <Field label="Du" htmlFor="rep-from">
            <Input id="rep-from" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          </Field>
          <Field label="Au" htmlFor="rep-to">
            <Input id="rep-to" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </Field>
          <Field label="Détail des ventes par" htmlFor="rep-group">
            <Select
              id="rep-group"
              value={group}
              onChange={(e) => setGroup(e.target.value as typeof group)}
            >
              <option value="warehouse">Lieu</option>
              <option value="seller">Vendeur</option>
              <option value="product">Article (top 100)</option>
            </Select>
          </Field>
        </CardBody>
      </Card>

      {/* Les quatre chiffres de la période, avant tout tableau. */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile
          label="Chiffre d'affaires"
          value={totaux?.revenue ?? 0}
          icon={TrendingUp}
          currency
          hint={`${formatNumber(totaux?.documents ?? 0)} facture(s)`}
        />
        <StatTile
          label="Coût des marchandises"
          value={totaux?.cost ?? 0}
          icon={Wallet}
          tone="navy"
          currency
          hint="Au prix d'achat de l'article"
        />
        <StatTile
          label="Bénéfice"
          value={totaux?.profit ?? 0}
          icon={Coins}
          tone={(totaux?.profit ?? 0) >= 0 ? 'ok' : 'bad'}
          currency
          hint="Chiffre d'affaires moins coût"
        />
        <StatTile
          label="Taux de marge"
          value={totaux?.margin_percent ?? 0}
          icon={Percent}
          tone="sky"
          hint="Part du chiffre d'affaires qui reste"
        />
      </div>

      {/* Un bénéfice calculé sur des coûts absents se lit comme acquis alors
          qu'il ne l'est pas. Le dire ici, pas en note de bas de page. */}
      {totaux && totaux.share_without_cost > 0 ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
          {totaux.share_without_cost} % du chiffre d'affaires porte sur{' '}
          {formatNumber(totaux.products_without_cost)} article(s) sans prix d'achat saisi (
          {formatCurrency(totaux.revenue_without_cost)}). Ces ventes comptent pour 100 % de marge :
          le bénéfice affiché est surestimé d'autant.
        </p>
      ) : null}

      {/* Le bénéfice sous ses trois angles. Un seul à la fois : les mettre
          côte à côte inviterait à additionner des totaux qui ne s'additionnent
          pas — un article livré par deux fournisseurs compte pour les deux. */}
      <div className="flex flex-wrap gap-2">
        {(
          [
            ['warehouse', 'Par lieu'],
            ['customer', 'Par client'],
            ['supplier', 'Par fournisseur'],
          ] as const
        ).map(([cle, libelle]) => (
          <button
            key={cle}
            type="button"
            onClick={() => setVue(cle)}
            className={cn(
              'rounded-lg border px-3 py-1.5 text-sm transition-colors',
              vue === cle
                ? 'border-brand bg-brand text-white'
                : 'border-line text-muted hover:bg-bg hover:text-ink',
            )}
          >
            {libelle}
          </button>
        ))}
      </div>

      <BlocBenefice titre={vues.titre} hint={vues.hint} rows={vues.rows} couleur={vues.couleur} />

      <div className="grid gap-4 lg:grid-cols-2">
        <ChartCard
          title={`Ventes par ${groupLabel.toLowerCase()}`}
          hint="Chiffre d'affaires sur la période choisie."
          isEmpty={(sales?.rows ?? []).length === 0}
          emptyLabel="Aucune vente confirmée sur la période."
          fitContent
        >
          <RankedBarChart
            rows={(sales?.rows ?? []).slice(0, 8).map((r) => ({
              name: r.label,
              value: r.revenue,
              detail: `${formatNumber(r.documents)} ${group === 'product' ? 'unité(s)' : 'document(s)'}`,
              note:
                r.collected < r.revenue
                  ? `${formatCurrency(r.revenue - r.collected)} non encaissés`
                  : undefined,
            }))}
          />
        </ChartCard>

        <ChartCard
          title="Valorisation du stock par lieu"
          hint={valuation ? `Total : ${formatCurrency(valuation.total_value)}` : undefined}
          isEmpty={(valuation?.warehouses ?? []).length === 0}
          emptyLabel="Aucun stock enregistré."
          fitContent
        >
          <RankedBarChart
            color={chartColors.purchases}
            valueLabel="Valeur au coût moyen"
            rows={(valuation?.warehouses ?? []).map((w) => ({
              name: w.code,
              value: w.value,
              detail: `${w.name} — ${formatNumber(w.units)} unité(s)`,
            }))}
          />
        </ChartCard>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Marges par article" hint="Les 20 premiers de la période." />
          <CardBody className="p-0">
            <div className="max-h-[360px] overflow-auto">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-card">
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 text-right font-medium">Qté</th>
                    <th className="px-5 py-3 text-right font-medium">CA</th>
                    <th className="px-5 py-3 text-right font-medium">Marge</th>
                  </tr>
                </thead>
                <tbody>
                  {(margins?.rows ?? []).length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-5 py-6 text-center text-muted">
                        Aucune vente sur la période.
                      </td>
                    </tr>
                  ) : (
                    (margins?.rows ?? []).slice(0, 20).map((r) => (
                      <tr key={r.sku} className="border-b border-line last:border-0">
                        <td className="px-5 py-3 text-ink">
                          <span className="mono text-muted">{r.sku}</span> {r.name}
                        </td>
                        <td className="tabular px-5 py-3 text-right text-muted">{r.quantity}</td>
                        <td className="tabular px-5 py-3 text-right text-muted">
                          {formatNumber(r.revenue)}
                        </td>
                        <td
                          className={cn(
                            'tabular px-5 py-3 text-right font-medium',
                            r.margin >= 0 ? 'text-ok' : 'text-bad',
                          )}
                        >
                          {formatNumber(r.margin)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            title="Articles dormants"
            hint={`Sans sortie depuis 90 jours — ${formatCurrency(dormantTotal)} immobilisés`}
          />
          <CardBody className="p-0">
            <div className="max-h-[360px] overflow-auto">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-card">
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 text-right font-medium">Stock</th>
                    <th className="px-5 py-3 text-right font-medium">Valeur</th>
                  </tr>
                </thead>
                <tbody>
                  {(dormant?.rows ?? []).length === 0 ? (
                    <tr>
                      <td colSpan={3} className="px-5 py-6 text-center text-muted">
                        Aucun article dormant.
                      </td>
                    </tr>
                  ) : (
                    (dormant?.rows ?? []).slice(0, 20).map((r) => (
                      <tr key={r.sku} className="border-b border-line last:border-0">
                        <td className="px-5 py-3 text-ink">
                          <span className="mono text-muted">{r.sku}</span> {r.name}
                        </td>
                        <td className="tabular px-5 py-3 text-right text-muted">{r.quantity}</td>
                        <td className="tabular px-5 py-3 text-right text-ink">
                          {formatNumber(r.immobilized_value)}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
