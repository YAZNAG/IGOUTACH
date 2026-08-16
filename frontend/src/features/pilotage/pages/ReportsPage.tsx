import { useQuery } from '@tanstack/react-query'
import { Download } from 'lucide-react'
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
import { formatCurrency, formatNumber } from '@/lib/utils'

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

function firstDayOfMonth(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

export function ReportsPage() {
  const [from, setFrom] = useState(firstDayOfMonth())
  const [to, setTo] = useState(today())
  const [group, setGroup] = useState<'warehouse' | 'seller' | 'product'>('warehouse')
  const [exporting, setExporting] = useState(false)

  const { data: sales, isLoading: ventesEnCours } = useQuery<{ rows: SalesRow[] }>({
    queryKey: ['report-sales', from, to, group],
    queryFn: async () => {
      const { data } = await api.get<{ data: { rows: SalesRow[] } }>('/reports/sales', {
        params: { from, to, group },
      })
      return data.data
    },
  })

  const { data: valuation, isLoading: valuationEnCours } = useQuery<{ warehouses: ValuationRow[]; total_value: number }>({
    queryKey: ['report-valuation'],
    queryFn: async () => {
      const { data } = await api.get<{ data: { warehouses: ValuationRow[]; total_value: number } }>('/reports/stock-valuation')
      return data.data
    },
  })

  const { data: margins } = useQuery<{ rows: MarginRow[] }>({
    queryKey: ['report-margins', from, to],
    queryFn: async () => {
      const { data } = await api.get<{ data: { rows: MarginRow[] } }>('/reports/margins', { params: { from, to } })
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
      await downloadFile(`/reports/sales?from=${from}&to=${to}&group=${group}&format=xlsx`, `ventes-${group}-${from}-${to}.xlsx`)
    } finally {
      setExporting(false)
    }
  }

  const groupLabel = group === 'warehouse' ? 'Lieu' : group === 'seller' ? 'Vendeur' : 'Article'

  // Les totaux se recomposent depuis les lignes déjà chargées : un appel de
  // plus pour des sommes que l'on a sous la main serait du gaspillage.
  const lignesVentes = sales?.rows ?? []
  const caPeriode = lignesVentes.reduce((somme, r) => somme + r.revenue, 0)
  const encaissePeriode = lignesVentes.reduce((somme, r) => somme + r.collected, 0)
  const documentsPeriode = lignesVentes.reduce((somme, r) => somme + r.documents, 0)

  const lignesMarge = margins?.rows ?? []
  const margeTotale = lignesMarge.reduce((somme, r) => somme + r.margin, 0)
  const caMarge = lignesMarge.reduce((somme, r) => somme + r.revenue, 0)
  // Le taux n'a de sens que rapporté au chiffre d'affaires valorisé : les
  // articles sans coût connu ne sont pas dans cette base.
  const tauxMarge = caMarge > 0 ? Math.round((margeTotale / caMarge) * 1000) / 10 : null

  const dormantTotal = (dormant?.rows ?? []).reduce((somme, r) => somme + r.immobilized_value, 0)

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-xl font-semibold text-ink">Rapports</h1>
          <p className="text-sm text-muted">Ventes, valorisation du stock, marges et articles dormants.</p>
        </div>
        <Button variant="outline" size="sm" onClick={exportSales} disabled={exporting}>
          <Download className="h-4 w-4" />
          {exporting ? 'Export…' : 'Exporter les ventes'}
        </Button>
      </div>

      {/* Les filtres commandent toute la page : ils vivent en haut, sur une
          seule ligne, et non enfouis dans la première carte. */}
      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-3">
          <Field label="Du" htmlFor="rep-from">
            <Input id="rep-from" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          </Field>
          <Field label="Au" htmlFor="rep-to">
            <Input id="rep-to" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </Field>
          <Field label="Regrouper par" htmlFor="rep-group">
            <Select id="rep-group" value={group} onChange={(e) => setGroup(e.target.value as typeof group)}>
              <option value="warehouse">Lieu</option>
              <option value="seller">Vendeur</option>
              <option value="product">Article (top 100)</option>
            </Select>
          </Field>
        </CardBody>
      </Card>

      {/* Les quatre chiffres qui résument la période, avant tout tableau. */}
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile label="Chiffre d'affaires" value={caPeriode} currency
          hint={`${formatNumber(documentsPeriode)} document(s)`} />
        <StatTile label="Encaissé" value={encaissePeriode} currency tone="ok"
          hint={caPeriode > 0 ? `${Math.round((encaissePeriode / caPeriode) * 100)} % du chiffre d'affaires` : undefined} />
        <StatTile label="Marge réalisée" value={margeTotale} currency
          tone={margeTotale >= 0 ? 'ok' : 'warn'}
          hint={tauxMarge === null ? 'Aucune vente valorisée' : `Taux de ${tauxMarge} %`} />
        <StatTile label="Valeur immobilisée" value={dormantTotal} currency tone="warn"
          hint={`${formatNumber(dormant?.rows.length ?? 0)} article(s) dormant(s)`} />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <ChartCard
          title={`Ventes par ${groupLabel.toLowerCase()}`}
          hint="Sur la période choisie."
          isLoading={ventesEnCours}
          isEmpty={(sales?.rows ?? []).length === 0}
          emptyLabel="Aucune vente confirmée sur la période."
          fitContent
        >
          <RankedBarChart
            rows={(sales?.rows ?? []).slice(0, 8).map((r) => ({
              name: r.label,
              value: r.revenue,
              detail: `${formatNumber(r.documents)} ${group === 'product' ? 'unité(s)' : 'document(s)'}`,
              note: r.collected < r.revenue ? `${formatCurrency(r.revenue - r.collected)} non encaissés` : undefined,
            }))}
          />
        </ChartCard>

        <ChartCard
          title="Valorisation du stock par lieu"
          hint={valuation ? `Total : ${formatCurrency(valuation.total_value)}` : undefined}
          isLoading={valuationEnCours}
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

      <Card>
        <CardHeader title={`Ventes par ${groupLabel.toLowerCase()}`} hint="Le détail chiffré du graphique ci-dessus." />
        <CardBody>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="py-2 pr-4 font-medium">{groupLabel}</th>
                <th className="py-2 pr-4 text-right font-medium">{group === 'product' ? 'Qté vendue' : 'Documents'}</th>
                <th className="py-2 pr-4 text-right font-medium">Chiffre d'affaires</th>
                <th className="py-2 text-right font-medium">{group === 'product' ? 'Coût (CMUP)' : 'Encaissé'}</th>
              </tr>
            </thead>
            <tbody>
              {(sales?.rows ?? []).length === 0 ? (
                <tr><td colSpan={4} className="py-6 text-center text-muted">Aucune vente confirmée sur la période.</td></tr>
              ) : (
                (sales?.rows ?? []).map((r) => (
                  <tr key={r.label} className="border-b border-line last:border-0">
                    <td className="py-2 pr-4 text-ink">{r.label}</td>
                    <td className="tabular py-2 pr-4 text-right text-muted">{r.documents}</td>
                    <td className="tabular py-2 pr-4 text-right font-medium text-ink">{formatNumber(r.revenue)} DH</td>
                    <td className="tabular py-2 text-right text-muted">{formatNumber(r.collected)} DH</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </CardBody>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader title="Articles dormants" hint="Sans sortie depuis 90 jours, stock > 0" />
          <CardBody className="p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Article</th>
                  <th className="px-5 py-3 text-right font-medium">Stock</th>
                  <th className="px-5 py-3 text-right font-medium">Valeur immobilisée</th>
                </tr>
              </thead>
              <tbody>
                {(dormant?.rows ?? []).slice(0, 10).map((r) => (
                  <tr key={r.sku} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink"><span className="mono text-muted">{r.sku}</span> {r.name}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{r.quantity}</td>
                    <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(r.immobilized_value)} DH</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader title="Marges réalisées par article" hint={`Période : ${from} → ${to}`} />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Qté</th>
                <th className="px-5 py-3 text-right font-medium">CA</th>
                <th className="px-5 py-3 text-right font-medium">Coût</th>
                <th className="px-5 py-3 text-right font-medium">Marge</th>
              </tr>
            </thead>
            <tbody>
              {(margins?.rows ?? []).length === 0 ? (
                <tr><td colSpan={5} className="px-5 py-6 text-center text-muted">Aucune vente sur la période.</td></tr>
              ) : (
                (margins?.rows ?? []).slice(0, 20).map((r) => (
                  <tr key={r.sku} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink"><span className="mono text-muted">{r.sku}</span> {r.name}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{r.quantity}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(r.revenue)} DH</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(r.cost)} DH</td>
                    <td className={`tabular px-5 py-3 text-right font-medium ${r.margin >= 0 ? 'text-ok' : 'text-bad'}`}>
                      {formatNumber(r.margin)} DH
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
