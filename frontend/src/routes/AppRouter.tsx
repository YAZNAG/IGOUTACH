import { createBrowserRouter, Navigate } from 'react-router-dom'
import { AppLayout } from '@/components/layout/AppLayout'
import { RolesPage, UsersPage } from '@/features/access'
import { ArticlesPage, ProductDetailPage } from '@/features/articles'
import { AuditPage } from '@/features/audit'
import { LoginPage } from '@/features/auth'
import { BrandsPage } from '@/features/brands'
import { CashPage } from '@/features/cash'
import { CategoriesPage } from '@/features/categories'
import { CustomerDetailPage, CustomersPage } from '@/features/customers'
import { ExpensesPage } from '@/features/expenses'
import { InventoryPage } from '@/features/inventory'
import { PaymentsPage } from '@/features/payments'
import { AlertsPage, ReportsPage } from '@/features/pilotage'
import {
  CreatePurchaseOrderPage,
  EditPurchaseOrderPage,
  GoodsReceiptDetailPage,
  GoodsReceiptsListPage,
  PurchaseOrderDetailPage,
  PurchaseOrdersListPage,
  PurchasesPage,
  ReceivePurchaseOrderPage,
  SupplierCreditsPage,
} from '@/features/purchases'
import { QuotesPage, SalesPage } from '@/features/sales'
import { TransfersPage } from '@/features/transfers'
import { PricingPage, ProductCostsPage } from '@/features/pricing'
import { SessionsPage } from '@/features/sessions'
import {
  BackupPage,
  DocumentSequencesPage,
  GeneralSettingsPage,
  ParametresLayout,
  PaymentMethodsPage,
  TaxRatesSettingsPage,
} from '@/features/settings'
import { StockEntriesPage, StockEntryDetailPage, StockExitDetailPage, StockExitsPage, StockPage } from '@/features/stock'
import { SupplierDetailPage, SuppliersPage } from '@/features/suppliers'
import { UnitsPage } from '@/features/units'
import { WarehouseDetailPage, WarehousesPage } from '@/features/warehouses'
import { HomePage } from './HomePage'
import { ProtectedRoute } from './ProtectedRoute'
import { PublicOnlyRoute } from './PublicOnlyRoute'

export const router = createBrowserRouter([
  {
    element: <PublicOnlyRoute />,
    children: [{ path: '/login', element: <LoginPage /> }],
  },
  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          { path: '/', element: <HomePage /> },
          { path: '/alertes', element: <AlertsPage /> },
          { path: '/rapports', element: <ReportsPage /> },
          { path: '/stock', element: <StockPage /> },
          { path: '/stock-entries', element: <StockEntriesPage /> },
          { path: '/stock-entries/:id', element: <StockEntryDetailPage /> },
          { path: '/stock-exits', element: <StockExitsPage /> },
          { path: '/stock-exits/:id', element: <StockExitDetailPage /> },
          { path: '/inventaire', element: <InventoryPage /> },
          { path: '/transferts', element: <TransfersPage /> },
          { path: '/achats', element: <PurchasesPage /> },
          { path: '/purchase-orders', element: <PurchaseOrdersListPage /> },
          { path: '/purchase-orders/create', element: <CreatePurchaseOrderPage /> },
          { path: '/purchase-orders/:id', element: <PurchaseOrderDetailPage /> },
          { path: '/purchase-orders/:id/edit', element: <EditPurchaseOrderPage /> },
          { path: '/purchase-orders/:id/receive', element: <ReceivePurchaseOrderPage /> },
          { path: '/goods-receipts', element: <GoodsReceiptsListPage /> },
          { path: '/goods-receipts/:id', element: <GoodsReceiptDetailPage /> },
          { path: '/supplier-credits', element: <SupplierCreditsPage /> },
          { path: '/ventes', element: <SalesPage /> },
          { path: '/devis', element: <QuotesPage /> },
          { path: '/reglements', element: <PaymentsPage /> },
          { path: '/caisse', element: <CashPage /> },
          { path: '/categories', element: <CategoriesPage /> },
          { path: '/articles', element: <ArticlesPage /> },
          { path: '/articles/:id', element: <ProductDetailPage /> },
          { path: '/tarifs', element: <PricingPage /> },
          { path: '/couts', element: <ProductCostsPage /> },
          { path: '/fournisseurs', element: <SuppliersPage /> },
          { path: '/fournisseurs/:id', element: <SupplierDetailPage /> },
          { path: '/clients', element: <CustomersPage /> },
          { path: '/clients/:id', element: <CustomerDetailPage /> },
          { path: '/charges', element: <ExpensesPage /> },
          { path: '/lieux', element: <WarehousesPage /> },
          { path: '/lieux/:id', element: <WarehouseDetailPage /> },
          { path: '/utilisateurs', element: <UsersPage /> },
          { path: '/roles', element: <RolesPage /> },
          { path: '/sessions', element: <SessionsPage /> },
          { path: '/audit', element: <AuditPage /> },
          {
            path: '/parametres',
            element: <ParametresLayout />,
            children: [
              { index: true, element: <Navigate to="/parametres/general" replace /> },
              { path: 'general', element: <GeneralSettingsPage /> },
              { path: 'taux-tva', element: <TaxRatesSettingsPage /> },
              { path: 'unites', element: <UnitsPage /> },
              { path: 'marques', element: <BrandsPage /> },
              { path: 'paiements', element: <PaymentMethodsPage /> },
              { path: 'numerotation', element: <DocumentSequencesPage /> },
              { path: 'sauvegarde', element: <BackupPage /> },
            ],
          },
        ],
      },
    ],
  },
])
