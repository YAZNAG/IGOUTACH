import { ArrowRight, Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useReglages } from '../components/Layout'
import { Button, Card } from '../components/ui'
import { dh } from '../lib/api'
import { usePanier } from '../lib/cart'

export function Cart() {
  const { lignes, retirer, changerQuantite, sousTotal, vider } = usePanier()
  const { data: reglages } = useReglages()

  const port = reglages ? (reglages.free_shipping_from > 0 && sousTotal >= reglages.free_shipping_from ? 0 : reglages.shipping_fee) : 0

  if (lignes.length === 0) {
    return (
      <div className="mx-auto max-w-md py-16 text-center">
        <ShoppingBag className="mx-auto h-12 w-12 text-faint" />
        <h1 className="mt-4 text-xl font-bold">Votre panier est vide</h1>
        <p className="mt-2 text-sm text-muted">Parcourez le catalogue et ajoutez des articles.</p>
        <Link to="/catalogue" className="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-medium text-white">Voir le catalogue <ArrowRight className="h-4 w-4" /></Link>
      </div>
    )
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Panier</h1>
          <button type="button" onClick={vider} className="text-sm text-muted hover:text-bad">Vider le panier</button>
        </div>
        {lignes.map(({ article, quantite }) => (
          <Card key={article.id} className="flex items-center gap-4 p-3">
            <Link to={`/article/${article.id}`} className="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-line bg-white p-1">
              {article.image ? <img src={article.image} alt="" className="h-full w-full object-contain" /> : null}
            </Link>
            <div className="min-w-0 flex-1">
              <Link to={`/article/${article.id}`} className="clamp-2 text-sm font-medium hover:text-brand">{article.name}</Link>
              <p className="text-xs text-faint">Réf. {article.sku} · {dh(article.price)} l’unité</p>
              <div className="mt-2 flex items-center gap-3">
                <div className="flex h-9 items-center rounded-lg border border-line">
                  <button type="button" className="px-2.5 text-muted" onClick={() => changerQuantite(article.id, quantite - 1)} aria-label="Moins"><Minus className="h-4 w-4" /></button>
                  <span className="w-8 text-center text-sm">{quantite}</span>
                  <button type="button" className="px-2.5 text-muted" onClick={() => changerQuantite(article.id, quantite + 1)} aria-label="Plus"><Plus className="h-4 w-4" /></button>
                </div>
                <button type="button" onClick={() => retirer(article.id)} className="inline-flex items-center gap-1 text-xs text-muted hover:text-bad"><Trash2 className="h-3.5 w-3.5" /> Retirer</button>
              </div>
            </div>
            <p className="shrink-0 text-right font-semibold">{dh(article.price * quantite)}</p>
          </Card>
        ))}
      </div>

      <Card className="h-fit p-5">
        <h2 className="font-semibold">Récapitulatif</h2>
        <dl className="mt-4 space-y-2 text-sm">
          <div className="flex justify-between"><dt className="text-muted">Sous-total</dt><dd>{dh(sousTotal)}</dd></div>
          <div className="flex justify-between"><dt className="text-muted">Livraison</dt><dd>{port === 0 ? 'Offerte' : dh(port)}</dd></div>
          {reglages && reglages.free_shipping_from > 0 && port > 0 ? (
            <p className="text-xs text-faint">Livraison offerte dès {dh(reglages.free_shipping_from)} d’achat.</p>
          ) : null}
          <div className="flex justify-between border-t border-line pt-3 text-base font-bold"><dt>Total</dt><dd>{dh(sousTotal + port)}</dd></div>
        </dl>
        <Link to="/commande" className="mt-5 block">
          <Button className="h-11 w-full">Passer la commande <ArrowRight className="h-4 w-4" /></Button>
        </Link>
        <p className="mt-3 text-center text-xs text-faint">Paiement à la livraison ou par virement.</p>
      </Card>
    </div>
  )
}
