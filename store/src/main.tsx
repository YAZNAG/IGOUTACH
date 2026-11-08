import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { AdminLayout } from './admin/AdminLayout'
import { AdminDashboard } from './admin/Dashboard'
import { AdminLogin } from './admin/Login'
import { AdminOrderDetail } from './admin/OrderDetail'
import { AdminOrders } from './admin/Orders'
import { AdminProducts } from './admin/Products'
import { AdminSettings } from './admin/Settings'
import { Layout } from './components/Layout'
import './index.css'
import { PanierProvider } from './lib/cart'
import { Cart } from './pages/Cart'
import { Catalog } from './pages/Catalog'
import { Checkout } from './pages/Checkout'
import { Home } from './pages/Home'
import { OrderDone } from './pages/OrderDone'
import { Product } from './pages/Product'
import { Track } from './pages/Track'

const client = new QueryClient({
  defaultOptions: { queries: { staleTime: 60_000, retry: 1, refetchOnWindowFocus: false } },
})

const routeur = createBrowserRouter([
  {
    path: '/',
    element: <Layout />,
    children: [
      { index: true, element: <Home /> },
      { path: 'catalogue', element: <Catalog /> },
      { path: 'categorie/:id', element: <Catalog /> },
      { path: 'article/:id', element: <Product /> },
      { path: 'panier', element: <Cart /> },
      { path: 'commande', element: <Checkout /> },
      { path: 'commande/confirmee', element: <OrderDone /> },
      { path: 'suivi', element: <Track /> },
    ],
  },
  { path: '/admin/connexion', element: <AdminLogin /> },
  {
    path: '/admin',
    element: <AdminLayout />,
    children: [
      { index: true, element: <AdminDashboard /> },
      { path: 'commandes', element: <AdminOrders /> },
      { path: 'commandes/:id', element: <AdminOrderDetail /> },
      { path: 'articles', element: <AdminProducts /> },
      { path: 'reglages', element: <AdminSettings /> },
    ],
  },
])

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={client}>
      <PanierProvider>
        <RouterProvider router={routeur} />
      </PanierProvider>
    </QueryClientProvider>
  </StrictMode>,
)
