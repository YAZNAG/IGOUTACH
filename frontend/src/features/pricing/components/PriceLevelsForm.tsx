import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { formatNumber } from '@/lib/utils'
import type { PriceLevelInput, ProductPrices } from '../api/pricingApi'

interface PriceLevelsFormProps {
  data: ProductPrices
  isPending: boolean
  onSubmit: (prices: PriceLevelInput[]) => void
  onCancel: () => void
}

function marginPercent(amount: number, cost: number | null): number | null {
  if (cost === null || amount <= 0) return null
  return Math.round(((amount - cost) / amount) * 1000) / 10
}

export function PriceLevelsForm({ data, isPending, onSubmit, onCancel }: PriceLevelsFormProps) {
  const cost = data.unit_cost
  const [amounts, setAmounts] = useState<Record<string, string>>(() =>
    Object.fromEntries(data.levels.map((l) => [l.code, l.amount !== null ? String(l.amount) : ''])),
  )
  const [quantities, setQuantities] = useState<Record<string, string>>(() =>
    Object.fromEntries(data.levels.map((l) => [l.code, String(l.min_quantity)])),
  )

  const order = data.levels.map((l) => l.code)
  const numericAmount = (code: string) => Number(amounts[code] || 0)

  const detail = numericAmount('detail')
  const semi = numericAmount('semi_gros')
  const gros = numericAmount('gros')
  const orderOk = gros <= semi && semi <= detail

  function submit() {
    if (!orderOk) return
    onSubmit(
      order.map((code) => ({
        price_type_code: code,
        amount: numericAmount(code),
        min_quantity: Math.max(1, Number(quantities[code] || 1)),
        min_margin_percent: data.levels.find((l) => l.code === code)?.min_margin_percent ?? 0,
      })),
    )
  }

  return (
    <div className="space-y-4">
      {cost !== null ? (
        <div className="flex items-center gap-2 rounded-lg bg-sky-soft px-4 py-2 text-sm text-navy">
          <span className="font-medium">CMUP (coût moyen) :</span>
          <span className="mono">{formatNumber(cost)} DH</span>
        </div>
      ) : null}

      <div className="hidden grid-cols-12 gap-3 px-1 text-xs font-medium uppercase tracking-wide text-faint sm:grid">
        <span className="col-span-3">Niveau</span>
        <span className="col-span-3">À partir de (qté)</span>
        <span className="col-span-3">Prix (DH)</span>
        <span className="col-span-3 text-right">Marge</span>
      </div>

      <div className="space-y-3">
        {data.levels.map((level) => {
          const amount = numericAmount(level.code)
          const margin = marginPercent(amount, cost)
          const belowFloor = margin !== null && margin < level.min_margin_percent
          return (
            <div key={level.code} className="grid grid-cols-1 items-center gap-3 sm:grid-cols-12">
              <div className="sm:col-span-3">
                <p className="text-sm font-medium text-ink">{level.name}</p>
              </div>
              <div className="sm:col-span-3">
                <Input
                  type="number"
                  min={1}
                  step="1"
                  value={quantities[level.code] ?? ''}
                  onChange={(e) => setQuantities((prev) => ({ ...prev, [level.code]: e.target.value }))}
                />
              </div>
              <div className="sm:col-span-3">
                <Input
                  type="number"
                  step="0.01"
                  min={0}
                  value={amounts[level.code] ?? ''}
                  onChange={(e) => setAmounts((prev) => ({ ...prev, [level.code]: e.target.value }))}
                />
              </div>
              <div className="text-right sm:col-span-3">
                {margin !== null ? (
                  <Badge tone={belowFloor ? 'bad' : margin >= 20 ? 'ok' : 'warn'}>Marge {margin}%</Badge>
                ) : (
                  <span className="text-xs text-faint">—</span>
                )}
              </div>
            </div>
          )
        })}
      </div>

      {!orderOk ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
          L'ordre doit respecter : gros ≤ demi-gros ≤ détail.
        </p>
      ) : null}

      <div className="flex justify-end gap-3 border-t border-line pt-4">
        <Button type="button" variant="outline" onClick={onCancel}>
          Annuler
        </Button>
        <Button type="button" onClick={submit} disabled={isPending || !orderOk}>
          {isPending ? 'Enregistrement…' : 'Enregistrer les tarifs'}
        </Button>
      </div>
    </div>
  )
}
