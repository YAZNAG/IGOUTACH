import { Copy, Plus, Save, Trash2 } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import type { Role } from '../api/rolesApi'
import {
  useCreateRole,
  useDeleteRole,
  useDuplicateRole,
  usePermissionGroups,
  useRoles,
  useSetRolePermissions,
} from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

export function RolesPage() {
  const can = usePermission()
  const canManage = can('role.manage')

  const { data: roles = [] } = useRoles()
  const { data: groups = [] } = usePermissionGroups()
  const setPermsMutation = useSetRolePermissions()
  const createMutation = useCreateRole()
  const deleteMutation = useDeleteRole()
  const duplicateMutation = useDuplicateRole()

  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [selected, setSelectedIds] = useState<Set<number>>(new Set())
  const [creating, setCreating] = useState(false)
  const [newRole, setNewRole] = useState({ name: '', display_name: '' })
  const [deleting, setDeleting] = useState<Role | null>(null)

  const currentRole = useMemo(() => roles.find((r) => r.id === selectedId) ?? null, [roles, selectedId])

  useEffect(() => {
    if (selectedId === null && roles.length > 0) {
      setSelectedId(roles[0].id)
    }
  }, [roles, selectedId])

  useEffect(() => {
    setSelectedIds(new Set(currentRole?.permission_ids ?? []))
  }, [currentRole])

  const dirty = useMemo(() => {
    const original = new Set(currentRole?.permission_ids ?? [])
    if (original.size !== selected.size) return true
    for (const id of selected) if (!original.has(id)) return true
    return false
  }, [currentRole, selected])

  function toggle(id: number) {
    setSelectedIds((prev) => {
      const next = new Set(prev)
      next.has(id) ? next.delete(id) : next.add(id)
      return next
    })
  }

  function save() {
    if (!currentRole) return
    setPermsMutation.mutate({ id: currentRole.id, permissionIds: Array.from(selected) })
  }

  function createRole(e: React.FormEvent) {
    e.preventDefault()
    createMutation.mutate(
      { name: newRole.name, display_name: newRole.display_name },
      {
        onSuccess: (role) => {
          setCreating(false)
          setNewRole({ name: '', display_name: '' })
          setSelectedId(role.id)
        },
      },
    )
  }

  function duplicate(role: Role) {
    const name = `${role.name}_copie`
    duplicateMutation.mutate({ id: role.id, name, displayName: `${role.display_name} (copie)` })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Rôles & permissions</h1>
          <p className="text-sm text-muted">Chaque rôle regroupe un jeu de permissions par module.</p>
        </div>
        {canManage && !creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouveau rôle
          </Button>
        ) : null}
      </div>

      {creating ? (
        <Card>
          <CardHeader title="Nouveau rôle" />
          <CardBody>
            {createMutation.isError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(createMutation.error, 'Création impossible (nom déjà utilisé ?).')}
              </p>
            ) : null}
            <form onSubmit={createRole} className="grid gap-4 sm:grid-cols-2">
              <Field label="Nom technique (a-z, _)" htmlFor="rname">
                <Input
                  id="rname"
                  value={newRole.name}
                  onChange={(e) => setNewRole({ ...newRole, name: e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '_') })}
                  required
                />
              </Field>
              <Field label="Nom affiché" htmlFor="rdisplay">
                <Input
                  id="rdisplay"
                  value={newRole.display_name}
                  onChange={(e) => setNewRole({ ...newRole, display_name: e.target.value })}
                  required
                />
              </Field>
              <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={createMutation.isPending}>
                  Créer
                </Button>
                <Button type="button" variant="ghost" onClick={() => setCreating(false)}>
                  Annuler
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>
      ) : null}

      <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
        <Card>
          <CardHeader title="Rôles" hint={`${roles.length}`} />
          <CardBody className="p-0">
            <ul>
              {roles.map((role) => (
                <li key={role.id}>
                  <button
                    type="button"
                    onClick={() => setSelectedId(role.id)}
                    className={`flex w-full items-center justify-between border-b border-line px-5 py-3 text-left last:border-0 transition-colors hover:bg-bg ${
                      role.id === selectedId ? 'bg-sky-soft' : ''
                    }`}
                  >
                    <span>
                      <span className="block text-sm font-medium text-ink">{role.display_name}</span>
                      <span className="mono block text-xs text-faint">{role.name}</span>
                    </span>
                    <span className="flex items-center gap-2">
                      {role.is_system ? <Badge tone="neutral">système</Badge> : null}
                      <Badge tone="sky">{role.users_count ?? 0} util.</Badge>
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            title={currentRole ? `Permissions — ${currentRole.display_name}` : 'Permissions'}
            hint={currentRole ? `${selected.size} activée(s)` : undefined}
            action={
              currentRole && canManage ? (
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => duplicate(currentRole)} disabled={duplicateMutation.isPending}>
                    <Copy className="h-4 w-4" />
                    Dupliquer
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="text-bad"
                    onClick={() => setDeleting(currentRole)}
                    disabled={currentRole.is_system}
                    title={currentRole.is_system ? 'Rôle système : non supprimable' : undefined}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                  <Button size="sm" onClick={save} disabled={!dirty || setPermsMutation.isPending}>
                    <Save className="h-4 w-4" />
                    Enregistrer
                  </Button>
                </div>
              ) : undefined
            }
          />
          <CardBody>
            {setPermsMutation.isError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(setPermsMutation.error, 'Enregistrement impossible.')}
              </p>
            ) : null}
            {!currentRole ? (
              <p className="text-sm text-muted">Sélectionnez un rôle.</p>
            ) : (
              <div className="space-y-5">
                {groups.map((group) => (
                  <div key={group.module}>
                    <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{group.module}</h3>
                    <div className="grid gap-2 sm:grid-cols-2">
                      {group.permissions.map((perm) => (
                        <label key={perm.id} className="flex items-center gap-2 rounded px-2 py-1 text-sm text-ink hover:bg-bg">
                          <input
                            type="checkbox"
                            className="h-4 w-4 accent-sky"
                            checked={selected.has(perm.id)}
                            disabled={!canManage}
                            onChange={() => toggle(perm.id)}
                          />
                          <span>
                            {perm.display_name}
                            <span className="mono ml-1 text-xs text-faint">{perm.name}</span>
                          </span>
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardBody>
        </Card>
      </div>

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer le rôle"
        message={
          <>
            Supprimer <strong>{deleting?.display_name}</strong> ? Impossible s'il est attribué à des utilisateurs.
          </>
        }
        confirmLabel="Supprimer"
        isPending={deleteMutation.isPending}
        error={deleteMutation.isError ? errorMessage(deleteMutation.error, 'Suppression impossible.') : null}
        onConfirm={() => {
          if (deleting)
            deleteMutation.mutate(deleting.id, {
              onSuccess: () => {
                setDeleting(null)
                setSelectedId(null)
              },
            })
        }}
        onCancel={() => {
          setDeleting(null)
          deleteMutation.reset()
        }}
      />
    </div>
  )
}
