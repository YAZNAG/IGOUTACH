/**
 * Accès à l'API de gestion iGouTech.
 *
 * La boutique n'a pas de base à elle : catalogue, stock, prix et commandes
 * vivent dans l'API de gestion. Les pages publiques appellent /api/store
 * sans authentification ; le tableau de bord appelle /api/v1 avec un jeton
 * obtenu à la connexion (même compte que l'application de gestion).
 */
export const API_URL = (import.meta.env.VITE_API_URL as string | undefined) ?? 'https://igoutech.optizaworks.com/api'

const CLE_JETON = 'store-admin:token'

export function jeton(): string | null {
  try {
    return localStorage.getItem(CLE_JETON)
  } catch {
    return null
  }
}

export function memoriserJeton(valeur: string | null): void {
  try {
    if (valeur) localStorage.setItem(CLE_JETON, valeur)
    else localStorage.removeItem(CLE_JETON)
  } catch {
    /* stockage indisponible : la session ne survit pas au rechargement */
  }
}

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(status: number, message: string, errors?: Record<string, string[]>) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

interface Options {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  params?: Record<string, string | number | boolean | undefined | null>
  auth?: boolean
}

export async function api<T>(chemin: string, options: Options = {}): Promise<T> {
  const url = new URL(API_URL + chemin)
  for (const [k, v] of Object.entries(options.params ?? {})) {
    if (v !== undefined && v !== null && v !== '' && v !== false) url.searchParams.set(k, String(v))
  }

  const entetes: Record<string, string> = { Accept: 'application/json' }
  if (options.body !== undefined) entetes['Content-Type'] = 'application/json'
  if (options.auth) {
    const t = jeton()
    if (t) entetes.Authorization = `Bearer ${t}`
  }

  const reponse = await fetch(url.toString(), {
    method: options.method ?? 'GET',
    headers: entetes,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  })

  if (reponse.status === 204) return undefined as T

  const texte = await reponse.text()
  let json: unknown = null
  try {
    json = texte ? JSON.parse(texte) : null
  } catch {
    json = null
  }

  if (!reponse.ok) {
    const corps = (json ?? {}) as { message?: string; errors?: Record<string, string[]> }
    if (reponse.status === 401 && options.auth) memoriserJeton(null)
    throw new ApiError(reponse.status, corps.message ?? `Erreur ${reponse.status}`, corps.errors)
  }

  return json as T
}

export function messageErreur(e: unknown, repli = 'Une erreur est survenue.'): string {
  if (e instanceof ApiError) {
    const premiere = e.errors ? Object.values(e.errors)[0]?.[0] : undefined
    return premiere ?? e.message
  }
  if (e instanceof TypeError) return 'Connexion impossible : vérifiez votre réseau.'
  return repli
}

export function dh(valeur: number): string {
  return `${valeur.toLocaleString('fr-MA', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} DH`
}

export function dateHeure(iso: string | null | undefined): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
