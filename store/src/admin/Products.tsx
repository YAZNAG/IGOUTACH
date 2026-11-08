import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImageOff, Star } from 'lucide-react'
import { useState } from 'react'
import { Badge, Button, Card, Input, Select, Spinner } from '../components/ui'
import { api, dh } from '../lib/api'
import type { Pagine } from '../lib/types'

interface ArticleAdmin {
  id: number
  sku: string
  name: string
  category: string | null
  price: number
  stock_total: number
  stock_by_warehouse: { warehouse: string; quantity: number }[]
  store_visible: boolean
  store_featured: boolean
  store_description: string | null
  image: string | null
  images_count: number
}

/** Les articles vus par la boutique : visibilité, mise en avant, stock par lieu. */
export function AdminProducts() {
  const qc = useQueryClient()
  const [q, setQ] = useState('')
  const [filtre, setFiltre] = useState('')
  const [page, setPage] = useState(1)
  const [edition, setEdition] = useState<{ id: number; texte: string } | null>(null)

  const { data, isLoading } = useQuery<Pagine<ArticleAdmin>>({
    queryKey: ['store-admin-products', { q, filtre, page }],
    queryFn: () => api<Pagine<ArticleAdmin>>('/v1/store-admin/products', {
      auth: true,
      params: { q, page, per_page: 30, visible: filtre === 'visible' ? '1' : filtre === 'hidden' ? '0' : undefined, without_image: filtre === 'noimage' },
    }),
  })

  const maj = useMutation({
    mutationFn: ({ id, ...corps }: { id: number; store_visible?: boolean; store_featured?: boolean; store_description?: string | null }) =>
      api(`/v1/store-admin/products/${id}`, { method: 'PATCH', auth: true, body: corps }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['store-admin-products'] })
      qc.invalidateQueries({ queryKey: ['store-products'] })
      setEdition(null)
    },
  })

  return (
    <div className="space-y-5">
      <div>
        <h1 className="text-xl font-bold">Articles en ligne</h1>
        <p className="text-sm text-muted">Le catalogue de la boutique est celui de la gestion : ici vous choisissez ce qui s’affiche, et vous voyez le stock par lieu.</p>
      </div>

      <Card className="grid gap-3 p-4 sm:grid-cols-[1fr_220px]">
        <Input placeholder="Nom ou référence…" value={q} onChange={(e) => { setQ(e.target.value); setPage(1) }} />
        <Select value={filtre} onChange={(e) => { setFiltre(e.target.value); setPage(1) }}>
          <option value="">Tous les articles actifs</option>
          <option value="visible">Visibles en ligne</option>
          <option value="hidden">Masqués</option>
          <option value="noimage">Sans photo</option>
        </Select>
      </Card>

      <Card className="overflow-x-auto">
        {isLoading ? <Spinner /> : (
          <table className="w-full text-sm">
            <thead><tr className="text-left text-xs text-muted"><th className="px-4 py-3">Article</th><th className="px-4 py-3 text-right">Prix</th><th className="px-4 py-3">Stock par lieu</th><th className="px-4 py-3">En ligne</th><th className="px-4 py-3">Mis en avant</th></tr></thead>
            <tbody>
              {(data?.data ?? []).map((a) => (
                <tr key={a.id} className="border-t border-line align-top hover:bg-paper">
                  <td className="px-4 py-3">
                    <div className="flex gap-3">
                      <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-line bg-white p-0.5">
                        {a.image ? <img src={a.image} alt="" className="h-full w-full object-contain" /> : <ImageOff className="m-auto mt-2.5 h-5 w-5 text-faint" />}
                      </div>
                      <div className="min-w-0">
                        <p className="font-medium">{a.name}</p>
                        <p className="text-xs text-faint">{a.sku}{a.category ? ` · ${a.category}` : ''}{a.images_count === 0 ? ' · sans photo' : ''}</p>
                        {edition?.id === a.id ? (
                          <div className="mt-2 flex gap-2">
                            <Input value={edition.texte} onChange={(e) => setEdition({ id: a.id, texte: e.target.value })} placeholder="Description pour la boutique" />
                            <Button className="h-10 px-3" onClick={() => maj.mutate({ id: a.id, store_description: edition.texte || null })} disabled={maj.isPending}>OK</Button>
                            <Button variant="ghost" className="h-10 px-3" onClick={() => setEdition(null)}>Annuler</Button>
                          </div>
                        ) : (
                          <button type="button" className="mt-1 text-xs text-brand hover:underline" onClick={() => setEdition({ id: a.id, texte: a.store_description ?? '' })}>
                            {a.store_description ? 'Modifier la description boutique' : 'Ajouter une description boutique'}
                          </button>
                        )}
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-right font-medium">{a.price > 0 ? dh(a.price) : <Badge tone="bad">Sans prix</Badge>}</td>
                  <td className="px-4 py-3">
                    <p className={a.stock_total <= 0 ? 'font-medium text-bad' : 'font-medium'}>{a.stock_total} au total</p>
                    <p className="text-xs text-faint">{a.stock_by_warehouse.map((s) => `${s.warehouse} ${s.quantity}`).join(' · ') || 'aucun lieu'}</p>
                  </td>
                  <td className="px-4 py-3">
                    <label className="inline-flex items-center gap-2 text-sm">
                      <input type="checkbox" className="h-4 w-4 accent-brand" checked={a.store_visible} onChange={(e) => maj.mutate({ id: a.id, store_visible: e.target.checked })} />
                      {a.store_visible ? 'Visible' : 'Masqué'}
                    </label>
                  </td>
                  <td className="px-4 py-3">
                    <button type="button" onClick={() => maj.mutate({ id: a.id, store_featured: !a.store_featured })} className={`inline-flex items-center gap-1 text-sm ${a.store_featured ? 'text-brand' : 'text-faint hover:text-ink'}`} aria-label="Mettre en avant">
                      <Star className={`h-4 w-4 ${a.store_featured ? 'fill-current' : ''}`} /> {a.store_featured ? 'Oui' : 'Non'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {data && data.meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {data.meta.current_page} / {data.meta.last_page} · {data.meta.total} articles</span>
          <Button variant="outline" className="h-8 px-3" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" className="h-8 px-3" disabled={page >= data.meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}
