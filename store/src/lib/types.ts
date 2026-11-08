export interface Reglages {
  shop_name: string
  phone: string
  whatsapp: string
  email: string
  address: string
  shipping_fee: number
  free_shipping_from: number
  payment_methods: ('cod' | 'transfer')[]
  bank_details: string
  announcement: string
  hero_title: string
  hero_subtitle: string
}

export interface Categorie {
  id: number
  name: string
  count: number
}

export interface Article {
  id: number
  sku: string
  name: string
  category: string | null
  category_id: number | null
  brand: string | null
  price: number
  in_stock: boolean
  stock_level: 'in' | 'low' | 'out'
  featured: boolean
  image: string | null
}

export interface Fiche extends Article {
  description: string | null
  images: string[]
  unit: string | null
  related: Article[]
}

export interface Pagine<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface LigneCommande {
  name: string
  quantity: number
  unit_price: number
  line_total: number
}

export interface SuiviCommande {
  number: string
  status: string
  payment_method: string
  payment_status: string
  subtotal: number
  shipping_fee: number
  total: number
  city: string
  created_at: string
  shipped_at: string | null
  delivered_at: string | null
  lines: LigneCommande[]
  bank_details: string | null
}

export const STATUTS: Record<string, { label: string; tone: 'neutral' | 'sky' | 'ok' | 'warn' | 'bad' }> = {
  new: { label: 'Nouvelle', tone: 'warn' },
  confirmed: { label: 'Confirmée', tone: 'sky' },
  preparing: { label: 'En préparation', tone: 'sky' },
  shipped: { label: 'Expédiée', tone: 'sky' },
  delivered: { label: 'Livrée', tone: 'ok' },
  cancelled: { label: 'Annulée', tone: 'bad' },
}

export const PAIEMENTS: Record<string, string> = {
  cod: 'Paiement à la livraison',
  transfer: 'Virement bancaire',
  cash: 'Espèces',
  card: 'Carte',
  other: 'Autre',
}

export const ETATS_PAIEMENT: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' }> = {
  unpaid: { label: 'Non payée', tone: 'bad' },
  partial: { label: 'Partiellement payée', tone: 'warn' },
  paid: { label: 'Payée', tone: 'ok' },
  refunded: { label: 'Remboursée', tone: 'warn' },
}
