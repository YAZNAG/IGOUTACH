import { useQuery } from '@tanstack/react-query'
import { Menu, MessageCircle, PackageSearch, Phone, Search, ShoppingCart, X } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'
import { api } from '../lib/api'
import { usePanier } from '../lib/cart'
import type { Categorie, Reglages } from '../lib/types'
import { cn } from './ui'

export function useReglages() {
  return useQuery<Reglages>({
    queryKey: ['store-settings'],
    queryFn: async () => (await api<{ data: Reglages }>('/store/settings')).data,
    staleTime: 10 * 60_000,
  })
}

export function useCategories() {
  return useQuery<Categorie[]>({
    queryKey: ['store-categories'],
    queryFn: async () => (await api<{ data: Categorie[] }>('/store/categories')).data,
    staleTime: 10 * 60_000,
  })
}

/** Gabarit du site public : annonce, en-tête avec recherche et panier, menu des catégories, pied de page. */
export function Layout() {
  const { data: reglages } = useReglages()
  const { data: categories = [] } = useCategories()
  const { nombre } = usePanier()
  const navigate = useNavigate()
  const [recherche, setRecherche] = useState('')
  const [menuOuvert, setMenuOuvert] = useState(false)

  function chercher(e: FormEvent) {
    e.preventDefault()
    const q = recherche.trim()
    navigate(q ? `/catalogue?q=${encodeURIComponent(q)}` : '/catalogue')
    setMenuOuvert(false)
  }

  const whatsapp = reglages?.whatsapp ? `https://wa.me/${reglages.whatsapp.replace(/\D/g, '')}` : null

  return (
    <div className="flex min-h-screen flex-col">
      {reglages?.announcement ? (
        <div className="bg-ink px-4 py-1.5 text-center text-xs font-medium text-white">{reglages.announcement}</div>
      ) : null}

      <header className="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3">
          <button type="button" className="rounded-lg p-2 text-muted hover:bg-paper lg:hidden" onClick={() => setMenuOuvert((v) => !v)} aria-label="Menu">
            {menuOuvert ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>

          <Link to="/" className="flex items-center gap-2">
            <span className="flex h-9 w-9 items-center justify-center rounded-full border-2 border-brand text-sm font-black text-brand">iG</span>
            <span className="hidden text-lg font-bold tracking-tight text-ink sm:block">
              iGou<span className="text-brand">Tech</span>
              <span className="ml-1 text-sm font-medium text-muted">Store</span>
            </span>
          </Link>

          <form onSubmit={chercher} className="relative mx-auto hidden flex-1 md:block md:max-w-xl">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
            <input
              value={recherche}
              onChange={(e) => setRecherche(e.target.value)}
              placeholder="Rechercher un récepteur, un câble, une télécommande…"
              className="h-10 w-full rounded-full border border-line bg-paper pl-10 pr-4 text-sm focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20"
            />
          </form>

          <div className="ml-auto flex items-center gap-1">
            {reglages?.phone ? (
              <a href={`tel:${reglages.phone}`} className="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-muted hover:text-ink lg:flex">
                <Phone className="h-4 w-4" />
                {reglages.phone}
              </a>
            ) : null}
            <NavLink to="/suivi" className="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-muted hover:text-ink sm:flex">
              <PackageSearch className="h-4 w-4" />
              Suivre ma commande
            </NavLink>
            <Link to="/panier" className="relative flex items-center gap-2 rounded-lg bg-brand px-3 py-2 text-sm font-medium text-white hover:bg-brand-strong">
              <ShoppingCart className="h-4 w-4" />
              <span className="hidden sm:inline">Panier</span>
              {nombre > 0 ? (
                <span className="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-ink px-1 text-[11px] font-bold text-white">
                  {nombre}
                </span>
              ) : null}
            </Link>
          </div>
        </div>

        <nav className={cn('border-t border-line bg-white lg:block', menuOuvert ? 'block' : 'hidden')}>
          <div className="mx-auto max-w-7xl px-4">
            <form onSubmit={chercher} className="relative py-3 md:hidden">
              <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
              <input
                value={recherche}
                onChange={(e) => setRecherche(e.target.value)}
                placeholder="Rechercher un article…"
                className="h-10 w-full rounded-full border border-line bg-paper pl-10 pr-4 text-sm focus:outline-none"
              />
            </form>
            <ul className="flex flex-col gap-1 pb-3 text-sm lg:flex-row lg:gap-1 lg:overflow-x-auto lg:pb-0">
              <li>
                <NavLink
                  to="/catalogue"
                  end
                  onClick={() => setMenuOuvert(false)}
                  className={({ isActive }) => cn('block whitespace-nowrap rounded-lg px-3 py-2 lg:rounded-none lg:border-b-2 lg:py-2.5', isActive ? 'text-brand lg:border-brand' : 'text-muted hover:text-ink lg:border-transparent')}
                >
                  Tous les articles
                </NavLink>
              </li>
              {categories.map((c) => (
                <li key={c.id}>
                  <NavLink
                    to={`/categorie/${c.id}`}
                    onClick={() => setMenuOuvert(false)}
                    className={({ isActive }) => cn('block whitespace-nowrap rounded-lg px-3 py-2 lg:rounded-none lg:border-b-2 lg:py-2.5', isActive ? 'text-brand lg:border-brand' : 'text-muted hover:text-ink lg:border-transparent')}
                  >
                    {c.name}
                  </NavLink>
                </li>
              ))}
              <li className="sm:hidden">
                <NavLink to="/suivi" onClick={() => setMenuOuvert(false)} className="block rounded-lg px-3 py-2 text-muted">Suivre ma commande</NavLink>
              </li>
            </ul>
          </div>
        </nav>
      </header>

      <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
        <Outlet />
      </main>

      <footer className="mt-10 border-t border-line bg-white">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:grid-cols-3">
          <div>
            <p className="text-lg font-bold">iGou<span className="text-brand">Tech</span></p>
            <p className="mt-2 text-muted">{reglages?.address}</p>
          </div>
          <div>
            <p className="font-semibold">Nous contacter</p>
            <ul className="mt-2 space-y-1 text-muted">
              {reglages?.phone ? <li><a href={`tel:${reglages.phone}`} className="hover:text-ink">{reglages.phone}</a></li> : null}
              {reglages?.email ? <li><a href={`mailto:${reglages.email}`} className="hover:text-ink">{reglages.email}</a></li> : null}
              {whatsapp ? <li><a href={whatsapp} target="_blank" rel="noreferrer" className="hover:text-ink">WhatsApp</a></li> : null}
            </ul>
          </div>
          <div>
            <p className="font-semibold">Commandes</p>
            <ul className="mt-2 space-y-1 text-muted">
              <li>Livraison partout au Maroc</li>
              <li>Paiement à la livraison ou par virement</li>
              <li><Link to="/suivi" className="hover:text-ink">Suivre ma commande</Link></li>
            </ul>
          </div>
        </div>
        <div className="border-t border-line py-4 text-center text-xs text-faint">© {new Date().getFullYear()} {reglages?.shop_name ?? 'iGouTech'}</div>
      </footer>

      {whatsapp ? (
        <a
          href={whatsapp}
          target="_blank"
          rel="noreferrer"
          aria-label="Nous écrire sur WhatsApp"
          className="fixed bottom-5 right-5 z-30 flex h-13 w-13 items-center justify-center rounded-full bg-[#25D366] p-3.5 text-white shadow-lg hover:scale-105"
        >
          <MessageCircle className="h-6 w-6" />
        </a>
      ) : null}
    </div>
  )
}
