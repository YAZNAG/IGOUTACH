import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { useWarehouseOptions } from '@/features/access/hooks'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { usePermission } from '@/hooks/usePermission'
import { cn, formatDateHeure, formatNumber } from '@/lib/utils'
import { useMovements, useMovementTypes } from '../hooks'

const PER_PAGE = [25, 50, 100, 200]

/**
 * Journal de tous les mouvements de stock, tous articles et tous lieux.
 *
 * La fiche d'un article montre ses propres mouvements ; il faut l'ouvrir un
 * par un. Ce journal part de la période et de la recherche, et l'article
 * devient un filtre — c'est la question « qu'est-ce qui a bougé cette
 * semaine » qu'aucun écran ne traitait.
 *
 * Le cloisonnement suit celui du stock : sans « stock.view_global », on ne
 * voit que les mouvements de son propre lieu, quel que soit le filtre.
 */
export function MovementsJournalPage() {
  const can = usePermission()
  const vueGlobale = can('stock.view_global')

  const [recherche, setRecherche] = useState('')
  const [lieu, setLieu] = useState(0)
  const [type, setType] = useState('')
  const [du, setDu] = useState('')
  const [au, setAu] = useState('')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(50)
  const [sort, setSort] = useState<SortState>({ sort: 'created_at', direction: 'desc' })

  // Le journal est paginé par le serveur : filtrer les lignes affichées
  // laisserait croire qu'un mouvement n'existe pas parce qu'il est page 4.
  const rechercheRetardee = useDebouncedValue(recherche, 300)

  const { data: lieux = [] } = useWarehouseOptions()
  const { data: types = [] } = useMovementTypes()

  /** Tout changement de filtre repart de la première page. */
  function appliquer<T>(setter: (v: T) => void) {
    return (valeur: T) => {
      setter(valeur)
      setPage(1)
    }
  }

  const { data, isLoading, isError } = useMovements(
    {
      search: rechercheRetardee || undefined,
      warehouse_id: lieu || undefined,
      type: type || undefined,
      from: du || undefined,
      to: au || undefined,
      page,
      per_page: perPage,
      sort: sort.sort,
      direction: sort.direction,
    },
    true,
  )

  const lignes = data?.data ?? []
  const meta = data?.meta
  const filtreActif = rechercheRetardee !== '' || lieu > 0 || type !== '' || du !== '' || au !== ''

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Historique des mouvements</h1>
        <p className="text-sm text-muted">
          Toutes les entrées, sorties et transferts de stock, article par article.
          {vueGlobale ? null : ' Limité à votre lieu.'}
        </p>
      </div>

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <div className="xl:col-span-2">
            <Field label="Article" htmlFor="jm-recherche">
              <SearchInput
                id="jm-recherche"
                value={recherche}
                onChange={appliquer(setRecherche)}
                placeholder="Référence ou désignation…"
                className="w-full"
              />
            </Field>
          </div>

          <Field label="Lieu" htmlFor="jm-lieu">
            <Select
              id="jm-lieu"
              value={lieu}
              onChange={(e) => appliquer(setLieu)(Number(e.target.value))}
              disabled={!vueGlobale}
            >
              <option value={0}>Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Type" htmlFor="jm-type">
            <Select id="jm-type" value={type} onChange={(e) => appliquer(setType)(e.target.value)}>
              <option value="">Tous les types</option>
              {types.map((t) => (
                <option key={t.code} value={t.code}>
                  {t.name}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Du" htmlFor="jm-du">
            <Input id="jm-du" type="date" value={du} onChange={(e) => appliquer(setDu)(e.target.value)} />
          </Field>

          <Field label="Au" htmlFor="jm-au">
            <Input id="jm-au" type="date" value={au} onChange={(e) => appliquer(setAu)(e.target.value)} />
          </Field>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Mouvements"
          hint={meta ? `${formatNumber(meta.total)} mouvement(s)${filtreActif ? ' (filtrés)' : ''}` : undefined}
          action={
            <div className="flex items-center gap-2">
              <Select
                value={perPage}
                onChange={(e) => appliquer(setPerPage)(Number(e.target.value))}
                className="w-28"
                aria-label="Lignes par page"
              >
                {PER_PAGE.map((n) => (
                  <option key={n} value={n}>
                    {n} / page
                  </option>
                ))}
              </Select>
              <Button
                variant="ghost"
                size="sm"
                disabled={!filtreActif}
                onClick={() => {
                  setRecherche('')
                  setLieu(0)
                  setType('')
                  setDu('')
                  setAu('')
                  setPage(1)
                }}
              >
                Effacer
              </Button>
            </div>
          }
        />
        <CardBody className="p-0">
          {isError ? (
            <p className="p-5 text-sm text-bad">Impossible de charger le journal des mouvements.</p>
          ) : isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <SortableTh field="created_at" current={sort} onSort={appliquer(setSort)}>
                      Date et heure
                    </SortableTh>
                    <th className="px-5 py-3 font-medium">Article</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 font-medium">Type</th>
                    <SortableTh
                      field="quantity"
                      current={sort}
                      onSort={appliquer(setSort)}
                      className="text-right"
                      align="right"
                    >
                      Qté
                    </SortableTh>
                    <SortableTh
                      field="balance_after"
                      current={sort}
                      onSort={appliquer(setSort)}
                      className="text-right"
                      align="right"
                    >
                      Solde
                    </SortableTh>
                    <th className="px-5 py-3 font-medium">Document</th>
                    <th className="px-5 py-3 font-medium">Auteur</th>
                  </tr>
                </thead>
                <tbody>
                  {lignes.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="px-5 py-10 text-center text-muted">
                        {filtreActif
                          ? 'Aucun mouvement ne correspond à ces filtres.'
                          : 'Aucun mouvement enregistré.'}
                      </td>
                    </tr>
                  ) : (
                    lignes.map((m) => (
                      <tr key={m.id} className="border-b border-line last:border-0">
                        <td className="whitespace-nowrap px-5 py-3 text-muted">
                          {formatDateHeure(m.created_at)}
                        </td>
                        <td className="px-5 py-3">
                          {/* La fiche article est à un clic : c'est là qu'on
                              va ensuite pour comprendre une ligne. */}
                          <Link
                            to={`/articles/${m.product_id}?tab=movements`}
                            className="text-ink hover:text-sky hover:underline"
                          >
                            <span className="mono text-xs text-faint">{m.sku}</span> {m.name}
                          </Link>
                        </td>
                        <td className="px-5 py-3 text-muted">
                          {m.warehouse_code ?? <span className="text-faint">—</span>}
                        </td>
                        <td className="px-5 py-3">
                          <Badge tone={m.quantity >= 0 ? 'ok' : 'bad'}>{m.type}</Badge>
                        </td>
                        {/* Le signe vient de la quantite, deja signee en base :
                            le deduire du type se tromperait sur un ajustement
                            negatif. */}
                        <td
                          className={cn(
                            'tabular px-5 py-3 text-right font-medium',
                            m.quantity >= 0 ? 'text-ok' : 'text-bad',
                          )}
                        >
                          {m.quantity > 0 ? `+${formatNumber(m.quantity)}` : formatNumber(m.quantity)}
                        </td>
                        <td className="tabular px-5 py-3 text-right text-ink">
                          {formatNumber(m.balance_after)}
                        </td>
                        <td className="px-5 py-3 text-muted">
                          {m.document?.label ?? <span className="text-faint">—</span>}
                        </td>
                        <td className="px-5 py-3 text-ink">
                          {m.user ?? <span className="text-faint">Non attribué</span>}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {meta ? (
        <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted">
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
    </div>
  )
}
