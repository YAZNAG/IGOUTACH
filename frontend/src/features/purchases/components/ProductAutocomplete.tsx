import { Loader2, Search } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { useProductAutoComplete } from '../hooks'
import type { ProductOption } from '../api/purchaseOrdersApi'

interface ProductAutocompleteProps {
  value: string
  onChange: (value: string) => void
  onSelect: (product: ProductOption) => void
  exclude?: number[]
  warehouseId?: number
  placeholder?: string
}

/** Valeur retardée : évite une requête à chaque frappe. */
function useDebouncedValue(value: string, delayMs: number): string {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delayMs)
    return () => clearTimeout(timer)
  }, [value, delayMs])
  return debounced
}

export function ProductAutocomplete({
  value,
  onChange,
  onSelect,
  exclude = [],
  warehouseId,
  placeholder = 'Rechercher article (référence, nom, code-barres)…',
}: ProductAutocompleteProps) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)

  const debouncedSearch = useDebouncedValue(value, 250)
  const { data: options = [], isFetching } = useProductAutoComplete(debouncedSearch, warehouseId)

  const filteredOptions = options.filter((o) => !exclude.includes(o.id))
  const searching = value.trim().length >= 1

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSelect = (product: ProductOption) => {
    onSelect(product)
    onChange('')
    setIsOpen(false)
    inputRef.current?.focus()
  }

  return (
    <div ref={containerRef} className="relative">
      <div className="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-muted">
        {isFetching ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
      </div>
      <Input
        ref={inputRef}
        type="text"
        placeholder={placeholder}
        value={value}
        onChange={(e) => {
          onChange(e.target.value)
          setIsOpen(true)
        }}
        onFocus={() => setIsOpen(true)}
        autoComplete="off"
        className="pl-9"
      />

      {isOpen && searching ? (
        <div className="absolute top-full z-50 mt-1 w-full overflow-hidden rounded-lg border border-line-2 bg-card shadow-xl">
          {filteredOptions.length === 0 ? (
            <p className="px-4 py-3 text-sm text-muted">
              {isFetching ? 'Recherche…' : 'Aucun article trouvé.'}
            </p>
          ) : (
            <ul className="max-h-72 overflow-auto">
              {filteredOptions.map((product) => (
                <li key={product.id}>
                  <button
                    type="button"
                    onClick={() => handleSelect(product)}
                    className="flex w-full items-center justify-between gap-3 border-b border-line px-4 py-2.5 text-left last:border-0 hover:bg-bg"
                  >
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <span className="mono text-xs font-medium text-sky">{product.sku}</span>
                        <span className="truncate text-sm text-ink">{product.name}</span>
                      </div>
                      {product.barcode ? (
                        <span className="mono text-xs text-muted">{product.barcode}</span>
                      ) : null}
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                      <Badge tone={product.current_stock < product.min_stock_alert ? 'warn' : 'ok'}>
                        Stock : {product.current_stock}
                      </Badge>
                      <span className="text-xs text-muted">Seuil : {product.min_stock_alert}</span>
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      ) : null}
    </div>
  )
}
