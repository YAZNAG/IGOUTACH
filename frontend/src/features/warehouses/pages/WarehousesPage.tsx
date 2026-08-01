import { Plus } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import { WarehouseForm } from '../components/WarehouseForm'
import { useCreateWarehouse, useUpdateWarehouse, useWarehouses, useWarehouseTypes } from '../hooks'
import type { Warehouse, WarehouseInput } from '../types'

export function WarehousesPage() {
  const can = usePermission()
  const navigate = useNavigate()
  const canCreate = can('warehouse.create')
  const canUpdate = can('warehouse.update')

  const warehousesQuery = useWarehouses()
  const typesQuery = useWarehouseTypes()
  const createMutation = useCreateWarehouse()
  const updateMutation = useUpdateWarehouse()

  const [editing, setEditing] = useState<Warehouse | null>(null)
  const [panelOpen, setPanelOpen] = useState(false)

  const warehouses = warehousesQuery.data?.data ?? []
  const types = typesQuery.data ?? []
  const isPending = createMutation.isPending || updateMutation.isPending

  function openCreate() {
    setEditing(null)
    setPanelOpen(true)
  }

  function openEdit(warehouse: Warehouse) {
    setEditing(warehouse)
    setPanelOpen(true)
  }

  function closePanel() {
    setPanelOpen(false)
    setEditing(null)
    createMutation.reset()
    updateMutation.reset()
  }

  function handleSubmit(input: WarehouseInput) {
    if (editing) {
      updateMutation.mutate({ id: editing.id, input }, { onSuccess: closePanel })
    } else {
      createMutation.mutate(input, { onSuccess: closePanel })
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Lieux</h1>
          <p className="text-sm text-muted">Dépôts, points de vente et véhicules.</p>
        </div>
        {canCreate && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nouveau lieu
          </Button>
        ) : null}
      </div>

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.code}` : 'Nouveau lieu'} />
          <CardBody>
            {(createMutation.isError || updateMutation.isError) && (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                Enregistrement impossible. Vérifiez les champs (le code doit être unique).
              </p>
            )}
            <WarehouseForm
              types={types}
              parents={warehouses}
              initial={editing ?? undefined}
              isPending={isPending}
              onSubmit={handleSubmit}
              onCancel={closePanel}
            />
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Liste des lieux" hint={`${warehouses.length} lieu(x)`} />
        <CardBody className="p-0">
          {warehousesQuery.isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Code</th>
                  <th className="px-5 py-3 font-medium">Nom</th>
                  <th className="px-5 py-3 font-medium">Type</th>
                  <th className="px-5 py-3 font-medium">Ville</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {warehouses.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-muted">
                      Aucun lieu enregistré.
                    </td>
                  </tr>
                ) : (
                  warehouses.map((warehouse) => (
                    <tr key={warehouse.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{warehouse.code}</td>
                      <td className="px-5 py-3 font-medium text-ink">{warehouse.name}</td>
                      <td className="px-5 py-3">
                        <Badge tone="sky">{warehouse.type?.name ?? '—'}</Badge>
                      </td>
                      <td className="px-5 py-3 text-muted">{warehouse.city ?? '—'}</td>
                      <td className="px-5 py-3">
                        {warehouse.is_active ? (
                          <Badge tone="ok">Actif</Badge>
                        ) : (
                          <Badge tone="bad">Inactif</Badge>
                        )}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => navigate(`/lieux/${warehouse.id}`)}>
                            Fiche
                          </Button>
                          {canUpdate ? (
                            <Button variant="ghost" size="sm" onClick={() => openEdit(warehouse)}>
                              Modifier
                            </Button>
                          ) : null}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
