import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronDown, ChevronRight, FileDown, Pencil, Table2 } from 'lucide-react'
import { Fragment, useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { cn, formatNumber } from '@/lib/utils'
import { usePricingCategories } from '../hooks'

interface CostRow {
  id: number
  sku: string
  name: string
  category: string | null
  total_quantity: number
  purchase_price: number | null
  /** Prix retenu : celui du dernier bon de réception, sinon celui de la fiche. */
  applied_purchase_price: number | null
  last_purchase_price: number | null
  last_purchase_at: string | null
  last_purchase_number: string | null
  last_purchase_supplier: string | null
  last_purchase_payment_status: 'paid' | 'partial' | 'unpaid' | null
  purchase_count: number
  detail_price: number | null
  semi_gros_price: number | null
  gros_price: number | null
  /** Marges des trois tarifs, exprimées sur le prix d'achat. */
  margin_detail: number | null
  margin_semi_gros: number | null
  margin_gros: number | null
}

interface CostList {
  data: CostRow[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

/** Filtre sur le prix d'achat : tous, sans aucun prix, jamais reçu par un bon, avec prix. */
type FiltrePrix = '' | 'sans' | 'sans_bon' | 'avec'

const FILTRES_PRIX: { valeur: FiltrePrix; libelle: string }[] = [
  { valeur: '', libelle: 'Tous les articles' },
  { valeur: 'sans', libelle: 'Sans aucun prix d’achat' },
  { valeur: 'sans_bon', libelle: 'Jamais reçus par un bon (prix de la fiche)' },
  { valeur: 'avec', libelle: 'Avec un prix d’achat' },
]

interface PurchaseLine {
  reference: string
  date: string
  supplier: string | null
  warehouse: string | null
  quantity: number
  unit_price: number
  line_total: number
}

interface PurchaseDetail {
  purchase_price: number | null
  cost: { cost: number; source: 'cmup' | 'purchase_price'; quantity: number }
  sale_price: number | null
  history: PurchaseLine[]
}

function money(v: number): string {
  return v.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function messageErreur(e: unknown, repli: string): string {
  if (e && typeof e === 'object' && 'response' in e) {
    const r = (e as { response?: { data?: { message?: string } } }).response
    if (r?.data?.message) return r.data.message
  }
  return repli
}

/**
 * Un tarif et sa marge sur le prix d'achat.
 *
 * La marge est exprimée en pourcentage du prix payé — un article acheté 16 et
 * vendu 28,80 affiche +80 %. C'est le coefficient manipulé au quotidien, pas
 * la part du prix de vente. Négative, elle passe en rouge : c'est une vente à
 * perte, pas une marge faible.
 */
function CelluleTarif({ montant, marge }: { montant: number | null; marge: number | null }) {
  return (
    <td className="tabular px-4 py-3 text-right">
      <span className={montant !== null ? 'text-ink' : 'text-muted'}>
        {montant !== null ? money(montant) : '—'}
      </span>
      <span
        className={cn(
          'block text-xs font-medium',
          marge === null ? 'text-faint' : marge < 0 ? 'text-bad' : 'text-ok',
        )}
      >
        {marge === null ? '—' : `${marge >= 0 ? '+' : ''}${marge.toFixed(0)} %`}
      </span>
    </td>
  )
}

/** Historique des réceptions, déplié sous la ligne de l'article. */
function HistoriqueAchats({ productId }: { productId: number }) {
  const { data, isLoading } = useQuery<PurchaseDetail>({
    queryKey: ['purchase-history', productId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: PurchaseDetail }>(
        `/products/${productId}/purchase-history`,
      )
      return r.data
    },
  })

  if (isLoading) return <p className="px-4 py-3 text-sm text-muted">Chargement de l’historique…</p>
  if (!data) return null

  if (data.history.length === 0) {
    return (
      <p className="px-4 py-3 text-sm text-muted">
        Aucune réception enregistrée pour cet article. Son coût vient donc du prix d’achat de la
        fiche, pas d’un achat constaté.
      </p>
    )
  }

  return (
    <div className="px-4 py-3">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-line text-left text-muted">
            <th className="py-2 pr-4 font-medium">Bon</th>
            <th className="py-2 pr-4 font-medium">Date</th>
            <th className="py-2 pr-4 font-medium">Fournisseur</th>
            <th className="py-2 pr-4 font-medium">Lieu</th>
            <th className="py-2 pr-4 text-right font-medium">Quantité</th>
            <th className="py-2 pr-4 text-right font-medium">Prix unitaire</th>
            <th className="py-2 text-right font-medium">Total</th>
          </tr>
        </thead>
        <tbody>
          {data.history.map((l, i) => (
            <tr key={`${l.reference}-${i}`} className="border-b border-line last:border-0">
              <td className="mono py-2 pr-4 text-muted">{l.reference}</td>
              <td className="py-2 pr-4 text-muted">
                {new Date(l.date).toLocaleDateString('fr-FR')}
              </td>
              <td className="py-2 pr-4 text-ink">{l.supplier ?? '—'}</td>
              <td className="mono py-2 pr-4 text-muted">{l.warehouse ?? '—'}</td>
              <td className="tabular py-2 pr-4 text-right text-muted">{formatNumber(l.quantity)}</td>
              <td className="tabular py-2 pr-4 text-right font-medium text-ink">
                {money(l.unit_price)}
              </td>
              <td className="tabular py-2 text-right text-muted">{money(l.line_total)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

/**
 * Prix d'achat des articles : ce qui est déclaré, ce qui est constaté.
 *
 * Le prix d'achat de la fiche est une déclaration ; les réceptions sont des
 * faits. Les mettre côte à côte est le seul
 * moyen de voir qu'une fiche est restée sur un prix périmé.
 */
export function PurchasePricesPage() {
  const can = usePermission()
  const peutModifier = can('product.set_price')
  const qc = useQueryClient()

  const [page, setPage] = useState(1)
  const [parPage, setParPage] = useState(50)
  const [recherche, setRecherche] = useState('')
  const [categorie, setCategorie] = useState('')
  const [filtrePrix, setFiltrePrix] = useState<FiltrePrix>('')
  const [tri, setTri] = useState<SortState>({ sort: 'name', direction: 'asc' })
  const [deplie, setDeplie] = useState<number | null>(null)
  const [exportEnCours, setExportEnCours] = useState<'xlsx' | 'pdf' | null>(null)

  const [enEdition, setEnEdition] = useState<CostRow | null>(null)
  const [saisie, setSaisie] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)
  const [succes, setSucces] = useState<string | null>(null)

  const { data: categories = [] } = usePricingCategories()

  // Les memes filtres servent a la liste et aux exports : le fichier
  // telecharge est ce que l'ecran montre.
  const filtres = {
    search: recherche || undefined,
    category_id: categorie || undefined,
    prix_achat: filtrePrix || undefined,
    sort: tri.sort,
    direction: tri.direction,
  }

  const { data, isLoading } = useQuery<CostList>({
    queryKey: ['purchase-prices', page, parPage, filtres],
    queryFn: async () => {
      const { data: r } = await api.get<CostList>('/product-costs', {
        params: { page, per_page: parPage, ...filtres },
      })
      return r
    },
  })

  async function exporter(format: 'xlsx' | 'pdf') {
    setExportEnCours(format)
    try {
      await downloadFile('/product-costs/export', `IGOUTECH_prix-achat.${format}`, { ...filtres, format })
    } finally {
      setExportEnCours(null)
    }
  }

  const enregistrer = useMutation({
    mutationFn: async ({ id, prix }: { id: number; prix: number }) => {
      await ensureCsrfCookie()
      const { data: r } = await api.patch<{ data: { sku: string; purchase_price: number } }>(
        `/products/${id}/purchase-price`,
        { purchase_price: prix },
      )
      return r.data
    },
    onSuccess: (r) => {
      setEnEdition(null)
      setErreur(null)
      setSucces(`Prix d’achat de ${r.sku} porté à ${money(r.purchase_price)} DH.`)
      qc.invalidateQueries({ queryKey: ['purchase-prices'] })
      qc.invalidateQueries({ queryKey: ['purchase-history'] })
    },
    onError: (e) => {
      setErreur(messageErreur(e, 'Modification impossible.'))
    },
  })

  const rows = data?.data ?? []
  const meta = data?.meta

  function ouvrirEdition(row: CostRow) {
    setErreur(null)
    setSucces(null)
    setSaisie(row.purchase_price !== null ? String(row.purchase_price) : '')
    setEnEdition(row)
  }

  const prixSaisi = Number(saisie.replace(',', '.'))
  const saisieValide = Number.isFinite(prixSaisi) && prixSaisi >= 0

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Prix d’achat</h1>
        <p className="text-sm text-muted">
          Le prix du dernier bon de réception, réglé ou non, et ce que rapporte chacun des trois
          tarifs sur ce prix-là.
        </p>
      </div>

      {succes ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">{succes}</p>
      ) : null}
      {erreur && enEdition === null ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
      ) : null}

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-4">
          <Field label="Rechercher" htmlFor="pa-q">
            <Input
              id="pa-q"
              placeholder="Référence ou nom…"
              value={recherche}
              onChange={(e) => {
                setRecherche(e.target.value)
                setPage(1)
              }}
            />
          </Field>
          <Field label="Catégorie" htmlFor="pa-cat">
            <Select
              id="pa-cat"
              value={categorie}
              onChange={(e) => {
                setCategorie(e.target.value)
                setPage(1)
              }}
            >
              <option value="">Toutes</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </Select>
          </Field>
          {/* Les articles sans prix d'achat sont ceux dont les marges ne
              veulent rien dire : les isoler est le premier pas pour les
              renseigner. */}
          <Field label="Prix d’achat" htmlFor="pa-prix">
            <Select
              id="pa-prix"
              value={filtrePrix}
              onChange={(e) => {
                setFiltrePrix(e.target.value as FiltrePrix)
                setPage(1)
              }}
            >
              {FILTRES_PRIX.map((f) => (
                <option key={f.valeur} value={f.valeur}>
                  {f.libelle}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Lignes par page" htmlFor="pa-par-page">
            <Select
              id="pa-par-page"
              value={parPage}
              onChange={(e) => {
                setParPage(Number(e.target.value))
                setPage(1)
              }}
            >
              {[20, 50, 100].map((n) => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </Select>
          </Field>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Articles"
          hint={meta ? `${formatNumber(meta.total)} article(s)` : undefined}
          action={
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={exportEnCours !== null}
                onClick={() => exporter('xlsx')}
              >
                <Table2 className="h-4 w-4" />
                {exportEnCours === 'xlsx' ? 'Export…' : 'Excel'}
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={exportEnCours !== null}
                onClick={() => exporter('pdf')}
              >
                <FileDown className="h-4 w-4" />
                {exportEnCours === 'pdf' ? 'Export…' : 'PDF'}
              </Button>
            </div>
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <SortableTh field="sku" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4">Référence</SortableTh>
                    <SortableTh field="name" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4">Article</SortableTh>
                    <SortableTh field="total_quantity" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4 text-right" align="right">Stock</SortableTh>
                    <SortableTh field="purchase_price" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4 text-right" align="right">Prix d’achat</SortableTh>
                    <SortableTh field="last_purchase_at" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4">Dernier bon</SortableTh>
                    <SortableTh field="detail_price" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4 text-right" align="right">Détail</SortableTh>
                    <SortableTh field="semi_gros_price" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4 text-right" align="right">Demi-gros</SortableTh>
                    <SortableTh field="gros_price" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4 text-right" align="right">Gros</SortableTh>
                    <SortableTh field="purchase_count" current={tri} onSort={(t) => { setTri(t); setPage(1) }} className="px-4">Achats</SortableTh>
                  </tr>
                </thead>
                <tbody>
                  {rows.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="px-4 py-8 text-center text-muted">
                        Aucun article ne correspond à ces filtres.
                      </td>
                    </tr>
                  ) : null}
                  {rows.map((row) => {
                    // Le prix retenu vient du dernier bon quand il existe :
                    // c'est celui qui vient d'etre debourse. Sans reception,
                    // la fiche reste la seule source connue, et on le dit.
                    const recu = row.last_purchase_price !== null
                    const achat = row.applied_purchase_price

                    return (
                      // La cle vit sur le fragment : ce sont lui et non les
                      // <tr> qui sont les enfants directs de la liste.
                      <Fragment key={row.id}>
                        <tr className="border-b border-line">
                          <td className="mono px-4 py-3 text-muted">
                            <Link to={`/articles/${row.id}`} className="hover:text-sky hover:underline">
                              {row.sku}
                            </Link>
                          </td>
                          <td className="px-4 py-3 text-ink">{row.name}</td>
                          <td className="tabular px-4 py-3 text-right text-muted">
                            {formatNumber(row.total_quantity)}
                          </td>
                          <td className="tabular px-4 py-3 text-right">
                            {achat === null ? (
                              <Badge tone="bad">Aucun prix</Badge>
                            ) : (
                              <span className="font-semibold text-ink">{money(achat)}</span>
                            )}
                            {peutModifier ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                className="ml-1"
                                onClick={() => ouvrirEdition(row)}
                                aria-label={`Modifier le prix d'achat de ${row.sku}`}
                              >
                                <Pencil className="h-3.5 w-3.5" />
                              </Button>
                            ) : null}
                            {recu || achat === null ? null : (
                              <span className="block text-xs font-normal text-faint">
                                fiche article
                              </span>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            {recu ? (
                              <div className="flex items-center gap-2">
                                <div>
                                  <span className="mono block text-xs text-muted">
                                    {row.last_purchase_number ?? '—'}
                                  </span>
                                  <span className="block text-xs text-faint">
                                    {row.last_purchase_at
                                      ? new Date(row.last_purchase_at).toLocaleDateString('fr-FR')
                                      : '—'}
                                    {row.last_purchase_supplier
                                      ? ` · ${row.last_purchase_supplier}`
                                      : ''}
                                  </span>
                                </div>
                                {/* Le reglement du fournisseur ne change pas le
                                    prix d'achat : la marchandise a ete recue a
                                    ce prix. L'information reste utile au suivi. */}
                                {row.last_purchase_payment_status === 'paid' ? (
                                  <Badge tone="ok">payé</Badge>
                                ) : row.last_purchase_payment_status === 'partial' ? (
                                  <Badge tone="warn">partiel</Badge>
                                ) : (
                                  <Badge tone="warn">non payé</Badge>
                                )}
                              </div>
                            ) : (
                              <span className="text-xs text-faint">jamais reçu par un bon</span>
                            )}
                          </td>
                          <CelluleTarif montant={row.detail_price} marge={row.margin_detail} />
                          <CelluleTarif montant={row.semi_gros_price} marge={row.margin_semi_gros} />
                          <CelluleTarif montant={row.gros_price} marge={row.margin_gros} />
                          <td className="px-4 py-3">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setDeplie(deplie === row.id ? null : row.id)}
                            >
                              {deplie === row.id ? (
                                <ChevronDown className="h-4 w-4" />
                              ) : (
                                <ChevronRight className="h-4 w-4" />
                              )}
                              Historique
                            </Button>
                          </td>
                        </tr>
                        {deplie === row.id ? (
                          <tr className="border-b border-line bg-bg">
                            <td colSpan={9} className="p-0">
                              <HistoriqueAchats productId={row.id} />
                            </td>
                          </tr>
                        ) : null}
                      </Fragment>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {meta ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <span>{paginationInfo(meta)}</span>
          {meta.last_page > 1 ? (
            <div className="flex items-center gap-2">
              <span>
                Page {meta.current_page} / {meta.last_page}
              </span>
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                Précédent
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      ) : null}

      <ConfirmDialog
        open={enEdition !== null}
        title="Modifier le prix d’achat"
        message={
          enEdition === null ? (
            ''
          ) : (
            <div className="space-y-3">
              <p className="text-sm text-ink">
                <span className="mono">{enEdition.sku}</span> — {enEdition.name}
              </p>
              <Field label="Nouveau prix d’achat (DH)" htmlFor="pa-nouveau">
                <Input
                  id="pa-nouveau"
                  type="number"
                  min={0}
                  step="0.01"
                  value={saisie}
                  onChange={(e) => setSaisie(e.target.value)}
                />
              </Field>
              <p className="text-xs text-muted">
                Ancien prix :{' '}
                {enEdition.purchase_price ? `${money(enEdition.purchase_price)} DH` : 'aucun'}
                {enEdition.last_purchase_number
                  ? ` · dernier bon ${enEdition.last_purchase_number} à ${money(
                      enEdition.last_purchase_price ?? 0,
                    )} DH`
                  : ' · aucune réception'}{' '}
                · Détail :{' '}
                {enEdition.detail_price !== null ? `${money(enEdition.detail_price)} DH` : '—'}
              </p>
              <p className="text-xs text-faint">
                La prochaine réception écrasera cette saisie : c’est le prix du bon qui fait foi.
              </p>
              {/* Le refus vient du serveur, mais l'annoncer avant evite un
                  aller-retour pour une regle que l'on connait deja. */}
              {enEdition.total_quantity === 0 &&
              enEdition.detail_price !== null &&
              saisieValide &&
              prixSaisi > enEdition.detail_price ? (
                <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
                  Ce prix dépasse le prix de vente ({money(enEdition.detail_price)} DH) : la vente
                  se ferait à perte. Le serveur refusera.
                </p>
              ) : null}
              {erreur ? (
                <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                  {erreur}
                </p>
              ) : null}
            </div>
          )
        }
        confirmLabel="Enregistrer"
        isPending={enregistrer.isPending}
        onCancel={() => {
          setEnEdition(null)
          setErreur(null)
        }}
        onConfirm={() => {
          if (enEdition && saisieValide) {
            enregistrer.mutate({ id: enEdition.id, prix: prixSaisi })
          }
        }}
      />
    </div>
  )
}
