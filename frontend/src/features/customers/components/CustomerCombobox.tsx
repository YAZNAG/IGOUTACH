import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { useMemo, useState } from 'react'
import { Combobox } from '@/components/ui/Combobox'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { api } from '@/lib/api'
import type { Paginated } from '@/types'

export interface CustomerChoice {
  id: number
  code: string
  name: string
  phone?: string | null
  balance: number
  is_blocked: boolean
}

interface CustomerComboboxProps {
  id?: string
  /** 0 : aucun client choisi. */
  value: number
  /** Le client entier accompagne l'identifiant : l'écran a besoin de son encours, de son blocage. */
  onChange: (id: number, customer: CustomerChoice | null) => void
  placeholder?: string
  allowEmpty?: boolean
  disabled?: boolean
  className?: string
}

/**
 * Choix d'un client en tapant son nom, son code ou son téléphone.
 *
 * Les clients se comptent par centaines : la liste n'est pas chargée
 * entière, c'est le serveur qui cherche, à chaque pause de frappe. Le
 * client choisi reste affiché même quand la recherche suivante ne le
 * ramène plus : on le garde en tête de liste tant qu'il est sélectionné.
 */
export function CustomerCombobox({
  id,
  value,
  onChange,
  placeholder = 'Nom, code ou téléphone du client…',
  allowEmpty = true,
  disabled,
  className,
}: CustomerComboboxProps) {
  const [texte, setTexte] = useState('')
  const [choisi, setChoisi] = useState<CustomerChoice | null>(null)

  // Une requête au repos de frappe, pas par caractère.
  const recherche = useDebouncedValue(texte, 250)

  const { data: trouves = [], isFetching } = useQuery<CustomerChoice[]>({
    queryKey: ['customer-combobox', recherche],
    queryFn: async () => {
      const { data } = await api.get<Paginated<CustomerChoice>>('/customers', {
        params: { q: recherche || undefined, per_page: 30 },
      })
      return data.data
    },
    placeholderData: keepPreviousData,
    staleTime: 30_000,
    enabled: !disabled,
  })

  const options = useMemo(() => {
    const liste = trouves.map((c) => ({
      id: c.id,
      label: c.name,
      hint: c.code,
      badge: c.is_blocked ? 'Bloqué' : undefined,
    }))
    // Le choix courant doit rester présentable même hors des résultats.
    if (choisi && value === choisi.id && !liste.some((o) => o.id === choisi.id)) {
      liste.unshift({
        id: choisi.id,
        label: choisi.name,
        hint: choisi.code,
        badge: choisi.is_blocked ? 'Bloqué' : undefined,
      })
    }
    return liste
  }, [trouves, choisi, value])

  return (
    <Combobox
      id={id}
      value={value}
      onChange={(nouvelId) => {
        const client = trouves.find((c) => c.id === nouvelId) ?? null
        setChoisi(client)
        onChange(nouvelId, client)
      }}
      options={options}
      onSearch={setTexte}
      chargement={isFetching}
      placeholder={placeholder}
      allowEmpty={allowEmpty}
      disabled={disabled}
      className={className}
    />
  )
}
