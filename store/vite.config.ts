import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// Boutique en ligne iGouTech : site public + tableau de bord (/admin).
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: { port: 5190 },
  build: { chunkSizeWarningLimit: 900 },
})
