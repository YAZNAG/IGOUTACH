import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, Boxes, Save } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import { api } from '@/lib/api'
import type { Paginated } from '@/types'
import { useAssignWarehouseUsers, useWarehouse, useWarehouseSummary, useWarehouseUsers } from '../hooks'

interface SimpleUser {
  id: number
  name: string
  email: string
  is_active: boolean
}

function KpiCard({ label, value, tone }: { label: string; value: string; tone?: 'bad' | 'warn' }) {
  return (
    <Card>
      <CardBody>
        <p className="text-xs uppercase tracking-wide text-muted">{label}</p>
        <p className={`mt-1 text-2xl font-semibold ${tone === 'bad' ? 'text-bad' : tone === 'warn' ? 'text-warn' : 'text-ink'}`}>{value}</p>
      </CardBody>
    </Card>
  )
}

export function WarehouseDetailPage() {
  const { id } = useParams()
  const warehouseId = Number(id)
  const navigate = useNavigate()
  const can = usePermission()
  const canAssign = can('warehouse.assign_users')

  const { data: warehouse } = useWarehouse(warehouseId)
  const { data: summary } = useWarehouseSummary(warehouseId)
  const { data: attached = [] } = useWarehouseUsers(warehouseId)
  const assignMutation = useAssignWarehouseUsers(warehouseId)

  const { data: allUsers = [] } = useQuery<SimpleUser[]>({
    queryKey: ['users', 'all-simple'],
    queryFn: async () => {
      const { data } = await api.get<Paginated<SimpleUser>>('/users', { params: { per_page: 100 } })
      return data.data
    },
    enabled: canAssign,
  })

  const [selected, setSelected] = useState<number[]>([])

  useEffect(() => {
    setSelected(attached.map((u) => u.id))
  }, [attached])

  function toggle(userId: number) {
    setSelected((prev) => (prev.includes(userId) ? prev.filter((i) => i !== userId) : [...prev, userId]))
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/lieux')}><ArrowLeft className="h-4 w-4" /></Button>
        <div>
          <h1 className="text-xl font-semibold text-ink">{warehouse?.code} · {warehouse?.name}</h1>
          <p className="text-sm text-muted">
            {warehouse?.type?.name ?? '—'} · {warehouse?.city ?? '—'}{' '}
            {warehouse?.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="neutral">Inactif</Badge>}
          </p>
        </div>
        <Button variant="outline" size="sm" className="ml-auto" onClick={() => navigate('/stock')}>
          <Boxes className="h-4 w-4" />
          Voir le stock
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <KpiCard label="Valeur du stock" value={summary ? `${summary.stock_value.toLocaleString('fr-FR')} MAD` : '…'} />
        <KpiCard label="Références" value={summary ? String(summary.references) : '…'} />
        <KpiCard label="Sous le seuil" value={summary ? String(summary.below_threshold) : '…'} tone={summary && summary.below_threshold > 0 ? 'warn' : undefined} />
        <KpiCard label="Ruptures" value={summary ? String(summary.ruptures) : '…'} tone={summary && summary.ruptures > 0 ? 'bad' : undefined} />
      </div>

      {summary?.seller_missing ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
          Ce véhicule n'a aucun vendeur rattaché.
        </p>
      ) : null}

      <Card>
        <CardHeader title="Utilisateurs rattachés" hint={String(attached.length)} />
        <CardBody>
          {!canAssign ? (
            attached.length === 0 ? (
              <p className="text-sm text-muted">Aucun utilisateur rattaché.</p>
            ) : (
              <ul className="space-y-1 text-sm text-ink">
                {attached.map((u) => (
                  <li key={u.id}>{u.name} <span className="text-muted">· {u.email}</span></li>
                ))}
              </ul>
            )
          ) : (
            <div className="space-y-4">
              <p className="text-sm text-muted">Cochez les utilisateurs à rattacher à ce lieu.</p>
              <div className="grid gap-2 sm:grid-cols-2">
                {allUsers.map((u) => (
                  <label key={u.id} className="flex items-center gap-2 rounded border border-line px-3 py-2 text-sm text-ink">
                    <input type="checkbox" checked={selected.includes(u.id)} onChange={() => toggle(u.id)} />
                    <span>{u.name}<span className="block text-xs text-muted">{u.email}</span></span>
                  </label>
                ))}
              </div>
              <div className="flex items-center gap-3">
                <Button onClick={() => assignMutation.mutate(selected)} disabled={assignMutation.isPending}>
                  <Save className="h-4 w-4" />
                  {assignMutation.isPending ? 'Enregistrement…' : 'Enregistrer le rattachement'}
                </Button>
                {assignMutation.isSuccess && !assignMutation.isPending ? (
                  <span className="text-sm text-ok">Rattachement mis à jour.</span>
                ) : null}
                {assignMutation.isError ? (
                  <span className="text-sm text-bad">Un véhicule ne peut avoir qu'un seul vendeur.</span>
                ) : null}
              </div>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
