import { useQuery } from '@tanstack/react-query'
import { SlidersHorizontal } from 'lucide-react'
import { useParams, useSearchParams } from 'react-router-dom'
import { ArticleCard } from '../components/ArticleCard'
import { useCategories } from '../components/Layout'
import { Button, Select, Spinner } from '../components/ui'
import { api } from '../lib/api'
import type { Article, Pagine } from '../lib/types'

export function Catalog() {
  const { id } = useParams<{ id: string }>()
  const [params, setParams] = useSearchParams()
  const { data: categories = [] } = useCategories()

  const categorieId = id ? Number(id) : Number(params.get('categorie') ?? 0)
  const q = params.get('q') ?? ''
  const sort = params.get('sort') ?? 'name'
  const enStock = params.get('stock') === '1'
  const page = Number(params.get('page') ?? 1)

  const { data, isLoading } = useQuery<Pagine<Article>>({
    queryKey: ['store-products', { q, categorieId, sort, enStock, page }],
    queryFn: () => api<Pagine<Article>>('/store/products', { params: { q, category_id: categorieId || undefined, sort, in_stock: enStock, page, per_page: 24 } }),
  })

  const categorie = categories.find((c) => c.id === categorieId)

  function maj(cle: string, valeur: string | null) {
    const p = new URLSearchParams(params)
    if (valeur === null || valeur === '') p.delete(cle)
    else p.set(cle, valeur)
    if (cle !== 'page') p.delete('page')
    setParams(p)
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold">{categorie?.name ?? (q ? `Résultats pour « ${q} »` : 'Tous les articles')}</h1>
          {data ? <p className="text-sm text-muted">{data.meta.total} article{data.meta.total > 1 ? 's' : ''}</p> : null}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <SlidersHorizontal className="hidden h-4 w-4 text-faint sm:block" />
          <Select value={sort} onChange={(e) => maj('sort', e.target.value)} className="w-44" aria-label="Trier">
            <option value="name">Nom A → Z</option>
            <option value="price_asc">Prix croissant</option>
            <option value="price_desc">Prix décroissant</option>
            <option value="newest">Nouveautés</option>
          </Select>
          <label className="flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-3 text-sm">
            <input type="checkbox" className="accent-brand" checked={enStock} onChange={(e) => maj('stock', e.target.checked ? '1' : null)} />
            En stock
          </label>
        </div>
      </div>

      {isLoading ? <Spinner /> : null}

      {data && data.data.length === 0 ? (
        <div className="rounded-2xl border border-line bg-white p-10 text-center text-muted">Aucun article ne correspond.</div>
      ) : null}

      {data ? (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
          {data.data.map((a) => <ArticleCard key={a.id} article={a} />)}
        </div>
      ) : null}

      {data && data.meta.last_page > 1 ? (
        <div className="flex items-center justify-center gap-3 pt-4">
          <Button variant="outline" disabled={page <= 1} onClick={() => maj('page', String(page - 1))}>Précédent</Button>
          <span className="text-sm text-muted">Page {data.meta.current_page} / {data.meta.last_page}</span>
          <Button variant="outline" disabled={page >= data.meta.last_page} onClick={() => maj('page', String(page + 1))}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}
