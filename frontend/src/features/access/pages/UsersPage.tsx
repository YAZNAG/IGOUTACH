import { Mail, Plus, Power } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import type { AdminUser } from '../api/usersApi'
import {
  useAssignRoles,
  useCreateUser,
  useResendInvitation,
  useRoles,
  useToggleUser,
  useUpdateUser,
  useUsers,
  useWarehouseOptions,
} from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

interface FormState {
  name: string
  email: string
  phone: string
  warehouse_id: number | null
  role_ids: number[]
}

const EMPTY: FormState = { name: '', email: '', phone: '', warehouse_id: null, role_ids: [] }

export function UsersPage() {
  const can = usePermission()
  const canCreate = can('user.create')
  const canUpdate = can('user.update')
  const canDeactivate = can('user.deactivate')
  const canAssign = can('user.assign_role')

  const [search, setSearch] = useState('')
  const { data, isLoading } = useUsers({ q: search || undefined, per_page: 50 })
  const { data: roles = [] } = useRoles()
  const { data: warehouses = [] } = useWarehouseOptions()

  const createMutation = useCreateUser()
  const updateMutation = useUpdateUser()
  const toggleMutation = useToggleUser()
  const assignMutation = useAssignRoles()
  const inviteMutation = useResendInvitation()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<AdminUser | null>(null)
  const [form, setForm] = useState<FormState>(EMPTY)
  const [notice, setNotice] = useState<string | null>(null)

  const users = data?.data ?? []
  const isPending = createMutation.isPending || updateMutation.isPending || assignMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError || assignMutation.isError

  // Un rôle à accès global rend le lieu facultatif.
  const globalRoleIds = new Set(roles.filter((r) => r.name === 'admin').map((r) => r.id))
  const requiresWarehouse = !form.role_ids.some((id) => globalRoleIds.has(id))

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
  }

  function openEdit(user: AdminUser) {
    setEditing(user)
    setForm({
      name: user.name,
      email: user.email,
      phone: user.phone ?? '',
      warehouse_id: user.warehouse_id,
      role_ids: user.roles.map((r) => r.id),
    })
    setPanelOpen(true)
    updateMutation.reset()
    assignMutation.reset()
  }

  function toggleRole(id: number) {
    setForm((prev) => ({
      ...prev,
      role_ids: prev.role_ids.includes(id) ? prev.role_ids.filter((r) => r !== id) : [...prev.role_ids, id],
    }))
  }

  function submit(e: React.FormEvent) {
    e.preventDefault()
    const base = {
      name: form.name,
      email: form.email,
      phone: form.phone || null,
      warehouse_id: form.warehouse_id,
    }
    if (editing) {
      updateMutation.mutate(
        { id: editing.id, input: base },
        {
          onSuccess: () => {
            if (canAssign) {
              assignMutation.mutate(
                { id: editing.id, roleIds: form.role_ids },
                { onSuccess: () => setPanelOpen(false) },
              )
            } else {
              setPanelOpen(false)
            }
          },
        },
      )
    } else {
      createMutation.mutate(
        { ...base, role_ids: form.role_ids },
        {
          onSuccess: () => {
            setPanelOpen(false)
            setNotice('Utilisateur créé. Une invitation a été envoyée pour définir le mot de passe.')
          },
        },
      )
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Utilisateurs</h1>
          <p className="text-sm text-muted">Comptes, rôles et rattachement à un lieu.</p>
        </div>
        {canCreate && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Inviter un utilisateur
          </Button>
        ) : null}
      </div>

      {notice ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">{notice}</p>
      ) : null}

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.name}` : 'Nouvel utilisateur (invitation)'} />
          <CardBody>
            {saveError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(
                  createMutation.error ?? updateMutation.error ?? assignMutation.error,
                  'Enregistrement impossible.',
                )}
              </p>
            ) : null}
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
              <Field label="Nom" htmlFor="uname">
                <Input id="uname" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
              </Field>
              <Field label="E-mail" htmlFor="uemail">
                <Input id="uemail" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
              </Field>
              <Field label="Téléphone" htmlFor="uphone">
                <Input id="uphone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </Field>
              <Field label={requiresWarehouse ? 'Lieu de rattachement (obligatoire)' : 'Lieu (accès global)'} htmlFor="uwh">
                <Select
                  id="uwh"
                  value={form.warehouse_id ?? ''}
                  disabled={!requiresWarehouse}
                  onChange={(e) => setForm({ ...form, warehouse_id: e.target.value ? Number(e.target.value) : null })}
                >
                  <option value="">{requiresWarehouse ? '— Choisir —' : 'Tous les lieux'}</option>
                  {warehouses.map((w) => (
                    <option key={w.id} value={w.id}>
                      {w.code} · {w.name}
                    </option>
                  ))}
                </Select>
              </Field>
              <div className="sm:col-span-2">
                <p className="mb-2 text-sm font-medium text-ink">Rôles</p>
                <div className="grid gap-2 sm:grid-cols-3">
                  {roles.map((role) => (
                    <label key={role.id} className="flex items-center gap-2 rounded px-2 py-1 text-sm text-ink hover:bg-bg">
                      <input
                        type="checkbox"
                        className="h-4 w-4 accent-sky"
                        checked={form.role_ids.includes(role.id)}
                        onChange={() => toggleRole(role.id)}
                      />
                      {role.display_name}
                    </label>
                  ))}
                </div>
              </div>
              <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={isPending}>
                  {isPending ? 'Enregistrement…' : editing ? 'Enregistrer' : 'Inviter'}
                </Button>
                <Button type="button" variant="ghost" onClick={() => setPanelOpen(false)}>
                  Annuler
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader
          title="Liste des utilisateurs"
          hint={data ? `${data.meta.total}` : undefined}
          action={
            <Input
              placeholder="Rechercher nom / e-mail…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-64"
            />
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Nom</th>
                  <th className="px-5 py-3 font-medium">E-mail</th>
                  <th className="px-5 py-3 font-medium">Rôles</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {users.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-muted">
                      Aucun utilisateur.
                    </td>
                  </tr>
                ) : (
                  users.map((user) => (
                    <tr key={user.id} className={`border-b border-line last:border-0 ${user.is_active ? '' : 'opacity-55'}`}>
                      <td className="px-5 py-3 text-ink">{user.name}</td>
                      <td className="px-5 py-3 text-muted">{user.email}</td>
                      <td className="px-5 py-3">
                        <div className="flex flex-wrap gap-1">
                          {user.roles.map((r) => (
                            <Badge key={r.id} tone="sky">
                              {r.display_name}
                            </Badge>
                          ))}
                        </div>
                      </td>
                      <td className="px-5 py-3 text-muted">{user.warehouse ? user.warehouse.code : 'Tous'}</td>
                      <td className="px-5 py-3">
                        {user.invited ? (
                          <Badge tone="warn">Invité</Badge>
                        ) : user.is_active ? (
                          <Badge tone="ok">Actif</Badge>
                        ) : (
                          <Badge tone="bad">Inactif</Badge>
                        )}
                      </td>
                      <td className="px-5 py-3">
                        <div className="flex justify-end gap-1">
                          {user.invited ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => inviteMutation.mutate(user.id, { onSuccess: () => setNotice('Invitation renvoyée.') })}
                            >
                              <Mail className="h-4 w-4" />
                            </Button>
                          ) : null}
                          {canUpdate ? (
                            <Button variant="ghost" size="sm" onClick={() => openEdit(user)}>
                              Modifier
                            </Button>
                          ) : null}
                          {canDeactivate ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              className={user.is_active ? 'text-bad hover:bg-bad-bg' : 'text-ok'}
                              onClick={() => toggleMutation.mutate(user.id)}
                              title={user.is_active ? 'Désactiver' : 'Activer'}
                            >
                              <Power className="h-4 w-4" />
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
