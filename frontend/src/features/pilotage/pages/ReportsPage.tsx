import { useQuery } from '@tanstack/react-query'
import { Download } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { api } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'

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

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Rapports</h1>
        <p className="text-sm text-muted">Ventes, valorisation du stock, marges et articles dormants.</p>
      </div>

      <Card>
        <CardHeader
          title="Ventes par période"
          action={
            <Button variant="outline" size="sm" onClick={exportSales} disabled={exporting}>
              <Download className="h-4 w-4" />
              {exporting ? 'Export…' : 'Excel'}
            </Button>
          }
        />
        <CardBody>
          <div className="mb-4 grid gap-4 sm:grid-cols-3">
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
          </div>

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
          <CardHeader
            title="Valorisation du stock par lieu"
            hint={valuation ? `Total : ${formatNumber(valuation.total_value)} DH` : undefined}
          />
          <CardBody className="p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 text-right font-medium">Unités</th>
                  <th className="px-5 py-3 text-right font-medium">Valeur (CMUP)</th>
                </tr>
              </thead>
              <tbody>
                {(valuation?.warehouses ?? []).map((w) => (
                  <tr key={w.code} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink">{w.code} · {w.name}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(w.units)}</td>
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatNumber(w.value)} DH</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardBody>
        </Card>

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
