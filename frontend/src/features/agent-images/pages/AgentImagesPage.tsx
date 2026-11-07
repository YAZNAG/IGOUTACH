import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, ExternalLink, ImageOff, Images, Play, RefreshCw, Search, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatDateHeure } from '@/lib/utils'

interface Candidate {
  url: string
  thumbnail: string
  title: string
  source: string
  width: number | null
  height: number | null
}

interface Proposal {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  query: string
  candidates: Candidate[]
  chosen_index: number | null
  product: { id: number; sku: string | null; name: string | null; category: string | null; brand: string | null }
  image_url: string | null
  reviewed_by: string | null
  reviewed_at: string | null
  created_at: string | null
}

interface Overview {
  configured: boolean
  /** Moteur en service : « google » ou « claude ». */
  engine: 'google' | 'claude' | null
  counts: { sans_image: number; en_attente: number; validees: number; rejetees: number }
  max_per_run: number
  next_products: { id: number; sku: string; name: string }[]
}

const OVERVIEW = ['agent-images-overview'] as const
const PROPOSALS = ['agent-images-proposals'] as const

function messageErreur(e: unknown, repli: string): string {
  if (e && typeof e === 'object' && 'response' in e) {
    const r = (e as { response?: { data?: { message?: string } } }).response
    if (r?.data?.message) return r.data.message
  }
  return repli
}

/**
 * Agent images : l'agent cherche des photos pour les articles sans visuel
 * et les propose ; rien n'entre dans la base tant que l'administrateur n'a
 * pas cliqué sur une image. Chaque proposition se rejette ou se relance
 * avec une requête corrigée.
 */
export function AgentImagesPage() {
  const qc = useQueryClient()
  const [onglet, setOnglet] = useState<'pending' | 'approved' | 'rejected'>('pending')
  const [recherche, setRecherche] = useState('')
  const [limite, setLimite] = useState(10)
  const [choisis, setChoisis] = useState<number[]>([])
  const [message, setMessage] = useState<string | null>(null)
  const [erreur, setErreur] = useState<string | null>(null)
  const [aValider, setAValider] = useState<{ proposal: Proposal; index: number } | null>(null)
  const [aRejeter, setARejeter] = useState<Proposal | null>(null)
  const [requetes, setRequetes] = useState<Record<number, string>>({})

  const { data: apercu } = useQuery<Overview>({
    queryKey: OVERVIEW,
    queryFn: async () => {
      const { data } = await api.get<{ data: Overview }>('/agent-images/overview')
      return data.data
    },
  })

  const { data: propositions, isLoading } = useQuery<{ data: Proposal[]; meta: { total: number } }>({
    queryKey: [...PROPOSALS, onglet, recherche],
    queryFn: async () => {
      const { data } = await api.get<{ data: Proposal[]; meta: { total: number } }>('/agent-images/proposals', {
        params: { status: onglet, q: recherche || undefined },
      })
      return data
    },
  })

  function rafraichir() {
    qc.invalidateQueries({ queryKey: OVERVIEW })
    qc.invalidateQueries({ queryKey: PROPOSALS })
  }

  const lancer = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const { data } = await api.post<{ data: { proposees: number; sans_resultat: number; erreurs: string[] } }>('/agent-images/run', {
        limit: limiteEffective,
        product_ids: choisis.length > 0 ? choisis : undefined,
      })
      return data.data
    },
    onMutate: () => {
      setErreur(null)
      setMessage(null)
    },
    onSuccess: (r) => {
      setChoisis([])
      setOnglet('pending')
      setMessage(
        `${r.proposees} proposition(s) prête(s) à valider` +
          (r.sans_resultat > 0 ? `, ${r.sans_resultat} article(s) sans résultat` : '') +
          (r.erreurs.length > 0 ? ` · ${r.erreurs.join(' ; ')}` : '') +
          '.',
      )
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'L’agent n’a pas pu se lancer.')),
  })

  const valider = useMutation({
    mutationFn: async ({ proposal, index }: { proposal: Proposal; index: number }) => {
      await ensureCsrfCookie()
      await api.post(`/agent-images/proposals/${proposal.id}/approve`, { index })
      return proposal
    },
    onSuccess: (p) => {
      setAValider(null)
      setMessage(`Image enregistrée pour ${p.product.name ?? p.product.sku}.`)
      rafraichir()
    },
  })

  const rejeter = useMutation({
    mutationFn: async (p: Proposal) => {
      await ensureCsrfCookie()
      await api.post(`/agent-images/proposals/${p.id}/reject`)
    },
    onSuccess: () => {
      setARejeter(null)
      rafraichir()
    },
  })

  const relancer = useMutation({
    mutationFn: async ({ id, query }: { id: number; query: string }) => {
      await ensureCsrfCookie()
      await api.post(`/agent-images/proposals/${id}/retry`, { query })
    },
    onSuccess: () => {
      setErreur(null)
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'Nouvelle recherche impossible.')),
  })

  const supprimer = useMutation({
    mutationFn: async (id: number) => {
      await ensureCsrfCookie()
      await api.delete(`/agent-images/proposals/${id}`)
    },
    onSuccess: rafraichir,
  })

  const configure = apercu?.configured ?? true
  const liste = propositions?.data ?? []
  const maxParLancement = apercu?.max_per_run ?? 25
  const tailles = [3, 5, 10, 15, 20, 25].filter((n) => n <= maxParLancement)
  const limiteEffective = Math.min(limite, maxParLancement)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="flex items-center gap-2 text-xl font-semibold text-ink">
          <Images className="h-5 w-5 text-sky" />
          Agent images
        </h1>
        <p className="text-sm text-muted">
          L’agent cherche des photos pour les articles sans visuel et vous les propose. Rien n’entre dans la base
          tant que vous n’avez pas choisi une image : elle devient alors l’image principale de l’article. Les noms,
          références et prix des articles ne sont jamais modifiés.
        </p>
      </div>

      {!configure ? (
        <div className="rounded border border-line bg-warn-bg px-4 py-3 text-sm text-warn">
          <p className="font-medium">L’agent n’est pas encore configuré.</p>
          <p className="mt-1">
            Il utilise l’API officielle Google Custom Search (100 recherches gratuites par jour). Il faut deux valeurs
            dans le fichier .env du serveur : <span className="mono">GOOGLE_CSE_KEY</span> (clé d’API Google Cloud avec
            « Custom Search API » activée) et <span className="mono">GOOGLE_CSE_CX</span> (identifiant d’un moteur
            programmable, recherche sur tout le web, images activées).
          </p>
        </div>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-4">
        {(
          [
            ['Articles sans image', apercu?.counts.sans_image, 'text-warn'],
            ['À valider', apercu?.counts.en_attente, 'text-navy'],
            ['Validées', apercu?.counts.validees, 'text-ok'],
            ['Rejetées', apercu?.counts.rejetees, 'text-muted'],
          ] as const
        ).map(([libelle, valeur, couleur]) => (
          <Card key={libelle}>
            <CardBody>
              <p className="text-xs font-medium text-muted">{libelle}</p>
              <p className={cn('text-2xl font-semibold', couleur)}>{valeur ?? '—'}</p>
            </CardBody>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader
          title="Lancer l’agent"
          hint={
            apercu
              ? `${apercu.max_per_run} articles au plus par lancement · moteur : ${
                  apercu.engine === 'claude' ? 'Claude (recherche web)' : apercu.engine === 'google' ? 'Google Images' : 'aucun'
                }`
              : undefined
          }
        />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-[180px_1fr_auto]">
            <Field label="Nombre d’articles" htmlFor="ai-limite">
              <Select id="ai-limite" value={limiteEffective} onChange={(e) => setLimite(Number(e.target.value))} disabled={choisis.length > 0}>
                {tailles.map((n) => (
                  <option key={n} value={n}>{n}</option>
                ))}
              </Select>
            </Field>
            <div className="text-sm text-muted sm:pt-7">
              {choisis.length > 0
                ? `${choisis.length} article(s) choisi(s) ci-dessous.`
                : 'Sans sélection, l’agent prend les premiers articles sans image, par ordre alphabétique.'}
            </div>
            <div className="sm:pt-6">
              <Button onClick={() => lancer.mutate()} disabled={!configure || lancer.isPending || (apercu?.counts.sans_image ?? 0) === 0}>
                <Play className="h-4 w-4" />
                {lancer.isPending
                  ? apercu?.engine === 'claude'
                    ? 'Recherche en cours (environ une minute par article)…'
                    : 'Recherche en cours…'
                  : 'Lancer l’agent'}
              </Button>
            </div>
          </div>

          {apercu && apercu.next_products.length > 0 ? (
            <details className="rounded border border-line">
              <summary className="cursor-pointer px-3 py-2 text-sm text-ink">
                Choisir les articles ({apercu.next_products.length} sans image affichés)
              </summary>
              <div className="grid max-h-56 gap-1 overflow-y-auto border-t border-line p-2 sm:grid-cols-2 lg:grid-cols-3">
                {apercu.next_products.map((p) => (
                  <label key={p.id} className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-surface-2">
                    <input
                      type="checkbox"
                      className="h-4 w-4 accent-sky"
                      checked={choisis.includes(p.id)}
                      disabled={!choisis.includes(p.id) && choisis.length >= (apercu.max_per_run ?? 25)}
                      onChange={(e) =>
                        setChoisis((prev) => (e.target.checked ? [...prev, p.id] : prev.filter((id) => id !== p.id)))
                      }
                    />
                    <span className="mono text-xs text-muted">{p.sku}</span>
                    <span className="truncate text-ink">{p.name}</span>
                  </label>
                ))}
              </div>
            </details>
          ) : null}

          {message ? <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">{message}</p> : null}
          {erreur ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p> : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Propositions"
          hint={propositions ? `${propositions.meta.total}` : undefined}
          action={
            <div className="flex items-center gap-2">
              <div className="flex rounded border border-line">
                {(
                  [
                    ['pending', 'À valider'],
                    ['approved', 'Validées'],
                    ['rejected', 'Rejetées'],
                  ] as const
                ).map(([valeur, libelle]) => (
                  <button
                    key={valeur}
                    type="button"
                    onClick={() => setOnglet(valeur)}
                    className={cn(
                      'px-3 py-1.5 text-sm transition-colors',
                      onglet === valeur ? 'bg-sky-soft text-navy' : 'text-muted hover:text-ink',
                    )}
                  >
                    {libelle}
                  </button>
                ))}
              </div>
              <Input placeholder="Article…" value={recherche} onChange={(e) => setRecherche(e.target.value)} className="w-48" />
            </div>
          }
        />
        <CardBody className="space-y-4">
          {isLoading ? <p className="text-sm text-muted">Chargement…</p> : null}
          {!isLoading && liste.length === 0 ? (
            <p className="flex items-center gap-2 text-sm text-muted">
              <ImageOff className="h-4 w-4" />
              {onglet === 'pending' ? 'Aucune proposition à valider : lancez l’agent.' : 'Rien ici.'}
            </p>
          ) : null}

          {liste.map((p) => (
            <div key={p.id} className="rounded-lg border border-line p-4">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="flex items-center gap-2 text-sm font-medium text-ink">
                    <Link to={`/articles/${p.product.id}`} className="hover:text-sky hover:underline">
                      {p.product.name ?? `Article ${p.product.id}`}
                    </Link>
                    <span className="mono text-xs text-muted">{p.product.sku}</span>
                    {p.status === 'approved' ? <Badge tone="ok">Validée</Badge> : null}
                    {p.status === 'rejected' ? <Badge tone="neutral">Rejetée</Badge> : null}
                  </p>
                  <p className="text-xs text-muted">
                    {[p.product.category, p.product.brand].filter(Boolean).join(' · ') || '—'}
                    {p.reviewed_by ? ` · ${p.reviewed_by}, ${formatDateHeure(p.reviewed_at)}` : ''}
                  </p>
                </div>
                {p.status === 'pending' ? (
                  <div className="flex items-center gap-2">
                    <Button variant="ghost" size="sm" className="text-bad" onClick={() => setARejeter(p)}>
                      <X className="h-4 w-4" />
                      Rejeter
                    </Button>
                  </div>
                ) : (
                  <Button variant="ghost" size="sm" onClick={() => supprimer.mutate(p.id)} title="Retirer de l’historique">
                    <Trash2 className="h-4 w-4" />
                  </Button>
                )}
              </div>

              {p.status === 'pending' ? (
                <form
                  className="mt-3 flex items-center gap-2"
                  onSubmit={(e) => {
                    e.preventDefault()
                    const q = (requetes[p.id] ?? p.query).trim()
                    if (q.length >= 2) relancer.mutate({ id: p.id, query: q })
                  }}
                >
                  {/* Ce champ est la requete envoyee au moteur d'images, rien
                      d'autre : le nom de l'article, lui, ne bouge jamais. */}
                  <Search className="h-4 w-4 shrink-0 text-faint" />
                  <span className="shrink-0 text-xs text-muted">Recherche d’images :</span>
                  <Input
                    value={requetes[p.id] ?? p.query}
                    onChange={(e) => setRequetes((prev) => ({ ...prev, [p.id]: e.target.value }))}
                    className="max-w-md"
                    aria-label="Requête de recherche d’images (le nom de l’article ne change pas)"
                    title="Mots envoyés au moteur d’images. Le nom de l’article ne change pas."
                  />
                  <Button type="submit" variant="outline" size="sm" disabled={relancer.isPending}>
                    <RefreshCw className={cn('h-4 w-4', relancer.isPending && 'animate-spin')} />
                    Chercher à nouveau
                  </Button>
                </form>
              ) : (
                <p className="mt-2 text-xs text-faint">Recherche : « {p.query} »</p>
              )}

              <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                {p.status === 'approved' && p.image_url ? (
                  <figure className="col-span-2 sm:col-span-2">
                    <img src={p.image_url} alt="" className="aspect-square w-full rounded border border-line object-contain bg-white" />
                    <figcaption className="mt-1 text-xs text-ok">Image enregistrée</figcaption>
                  </figure>
                ) : null}
                {p.candidates.map((c, i) => {
                  const retenue = p.status === 'approved' && p.chosen_index === i
                  return (
                    <figure key={i} className={cn('group relative', retenue && 'ring-2 ring-ok rounded')}>
                      <button
                        type="button"
                        disabled={p.status !== 'pending'}
                        onClick={() => setAValider({ proposal: p, index: i })}
                        className="block w-full overflow-hidden rounded border border-line bg-white disabled:cursor-default"
                        title={p.status === 'pending' ? 'Utiliser cette image' : c.title}
                      >
                        <img src={c.thumbnail} alt={c.title} loading="lazy" className="aspect-square w-full object-contain" />
                        {p.status === 'pending' ? (
                          <span className="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-navy/85 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">
                            <Check className="h-3.5 w-3.5" />
                            Utiliser
                          </span>
                        ) : null}
                      </button>
                      <figcaption className="mt-1 flex items-center justify-between gap-1 text-[11px] text-faint">
                        <span className="truncate">{c.source}{c.width ? ` · ${c.width}×${c.height}` : ''}</span>
                        <a href={c.url} target="_blank" rel="noreferrer" className="shrink-0 hover:text-sky" title="Ouvrir l’original">
                          <ExternalLink className="h-3 w-3" />
                        </a>
                      </figcaption>
                    </figure>
                  )
                })}
              </div>
            </div>
          ))}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={aValider !== null}
        title="Enregistrer cette image"
        message={
          aValider ? (
            <div className="space-y-3">
              <img
                src={aValider.proposal.candidates[aValider.index]?.thumbnail}
                alt=""
                className="mx-auto max-h-56 rounded border border-line bg-white object-contain"
              />
              <p>
                Elle sera téléchargée et deviendra l’image de <strong>{aValider.proposal.product.name}</strong>.
              </p>
            </div>
          ) : (
            ''
          )
        }
        confirmLabel="Enregistrer"
        isPending={valider.isPending}
        error={valider.isError ? messageErreur(valider.error, 'Enregistrement impossible.') : null}
        onConfirm={() => {
          if (aValider) valider.mutate(aValider)
        }}
        onCancel={() => {
          setAValider(null)
          valider.reset()
        }}
      />

      <ConfirmDialog
        open={aRejeter !== null}
        title="Rejeter la proposition"
        message={
          <>
            Aucune de ces images ne convient pour <strong>{aRejeter?.product.name}</strong> ? L’article restera sans
            image ; vous pourrez relancer l’agent plus tard.
          </>
        }
        confirmLabel="Rejeter"
        isPending={rejeter.isPending}
        error={rejeter.isError ? messageErreur(rejeter.error, 'Rejet impossible.') : null}
        onConfirm={() => {
          if (aRejeter) rejeter.mutate(aRejeter)
        }}
        onCancel={() => {
          setARejeter(null)
          rejeter.reset()
        }}
      />
    </div>
  )
}
