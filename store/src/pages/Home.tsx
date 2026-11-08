import { useQuery } from '@tanstack/react-query'
import { ArrowRight, BadgeCheck, Truck, Wallet } from 'lucide-react'
import { Link } from 'react-router-dom'
import { ArticleCard } from '../components/ArticleCard'
import { useCategories, useReglages } from '../components/Layout'
import { Spinner } from '../components/ui'
import { api } from '../lib/api'
import type { Article, Pagine } from '../lib/types'

export function Home() {
  const { data: reglages } = useReglages()
  const { data: categories = [] } = useCategories()

  const { data: vedettes } = useQuery<Pagine<Article>>({
    queryKey: ['store-products', { featured: true }],
    queryFn: () => api<Pagine<Article>>('/store/products', { params: { featured: true, in_stock: true, per_page: 12 } }),
  })
  const { data: nouveautes, isLoading } = useQuery<Pagine<Article>>({
    queryKey: ['store-products', { sort: 'newest' }],
    queryFn: () => api<Pagine<Article>>('/store/products', { params: { sort: 'newest', in_stock: true, per_page: 12 } }),
  })

  const miseEnAvant = (vedettes?.data.length ?? 0) > 0 ? vedettes!.data : (nouveautes?.data ?? [])

  return (
    <div className="space-y-12">
      <section className="overflow-hidden rounded-3xl bg-gradient-to-br from-ink via-[#2a2a2e] to-brand-strong px-6 py-12 text-white sm:px-12 sm:py-16">
        <div className="max-w-2xl">
          <p className="text-xs font-semibold uppercase tracking-widest text-white/70">iGouTech · Dcheira El Jihadia</p>
          <h1 className="mt-3 text-3xl font-bold leading-tight sm:text-5xl">{reglages?.hero_title || 'Électronique, satellite et câblage'}</h1>
          <p className="mt-4 text-base text-white/80 sm:text-lg">{reglages?.hero_subtitle}</p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to="/catalogue" className="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-ink hover:bg-white/90">
              Voir le catalogue <ArrowRight className="h-4 w-4" />
            </Link>
            {reglages?.whatsapp ? (
              <a href={`https://wa.me/${reglages.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-full border border-white/40 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                Commander par WhatsApp
              </a>
            ) : null}
          </div>
        </div>
      </section>

      <section className="grid gap-4 sm:grid-cols-3">
        {[
          [Truck, 'Livraison partout au Maroc', reglages && reglages.free_shipping_from > 0 ? `Offerte dès ${reglages.free_shipping_from} DH d’achat` : 'Expédition rapide depuis Agadir'],
          [Wallet, 'Paiement à la livraison', 'Ou par virement bancaire, à votre choix'],
          [BadgeCheck, 'Matériel contrôlé', 'Articles issus de notre stock, prix détail affichés'],
        ].map(([Icone, titre, texte]) => {
          const I = Icone as typeof Truck
          return (
            <div key={titre as string} className="flex items-start gap-3 rounded-2xl border border-line bg-white p-4">
              <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand"><I className="h-5 w-5" /></span>
              <div>
                <p className="text-sm font-semibold">{titre as string}</p>
                <p className="text-xs text-muted">{texte as string}</p>
              </div>
            </div>
          )
        })}
      </section>

      {categories.length > 0 ? (
        <section>
          <div className="mb-4 flex items-end justify-between">
            <h2 className="text-xl font-bold">Nos catégories</h2>
            <Link to="/catalogue" className="text-sm text-brand hover:underline">Tout voir</Link>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {categories.map((c) => (
              <Link key={c.id} to={`/categorie/${c.id}`} className="rounded-2xl border border-line bg-white p-4 transition-colors hover:border-brand">
                <p className="font-medium text-ink">{c.name}</p>
                <p className="text-xs text-muted">{c.count} article{c.count > 1 ? 's' : ''}</p>
              </Link>
            ))}
          </div>
        </section>
      ) : null}

      <section>
        <div className="mb-4 flex items-end justify-between">
          <h2 className="text-xl font-bold">{(vedettes?.data.length ?? 0) > 0 ? 'Nos coups de cœur' : 'Nouveautés'}</h2>
          <Link to="/catalogue?sort=newest" className="text-sm text-brand hover:underline">Voir plus</Link>
        </div>
        {isLoading ? <Spinner /> : (
          <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            {miseEnAvant.map((a) => <ArticleCard key={a.id} article={a} />)}
          </div>
        )}
      </section>
    </div>
  )
}
