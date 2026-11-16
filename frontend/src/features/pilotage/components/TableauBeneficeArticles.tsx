import { useQuery } from '@tanstack/react-query'
import { Download, X } from 'lucide-react'
import { useMemo, useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { useWarehouseOptions } from '@/features/access/hooks'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { api } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { cn, formatCurrency, formatNumber } from '@/lib/utils'

/** Un article vendu sur la période, avec ce qu'il a rapporté. */
interface LigneArticle {
  product_id: number
  sku: string
  name: string
  category: string | null
  documents: number
  quantity: number
  revenue: number
  cost: number
  unit_cost: number
  avg_price: number | null
  profit: number
  unit_profit: number | null
  margin_percent: number | null
}

interface Reponse {
  from: string
  to: string
  rows: LigneArticle[]
  totals: {
    products: number
    quantity: number
    revenue: number
    cost: number
    profit: number
    margin_percent: number | null
  }
}

interface Props {
  /** Période de la page : le tableau la suit, sauf si un jour précis est choisi. */
  du: string
  au: string
}

type Colonne = 'sku' | 'name' | 'category' | 'quantity' | 'documents' | 'revenue' | 'cost' | 'profit' | 'unit_profit' | 'margin_percent'

const PAR_PAGE = [20, 50, 100] as const

/**
 * Tous les articles vendus et leur bénéfice, dans un tableau qu'on fouille :
 * recherche, tri sur chaque colonne, pagination, filtre par jour et par lieu,
 * export. Le classement de la page ne montre que les dix premiers ; ici on
 * cherche aussi ceux qui se vendent à perte.
 */
export function TableauBeneficeArticles({ du, au }: Props) {
  const [jour, setJour] = useState('')
  const [lieu, setLieu] = useState(0)
  const [tri, setTri] = useState<SortState>({ sort: 'profit', direction: 'desc' })
  const [page, setPage] = useState(1)
  const [parPage, setParPage] = useState<(typeof PAR_PAGE)[number]>(20)
  const [exportEnCours, setExportEnCours] = useState<'xlsx' | 'pdf' | null>(null)

  const { data: lieux = [] } = useWarehouseOptions()

  const params = {
    from: du,
    to: au,
    day: jour || undefined,
    warehouse_id: lieu || undefined,
  }

  const { data, isLoading, isError } = useQuery<Reponse>({
    queryKey: ['reports-profit-by-product', du, au, jour, lieu],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: Reponse }>('/reports/profit-by-product', { params })
      return r.data
    },
  })

  const lignes = useMemo(() => data?.rows ?? [], [data])

  // La recherche se fait sur place : la liste entière est déjà là, et une
  // frappe ne doit pas attendre le serveur.
  const { terme, setTerme, resultats, actif } = useRechercheLocale(lignes, (l) => [l.sku, l.name, l.category ?? ''])

  const triees = useMemo(() => {
    const champ = tri.sort as Colonne
    const sens = tri.direction === 'asc' ? 1 : -1
    return [...resultats].sort((a, b) => {
      const va = a[champ]
      const vb = b[champ]
      if (va === null || va === undefined) return 1
      if (vb === null || vb === undefined) return -1
      if (typeof va === 'string' && typeof vb === 'string') return va.localeCompare(vb, 'fr') * sens
      return (Number(va) - Number(vb)) * sens
    })
  }, [resultats, tri])

  const total = triees.length
  const lastPage = Math.max(1, Math.ceil(total / parPage))
  const pageCourante = Math.min(page, lastPage)
  const visibles = triees.slice((pageCourante - 1) * parPage, pageCourante * parPage)

  // Les totaux suivent la recherche : ce qu'on voit, c'est ce qu'on additionne.
  const totaux = useMemo(() => {
    const t = resultats.reduce(
      (acc, l) => ({
        quantity: acc.quantity + l.quantity,
        revenue: acc.revenue + l.revenue,
        cost: acc.cost + l.cost,
        profit: acc.profit + l.profit,
      }),
      { quantity: 0, revenue: 0, cost: 0, profit: 0 },
    )
    return { ...t, margin: t.revenue > 0 ? Math.round((t.profit / t.revenue) * 1000) / 10 : null }
  }, [resultats])

  const changerTri = (t: SortState) => {
    setTri(t)
    setPage(1)
  }

  async function exporter(format: 'xlsx' | 'pdf') {
    setExportEnCours(format)
    try {
      const periode = jour || `${du}_${au}`
      await downloadFile('/reports/profit-by-product', `IGOUTECH_benefice-articles_${periode}.${format}`, {
        ...params,
        q: terme || undefined,
        format,
      })
    } catch (e) {
      console.error('Export du bénéfice par article impossible :', e)
    } finally {
      setExportEnCours(null)
    }
  }

  const filtresActifs = jour !== '' || lieu !== 0 || terme !== ''

  return (
    <Card>
      <CardHeader
        title="Bénéfice de tous les articles"
        hint={
          isLoading
            ? 'Chargement…'
            : jour
              ? `Le ${jour} · ${formatNumber(total)} article(s)`
              : `Du ${du} au ${au} · ${formatNumber(total)} article(s)`
        }
        action={
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" disabled={exportEnCours !== null || total === 0} onClick={() => exporter('xlsx')}>
              <Download className="h-4 w-4" />
              {exportEnCours === 'xlsx' ? 'Export…' : 'Excel'}
            </Button>
            <Button variant="outline" size="sm" disabled={exportEnCours !== null || total === 0} onClick={() => exporter('pdf')}>
              <Download className="h-4 w-4" />
              {exportEnCours === 'pdf' ? 'Export…' : 'PDF'}
            </Button>
          </div>
        }
      />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Field label="Jour précis" htmlFor="bpa-jour">
            <Input
              id="bpa-jour"
              type="date"
              value={jour}
              onChange={(e) => {
                setJour(e.target.value)
                setPage(1)
              }}
            />
          </Field>
          <Field label="Lieu" htmlFor="bpa-lieu">
            <Select
              id="bpa-lieu"
              value={lieu || ''}
              onChange={(e) => {
                setLieu(Number(e.target.value))
                setPage(1)
              }}
            >
              <option value="">Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Rechercher" htmlFor="bpa-recherche">
            <SearchInput
              value={terme}
              onChange={(v) => {
                setTerme(v)
                setPage(1)
              }}
              placeholder="Référence, article, catégorie…"
            />
          </Field>
          <Field label="Lignes par page" htmlFor="bpa-par-page">
            <Select
              id="bpa-par-page"
              value={parPage}
              onChange={(e) => {
                setParPage(Number(e.target.value) as (typeof PAR_PAGE)[number])
                setPage(1)
              }}
            >
              {PAR_PAGE.map((n) => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </Select>
          </Field>
        </div>

        {filtresActifs ? (
          <Button
            variant="outline"
            size="sm"
            className="text-muted"
            onClick={() => {
              setJour('')
              setLieu(0)
              setTerme('')
              setPage(1)
            }}
          >
            <X className="h-4 w-4" />
            Réinitialiser les filtres
          </Button>
        ) : null}

        {jour ? (
          <p className="text-xs text-muted">
            Le jour choisi remplace la période de la page : seules les ventes du {jour} sont comptées.
          </p>
        ) : null}

        {isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Impossible de charger le bénéfice par article.
          </p>
        ) : null}

        <div className="overflow-x-auto rounded border border-line">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line bg-bg text-left text-muted">
                <SortableTh field="sku" current={tri} onSort={changerTri} className="px-4">Référence</SortableTh>
                <SortableTh field="name" current={tri} onSort={changerTri} className="px-4">Article</SortableTh>
                <SortableTh field="category" current={tri} onSort={changerTri} className="px-4">Catégorie</SortableTh>
                <SortableTh field="quantity" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Qté vendue</SortableTh>
                <SortableTh field="documents" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Factures</SortableTh>
                <SortableTh field="revenue" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Chiffre d'affaires</SortableTh>
                <SortableTh field="cost" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Coût</SortableTh>
                <SortableTh field="profit" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Bénéfice</SortableTh>
                <SortableTh field="unit_profit" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Bénéfice / unité</SortableTh>
                <SortableTh field="margin_percent" current={tri} onSort={changerTri} className="px-4 text-right" align="right">Marge</SortableTh>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={10} className="px-4 py-6 text-center text-muted">Chargement…</td>
                </tr>
              ) : visibles.length === 0 ? (
                <tr>
                  <td colSpan={10} className="px-4 py-6 text-center text-muted">
                    {actif ? 'Aucun article ne correspond à cette recherche.' : 'Aucune vente sur la période.'}
                  </td>
                </tr>
              ) : (
                visibles.map((l) => (
                  <tr key={l.product_id} className="border-b border-line last:border-0">
                    <td className="mono px-4 py-2 text-muted">{l.sku}</td>
                    <td className="px-4 py-2 text-ink">{l.name}</td>
                    <td className="px-4 py-2 text-muted">{l.category ?? '—'}</td>
                    <td className="tabular px-4 py-2 text-right text-ink">{formatNumber(l.quantity)}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{formatNumber(l.documents)}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{formatCurrency(l.revenue)}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{formatCurrency(l.cost)}</td>
                    <td className={cn('tabular px-4 py-2 text-right font-medium', l.profit >= 0 ? 'text-ok' : 'text-bad')}>
                      {formatCurrency(l.profit)}
                    </td>
                    <td className={cn('tabular px-4 py-2 text-right', (l.unit_profit ?? 0) >= 0 ? 'text-muted' : 'text-bad')}>
                      {l.unit_profit === null ? '—' : formatCurrency(l.unit_profit)}
                    </td>
                    <td className={cn('tabular px-4 py-2 text-right', (l.margin_percent ?? 0) < 0 ? 'text-bad' : 'text-muted')}>
                      {l.margin_percent === null ? '—' : `${l.margin_percent} %`}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
            {resultats.length > 0 ? (
              <tfoot>
                <tr className="border-t-2 border-line bg-bg font-semibold text-ink">
                  <td className="px-4 py-2" colSpan={3}>
                    Total {actif ? 'de la recherche' : ''} ({formatNumber(resultats.length)} article(s))
                  </td>
                  <td className="tabular px-4 py-2 text-right">{formatNumber(totaux.quantity)}</td>
                  <td />
                  <td className="tabular px-4 py-2 text-right">{formatCurrency(totaux.revenue)}</td>
                  <td className="tabular px-4 py-2 text-right">{formatCurrency(totaux.cost)}</td>
                  <td className={cn('tabular px-4 py-2 text-right', totaux.profit >= 0 ? 'text-ok' : 'text-bad')}>
                    {formatCurrency(totaux.profit)}
                  </td>
                  <td />
                  <td className="tabular px-4 py-2 text-right">{totaux.margin === null ? '—' : `${totaux.margin} %`}</td>
                </tr>
              </tfoot>
            ) : null}
          </table>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted">
          <span>{paginationInfo({ current_page: pageCourante, per_page: parPage, total })}</span>
          {lastPage > 1 ? (
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" disabled={pageCourante <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                Précédent
              </Button>
              <span>
                Page {pageCourante} / {lastPage}
              </span>
              <Button variant="outline" size="sm" disabled={pageCourante >= lastPage} onClick={() => setPage((p) => p + 1)}>
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      </CardBody>
    </Card>
  )
}
