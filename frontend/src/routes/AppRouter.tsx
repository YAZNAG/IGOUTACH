import { createBrowserRouter, Navigate } from 'react-router-dom'
import { AppLayout } from '@/components/layout/AppLayout'
import { PlaceholderPage } from '@/components/layout/PlaceholderPage'
import { RolesPage, UsersPage } from '@/features/access'
import { ArticlesPage } from '@/features/articles'
import { AuditPage } from '@/features/audit'
import { LoginPage } from '@/features/auth'
import { BrandsPage } from '@/features/brands'
import { CategoriesPage } from '@/features/categories'
import { CustomersPage } from '@/features/customers'
import { InventoryPage } from '@/features/inventory'
import { PricingPage } from '@/features/pricing'
import { SessionsPage } from '@/features/sessions'
import {
  BackupPage,
  DocumentSequencesPage,
  GeneralSettingsPage,
  ParametresLayout,
  PaymentMethodsPage,
  TaxRatesSettingsPage,
} from '@/features/settings'
import { StockPage } from '@/features/stock'
import { SuppliersPage } from '@/features/suppliers'
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
          { path: '/alertes', element: <PlaceholderPage title="Alertes" /> },
          { path: '/rapports', element: <PlaceholderPage title="Rapports" /> },
          { path: '/stock', element: <StockPage /> },
          { path: '/inventaire', element: <InventoryPage /> },
          { path: '/transferts', element: <PlaceholderPage title="Transferts" /> },
          { path: '/achats', element: <PlaceholderPage title="Achats" /> },
          { path: '/ventes', element: <PlaceholderPage title="Ventes" /> },
          { path: '/categories', element: <CategoriesPage /> },
          { path: '/articles', element: <ArticlesPage /> },
          { path: '/tarifs', element: <PricingPage /> },
          { path: '/fournisseurs', element: <SuppliersPage /> },
          { path: '/clients', element: <CustomersPage /> },
          { path: '/charges', element: <PlaceholderPage title="Charges" /> },
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
