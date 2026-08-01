import { QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { RouterProvider } from 'react-router-dom'
import { LanguageProvider } from '@/components/i18n/LanguageProvider'
import { ThemeProvider } from '@/components/theme/ThemeProvider'
import { applyLang, getStoredLang } from '@/lib/lang'
import { queryClient } from '@/lib/queryClient'
import { applyTheme, getStoredTheme } from '@/lib/theme'
import { router } from '@/routes/AppRouter'
import '@/styles/global.css'

// Applique le thème et la langue avant le premier rendu.
applyTheme(getStoredTheme())
applyLang(getStoredLang())

const rootElement = document.getElementById('root')

if (!rootElement) {
  throw new Error("L'élément racine #root est introuvable.")
}

createRoot(rootElement).render(
  <StrictMode>
    <ThemeProvider>
      <LanguageProvider>
        <QueryClientProvider client={queryClient}>
          <RouterProvider router={router} />
        </QueryClientProvider>
      </LanguageProvider>
    </ThemeProvider>
  </StrictMode>,
)
