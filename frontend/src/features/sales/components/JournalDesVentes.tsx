import { useQuery } from '@tanstack/react-query'
import { CalendarDays, ChevronDown, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { Card, CardBody } from '@/components/ui/Card'
import { SearchInput } from '@/components/ui/SearchInput'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { api } from '@/lib/api'

interface JournalDay {
  date: string
  documents: number
  revenue: number
  collected: number
  credit: number
}

interface Journal {
  days: JournalDay[]
  totals: { documents: number; revenue: number; collected: number; credit: number }
}

export interface JournalFiltres {
  warehouse_id?: number
  category_id?: number
  date_from?: string
  date_to?: string
}

function money(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** « lundi 2 septembre 2026 » — le jour de la semaine situe la journée. */
function jourLong(iso: string): string {
  const d = new Date(`${iso}T00:00:00`)
  return d.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

/**
 * Journal des ventes : une ligne par journée, avec ce qu'elle a pesé.
 *
 * La liste des factures répond à « quelles ventes » ; elle ne dit pas ce
 * qu'a rapporté une journée sans additionner à la main sur plusieurs pages.
 * Les totaux viennent du serveur et couvrent tout le filtre, pas l'écran.
 */
export function JournalDesVentes({ filtres }: { filtres: JournalFiltres }) {
  const [ouvert, setOuvert] = useState(false)

  const { data, isLoading, isError } = useQuery<Journal>({
    queryKey: [
      'sales-journal',
      filtres.warehouse_id ?? 0,
      filtres.category_id ?? 0,
      filtres.date_from ?? '',
      filtres.date_to ?? '',
    ],
    queryFn: async () => {
      const { data } = await api.get<{ data: Journal }>('/sales/journal', { params: filtres })
      return data.data
    },
    // Replié, le journal n'est pas lu : inutile d'agréger côté serveur à
    // chaque passage sur la page des ventes.
    enabled: ouvert,
  })

  const jours = data?.days ?? []
  const totals = data?.totals

  // La recherche porte sur les deux ecritures de la date : la forme ISO
  // et la forme lisible doivent toutes deux trouver la journee.
  const { terme, setTerme, resultats, actif } = useRechercheLocale(jours, (j) => [
    j.date,
    jourLong(j.date),
  ])

  return (
    <Card>
      <button
        type="button"
        onClick={() => setOuvert((v) => !v)}
        aria-expanded={ouvert}
        className="flex w-full items-center gap-3 px-5 py-4 text-left transition-colors hover:bg-sky-soft/40"
      >
        {ouvert ? (
          <ChevronDown className="h-4 w-4 shrink-0 text-muted" />
        ) : (
          <ChevronRight className="h-4 w-4 shrink-0 text-muted" />
        )}
        <CalendarDays className="h-4 w-4 shrink-0 text-navy" />
        <span className="font-medium text-ink">Journal des ventes</span>
        <span className="ml-auto text-sm text-muted">
          {totals
            ? `${totals.documents} facture(s) — ${money(totals.revenue)} DH`
            : 'jour par jour, avec les totaux de la période'}
        </span>
      </button>

      {ouvert ? (
        <CardBody className="border-t border-line p-0">
          {isError ? (
            <p className="p-5 text-sm text-bad">Impossible de charger le journal.</p>
          ) : isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : jours.length === 0 ? (
            <p className="p-8 text-center text-sm text-muted">
              Aucune facture sur la période filtrée.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <div className="flex justify-end px-5 py-3">
                <SearchInput value={terme} onChange={setTerme} placeholder="Journée…" />
              </div>
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Journée</th>
                    <th className="px-5 py-3 text-right font-medium">Factures</th>
                    <th className="px-5 py-3 text-right font-medium">Chiffre d'affaires</th>
                    <th className="px-5 py-3 text-right font-medium">Encaissé</th>
                    <th className="px-5 py-3 text-right font-medium">À crédit</th>
                  </tr>
                </thead>
                <tbody>
                  {resultats.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="px-5 py-8 text-center text-muted">
                        Aucune journée ne correspond à cette recherche.
                      </td>
                    </tr>
                  ) : null}
                  {resultats.map((j) => (
                    <tr key={j.date} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        <span className="text-ink">{jourLong(j.date)}</span>
                        <span className="mono ml-2 text-xs text-faint">{j.date}</span>
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{j.documents}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">
                        {money(j.revenue)}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-ok">{money(j.collected)}</td>
                      {/* Un crédit à zéro n'a pas à s'afficher en rouge : la
                          journée est soldée, ce n'est pas une alerte. */}
                      <td
                        className={`tabular px-5 py-3 text-right ${j.credit > 0.005 ? 'text-bad' : 'text-faint'}`}
                      >
                        {money(j.credit)}
                      </td>
                    </tr>
                  ))}
                </tbody>
                {totals ? (
                  <tfoot>
                    <tr className="border-t-2 border-line bg-sky-soft/30">
                      <td className="px-5 py-3 font-semibold text-ink">
                        Total de la période
                        {actif ? (
                          <span className="ml-2 text-xs font-normal text-faint">
                            (toutes les journées, recherche comprise)
                          </span>
                        ) : null}
                      </td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-ink">
                        {totals.documents}
                      </td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-ink">
                        {money(totals.revenue)}
                      </td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-ok">
                        {money(totals.collected)}
                      </td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-bad">
                        {money(totals.credit)}
                      </td>
                    </tr>
                  </tfoot>
                ) : null}
              </table>

              {/* Le serveur s'arrête à 120 journées : sans cette mention, une
                  période plus longue paraîtrait complète. */}
              {jours.length >= 120 ? (
                <p className="px-5 py-3 text-xs text-faint">
                  Les 120 journées les plus récentes sont détaillées. Le total ci-dessus couvre
                  bien toute la période filtrée.
                </p>
              ) : null}
            </div>
          )}
        </CardBody>
      ) : null}
    </Card>
  )
}
