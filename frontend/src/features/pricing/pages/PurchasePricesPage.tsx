import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronDown, ChevronRight, Pencil } from 'lucide-react'
import { Fragment, useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatNumber } from '@/lib/utils'
import { usePricingCategories } from '../hooks'

interface CostRow {
  id: number
  sku: string
  name: string
  category: string | null
  total_quantity: number
  purchase_price: number | null
  cmup: number
  detail_price: number | null
  margin_percent: number | null
  below_cost: boolean
}

interface CostList {
  data: CostRow[]
  meta: { current_page: number; last_page: number; total: number }
}

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
 * faits, et le coût moyen en découle. Les mettre côte à côte est le seul
 * moyen de voir qu'une fiche est restée sur un prix périmé.
 */
export function PurchasePricesPage() {
  const can = usePermission()
  const peutModifier = can('product.set_price')
  const qc = useQueryClient()

  const [page, setPage] = useState(1)
  const [recherche, setRecherche] = useState('')
  const [categorie, setCategorie] = useState('')
  const [deplie, setDeplie] = useState<number | null>(null)

  const [enEdition, setEnEdition] = useState<CostRow | null>(null)
  const [saisie, setSaisie] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)
  const [succes, setSucces] = useState<string | null>(null)

  const { data: categories = [] } = usePricingCategories()

  const { data, isLoading } = useQuery<CostList>({
    queryKey: ['purchase-prices', page, recherche, categorie],
    queryFn: async () => {
      const { data: r } = await api.get<CostList>('/product-costs', {
        params: {
          page,
          per_page: 50,
          search: recherche || undefined,
          category_id: categorie || undefined,
        },
      })
      return r
    },
  })

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
          Le prix déclaré sur la fiche, le coût réellement utilisé, et les réceptions qui le
          justifient.
        </p>
      </div>

      {succes ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">{succes}</p>
      ) : null}
      {erreur && enEdition === null ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
      ) : null}

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-3">
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
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Articles"
          hint={meta ? `${formatNumber(meta.total)} article(s)` : undefined}
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-3 font-medium">Référence</th>
                    <th className="px-4 py-3 font-medium">Article</th>
                    <th className="px-4 py-3 text-right font-medium">Stock</th>
                    <th className="px-4 py-3 text-right font-medium">Prix d’achat</th>
                    <th className="px-4 py-3 text-right font-medium">Coût utilisé</th>
                    <th className="px-4 py-3 text-right font-medium">Prix de vente</th>
                    <th className="px-4 py-3 text-right font-medium">Marge</th>
                    <th className="px-4 py-3 font-medium">Achats</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => {
                    // Le cout vient du stock quand il y en a ; sinon c'est le
                    // prix de la fiche qui sert, et il n'y a rien a comparer.
                    const coutDuStock = row.total_quantity > 0
                    const ecart =
                      coutDuStock &&
                      row.purchase_price !== null &&
                      row.purchase_price > 0 &&
                      Math.abs(row.cmup - row.purchase_price) > 0.005

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
                            <span className={row.purchase_price ? 'text-ink' : 'text-muted'}>
                              {row.purchase_price ? money(row.purchase_price) : '—'}
                            </span>
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
                          </td>
                          <td className="tabular px-4 py-3 text-right font-semibold text-ink">
                            {money(row.cmup)}
                            <span className="block text-xs font-normal text-faint">
                              {coutDuStock ? 'CMUP' : 'prix d’achat'}
                            </span>
                          </td>
                          <td className="tabular px-4 py-3 text-right text-muted">
                            {row.detail_price !== null ? money(row.detail_price) : '—'}
                          </td>
                          <td className="px-4 py-3 text-right">
                            {row.margin_percent === null ? (
                              <span className="text-muted">—</span>
                            ) : row.below_cost ? (
                              <Badge tone="bad">à perte</Badge>
                            ) : (
                              <span
                                className={cn(
                                  'tabular',
                                  ecart ? 'text-warn' : 'text-ink',
                                )}
                              >
                                {row.margin_percent} %
                              </span>
                            )}
                          </td>
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
                            <td colSpan={8} className="p-0">
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

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between">
          <Button variant="outline" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Précédent
          </Button>
          <span className="text-sm text-muted">
            Page {meta.current_page} sur {meta.last_page}
          </span>
          <Button
            variant="outline"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Suivant
          </Button>
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
                {enEdition.purchase_price ? `${money(enEdition.purchase_price)} DH` : 'aucun'} ·
                Coût utilisé : {money(enEdition.cmup)} DH · Prix de vente :{' '}
                {enEdition.detail_price !== null ? `${money(enEdition.detail_price)} DH` : '—'}
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
