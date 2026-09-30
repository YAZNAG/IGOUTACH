import {
  AlertTriangle,
  ArrowLeftRight,
  Boxes,
  Building2,
  Calculator,
  CalendarClock,
  ClipboardList,
  FileBarChart,
  FileText,
  HandCoins,
  History,
  LayoutDashboard,
  MonitorSmartphone,
  Package,
  PiggyBank,
  PackageCheck,
  PackageMinus,
  PackagePlus,
  Receipt,
  Ruler,
  Settings,
  ShieldCheck,
  ShoppingCart,
  Store,
  Tag,
  Tags,
  Truck,
  TrendingUp,
  Undo2,
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
  /**
   * Permission qui MASQUE l'entrée. Sert aux doublons d'un même écran : un
   * responsable a « Mon tableau de bord », la direction a « Vue globale » —
   * les deux pointent vers l'accueil, chacun ne doit voir que le sien.
   */
  hiddenFor?: string
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
      { label: 'Mon tableau de bord', to: '/', icon: LayoutDashboard, permission: 'stock.view', hiddenFor: 'stock.view_global' },
      { label: 'Alertes', to: '/alertes', icon: AlertTriangle, permission: 'stock.view' },
      { label: "Chiffre d'affaires", to: '/chiffre-affaires', icon: TrendingUp, permission: 'report.consolidated' },
      { label: 'Bénéfice', to: '/benefice', icon: PiggyBank, permission: 'report.consolidated' },
      { label: 'Rapports', to: '/rapports', icon: FileBarChart, permission: 'report.consolidated' },
    ],
  },
  {
    title: 'Stock',
    items: [
      { label: 'État du stock', to: '/stock', icon: Boxes, permission: 'stock.view' },
      { label: 'Historique des mouvements', to: '/mouvements', icon: History, permission: 'stock.view' },
      { label: 'Entrées de stock', to: '/stock-entries', icon: PackagePlus, permission: 'stock.view' },
      { label: 'Sorties de stock', to: '/stock-exits', icon: PackageMinus, permission: 'stock.view' },
      { label: 'Transferts', to: '/transferts', icon: ArrowLeftRight, permission: 'stock.view' },
      { label: 'Inventaire', to: '/inventaire', icon: ClipboardList, permission: 'inventory.create' },
    ],
  },
  {
    title: 'Achats',
    items: [
      { label: 'Bons de commande', to: '/purchase-orders', icon: ShoppingCart, permission: 'purchase.view' },
      { label: 'Réceptions', to: '/goods-receipts', icon: PackageCheck, permission: 'receipt.view' },
      { label: 'Retours fournisseurs', to: '/retours-fournisseurs', icon: Undo2, permission: 'purchase.return' },
      { label: 'Crédits fournisseurs', to: '/supplier-credits', icon: HandCoins, permission: 'receipt.view' },
      { label: 'Fournisseurs', to: '/fournisseurs', icon: Truck, permission: 'supplier.view' },
    ],
  },
  {
    title: 'Ventes',
    items: [
      { label: 'Devis', to: '/devis', icon: FileText, permission: 'quote.create' },
      { label: 'Ventes', to: '/ventes', icon: Store, permission: 'sale.create' },
      { label: 'Retours clients', to: '/retours-clients', icon: Undo2, permission: 'sale.return' },
      { label: 'Crédits clients', to: '/credits-clients', icon: HandCoins, permission: 'credit.view' },
      { label: 'Clients', to: '/clients', icon: Users, permission: 'customer.view' },
    ],
  },
  {
    title: 'Trésorerie',
    items: [
      { label: 'Règlements', to: '/reglements', icon: Receipt, permission: 'payment.view' },
      { label: 'Charges', to: '/charges', icon: Wallet, permission: 'expense.create' },
      { label: 'Charges fixes', to: '/charges-fixes', icon: CalendarClock, permission: 'expense.create' },
    ],
  },
  {
    title: 'Catalogue',
    items: [
      { label: 'Articles', to: '/articles', icon: Package, permission: 'product.view' },
      { label: 'Catégories', to: '/categories', icon: Tags, permission: 'category.view' },
      { label: 'Tarifs de vente', to: '/tarifs', icon: Tag, permission: 'price.view' },
      { label: 'Coûts des articles', to: '/couts', icon: Calculator, permission: 'product.view_cost_price' },
      { label: "Prix d'achat", to: '/prix-achat', icon: Receipt, permission: 'product.view_cost_price' },
      { label: 'Unités', to: '/unites', icon: Ruler, permission: 'unit.view' },
      { label: 'Marques', to: '/marques', icon: Tag, permission: 'brand.view' },
    ],
  },
  {
    title: 'Administration',
    items: [
      { label: 'Lieux', to: '/lieux', icon: Building2, permission: 'warehouse.manage' },
      { label: 'Utilisateurs', to: '/utilisateurs', icon: Users, permission: 'user.view' },
      { label: 'Rôles et permissions', to: '/roles', icon: ShieldCheck, permission: 'role.manage' },
      { label: 'Sessions actives', to: '/sessions', icon: MonitorSmartphone, permission: 'user.view' },
      { label: "Journal d'audit", to: '/audit', icon: Receipt, permission: 'audit.view' },
      { label: 'Paramètres', to: '/parametres', icon: Settings, permission: 'settings.manage' },
    ],
  },
]
