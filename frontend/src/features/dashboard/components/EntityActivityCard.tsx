import { useQuery } from '@tanstack/react-query'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { api } from '@/lib/api'
import { cn, formatCurrency, formatNumber } from '@/lib/utils'
import { MonthlySeriesChart, type MonthlyPoint } from './MonthlySeriesChart'
import { RankedBarChart } from './RankedBarChart'
import { chartColors } from './chartTheme'

interface Totals {
  total: number
  average: number
  best: number
  current_month: number
  last_closed_month: number
  change_percent: number | null
  documents: number
}

interface TopRow {
  sku?: string
  name: string
  quantity?: number
  count?: number
  revenue: number
}

interface ActivityPayload {
  monthly: ({ month: string; label: string; count: number } & Record<string, unknown>)[]
  totals: Totals
  top_products?: TopRow[]
  top_customers?: TopRow[]
}

interface EntityActivityCardProps {
  /** Chemin de l'endpoint, sans le préfixe d'API. */
  path: string
  /** Clé du montant dans la série : `revenue` pour les ventes, `purchases`
   *  pour les achats fournisseurs. */
  measureKey: 'revenue' | 'purchases'
  title: string
  /** « vente » ou « réception ». */
  countLabel: string
  color?: string
}

function Chiffre({
  libelle,
  valeur,
  precision,
  ton,
}: {
  libelle: string
  valeur: string
  precision?: string
  ton?: 'ok' | 'bad'
}) {
  return (
    <div>
      <p className="text-xs text-muted">{libelle}</p>
      <p
        className={cn(
          'tabular text-lg font-semibold',
          ton === 'ok' ? 'text-ok' : ton === 'bad' ? 'text-bad' : 'text-ink',
        )}
      >
        {valeur}
      </p>
      {precision ? <p className="text-xs text-faint">{precision}</p> : null}
    </div>
  )
}

/**
 * Activité chiffrée d'une entité : douze mois, quelques repères, et ce qui
 * compose le montant.
 *
 * Le même bloc sert au lieu, au client et au fournisseur. Leur question est la
 * même — combien, et depuis quand — seule la source diffère.
 */
export function EntityActivityCard({
  path,
  measureKey,
  title,
  countLabel,
  color = chartColors.brand,
}: EntityActivityCardProps) {
  const { data, isLoading, isError } = useQuery<ActivityPayload>({
    queryKey: ['entity-activity', path],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ActivityPayload }>(path)
      return r.data
    },
  })

  if (isError) {
    return (
      <Card>
        <CardBody>
          <p className="text-sm text-bad">Impossible de charger l’activité.</p>
        </CardBody>
      </Card>
    )
  }

  if (isLoading || !data) {
    return <div className="h-[320px] animate-pulse rounded-lg bg-line" />
  }

  const points: MonthlyPoint[] = data.monthly.map((m) => ({
    month: m.month,
    label: m.label,
    value: Number(m[measureKey] ?? 0),
    count: m.count,
  }))

  const vide = points.every((p) => p.value === 0)
  const { totals } = data
  const variation = totals.change_percent

  return (
    <div className="space-y-4">
      <Card className="flex flex-col">
        <CardHeader title={title} hint="12 mois glissants." />
        <CardBody className="space-y-4">
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <Chiffre
              libelle="Total 12 mois"
              valeur={formatCurrency(totals.total)}
              precision={`${formatNumber(totals.documents)} ${countLabel}${totals.documents > 1 ? 's' : ''}`}
            />
            <Chiffre libelle="Moyenne mensuelle" valeur={formatCurrency(totals.average)} />
            <Chiffre libelle="Meilleur mois" valeur={formatCurrency(totals.best)} />
            <Chiffre
              libelle="Mois en cours"
              valeur={formatCurrency(totals.current_month)}
              // La variation se lit sur les deux derniers mois clos : comparer
              // un mois entamé à un mois plein annoncerait une chute chaque 1er.
              precision={
                variation === null
                  ? 'dernier mois clos sans référence'
                  : `${variation >= 0 ? '+' : ''}${variation} % sur le dernier mois clos`
              }
              ton={variation === null ? undefined : variation >= 0 ? 'ok' : 'bad'}
            />
          </div>

          {vide ? (
            <div className="flex h-[220px] items-center justify-center rounded-lg border border-dashed border-line">
              <p className="text-sm text-muted">Aucun mouvement sur les douze derniers mois.</p>
            </div>
          ) : (
            <div className="h-[220px] w-full">
              <MonthlySeriesChart
                data={points}
                measureLabel={title}
                countLabel={countLabel}
                color={color}
              />
            </div>
          )}
        </CardBody>
      </Card>

      <div className="grid gap-4 lg:grid-cols-2">
        {data.top_products && data.top_products.length > 0 ? (
          <Card className="flex flex-col">
            <CardHeader
              title="Articles"
              hint={measureKey === 'purchases' ? 'Les plus achetés.' : 'Les plus vendus.'}
            />
            <CardBody>
              <RankedBarChart
                color={color}
                rows={data.top_products.map((p) => ({
                  name: p.sku ?? p.name,
                  value: p.revenue,
                  detail: `${p.name} — ${formatNumber(p.quantity ?? 0)} unité(s)`,
                }))}
              />
            </CardBody>
          </Card>
        ) : null}

        {data.top_customers && data.top_customers.length > 0 ? (
          <Card className="flex flex-col">
            <CardHeader title="Clients" hint="Les plus gros acheteurs de ce lieu." />
            <CardBody>
              <RankedBarChart
                color={chartColors.sales}
                rows={data.top_customers.map((c) => ({
                  name: c.name,
                  value: c.revenue,
                  detail: `${formatNumber(c.count ?? 0)} facture(s)`,
                }))}
              />
            </CardBody>
          </Card>
        ) : null}
      </div>
    </div>
  )
}
