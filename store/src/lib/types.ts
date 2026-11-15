/** Un palier de prix : à partir de `min_quantity`, l'unité vaut `price`. */
export interface Palier {
  code: string
  label: string
  rank: number
  min_quantity: number
  price: number
  /** Ce palier ne se commande pas en ligne : il passe par un devis. */
  on_quote: boolean
}

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
  /** Codes des paliers vendus sur devis (vide = tout se commande en ligne). */
  quote_from: string[]
  tiers_public: boolean
  quote_intro: string
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
  tiers: Palier[]
  /** Quantité à partir de laquelle cet article passe par un devis. */
  quote_from_quantity: number | null
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

export interface LigneDevis {
  name: string
  sku: string | null
  quantity: number
  unit_price: number
  line_total: number
}

export interface Devis {
  number: string
  status: string
  customer_name: string
  company: string | null
  city: string
  note: string | null
  reply: string | null
  subtotal: number
  shipping_fee: number
  total: number
  valid_until: string | null
  created_at: string
  quoted_at: string | null
  answered_at: string | null
  payment_methods: ('cod' | 'transfer')[]
  bank_details: string
  lines: LigneDevis[]
  public_token?: string
}

export const STATUTS_DEVIS: Record<string, { label: string; tone: 'neutral' | 'sky' | 'ok' | 'warn' | 'bad'; aide: string }> = {
  requested: { label: 'Demande reçue', tone: 'warn', aide: 'Nous préparons votre prix.' },
  quoted: { label: 'Devis envoyé', tone: 'sky', aide: 'À vous de répondre.' },
  accepted: { label: 'Accepté', tone: 'ok', aide: 'La commande est lancée.' },
  declined: { label: 'Refusé', tone: 'bad', aide: 'Vous avez décliné cette offre.' },
  expired: { label: 'Expiré', tone: 'bad', aide: 'La validité est passée ; demandez-nous une nouvelle offre.' },
  cancelled: { label: 'Annulé', tone: 'neutral', aide: 'Ce devis a été retiré.' },
}
