import { Eye, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { usePermission } from '@/hooks/usePermission'
import type { Supplier, SupplierInput } from '../api/suppliersApi'
import { useCreateSupplier, useDeleteSupplier, useSuppliers, useUpdateSupplier } from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

const EMPTY: SupplierInput = { code: '', name: '', payment_terms_days: 0, is_active: true }

export function SuppliersPage() {
  const navigate = useNavigate()
  const can = usePermission()
  const canCreate = can('supplier.create')
  const canUpdate = can('supplier.update')
  const canDelete = can('supplier.delete')

  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [sort, setSort] = useState<SortState>({ sort: 'name', direction: 'asc' })

  const { data, isLoading } = useSuppliers({
    q: search || undefined,
    page,
    per_page: 20,
    sort: sort.sort,
    direction: sort.direction,
  })
  const createMutation = useCreateSupplier()
  const updateMutation = useUpdateSupplier()
  const deleteMutation = useDeleteSupplier()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<Supplier | null>(null)
  const [form, setForm] = useState<SupplierInput>(EMPTY)
  const [deleting, setDeleting] = useState<Supplier | null>(null)

  const suppliers = data?.data ?? []
  const meta = data?.meta
  const isPending = createMutation.isPending || updateMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError

  function set<K extends keyof SupplierInput>(key: K, value: SupplierInput[K]) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function openEdit(supplier: Supplier) {
    setEditing(supplier)
    setForm({
      code: supplier.code,
      name: supplier.name,
      contact_name: supplier.contact_name ?? '',
      phone: supplier.phone ?? '',
      email: supplier.email ?? '',
      address: supplier.address ?? '',
      city: supplier.city ?? '',
      ice: supplier.ice ?? '',
      rc: supplier.rc ?? '',
      payment_terms_days: supplier.payment_terms_days,
      notes: supplier.notes ?? '',
      is_active: supplier.is_active,
    })
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function submit(e: React.FormEvent) {
    e.preventDefault()
    if (editing) {
      updateMutation.mutate({ id: editing.id, input: form }, { onSuccess: () => setPanelOpen(false) })
    } else {
      createMutation.mutate(form, { onSuccess: () => setPanelOpen(false) })
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Fournisseurs</h1>
          <p className="text-sm text-muted">Répertoire des fournisseurs et conditions de paiement.</p>
        </div>
        {canCreate && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nouveau fournisseur
          </Button>
        ) : null}
      </div>

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.name}` : 'Nouveau fournisseur'} />
          <CardBody>
            {saveError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(createMutation.error ?? updateMutation.error, 'Enregistrement impossible (code déjà utilisé ?).')}
              </p>
            ) : null}
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
              <Field label="Code" htmlFor="code">
                <Input id="code" value={form.code} onChange={(e) => set('code', e.target.value)} required />
              </Field>
              <Field label="Nom / Raison sociale" htmlFor="name">
                <Input id="name" value={form.name} onChange={(e) => set('name', e.target.value)} required />
              </Field>
              <Field label="Contact" htmlFor="contact">
                <Input id="contact" value={form.contact_name ?? ''} onChange={(e) => set('contact_name', e.target.value)} />
              </Field>
              <Field label="Téléphone" htmlFor="phone">
                <Input id="phone" value={form.phone ?? ''} onChange={(e) => set('phone', e.target.value)} />
              </Field>
              <Field label="E-mail" htmlFor="email">
                <Input id="email" type="email" value={form.email ?? ''} onChange={(e) => set('email', e.target.value)} />
              </Field>
              <Field label="Ville" htmlFor="city">
                <Input id="city" value={form.city ?? ''} onChange={(e) => set('city', e.target.value)} />
              </Field>
              <Field label="ICE" htmlFor="ice">
                <Input id="ice" value={form.ice ?? ''} onChange={(e) => set('ice', e.target.value)} />
              </Field>
              <Field label="Registre de commerce (RC)" htmlFor="rc">
                <Input id="rc" value={form.rc ?? ''} onChange={(e) => set('rc', e.target.value)} />
              </Field>
              <Field label="Délai de paiement (jours)" htmlFor="terms">
                <Input
                  id="terms"
                  type="number"
                  min={0}
                  max={365}
                  value={form.payment_terms_days ?? 0}
                  onChange={(e) => set('payment_terms_days', Number(e.target.value))}
                />
              </Field>
              <label className="flex items-center gap-2 self-end pb-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_active ?? true}
                  onChange={(e) => set('is_active', e.target.checked)}
                />
                Actif
              </label>
              <div className="sm:col-span-2">
                <Field label="Adresse" htmlFor="address">
                  <Input id="address" value={form.address ?? ''} onChange={(e) => set('address', e.target.value)} />
                </Field>
              </div>
              <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={isPending}>
                  {isPending ? 'Enregistrement…' : 'Enregistrer'}
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
          title="Liste des fournisseurs"
          hint={meta ? `${meta.total}` : undefined}
          action={
            <Input
              placeholder="Rechercher nom / code / ville…"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
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
                  <SortableTh field="code" current={sort} onSort={setSort}>Code</SortableTh>
                  <SortableTh field="name" current={sort} onSort={setSort}>Nom</SortableTh>
                  <th className="px-5 py-3 font-medium">Contact</th>
                  <SortableTh field="city" current={sort} onSort={setSort}>Ville</SortableTh>
                  <th className="px-5 py-3 text-right font-medium">Délai (j)</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {suppliers.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-5 py-8 text-center text-muted">
                      Aucun fournisseur.
                    </td>
                  </tr>
                ) : (
                  suppliers.map((supplier) => (
                    <tr key={supplier.id} className={`border-b border-line last:border-0 ${supplier.is_active ? '' : 'opacity-55'}`}>
                      <td className="mono px-5 py-3 text-muted">{supplier.code}</td>
                      <td className="px-5 py-3 text-ink">{supplier.name}</td>
                      <td className="px-5 py-3 text-muted">
                        {supplier.contact_name ?? '—'}
                        {supplier.phone ? <span className="block text-xs text-faint">{supplier.phone}</span> : null}
                      </td>
                      <td className="px-5 py-3 text-muted">{supplier.city ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{supplier.payment_terms_days}</td>
                      <td className="px-5 py-3">
                        {supplier.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="bad">Inactif</Badge>}
                      </td>
                      <td className="px-5 py-3">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => navigate(`/fournisseurs/${supplier.id}`)}>
                            <Eye className="h-4 w-4" />
                            Voir
                          </Button>
                          {canUpdate ? (
                            <Button variant="ghost" size="sm" onClick={() => openEdit(supplier)}>
                              Modifier
                            </Button>
                          ) : null}
                          {canDelete ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-bad hover:bg-bad-bg"
                              onClick={() => setDeleting(supplier)}
                            >
                              <Trash2 className="h-4 w-4" />
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

      {meta ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <span>{paginationInfo(meta)}</span>
          {meta.last_page > 1 ? (
            <div className="flex items-center gap-2">
              <span>
                Page {meta.current_page} / {meta.last_page}
              </span>
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                Précédent
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      ) : null}

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer le fournisseur"
        message={
          <>
            Supprimer <strong>{deleting?.name}</strong> ?
          </>
        }
        confirmLabel="Supprimer"
        isPending={deleteMutation.isPending}
        error={deleteMutation.isError ? errorMessage(deleteMutation.error, 'Suppression impossible.') : null}
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
