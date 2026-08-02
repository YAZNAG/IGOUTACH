import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Select } from '@/components/ui/Select'
import type { Movement } from '../api/articlesApi'

interface MovementsTabProps {
  movements: Movement[]
  isLoading?: boolean
}

const movementTypeLabels: Record<string, string> = {
  entry: 'Entrée',
  exit: 'Sortie',
  transfer_out: 'Transfert sortant',
  transfer_in: 'Transfert entrant',
  inventory: 'Inventaire',
  adjustment: 'Ajustement',
  reservation: 'Réservation',
  unreservation: 'Dérés.',
}

const movementTypeTones: Record<string, 'ok' | 'bad' | 'warn' | 'sky'> = {
  entry: 'ok',
  exit: 'bad',
  transfer_out: 'warn',
  transfer_in: 'sky',
  inventory: 'sky',
  adjustment: 'sky',
  reservation: 'warn',
  unreservation: 'ok',
}

export function MovementsTab({ movements, isLoading }: MovementsTabProps) {
  const [typeFilter, setTypeFilter] = useState('')
  const [warehouseFilter, setWarehouseFilter] = useState('')

  const warehouses = Array.from(new Set(movements.map((m) => m.warehouse_name)))
  const types = Array.from(new Set(movements.map((m) => m.type)))

  const filtered = movements.filter((m) => {
    if (typeFilter && m.type !== typeFilter) return false
    if (warehouseFilter && m.warehouse_name !== warehouseFilter) return false
    return true
  })

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader
          title="Filtres"
          action={
            <div className="flex gap-2">
              <Select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="w-40"
              >
                <option value="">Tous les types</option>
                {types.map((t) => (
                  <option key={t} value={t}>
                    {movementTypeLabels[t] || t}
                  </option>
                ))}
              </Select>
              <Select
                value={warehouseFilter}
                onChange={(e) => setWarehouseFilter(e.target.value)}
                className="w-44"
              >
                <option value="">Tous les lieux</option>
                {warehouses.map((w) => (
                  <option key={w} value={w}>
                    {w}
                  </option>
                ))}
              </Select>
            </div>
          }
        />
      </Card>

      <Card>
        <CardHeader
          title="Mouvements"
          hint={`${filtered.length} mouvement(s)`}
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : filtered.length === 0 ? (
            <p className="p-5 text-sm text-muted">Aucun mouvement.</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Type</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium text-right">Quantité</th>
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Utilisateur</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((m) => (
                  <tr key={m.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-muted">
                      {new Date(m.created_at).toLocaleDateString('fr-FR')}
                    </td>
                    <td className="px-5 py-3">
                      <Badge
                        tone={movementTypeTones[m.type] || 'sky'}
                        className="text-xs"
                      >
                        {movementTypeLabels[m.type] || m.type}
                      </Badge>
                    </td>
                    <td className="px-5 py-3 text-ink">{m.warehouse_name}</td>
                    <td className="px-5 py-3 text-right font-medium text-ink">
                      {m.type === 'exit' || m.type === 'transfer_out' ? '-' : '+'}
                      {m.quantity}
                    </td>
                    <td className="px-5 py-3 text-muted">{m.reference || '—'}</td>
                    <td className="px-5 py-3 text-ink">{m.user.name}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
