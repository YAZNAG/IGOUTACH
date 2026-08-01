export type Lang = 'fr' | 'ar'

const STORAGE_KEY = 'igoutech-lang'

export function getStoredLang(): Lang {
  return localStorage.getItem(STORAGE_KEY) === 'ar' ? 'ar' : 'fr'
}

/**
 * Applique la langue au document : attribut lang + sens de lecture (RTL pour l'arabe).
 */
export function applyLang(lang: Lang): void {
  const root = document.documentElement
  root.lang = lang
  root.dir = lang === 'ar' ? 'rtl' : 'ltr'
  localStorage.setItem(STORAGE_KEY, lang)
}
