import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Lock, Unlock } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface Session {
  id: number
  warehouse: string | null
  opened_by: string | null
  opened_at: string
  opening_amount: number
  closed_at: string | null
  closing_amount: number | null
  expected_amount: number | null
  difference: number | null
  status: string
}

const KEY = ['cash-sessions'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Caisse : ouverture avec fonds, clôture avec écart calculé
 * (attendu = fonds + encaissements de la session).
 */
export function CashPage() {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()
  const [warehouseId, setWarehouseId] = useState(0)
  const [openingAmount, setOpeningAmount] = useState('')
  const [closingAmount, setClosingAmount] = useState('')

  const { data: current } = useQuery<Session | null>({
    queryKey: [...KEY, 'current', warehouseId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: Session | null }>('/cash-sessions/current', {
        params: { warehouse_id: warehouseId },
      })
      return r.data
    },
    enabled: warehouseId > 0,
  })

  const { data: history } = useQuery<Paginated<Session>>({
    queryKey: [...KEY, 'history', warehouseId],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<Session>>('/cash-sessions', {
        params: { warehouse_id: warehouseId || undefined },
      })
      return r
    },
  })

  const open = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post('/cash-sessions/open', { warehouse_id: warehouseId, opening_amount: Number(openingAmount) })
    },
    onSuccess: () => {
      setOpeningAmount('')
      qc.invalidateQueries({ queryKey: KEY })
    },
  })

  const close = useMutation({
    mutationFn: async () => {
      if (current == null) return
      await ensureCsrfCookie()
      await api.post(`/cash-sessions/${current.id}/close`, { closing_amount: Number(closingAmount) })
    },
    onSuccess: () => {
      setClosingAmount('')
      qc.invalidateQueries({ queryKey: KEY })
    },
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Caisse</h1>
        <p className="text-sm text-muted">Sessions de caisse par lieu : fonds d'ouverture, clôture et écart.</p>
      </div>

      <Card>
        <CardHeader title="Session courante" />
        <CardBody className="space-y-4">
          <Field label="Lieu" htmlFor="cash-warehouse">
            <Select id="cash-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))} className="max-w-xs">
              <option value="" disabled>Choisir un lieu…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>

          {warehouseId > 0 ? (
            current != null ? (
              <div className="space-y-3">
                <p className="text-sm text-muted">
                  Ouverte par <b className="text-ink">{current.opened_by}</b> le {current.opened_at} — fonds initial{' '}
                  <b className="tabular text-ink">{formatNumber(current.opening_amount)} DH</b>
                </p>
                <div className="flex items-end gap-2">
                  <Field label="Montant compté à la clôture (DH)" htmlFor="cash-closing">
                    <Input id="cash-closing" type="number" min={0} step="0.01" value={closingAmount} onChange={(e) => setClosingAmount(e.target.value)} className="w-56" />
                  </Field>
                  <Button onClick={() => close.mutate()} disabled={close.isPending || closingAmount === ''}>
                    <Lock className="h-4 w-4" />
                    Clôturer la session
                  </Button>
                </div>
                {close.isError ? <p className="text-sm text-bad">{errorMessage(close.error, 'Clôture impossible.')}</p> : null}
              </div>
            ) : (
              <div className="flex items-end gap-2">
                <Field label="Fonds d'ouverture (DH)" htmlFor="cash-opening">
                  <Input id="cash-opening" type="number" min={0} step="0.01" value={openingAmount} onChange={(e) => setOpeningAmount(e.target.value)} className="w-56" />
                </Field>
                <Button onClick={() => open.mutate()} disabled={open.isPending || openingAmount === ''}>
                  <Unlock className="h-4 w-4" />
                  Ouvrir la caisse
                </Button>
                {open.isError ? <p className="self-center text-sm text-bad">{errorMessage(open.error, 'Ouverture impossible.')}</p> : null}
              </div>
            )
          ) : (
            <p className="text-sm text-muted">Sélectionnez un lieu pour gérer sa caisse.</p>
          )}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Historique des sessions" />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Lieu</th>
                <th className="px-5 py-3 font-medium">Ouverture</th>
                <th className="px-5 py-3 text-right font-medium">Fonds</th>
                <th className="px-5 py-3 font-medium">Clôture</th>
                <th className="px-5 py-3 text-right font-medium">Attendu</th>
                <th className="px-5 py-3 text-right font-medium">Compté</th>
                <th className="px-5 py-3 text-right font-medium">Écart</th>
                <th className="px-5 py-3 font-medium">Statut</th>
              </tr>
            </thead>
            <tbody>
              {(history?.data ?? []).length === 0 ? (
                <tr><td colSpan={8} className="px-5 py-8 text-center text-muted">Aucune session.</td></tr>
              ) : (
                (history?.data ?? []).map((s) => (
                  <tr key={s.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink">{s.warehouse}</td>
                    <td className="px-5 py-3 text-muted">{s.opened_at}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(s.opening_amount)}</td>
                    <td className="px-5 py-3 text-muted">{s.closed_at ?? '—'}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{s.expected_amount !== null ? formatNumber(s.expected_amount) : '—'}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{s.closing_amount !== null ? formatNumber(s.closing_amount) : '—'}</td>
                    <td className={cn('tabular px-5 py-3 text-right font-medium', (s.difference ?? 0) === 0 ? 'text-muted' : (s.difference ?? 0) > 0 ? 'text-ok' : 'text-bad')}>
                      {s.difference !== null ? formatNumber(s.difference) : '—'}
                    </td>
                    <td className="px-5 py-3">
                      {s.status === 'open' ? <Badge tone="ok">Ouverte</Badge> : <Badge tone="neutral">Clôturée</Badge>}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
