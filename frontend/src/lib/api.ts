import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL

if (!API_URL) {
  throw new Error('VITE_API_URL est manquant dans le fichier .env du frontend.')
}

// Origine du backend (sans le préfixe /api/v1), pour le cookie CSRF Sanctum.
const apiOrigin = new URL(API_URL).origin

export const api = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  // Requis pour joindre le token XSRF (décodé) sur les requêtes cross-origin SPA.
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

/**
 * Récupère le cookie XSRF avant toute requête mutante (flux SPA Sanctum).
 * Axios renvoie ensuite automatiquement l'en-tête X-XSRF-TOKEN.
 */
export async function ensureCsrfCookie(): Promise<void> {
  await axios.get(`${apiOrigin}/sanctum/csrf-cookie`, { withCredentials: true })
}
