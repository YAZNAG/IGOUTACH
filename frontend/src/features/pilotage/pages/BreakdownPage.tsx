import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { Card, CardBody } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { ChartCard } from '@/features/dashboard/components/ChartCard'
import { RankedBarChart } from '@/features/dashboard/components/RankedBarChart'
import { StatTile } from '@/features/dashboard/components/StatTile'
import { chartColors } from '@/features/dashboard/components/chartTheme'
import { api } from '@/lib/api'
import { cn, formatCurrency, formatNumber } from '@/lib/utils'

/** Une ligne de découpage : un lieu, un client, un article, un fournisseur. */
interface BreakdownRow {
  name: string
  documents: number
  revenue: number
  quantity?: number | null
  /** Absents sans le droit de voir les coûts. */
  cost?: number
  profit?: number
  margin_percent?: number | null
}

interface BreakdownData {
  from: string
  to: string
  totals: {
    documents: number
    revenue: number
    cost?: number
    profit?: number
    margin_percent?: number | null
  }
  by_warehouse: BreakdownRow[]
  by_customer: BreakdownRow[]
  by_product: BreakdownRow[]
  by_supplier: BreakdownRow[]
  /** Absent sans le droit de voir les couts. */
  missing_cost?: {
    products: number
    revenue: number
    revenue_share: number
  }
}

interface BreakdownPageProps {
  /** La mesure mise en avant. Les deux pages lisent la même source. */
  mesure: 'revenue' | 'profit'
}

function premierJourDuMois(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

function aujourdHui(): string {
  return new Date().toISOString().slice(0, 10)
}

/** Tableau du détail, sous chaque graphique : le chiffre exact et la marge. */
function TableauDetail({
  lignes,
  mesure,
  colonneLibelle,
}: {
  lignes: BreakdownRow[]
  mesure: 'revenue' | 'profit'
  colonneLibelle: string
}) {
  const montreLaMarge = lignes.some((l) => l.profit !== undefined)

  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="border-b border-line text-left text-muted">
          <th className="py-2 pr-4 font-medium">{colonneLibelle}</th>
          <th className="py-2 pr-4 text-right font-medium">Chiffre d'affaires</th>
          {montreLaMarge ? (
            <>
              <th className="py-2 pr-4 text-right font-medium">Bénéfice</th>
              <th className="py-2 text-right font-medium">Marge</th>
            </>
          ) : null}
        </tr>
      </thead>
      <tbody>
        {lignes.length === 0 ? (
          <tr>
            <td colSpan={montreLaMarge ? 4 : 2} className="py-6 text-center text-muted">
              Aucune activité sur la période.
            </td>
          </tr>
        ) : (
          lignes.map((l) => (
            <tr key={l.name} className="border-b border-line last:border-0">
              <td className="py-2 pr-4 text-ink">{l.name}</td>
              <td
                className={cn(
                  'tabular py-2 pr-4 text-right',
                  mesure === 'revenue' ? 'font-medium text-ink' : 'text-muted',
                )}
              >
                {formatCurrency(l.revenue)}
              </td>
              {montreLaMarge ? (
                <>
                  <td
                    className={cn(
                      'tabular py-2 pr-4 text-right',
                      mesure === 'profit' ? 'font-medium' : '',
                      (l.profit ?? 0) >= 0 ? 'text-ok' : 'text-bad',
                    )}
                  >
                    {formatCurrency(l.profit ?? 0)}
                  </td>
                  <td className="tabular py-2 text-right text-muted">
                    {l.margin_percent === null || l.margin_percent === undefined
                      ? '—'
                      : `${l.margin_percent} %`}
                  </td>
                </>
              ) : null}
            </tr>
          ))
        )}
      </tbody>
    </table>
  )
}

/**
 * Le chiffre d'affaires, ou le bénéfice, découpé par lieu, client, article et
 * fournisseur.
 *
 * Une seule page pour deux écrans : les deux mesures sortent de la même
 * requête, seule la mise en avant change. Les séparer aurait fini par les
 * faire diverger, et personne n'aurait su laquelle croire.
 */
export function BreakdownPage({ mesure }: BreakdownPageProps) {
  const [du, setDu] = useState(premierJourDuMois())
  const [au, setAu] = useState(aujourdHui())

  const { data, isLoading, isError } = useQuery<BreakdownData>({
    queryKey: ['reports-breakdown', du, au],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: BreakdownData }>('/reports/breakdown', {
        params: { from: du, to: au },
      })
      return r.data
    },
  })

  const beneficeVise = mesure === 'profit'
  const titre = beneficeVise ? 'Bénéfice' : "Chiffre d'affaires"

  if (isError) {
    return (
      <Card className="p-5">
        <p className="text-sm text-bad">Impossible de charger le rapport.</p>
      </Card>
    )
  }

  const totaux = data?.totals
  // Sans le droit de voir les couts, la page du benefice n'a rien a montrer :
  // le dire vaut mieux qu'afficher des colonnes vides.
  const beneficeInterdit = beneficeVise && data !== undefined && totaux?.profit === undefined

  /** Les quatre découpages, dans l'ordre où on se pose la question. */
  const blocs: { titre: string; libelle: string; lignes: BreakdownRow[]; couleur: string }[] = [
    {
      titre: 'Par lieu',
      libelle: 'Lieu',
      lignes: data?.by_warehouse ?? [],
      couleur: chartColors.brand,
    },
    {
      titre: 'Par client',
      libelle: 'Client',
      lignes: data?.by_customer ?? [],
      couleur: chartColors.sales,
    },
    {
      titre: 'Par article',
      libelle: 'Article',
      lignes: data?.by_product ?? [],
      couleur: 'var(--chart-2)',
    },
    {
      titre: 'Par fournisseur',
      libelle: 'Fournisseur',
      lignes: data?.by_supplier ?? [],
      couleur: chartColors.purchases,
    },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">{titre}</h1>
        <p className="text-sm text-muted">
          {beneficeVise
            ? 'Ce que rapporte chaque lieu, chaque client, chaque article et chaque fournisseur.'
            : 'Ce que vend chaque lieu, chaque client, chaque article et chaque fournisseur.'}
        </p>
      </div>

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Field label="Du" htmlFor="bd-du">
            <Input id="bd-du" type="date" value={du} onChange={(e) => setDu(e.target.value)} />
          </Field>
          <Field label="Au" htmlFor="bd-au">
            <Input id="bd-au" type="date" value={au} onChange={(e) => setAu(e.target.value)} />
          </Field>
        </CardBody>
      </Card>

      {beneficeInterdit ? (
        <Card>
          <CardBody>
            <p className="text-sm text-muted">
              Le bénéfice se déduit des prix d'achat. Votre compte n'a pas le droit de les
              consulter : cette page reste donc vide. Le chiffre d'affaires, lui, est accessible.
            </p>
          </CardBody>
        </Card>
      ) : null}

      {/* Une marge de 100 % n'est pas une performance : c'est un prix d'achat
          que personne n'a saisi. Le dire evite de lire une lacune comme un
          resultat. */}
      {beneficeVise && data?.missing_cost && data.missing_cost.products > 0 ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
          {formatNumber(data.missing_cost.products)} article(s) vendu(s) sur la période n'ont pas de
          prix d'achat enregistré — ils ressortent à 100 % de marge. Ils pèsent{' '}
          {formatCurrency(data.missing_cost.revenue)} de chiffre d'affaires, soit{' '}
          {data.missing_cost.revenue_share} % du total : le bénéfice affiché est donc surestimé
          d'autant.
        </p>
      ) : null}

      {isLoading || !totaux ? (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          {Array.from({ length: 4 }, (_, i) => (
            <div key={i} className="h-[92px] animate-pulse rounded-lg bg-line" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <StatTile
            label="Chiffre d'affaires"
            value={totaux.revenue}
            currency
            tone={beneficeVise ? 'navy' : 'ok'}
            hint={`${formatNumber(totaux.documents)} facture(s)`}
          />
          {totaux.cost !== undefined ? (
            <StatTile label="Coût des marchandises" value={totaux.cost} currency tone="navy" />
          ) : null}
          {totaux.profit !== undefined ? (
            <StatTile
              label="Bénéfice"
              value={totaux.profit}
              currency
              tone={totaux.profit >= 0 ? 'ok' : 'warn'}
              hint={
                totaux.margin_percent === null || totaux.margin_percent === undefined
                  ? undefined
                  : `Marge de ${totaux.margin_percent} %`
              }
            />
          ) : null}
          <StatTile
            label="Panier moyen"
            value={totaux.documents > 0 ? totaux.revenue / totaux.documents : 0}
            currency
            tone="sky"
            hint="Par facture"
          />
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        {blocs.map((bloc) => {
          // Le classement suit la mesure regardée : sur la page du bénéfice,
          // c'est le plus rentable qui doit venir en tête, pas le plus gros.
          const triees = [...bloc.lignes].sort(
            (a, b) => (b[mesure] ?? 0) - (a[mesure] ?? 0),
          )

          return (
            <div key={bloc.titre} className="space-y-3">
              <ChartCard
                title={`${titre} ${bloc.titre.toLowerCase()}`}
                hint={`Du ${du} au ${au}.`}
                isEmpty={triees.length === 0}
                emptyLabel="Aucune activité sur la période."
                fitContent
              >
                <RankedBarChart
                  color={bloc.couleur}
                  valueLabel={titre}
                  rows={triees.slice(0, 10).map((l) => ({
                    name: l.name.length > 28 ? `${l.name.slice(0, 27)}…` : l.name,
                    value: l[mesure] ?? 0,
                    detail:
                      l.quantity !== null && l.quantity !== undefined
                        ? `${formatNumber(l.quantity)} unité(s) · ${formatNumber(l.documents)} facture(s)`
                        : `${formatNumber(l.documents)} facture(s)`,
                    note:
                      l.margin_percent === null || l.margin_percent === undefined
                        ? undefined
                        : `marge ${l.margin_percent} %`,
                  }))}
                />
              </ChartCard>

              <Card>
                <CardBody className="overflow-x-auto">
                  <TableauDetail
                    lignes={triees}
                    mesure={mesure}
                    colonneLibelle={bloc.libelle}
                  />
                </CardBody>
              </Card>
            </div>
          )
        })}
      </div>
    </div>
  )
}
