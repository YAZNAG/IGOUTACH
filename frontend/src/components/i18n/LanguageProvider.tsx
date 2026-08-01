import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'
import { applyLang, getStoredLang, type Lang } from '@/lib/lang'

interface LanguageContextValue {
  lang: Lang
  setLang: (lang: Lang) => void
}

const LanguageContext = createContext<LanguageContextValue | null>(null)

export function LanguageProvider({ children }: { children: ReactNode }) {
  const [lang, setLangState] = useState<Lang>(() => getStoredLang())

  const setLang = useCallback((next: Lang) => {
    applyLang(next)
    setLangState(next)
  }, [])

  const value = useMemo(() => ({ lang, setLang }), [lang, setLang])

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>
}

export function useLanguage(): LanguageContextValue {
  const context = useContext(LanguageContext)
  if (context === null) {
    throw new Error('useLanguage doit être utilisé dans un LanguageProvider.')
  }
  return context
}
