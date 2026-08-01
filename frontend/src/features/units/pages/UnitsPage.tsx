import { Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import type { Unit, UnitInput } from '../api/unitsApi'
import { useCreateUnit, useDeleteUnit, useUnits, useUpdateUnit } from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

const EMPTY: UnitInput = { code: '', name: '', is_decimal: false, is_active: true }

export function UnitsPage() {
  const can = usePermission()
  const canManage = can('unit.manage')

  const { data: units = [], isLoading } = useUnits()
  const createMutation = useCreateUnit()
  const updateMutation = useUpdateUnit()
  const deleteMutation = useDeleteUnit()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<Unit | null>(null)
  const [form, setForm] = useState<UnitInput>(EMPTY)
  const [deleting, setDeleting] = useState<Unit | null>(null)

  const isPending = createMutation.isPending || updateMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function openEdit(unit: Unit) {
    setEditing(unit)
    setForm({ code: unit.code, name: unit.name, is_decimal: unit.is_decimal, is_active: unit.is_active })
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function close() {
    setPanelOpen(false)
    setEditing(null)
  }

  function submit(e: React.FormEvent) {
    e.preventDefault()
    if (editing) {
      updateMutation.mutate({ id: editing.id, input: form }, { onSuccess: close })
    } else {
      createMutation.mutate(form, { onSuccess: close })
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Unités</h1>
          <p className="text-sm text-muted">Unités de mesure des articles (décimales autorisées ou non).</p>
        </div>
        {canManage && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nouvelle unité
          </Button>
        ) : null}
      </div>

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.code}` : 'Nouvelle unité'} />
          <CardBody>
            {saveError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(createMutation.error ?? updateMutation.error, 'Enregistrement impossible (code déjà utilisé ?).')}
              </p>
            ) : null}
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
              <Field label="Code" htmlFor="code">
                <Input
                  id="code"
                  value={form.code}
                  onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })}
                  placeholder="PCE, MTR…"
                  required
                />
              </Field>
              <Field label="Nom" htmlFor="name">
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
              </Field>
              <label className="flex items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_decimal}
                  onChange={(e) => setForm({ ...form, is_decimal: e.target.checked })}
                />
                Accepte les quantités décimales
              </label>
              <label className="flex items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_active ?? true}
                  onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                />
                Active
              </label>
              <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={isPending}>
                  {isPending ? 'Enregistrement…' : 'Enregistrer'}
                </Button>
                <Button type="button" variant="ghost" onClick={close}>
                  Annuler
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Liste des unités" hint={`${units.length} unité(s)`} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Code</th>
                  <th className="px-5 py-3 font-medium">Nom</th>
                  <th className="px-5 py-3 font-medium">Décimale</th>
                  <th className="px-5 py-3 text-right font-medium">Articles</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {units.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-muted">
                      Aucune unité.
                    </td>
                  </tr>
                ) : (
                  units.map((unit) => (
                    <tr key={unit.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{unit.code}</td>
                      <td className="px-5 py-3 text-ink">{unit.name}</td>
                      <td className="px-5 py-3">
                        {unit.is_decimal ? <Badge tone="sky">Décimale</Badge> : <Badge tone="neutral">Entière</Badge>}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{unit.products_count ?? 0}</td>
                      <td className="px-5 py-3">
                        {unit.is_active ? <Badge tone="ok">Active</Badge> : <Badge tone="bad">Inactive</Badge>}
                      </td>
                      {canManage ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="sm" onClick={() => openEdit(unit)}>
                              Modifier
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-bad hover:bg-bad-bg"
                              onClick={() => setDeleting(unit)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </td>
                      ) : null}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={deleting !== null}
        title="Désactiver l'unité"
        message={
          <>
            Désactiver <strong>{deleting?.name}</strong> ? Impossible si des articles l'utilisent encore.
          </>
        }
        confirmLabel="Désactiver"
        isPending={deleteMutation.isPending}
        error={deleteMutation.isError ? errorMessage(deleteMutation.error, 'Désactivation impossible.') : null}
        onConfirm={() => {
          if (deleting) deleteMutation.mutate(deleting.id, { onSuccess: () => setDeleting(null) })
        }}
        onCancel={() => {
          setDeleting(null)
          deleteMutation.reset()
        }}
      />
    </div>
  )
}
