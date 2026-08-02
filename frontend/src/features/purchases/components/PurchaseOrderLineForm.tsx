import { Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'

export interface PurchaseOrderLineItem {
  product_id: number
  sku: string
  name: string
  current_stock: number
  min_stock_alert: number
  quantity: number
  cost_price?: number
}

interface PurchaseOrderLineFormProps {
  line: PurchaseOrderLineItem
  index: number
  onQuantityChange: (index: number, quantity: number) => void
  onRemove: (index: number) => void
  showCostPrice?: boolean
}

export function PurchaseOrderLineForm({
  line,
  index,
  onQuantityChange,
  onRemove,
  showCostPrice = false,
}: PurchaseOrderLineFormProps) {
  const isLowStock = line.current_stock < line.min_stock_alert

  return (
    <div className="flex items-center gap-2 rounded border border-line bg-card p-3">
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <span className="mono text-xs font-medium text-muted">{line.sku}</span>
          <Badge tone={isLowStock ? 'warn' : 'ok'}>
            Stock: {line.current_stock}
          </Badge>
          <span className="text-xs text-muted">Seuil: {line.min_stock_alert}</span>
        </div>
        <p className="text-sm text-ink truncate mt-1">{line.name}</p>
        {showCostPrice && line.cost_price ? (
          <p className="text-xs text-muted">Dernier prix: {line.cost_price} DH</p>
        ) : null}
      </div>

      <div className="flex items-center gap-2">
        <div className="flex flex-col items-center gap-1">
          <label htmlFor={`qty-${index}`} className="text-xs text-muted">
            Qté
          </label>
          <Input
            id={`qty-${index}`}
            type="number"
            min={1}
            value={line.quantity}
            onChange={(e) => onQuantityChange(index, Math.max(1, Number(e.target.value)))}
            className="w-20 text-center"
          />
        </div>
        <Button
          variant="ghost"
          size="sm"
          onClick={() => onRemove(index)}
          className="text-bad hover:bg-bad-bg"
          title={`Retirer ${line.name}`}
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>
    </div>
  )
}
