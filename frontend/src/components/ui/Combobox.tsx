import { ChevronDown, X } from 'lucide-react'
import { useEffect, useId, useMemo, useRef, useState } from 'react'
import { cn } from '@/lib/utils'

export interface ComboboxOption {
  id: number
  label: string
  /** Complément affiché en retrait et cherché lui aussi : un code, une ville. */
  hint?: string
}

interface ComboboxProps {
  id?: string
  /** 0 : rien de choisi. */
  value: number
  onChange: (id: number) => void
  options: ComboboxOption[]
  /** Texte du champ vide : « Tous les fournisseurs », « Choisir… ». */
  placeholder?: string
  /** Permet d'effacer le choix (filtres). Faux sur un champ obligatoire. */
  allowEmpty?: boolean
  disabled?: boolean
  className?: string
}

/** Minuscules sans accents : « société » trouve « SOCIÉTÉ » et « societe ». */
function normaliser(s: string): string {
  return s
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .trim()
}

/**
 * Liste déroulante dans laquelle on tape.
 *
 * Une liste native ne cherche que sur la première lettre : avec quarante
 * fournisseurs, on tape « S » et on obtient le premier « STE ». Ici on tape
 * n'importe quel morceau du nom ou du code — « yousa », « brahim » —, dans
 * n'importe quel ordre de mots, et la liste se réduit à ce qui correspond.
 *
 * Le choix se fait au clic ou au clavier (flèches, Entrée, Échap). Le texte
 * tapé n'est jamais la valeur : seule une entrée de la liste l'est, et un
 * texte sans correspondance revient au choix précédent quand on quitte le
 * champ.
 */
export function Combobox({
  id,
  value,
  onChange,
  options,
  placeholder = 'Choisir…',
  allowEmpty = true,
  disabled = false,
  className,
}: ComboboxProps) {
  const idInterne = useId()
  const idListe = `${id ?? idInterne}-liste`
  const racine = useRef<HTMLDivElement>(null)
  const champ = useRef<HTMLInputElement>(null)

  const choisi = useMemo(() => options.find((o) => o.id === value) ?? null, [options, value])

  const [ouvert, setOuvert] = useState(false)
  const [texte, setTexte] = useState('')
  const [surligne, setSurligne] = useState(0)

  // Le champ affiche le choix tant qu'on ne tape pas : à l'ouverture, on
  // repart d'un texte vide pour que la liste entière soit proposée.
  const affichage = ouvert ? texte : (choisi?.label ?? '')

  const filtrees = useMemo(() => {
    const mots = normaliser(texte).split(/\s+/).filter(Boolean)
    if (mots.length === 0) return options
    return options.filter((o) => {
      const cible = normaliser(`${o.label} ${o.hint ?? ''}`)
      return mots.every((m) => cible.includes(m))
    })
  }, [options, texte])

  useEffect(() => {
    setSurligne(0)
  }, [texte, ouvert])

  // Un clic hors du composant ferme la liste et abandonne le texte tapé.
  useEffect(() => {
    if (!ouvert) return
    function dehors(e: MouseEvent) {
      if (racine.current && !racine.current.contains(e.target as Node)) {
        setOuvert(false)
        setTexte('')
      }
    }
    document.addEventListener('mousedown', dehors)
    return () => document.removeEventListener('mousedown', dehors)
  }, [ouvert])

  function ouvrir() {
    if (disabled) return
    setTexte('')
    setOuvert(true)
  }

  function fermer() {
    setOuvert(false)
    setTexte('')
  }

  function choisir(o: ComboboxOption) {
    onChange(o.id)
    fermer()
    champ.current?.blur()
  }

  function effacer() {
    onChange(0)
    setTexte('')
    champ.current?.focus()
  }

  function clavier(e: React.KeyboardEvent<HTMLInputElement>) {
    if (!ouvert) {
      if (e.key === 'ArrowDown' || e.key === 'Enter') {
        e.preventDefault()
        ouvrir()
      }
      return
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      setSurligne((i) => Math.min(i + 1, filtrees.length - 1))
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      setSurligne((i) => Math.max(i - 1, 0))
    } else if (e.key === 'Enter') {
      e.preventDefault()
      const o = filtrees[surligne]
      if (o) choisir(o)
    } else if (e.key === 'Escape') {
      e.preventDefault()
      fermer()
    } else if (e.key === 'Tab') {
      fermer()
    }
  }

  return (
    <div ref={racine} className={cn('relative', className)}>
      <input
        ref={champ}
        id={id}
        type="text"
        role="combobox"
        aria-expanded={ouvert}
        aria-controls={idListe}
        aria-autocomplete="list"
        autoComplete="off"
        disabled={disabled}
        value={affichage}
        placeholder={placeholder}
        onFocus={ouvrir}
        onClick={() => {
          if (!ouvert) ouvrir()
        }}
        onChange={(e) => {
          if (!ouvert) setOuvert(true)
          setTexte(e.target.value)
        }}
        onKeyDown={clavier}
        className={cn(
          'h-10 w-full rounded border border-line-2 bg-card pl-3 pr-16 text-sm text-ink placeholder:text-faint',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky focus-visible:border-sky',
          'disabled:cursor-not-allowed disabled:opacity-60',
        )}
      />

      <div className="pointer-events-none absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-1">
        {choisi && allowEmpty && !disabled ? (
          <button
            type="button"
            onClick={effacer}
            aria-label="Effacer le choix"
            className="pointer-events-auto rounded p-1 text-faint transition-colors hover:text-ink"
          >
            <X className="h-4 w-4" />
          </button>
        ) : null}
        <ChevronDown className="h-4 w-4 text-faint" />
      </div>

      {ouvert ? (
        <ul
          id={idListe}
          role="listbox"
          className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded border border-line bg-card py-1 shadow-lg"
        >
          {filtrees.length === 0 ? (
            <li className="px-3 py-2 text-sm text-muted">Aucun résultat pour « {texte} »</li>
          ) : (
            filtrees.map((o, i) => (
              <li
                key={o.id}
                role="option"
                aria-selected={o.id === value}
                // mousedown plutôt que click : le blur du champ, sinon,
                // ferme la liste avant que le clic n'arrive.
                onMouseDown={(e) => {
                  e.preventDefault()
                  choisir(o)
                }}
                onMouseEnter={() => setSurligne(i)}
                className={cn(
                  'flex cursor-pointer items-baseline justify-between gap-3 px-3 py-2 text-sm text-ink',
                  i === surligne ? 'bg-surface-2' : '',
                  o.id === value ? 'font-medium' : '',
                )}
              >
                <span className="truncate">{o.label}</span>
                {o.hint ? <span className="mono shrink-0 text-xs text-faint">{o.hint}</span> : null}
              </li>
            ))
          )}
        </ul>
      ) : null}
    </div>
  )
}
