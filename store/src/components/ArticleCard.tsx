import { ImageOff, ShoppingCart } from 'lucide-react'
import { Link } from 'react-router-dom'
import { dh } from '../lib/api'
import { usePanier } from '../lib/cart'
import type { Article } from '../lib/types'
import { Button, Dispo } from './ui'

/** Carte d'un article dans une grille : photo, nom, prix, disponibilité, ajout au panier. */
export function ArticleCard({ article }: { article: Article }) {
  const { ajouter } = usePanier()

  return (
    <div className="group flex flex-col overflow-hidden rounded-2xl border border-line bg-white transition-shadow hover:shadow-md">
      <Link to={`/article/${article.id}`} className="relative block aspect-square bg-white p-3">
        {article.image ? (
          <img src={article.image} alt={article.name} loading="lazy" className="h-full w-full object-contain transition-transform group-hover:scale-[1.03]" />
        ) : (
          <div className="flex h-full w-full items-center justify-center rounded-xl bg-paper text-faint">
            <ImageOff className="h-8 w-8" />
          </div>
        )}
        {article.featured ? (
          <span className="absolute left-3 top-3 rounded-full bg-brand px-2 py-0.5 text-[11px] font-semibold text-white">Coup de cœur</span>
        ) : null}
      </Link>
      <div className="flex flex-1 flex-col gap-2 p-4 pt-1">
        {article.category ? <p className="text-[11px] uppercase tracking-wide text-faint">{article.category}</p> : null}
        <Link to={`/article/${article.id}`} className="clamp-2 text-sm font-medium text-ink hover:text-brand" title={article.name}>
          {article.name}
        </Link>
        <div className="mt-auto flex items-end justify-between gap-2">
          <div>
            <p className="text-lg font-bold text-ink">{dh(article.price)}</p>
            <Dispo niveau={article.stock_level} />
          </div>
          <Button
            variant={article.in_stock ? 'primary' : 'outline'}
            className="h-9 px-3"
            disabled={!article.in_stock}
            onClick={() => ajouter(article)}
            aria-label={`Ajouter ${article.name} au panier`}
          >
            <ShoppingCart className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  )
}
