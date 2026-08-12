import react from '@vitejs/plugin-react'
import path from 'node:path'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5180,
    strictPort: true,
  },
  build: {
    // Sans cible explicite, le minifieur CSS réécrit « (max-width: 767px) » en
    // « (width <= 767px) », syntaxe qu'aucun Safari antérieur à 16.4 ne
    // comprend : sur un iPhone un peu ancien, toute la mise en page téléphone
    // serait ignorée d'un bloc. L'application se consultant depuis des
    // téléphones de terrain, on vise volontairement plus large.
    cssTarget: ['safari13', 'chrome87', 'firefox78', 'edge88'],
  },
})
