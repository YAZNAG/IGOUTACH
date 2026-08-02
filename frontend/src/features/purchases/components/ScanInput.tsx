import { Barcode } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Input } from '@/components/ui/Input'
import { useProductAutoComplete } from '../hooks'
import type { ProductOption } from '../api/purchaseOrdersApi'

interface ScanInputProps {
  onScan: (product: ProductOption) => void
  warehouseId?: number
  exclude?: number[]
}

export function ScanInput({ onScan, warehouseId, exclude = [] }: ScanInputProps) {
  const [scanValue, setScanValue] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)
  const [flashKey, setFlashKey] = useState(0)

  // Fetch product by barcode/sku
  const { data: options = [] } = useProductAutoComplete(scanValue, warehouseId)

  useEffect(() => {
    inputRef.current?.focus()
  }, [])

  useEffect(() => {
    // If we have a single match and it's not excluded, scan it
    if (scanValue.trim().length > 0) {
      const filtered = options.filter((o) => !exclude.includes(o.id))
      if (filtered.length === 1) {
        // Auto-select after a short delay
        const timer = setTimeout(() => {
          onScan(filtered[0])
          setScanValue('')
          // Flash animation
          setFlashKey((k) => k + 1)
          inputRef.current?.focus()
        }, 100)
        return () => clearTimeout(timer)
      }
    }
  }, [scanValue, options, exclude, onScan])

  return (
    <div
      key={flashKey}
      className={`relative transition-colors duration-300 ${
        flashKey > 0 ? 'bg-sky-50 dark:bg-sky-950' : ''
      }`}
    >
      <div className="absolute left-3 top-1/2 -translate-y-1/2 text-muted">
        <Barcode className="h-4 w-4" />
      </div>
      <Input
        ref={inputRef}
        type="text"
        placeholder="Scannez un article…"
        value={scanValue}
        onChange={(e) => setScanValue(e.target.value)}
        className="pl-9"
        autoComplete="off"
      />
    </div>
  )
}
