import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ImageOff, Minus, Plus, ShoppingCart } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArticleCard } from '../components/ArticleCard'
import { useReglages } from '../components/Layout'
import { Button, Dispo, Erreur, Spinner } from '../components/ui'
import { api, dh, messageErreur } from '../lib/api'
import { usePanier } from '../lib/cart'
import type { Fiche } from '../lib/types'

export function Product() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const { ajouter } = usePanier()
  const { data: reglages } = useReglages()
  const [quantite, setQuantite] = useState(1)
  const [imageActive, setImageActive] = useState(0)

  const { data: fiche, isLoading, error } = useQuery<Fiche>({
    queryKey: ['store-product', id],
    queryFn: async () => (await api<{ data: Fiche }>(`/store/products/${id}`)).data,
  })

  if (isLoading) return <Spinner />
  if (error || !fiche) return <Erreur message={messageErreur(error, 'Article indisponible.')} />

  const images = fiche.images.length > 0 ? fiche.images : fiche.image ? [fiche.image] : []
  const whatsapp = reglages?.whatsapp
    ? `https://wa.me/${reglages.whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(`Bonjour, je souhaite commander : ${fiche.name} (réf. ${fiche.sku})`)}`
    : null

  return (
    <div className="space-y-10">
      <Link to={fiche.category_id ? `/categorie/${fiche.category_id}` : '/catalogue'} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
        <ChevronLeft className="h-4 w-4" /> {fiche.category ?? 'Catalogue'}
      </Link>

      <div className="grid gap-8 lg:grid-cols-2">
        <div className="space-y-3">
          <div className="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-line bg-white p-6">
            {images[imageActive] ? (
              <img src={images[imageActive]} alt={fiche.name} className="h-full w-full object-contain" />
            ) : (
              <ImageOff className="h-12 w-12 text-faint" />
            )}
          </div>
          {images.length > 1 ? (
            <div className="flex gap-2 overflow-x-auto">
              {images.map((src, i) => (
                <button
                  key={src}
                  type="button"
                  onClick={() => setImageActive(i)}
                  className={`h-16 w-16 shrink-0 overflow-hidden rounded-lg border bg-white p-1 ${i === imageActive ? 'border-brand' : 'border-line'}`}
                >
                  <img src={src} alt="" className="h-full w-full object-contain" />
                </button>
              ))}
            </div>
          ) : null}
        </div>

        <div className="space-y-5">
          <div>
            <p className="text-xs uppercase tracking-wide text-faint">{[fiche.category, fiche.brand].filter(Boolean).join(' · ')}</p>
            <h1 className="mt-1 text-2xl font-bold sm:text-3xl">{fiche.name}</h1>
            <p className="mt-1 text-xs text-faint">Réf. {fiche.sku}</p>
          </div>

          <div className="flex items-center gap-4">
            <p className="text-3xl font-bold text-brand">{dh(fiche.price)}</p>
            <Dispo niveau={fiche.stock_level} />
          </div>

          {fiche.description ? <div className="prose-store text-sm text-muted">{fiche.description}</div> : null}

          <div className="flex flex-wrap items-center gap-3">
            <div className="flex h-11 items-center rounded-lg border border-line bg-white">
              <button type="button" className="px-3 text-muted hover:text-ink" onClick={() => setQuantite((q) => Math.max(1, q - 1))} aria-label="Moins"><Minus className="h-4 w-4" /></button>
              <span className="w-10 text-center text-sm font-medium">{quantite}</span>
              <button type="button" className="px-3 text-muted hover:text-ink" onClick={() => setQuantite((q) => Math.min(500, q + 1))} aria-label="Plus"><Plus className="h-4 w-4" /></button>
            </div>
            <Button className="h-11 px-5" disabled={!fiche.in_stock} onClick={() => { ajouter(fiche, quantite); navigate('/panier') }}>
              <ShoppingCart className="h-4 w-4" /> Ajouter au panier
            </Button>
            {whatsapp ? (
              <a href={whatsapp} target="_blank" rel="noreferrer" className="inline-flex h-11 items-center rounded-lg border border-line bg-white px-4 text-sm font-medium text-ink hover:border-brand">
                Demander sur WhatsApp
              </a>
            ) : null}
          </div>

          <ul className="grid gap-2 text-sm text-muted sm:grid-cols-2">
            <li className="rounded-lg bg-white p-3">Livraison partout au Maroc{reglages && reglages.shipping_fee > 0 ? ` · ${dh(reglages.shipping_fee)}` : ''}</li>
            <li className="rounded-lg bg-white p-3">Paiement à la livraison ou par virement</li>
          </ul>
        </div>
      </div>

      {fiche.related.length > 0 ? (
        <section>
          <h2 className="mb-4 text-xl font-bold">Dans la même catégorie</h2>
          <div className="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-6">
            {fiche.related.slice(0, 6).map((a) => <ArticleCard key={a.id} article={a} />)}
          </div>
        </section>
      ) : null}
    </div>
  )
}
