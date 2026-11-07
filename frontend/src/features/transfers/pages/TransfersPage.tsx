import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Pencil, Plus, Printer, Save, Send, Trash2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { openPdf } from '@/lib/download'
import type { Paginated } from '@/types'
import { formatDateHeure } from '@/lib/utils'
import { DemandesEnAttente } from '../components/DemandesEnAttente'

interface TransferRow {
  id: number
  reference: string
  from: string | null
  to: string | null
  status: string
  status_name: string | null
  lines_count: number
  /** Articles transferes, dans l'ordre des lignes du bon. */
  products: {
    sku: string | null
    name: string | null
    quantity: number
    /** Quantite reellement recue, `null` tant que le bon est en transit. */
    quantity_received: number | null
  }[]
  /** Horodatage de la creation du transfert, avant tout envoi. */
  created_at: string | null
  sent_at: string | null
  received_at: string | null
  days_in_transit: number | null
  is_late: boolean
  /** Brouillon modifiable par celui qui regarde (auteur ou direction). */
  can_edit?: boolean
}

interface TransferDetail {
  id: number
  reference: string
  from: string | null
  to: string | null
  from_warehouse_id?: number
  to_warehouse_id?: number
  status: string
  can_edit?: boolean
  sent_at: string | null
  received_at: string | null
  note: string | null
  lines: { id: number; product_id?: number; sku: string | null; name: string | null; quantity_sent: number; quantity_received: number | null }[]
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
}

interface ProductOption {
  id: number
  sku: string
  name: string
}

const KEY = ['transfers'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/** Nombre d'articles nommes avant de basculer sur « et N autres ». */
const ARTICLES_VISIBLES = 3

/**
 * Les articles d'un transfert, en clair.
 *
 * « 4 lignes » ne dit pas ce qui a bouge ; c'est pourtant l'article qu'on a
 * en tete quand on cherche un transfert. Au-dela de trois, la liste
 * deborderait la ligne du tableau : on nomme les premiers et on compte le
 * reste, dont le detail reste accessible en ouvrant le bon.
 */
function ArticlesTransferes({
  produits,
}: {
  produits: {
    sku: string | null
    name: string | null
    quantity: number
    quantity_received: number | null
  }[]
}) {
  if (produits.length === 0) return <span className="text-faint">—</span>

  const nommes = produits.slice(0, ARTICLES_VISIBLES)
  const reste = produits.length - nommes.length

  return (
    <span
      className="block truncate"
      title={produits
        .map((p) => {
          const recu =
            p.quantity_received !== null && p.quantity_received !== p.quantity
              ? ` (reçu ${p.quantity_received})`
              : ''
          return `${p.quantity} × ${p.name ?? p.sku ?? '?'}${recu}`
        })
        .join('\n')}
    >
      {nommes.map((p, i) => {
        // Un ecart entre envoye et recu doit se voir sans ouvrir le bon :
        // c'est le seul cas qui demande une action.
        const ecart = p.quantity_received !== null && p.quantity_received !== p.quantity
        return (
          <span key={i}>
            {i > 0 ? ', ' : ''}
            <span className="text-ink">{p.name ?? p.sku ?? '—'}</span>
            <span className={ecart ? 'text-bad' : 'text-faint'}>
              {' ×'}
              {p.quantity}
              {ecart ? ` (reçu ${p.quantity_received})` : ''}
            </span>
          </span>
        )
      })}
      {reste > 0 ? <span className="text-faint"> et {reste} autre{reste > 1 ? 's' : ''}</span> : null}
    </span>
  )
}

/**
 * Transferts inter-lieux : liste avec alerte transit > 3 jours,
 * création (envoi) et réception avec saisie des écarts.
 */
export function TransfersPage() {
  const [detailId, setDetailId] = useState<number | null>(null)

  if (detailId !== null) {
    return <TransferDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  return <TransferList onOpen={setDetailId} />
}

function TransferList({ onOpen }: { onOpen: (id: number) => void }) {
  const can = usePermission()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  /** Brouillon ouvert dans le formulaire pour modification. */
  const [brouillon, setBrouillon] = useState<number | null>(null)

  const [recherche, setRecherche] = useState('')

  // Les filtres de la liste. « De DCHIERA vers VEH / H-1 » est la question
  // courante : elle demande les deux sens, pas un lieu unique.
  const [depuis, setDepuis] = useState(0)
  const [vers, setVers] = useState(0)
  const [statut, setStatut] = useState('')
  const [du, setDu] = useState('')
  const [au, setAu] = useState('')

  const { data: lieux = [] } = useWarehouseOptions()

  // La liste est paginee : filtrer les vingt lignes affichees laisserait
  // croire qu'un transfert n'existe pas parce qu'il est page 3.
  const rechercheRetardee = useDebouncedValue(recherche, 300)

  const filtres = {
    search: rechercheRetardee || undefined,
    from_warehouse_id: depuis || undefined,
    to_warehouse_id: vers || undefined,
    status: statut || undefined,
    date_from: du || undefined,
    date_to: au || undefined,
  }

  const filtreActif =
    depuis > 0 || vers > 0 || statut !== '' || du !== '' || au !== '' || recherche !== ''

  /** Change un filtre et revient en page 1 : la page 3 d'un autre filtre
   *  serait souvent vide, ce qui se lit comme « aucun resultat ». */
  function appliquer(action: () => void) {
    action()
    setPage(1)
  }

  const { data, isLoading } = useQuery<Paginated<TransferRow>>({
    queryKey: [...KEY, page, filtres],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<TransferRow>>('/transfers', {
        params: { page, ...filtres },
      })
      return r
    },
  })

  const transfers = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Transferts</h1>
          <p className="text-sm text-muted">Mouvements de marchandise entre lieux — la valeur voyage avec la marchandise.</p>
        </div>
        {can('transfer.create') && !creating ? (
          <Button onClick={() => { setBrouillon(null); setCreating(true) }}>
            <Plus className="h-4 w-4" />
            Nouveau transfert
          </Button>
        ) : null}
      </div>

      {creating ? (
        <CreateTransferPanel
          draftId={brouillon}
          onClose={() => {
            setCreating(false)
            setBrouillon(null)
          }}
        />
      ) : null}

      <DemandesEnAttente />

      {/* Filtres : le sens du mouvement d'abord, puis l'etat et la periode. */}
      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <Field label="Recherche" htmlFor="tr-recherche">
            <SearchInput
              id="tr-recherche"
              value={recherche}
              onChange={(v) => appliquer(() => setRecherche(v))}
              placeholder="Référence ou article…"
              className="w-full"
            />
          </Field>

          <Field label="Depuis le lieu" htmlFor="tr-depuis">
            <Select
              id="tr-depuis"
              value={depuis}
              onChange={(e) => appliquer(() => setDepuis(Number(e.target.value)))}
            >
              <option value={0}>Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Vers le lieu" htmlFor="tr-vers">
            <Select
              id="tr-vers"
              value={vers}
              onChange={(e) => appliquer(() => setVers(Number(e.target.value)))}
            >
              <option value={0}>Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="État" htmlFor="tr-statut">
            <Select
              id="tr-statut"
              value={statut}
              onChange={(e) => appliquer(() => setStatut(e.target.value))}
            >
              <option value="">Tous les états</option>
              <option value="draft">Brouillon</option>
              <option value="requested">Demandé</option>
              <option value="in_transit">En transit</option>
              <option value="received">Reçu</option>
              <option value="refused">Refusé</option>
              <option value="cancelled">Annulé</option>
            </Select>
          </Field>

          <Field label="Du" htmlFor="tr-du">
            <Input
              id="tr-du"
              type="date"
              value={du}
              onChange={(e) => appliquer(() => setDu(e.target.value))}
            />
          </Field>

          <Field label="Au" htmlFor="tr-au">
            <Input
              id="tr-au"
              type="date"
              value={au}
              onChange={(e) => appliquer(() => setAu(e.target.value))}
            />
          </Field>

          <div className="flex items-end xl:col-span-6">
            <Button
              variant="ghost"
              disabled={!filtreActif}
              onClick={() =>
                appliquer(() => {
                  setRecherche('')
                  setDepuis(0)
                  setVers(0)
                  setStatut('')
                  setDu('')
                  setAu('')
                })
              }
            >
              Effacer les filtres
            </Button>
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Historique"
          hint={
            meta
              ? `${meta.total} transfert(s)${
                  depuis > 0 || vers > 0
                    ? ` · ${lieux.find((w) => w.id === depuis)?.code ?? 'tous'} → ${
                        lieux.find((w) => w.id === vers)?.code ?? 'tous'
                      }`
                    : ''
                }`
              : undefined
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Trajet</th>
                  <th className="px-5 py-3 font-medium">Articles</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes</th>
                  <th className="px-5 py-3 font-medium">Envoyé le</th>
                  <th className="px-5 py-3 font-medium">Créé le</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {transfers.length === 0 ? (
                  <tr><td colSpan={8} className="px-5 py-8 text-center text-muted">
                    {recherche ? 'Aucun transfert ne correspond à cette recherche.' : 'Aucun transfert.'}
                  </td></tr>
                ) : (
                  transfers.map((t) => (
                    <tr key={t.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{t.reference}</td>
                      <td className="px-5 py-3 text-ink">{t.from} → {t.to}</td>
                      <td className="max-w-xs px-5 py-3 text-muted">
                        <ArticlesTransferes produits={t.products} />
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{t.lines_count}</td>
                      <td className="px-5 py-3 text-muted">{formatDateHeure(t.sent_at)}</td>
                      <td className="px-5 py-3 text-faint">{formatDateHeure(t.created_at)}</td>
                      <td className="px-5 py-3">
                        {/* Une demande, un refus ou une annulation n'avaient aucun
                            badge : la ligne affichait un statut vide. */}
                        {t.status === 'draft' ? <Badge tone="neutral">Brouillon</Badge> : null}
                        {t.status === 'requested' ? <Badge tone="warn">Demande en attente</Badge> : null}
                        {t.status === 'refused' ? <Badge tone="bad">Refusée</Badge> : null}
                        {t.status === 'cancelled' ? <Badge tone="neutral">Annulé</Badge> : null}
                        {t.status === 'received' ? <Badge tone="ok">Reçu</Badge> : null}
                        {t.status === 'in_transit' && !t.is_late ? <Badge tone="sky">En transit</Badge> : null}
                        {t.status === 'in_transit' && t.is_late ? (
                          <Badge tone="bad">En transit {t.days_in_transit} j ⚠</Badge>
                        ) : null}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="sm"
                            title="Imprimer le bon de transfert"
                            aria-label={`Imprimer le bon ${t.reference}`}
                            onClick={() => imprimerBonDeTransfert(t.id, t.reference)}
                          >
                            <Printer className="h-4 w-4" />
                          </Button>
                          {t.status === 'draft' && t.can_edit ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setBrouillon(t.id)
                                setCreating(true)
                                window.scrollTo({ top: 0, behavior: 'smooth' })
                              }}
                            >
                              <Pencil className="h-4 w-4" />
                              Modifier
                            </Button>
                          ) : (
                            <Button variant="ghost" size="sm" onClick={() => onOpen(t.id)}>
                              {t.status === 'in_transit' && can('transfer.receive') ? 'Réceptionner' : 'Consulter'}
                            </Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}

/**
 * Creation d'un transfert, ou reprise d'un brouillon.
 *
 * Deux sorties : « Enregistrer en brouillon » garde le bon sans toucher au
 * stock, modifiable a volonte ; « Envoyer » fait partir la marchandise et
 * attribue le numero TRN. Un brouillon repris se modifie, s'envoie ou se
 * supprime.
 */
function CreateTransferPanel({ onClose, draftId = null }: { onClose: () => void; draftId?: number | null }) {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [fromId, setFromId] = useState(0)
  const [toId, setToId] = useState(0)
  const [note, setNote] = useState('')
  const [lines, setLines] = useState<DraftLine[]>([])
  const [search, setSearch] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)
  const [confirmerSuppression, setConfirmerSuppression] = useState(false)

  // Le brouillon repris remplit le formulaire une fois charge.
  const { data: brouillon, isLoading: chargementBrouillon } = useQuery<TransferDetail>({
    queryKey: ['transfer', draftId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: TransferDetail }>(`/transfers/${draftId}`)
      return r.data
    },
    enabled: draftId !== null,
  })

  useEffect(() => {
    if (!brouillon) return
    setFromId(brouillon.from_warehouse_id ?? 0)
    setToId(brouillon.to_warehouse_id ?? 0)
    setNote(brouillon.note ?? '')
    setLines(
      brouillon.lines.map((l) => ({
        product_id: l.product_id ?? 0,
        sku: l.sku ?? '',
        name: l.name ?? '',
        quantity: l.quantity_sent,
      })),
    )
  }, [brouillon])

  const { data: options = [] } = useQuery<ProductOption[]>({
    queryKey: ['transfer-product-search', search],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', { params: { search, per_page: 20 } })
      return r.data
    },
    enabled: search.trim().length >= 2,
  })

  const corps = () => ({
    from_warehouse_id: fromId,
    to_warehouse_id: toId,
    note: note || null,
    lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
  })

  function termine() {
    qc.invalidateQueries({ queryKey: KEY })
    qc.invalidateQueries({ queryKey: ['stock'] })
    if (draftId !== null) qc.invalidateQueries({ queryKey: ['transfer', draftId] })
    onClose()
  }

  /** Envoi direct (nouveau bon), ou envoi du brouillon repris. */
  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      if (draftId !== null) {
        // Les dernieres modifications partent avec le bon.
        await api.put(`/transfers/${draftId}`, corps())
        await api.post(`/transfers/${draftId}/send`)
        return
      }
      await api.post('/transfers', corps())
    },
    onMutate: () => setErreur(null),
    onSuccess: termine,
    onError: (e) => setErreur(errorMessage(e, 'Envoi impossible (stock insuffisant ?).')),
  })

  /** Enregistrement en brouillon : rien ne bouge en stock. */
  const enregistrer = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      if (draftId !== null) {
        await api.put(`/transfers/${draftId}`, corps())
        return
      }
      await api.post('/transfers', { ...corps(), draft: true })
    },
    onMutate: () => setErreur(null),
    onSuccess: termine,
    onError: (e) => setErreur(errorMessage(e, 'Enregistrement impossible.')),
  })

  const supprimer = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.delete(`/transfers/${draftId}`)
    },
    onSuccess: termine,
    onError: (e) => setErreur(errorMessage(e, 'Suppression impossible.')),
  })

  const occupe = create.isPending || enregistrer.isPending || supprimer.isPending
  const pretAEnvoyer = fromId !== 0 && toId !== 0 && lines.length > 0 && lines.every((l) => l.quantity > 0)

  return (
    <Card>
      <CardHeader
        title={draftId !== null ? `Brouillon ${brouillon?.reference ?? ''}` : 'Nouveau transfert'}
        hint={draftId !== null ? 'Modifiable tant qu’il n’est pas envoyé ; le stock ne bouge qu’à l’envoi.' : undefined}
      />
      <CardBody className="space-y-4">
        {chargementBrouillon ? <p className="text-sm text-muted">Chargement du brouillon…</p> : null}
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Lieu source" htmlFor="tr-from">
            <Select id="tr-from" value={fromId || ''} onChange={(e) => setFromId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Lieu destination" htmlFor="tr-to">
            <Select id="tr-to" value={toId || ''} onChange={(e) => setToId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.filter((w) => w.id !== fromId).map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Note" htmlFor="tr-note">
            <Input id="tr-note" value={note} onChange={(e) => setNote(e.target.value)} />
          </Field>
        </div>

        <div className="space-y-2 rounded-lg border border-line p-3">
          <Input placeholder="Rechercher un article à ajouter…" value={search} onChange={(e) => setSearch(e.target.value)} />
          {options.length > 0 && search.trim().length >= 2 ? (
            <ul className="max-h-40 overflow-auto rounded border border-line">
              {options.filter((o) => !lines.some((l) => l.product_id === o.id)).map((o) => (
                <li key={o.id}>
                  <button
                    type="button"
                    onClick={() => {
                      // Le dernier article ajoute passe en tete : c'est celui dont on
                      // saisit la quantite, il doit rester sous les yeux.
                      setLines((p) => [{ product_id: o.id, sku: o.sku, name: o.name, quantity: 1 }, ...p])
                      setSearch('')
                    }}
                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                  >
                    <span className="mono text-muted">{o.sku}</span>
                    <span className="text-ink">{o.name}</span>
                  </button>
                </li>
              ))}
            </ul>
          ) : null}

          {lines.map((l, i) => (
            <div key={l.product_id} className="flex items-center gap-2">
              <span className="mono w-28 text-sm text-muted">{l.sku}</span>
              <span className="flex-1 truncate text-sm text-ink">{l.name}</span>
              <Input
                type="number"
                min={1}
                value={l.quantity}
                onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))}
                className="w-24"
              />
              <Button
                variant="ghost"
                size="sm"
                className="text-bad hover:bg-bad-bg"
                onClick={() => setLines((p) => p.filter((_, j) => j !== i))}
                aria-label={`Retirer ${l.name}`}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          ))}
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article ajouté.</p> : null}
        </div>

        {erreur ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p> : null}

        <div className="flex flex-wrap items-center gap-2">
          {/* Deux sorties : garder le bon pour plus tard, ou faire partir la
              marchandise maintenant. */}
          <Button variant="outline" onClick={() => enregistrer.mutate()} disabled={occupe || !pretAEnvoyer}>
            <Save className="h-4 w-4" />
            {enregistrer.isPending ? 'Enregistrement…' : draftId !== null ? 'Enregistrer le brouillon' : 'Enregistrer en brouillon'}
          </Button>
          <Button onClick={() => create.mutate()} disabled={occupe || !pretAEnvoyer}>
            <Send className="h-4 w-4" />
            {create.isPending ? 'Envoi…' : 'Envoyer le transfert'}
          </Button>
          {draftId !== null ? (
            <Button variant="ghost" className="text-bad hover:bg-bad-bg" onClick={() => setConfirmerSuppression(true)} disabled={occupe}>
              <Trash2 className="h-4 w-4" />
              Supprimer le brouillon
            </Button>
          ) : null}
          <Button variant="ghost" onClick={onClose} disabled={occupe}>Annuler</Button>
        </div>

        <ConfirmDialog
          open={confirmerSuppression}
          title="Supprimer le brouillon"
          message={<>Le brouillon sera effacé. Aucun stock n’a bougé.</>}
          confirmLabel="Supprimer"
          isPending={supprimer.isPending}
          onConfirm={() => supprimer.mutate()}
          onCancel={() => setConfirmerSuppression(false)}
        />
      </CardBody>
    </Card>
  )
}

/**
 * Télécharge le bon de transfert.
 *
 * La référence sert de nom de fichier quand elle est connue : un dossier plein
 * de « transfert.pdf » serait inexploitable.
 */
function imprimerBonDeTransfert(id: number, reference?: string): void {
  // Le bon s'ouvre dans un onglet pour etre imprime ; un echec se dit, au lieu
  // de laisser croire que le bouton ne fait rien.
  openPdf(`/transfers/${id}/pdf`, `${reference ?? `transfert-${id}`}.pdf`).catch(() => {
    window.alert("Le bon de transfert n'a pas pu être généré. Réessayez dans un instant.")
  })
}

function TransferDetailView({ id, onBack }: { id: number; onBack: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()

  const { data: transfer } = useQuery<TransferDetail>({
    queryKey: [...KEY, 'detail', id],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: TransferDetail }>(`/transfers/${id}`)
      return r.data
    },
  })

  const [received, setReceived] = useState<Record<number, number>>({})

  const receive = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/transfers/${id}/receive`, { quantities: received })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
    },
  })

  const inTransit = transfer?.status === 'in_transit'

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">Transfert {transfer?.reference}</h1>
            <p className="text-sm text-muted">
              {transfer?.from} → {transfer?.to} · envoyé le {transfer?.sent_at}{' '}
              {transfer?.status === 'received' ? (
                <Badge tone="ok">Reçu</Badge>
              ) : transfer?.status === 'draft' ? (
                <Badge tone="neutral">Brouillon</Badge>
              ) : (
                <Badge tone="sky">En transit</Badge>
              )}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => imprimerBonDeTransfert(id, transfer?.reference)}
          >
            <Printer className="h-4 w-4" />
            Imprimer le bon
          </Button>
          {inTransit && can('transfer.receive') ? (
            <Button onClick={() => receive.mutate()} disabled={receive.isPending}>
              {receive.isPending ? 'Validation…' : 'Valider la réception'}
            </Button>
          ) : null}
        </div>
      </div>

      {receive.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(receive.error, 'Réception impossible.')}
        </p>
      ) : null}

      <Card>
        <CardHeader title={inTransit ? 'Réception — saisir les quantités reçues' : 'Lignes'} />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Envoyé</th>
                <th className="px-5 py-3 text-right font-medium">Reçu</th>
              </tr>
            </thead>
            <tbody>
              {(transfer?.lines ?? []).map((l) => (
                <tr key={l.id} className="border-b border-line last:border-0">
                  <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                  <td className="px-5 py-3 text-ink">{l.name}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.quantity_sent}</td>
                  <td className="px-5 py-3 text-right">
                    {inTransit && can('transfer.receive') ? (
                      <Input
                        type="number"
                        min={0}
                        max={l.quantity_sent}
                        value={received[l.id] ?? l.quantity_sent}
                        onChange={(e) => setReceived((p) => ({ ...p, [l.id]: Number(e.target.value) }))}
                        className="ml-auto w-24"
                      />
                    ) : (
                      <span className="tabular text-ink">{l.quantity_received ?? '—'}</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
