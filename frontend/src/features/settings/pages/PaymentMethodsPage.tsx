import { Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import {
  useCreatePaymentMethod,
  useDeletePaymentMethod,
  usePaymentMethods,
  useUpdatePaymentMethod,
} from '../hooks'
import type { PaymentMethod, PaymentMethodInput } from '../api/settingsApi'

const TYPES: { value: string; label: string }[] = [
  { value: 'cash', label: 'Espèces' },
  { value: 'cheque', label: 'Chèque' },
  { value: 'transfer', label: 'Virement' },
  { value: 'card', label: 'Carte bancaire' },
  { value: 'other', label: 'Autre' },
]

const EMPTY: PaymentMethodInput = { code: '', name: '', type: 'cash', is_active: true, position: 0 }

export function PaymentMethodsPage() {
  const can = usePermission()
  const canManage = can('payment_method.manage')
  const { data: methods = [], isLoading } = usePaymentMethods()
  const createMutation = useCreatePaymentMethod()
  const updateMutation = useUpdatePaymentMethod()
  const deleteMutation = useDeletePaymentMethod()

  const [form, setForm] = useState<PaymentMethodInput>(EMPTY)
  const [editing, setEditing] = useState<number | null>(null)
  const [toDelete, setToDelete] = useState<PaymentMethod | null>(null)

  function submit(e: React.FormEvent) {
    e.preventDefault()
    if (editing !== null) {
      updateMutation.mutate({ id: editing, input: form }, { onSuccess: reset })
    } else {
      createMutation.mutate(form, { onSuccess: reset })
    }
  }

  function reset() {
    setForm(EMPTY)
    setEditing(null)
  }

  function edit(method: PaymentMethod) {
    setEditing(method.id)
    setForm({ code: method.code, name: method.name, type: method.type, is_active: method.is_active, position: method.position })
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-ink">Modes de paiement</h2>
        <p className="text-sm text-muted">Espèces, chèque, virement, carte…</p>
      </div>

      {canManage ? (
        <Card>
          <CardHeader title={editing !== null ? 'Modifier le mode' : 'Nouveau mode de paiement'} />
          <CardBody>
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-4">
              <Field label="Code" htmlFor="pm-code">
                <Input id="pm-code" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} required />
              </Field>
              <Field label="Nom" htmlFor="pm-name">
                <Input id="pm-name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
              </Field>
              <Field label="Type" htmlFor="pm-type">
                <Select id="pm-type" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
                  {TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Position" htmlFor="pm-pos">
                <Input id="pm-pos" type="number" min={0} value={form.position} onChange={(e) => setForm({ ...form, position: Number(e.target.value) })} />
              </Field>
              <label className="inline-flex items-center gap-2 text-sm text-ink sm:col-span-4">
                <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
                Actif
              </label>
              <div className="flex gap-2 sm:col-span-4">
                <Button type="submit" disabled={createMutation.isPending || updateMutation.isPending}>
                  {editing !== null ? 'Enregistrer' : 'Ajouter'}
                </Button>
                {editing !== null ? (
                  <Button type="button" variant="ghost" onClick={reset}>Annuler</Button>
                ) : null}
              </div>
            </form>
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Liste" hint={String(methods.length)} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Code</th>
                  <th className="px-5 py-3 font-medium">Nom</th>
                  <th className="px-5 py-3 font-medium">Type</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {methods.length === 0 ? (
                  <tr><td colSpan={5} className="px-5 py-8 text-center text-muted">Aucun mode de paiement.</td></tr>
                ) : (
                  methods.map((m) => (
                    <tr key={m.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{m.code}</td>
                      <td className="px-5 py-3 text-ink">{m.name}</td>
                      <td className="px-5 py-3 text-muted">{TYPES.find((t) => t.value === m.type)?.label ?? m.type}</td>
                      <td className="px-5 py-3">{m.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="neutral">Inactif</Badge>}</td>
                      {canManage ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="sm" onClick={() => edit(m)}><Pencil className="h-4 w-4" /></Button>
                            <Button variant="ghost" size="sm" onClick={() => setToDelete(m)}><Trash2 className="h-4 w-4 text-bad" /></Button>
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
        open={toDelete !== null}
        title="Supprimer le mode de paiement"
        message={<>Supprimer <strong>{toDelete?.name}</strong> ?</>}
        confirmLabel="Supprimer"
        danger
        isPending={deleteMutation.isPending}
        onConfirm={() => toDelete && deleteMutation.mutate(toDelete.id, { onSuccess: () => setToDelete(null) })}
        onCancel={() => setToDelete(null)}
      />
    </div>
  )
}
