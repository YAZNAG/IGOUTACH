import {
  AlertTriangle,
  ArrowLeftRight,
  Boxes,
  Building2,
  ClipboardList,
  FileBarChart,
  HandCoins,
  LayoutDashboard,
  MonitorSmartphone,
  Package,
  PackageCheck,
  Receipt,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Store,
  Tag,
  Tags,
  Truck,
  Users,
  Wallet,
  type LucideIcon,
} from 'lucide-react'

export interface NavItem {
  label: string
  to: string
  icon: LucideIcon
  /** Permission requise pour afficher l'entrée. Absente = toujours visible. */
  permission?: string
}

export interface NavGroup {
  title: string
  items: NavItem[]
}

export const navGroups: NavGroup[] = [
  {
    title: 'Pilotage',
    items: [
      { label: 'Vue globale', to: '/', icon: LayoutDashboard, permission: 'stock.view_global' },
      { label: 'Alertes', to: '/alertes', icon: AlertTriangle },
      { label: 'Rapports', to: '/rapports', icon: FileBarChart, permission: 'report.consolidated' },
    ],
  },
  {
    title: 'Exploitation',
    items: [
      { label: 'Stock', to: '/stock', icon: Boxes, permission: 'stock.view' },
      { label: 'Inventaire', to: '/inventaire', icon: ClipboardList, permission: 'inventory.create' },
      { label: 'Transferts', to: '/transferts', icon: ArrowLeftRight, permission: 'transfer.create' },
      { label: 'Achats', to: '/purchase-orders', icon: ShoppingCart, permission: 'purchase.create' },
      { label: 'Réceptions', to: '/goods-receipts', icon: PackageCheck, permission: 'receipt.view' },
      { label: 'Crédits fournisseurs', to: '/supplier-credits', icon: HandCoins, permission: 'receipt.view' },
      { label: 'Ventes', to: '/ventes', icon: Store, permission: 'sale.create' },
      { label: 'Règlements', to: '/reglements', icon: Receipt, permission: 'payment.view' },
      { label: 'Caisse', to: '/caisse', icon: Wallet, permission: 'cash.manage' },
      { label: 'Catégories', to: '/categories', icon: Tags, permission: 'category.view' },
      { label: 'Articles', to: '/articles', icon: Package, permission: 'product.view' },
      { label: 'Tarifs de vente', to: '/tarifs', icon: Tag, permission: 'price.view' },
    ],
  },
  {
    title: 'Gestion',
    items: [
      { label: 'Fournisseurs', to: '/fournisseurs', icon: Truck, permission: 'supplier.view' },
      { label: 'Clients', to: '/clients', icon: Users, permission: 'customer.view' },
      { label: 'Charges', to: '/charges', icon: Wallet, permission: 'expense.create' },
      { label: 'Lieux', to: '/lieux', icon: Building2, permission: 'warehouse.view' },
    ],
  },
  {
    title: 'Système',
    items: [
      { label: 'Utilisateurs', to: '/utilisateurs', icon: Users, permission: 'user.view' },
      { label: 'Rôles & permissions', to: '/roles', icon: ShieldCheck, permission: 'role.view' },
      { label: 'Sessions actives', to: '/sessions', icon: MonitorSmartphone, permission: 'user.manage_sessions' },
      { label: "Journal d'audit", to: '/audit', icon: Receipt, permission: 'audit.view' },
      { label: 'Paramètres', to: '/parametres', icon: Settings, permission: 'settings.view' },
    ],
  },
]
