import { useEffect, useState } from 'react'

/**
 * Valeur retardée : une requête au repos de frappe, pas par caractère.
 *
 * À utiliser dès qu'une saisie déclenche un appel réseau — une recherche
 * serveur sur une liste paginée en émettrait sinon une par lettre.
 */
export function useDebouncedValue<T>(value: T, delayMs = 250): T {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delayMs)
    return () => clearTimeout(timer)
  }, [value, delayMs])

  return debounced
}
