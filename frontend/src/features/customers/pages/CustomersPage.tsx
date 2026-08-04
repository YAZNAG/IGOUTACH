import { useQuery } from '@tanstack/react-query'
import { Ban, CircleCheck, CreditCard, Eye, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api } from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import type { Customer, CustomerInput } from '../api/customersApi'
import {
  useCreateCustomer,
  useCustomers,
  useDeleteCustomer,
  useSetCreditLimit,
  useToggleBlock,
  useUpdateCustomer,
} from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

const EMPTY: CustomerInput = { name: '', is_company: false, is_active: true, credit_limit: 0 }

export function CustomersPage() {
  const navigate = useNavigate()
  const can = usePermission()
  const canCreate = can('customer.create')
  const canUpdate = can('customer.update')
  const canDelete = can('customer.delete')
  const canCredit = can('customer.set_credit_limit')

  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [sort, setSort] = useState<SortState>({ sort: 'name', direction: 'asc' })

  const { data, isLoading } = useCustomers({
    q: search || undefined,
    page,
    per_page: 20,
    sort: sort.sort,
    direction: sort.direction,
  })
  const createMutation = useCreateCustomer()
  const updateMutation = useUpdateCustomer()
  const deleteMutation = useDeleteCustomer()
  const creditMutation = useSetCreditLimit()
  const blockMutation = useToggleBlock()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<Customer | null>(null)
  const [form, setForm] = useState<CustomerInput>(EMPTY)

  // Référentiels des valeurs par défaut du client.
  const { data: priceTypes = [] } = useQuery<{ id: number; code: string; name: string }[]>({
    queryKey: ['price-type-options'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: { id: number; code: string; name: string }[] }>('/price-types')
      return r.data
    },
    staleTime: 5 * 60_000,
  })
  const { data: warehouses = [] } = useWarehouseOptions()
  const canPickSeller = can('user.view')
  const { data: sellers = [] } = useQuery<{ id: number; name: string }[]>({
    queryKey: ['seller-options'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: { id: number; name: string }[] }>('/users', {
        params: { per_page: 100 },
      })
      return r.data
    },
    enabled: canPickSeller && panelOpen,
    staleTime: 5 * 60_000,
  })
  const [deleting, setDeleting] = useState<Customer | null>(null)
  const [creditFor, setCreditFor] = useState<Customer | null>(null)
  const [creditValue, setCreditValue] = useState(0)

  const customers = data?.data ?? []
  const meta = data?.meta
  const isPending = createMutation.isPending || updateMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError

  function set<K extends keyof CustomerInput>(key: K, value: CustomerInput[K]) {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function openEdit(c: Customer) {
    setEditing(c)
    setForm({
      code: c.code,
      name: c.name,
      is_company: c.is_company,
      contact_name: c.contact_name ?? '',
      phone: c.phone ?? '',
      email: c.email ?? '',
      address: c.address ?? '',
      city: c.city ?? '',
      ice: c.ice ?? '',
      price_type_id: c.price_type_id,
      seller_id: c.seller_id,
      warehouse_id: c.warehouse_id,
      notes: c.notes ?? '',
      is_active: c.is_active,
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

  function openCredit(c: Customer) {
    setCreditFor(c)
    setCreditValue(c.credit_limit)
    creditMutation.reset()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Clients</h1>
          <p className="text-sm text-muted">Répertoire clients, plafonds de crédit et encours.</p>
        </div>
        {canCreate && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nouveau client
          </Button>
        ) : null}
      </div>

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.name}` : 'Nouveau client'} />
          <CardBody>
            {saveError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(createMutation.error ?? updateMutation.error, 'Enregistrement impossible (code déjà utilisé ?).')}
              </p>
            ) : null}
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
              <Field label="Nom / Raison sociale *" htmlFor="name">
                <Input id="name" value={form.name} onChange={(e) => set('name', e.target.value)} required />
              </Field>
              {!editing ? (
                <Field label="Plafond de crédit (DH)" htmlFor="credit-limit">
                  <Input
                    id="credit-limit"
                    type="number"
                    min={0}
                    step={100}
                    value={form.credit_limit ?? 0}
                    onChange={(e) => set('credit_limit', Number(e.target.value))}
                  />
                </Field>
              ) : null}
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
              <Field label="ICE (entreprise)" htmlFor="ice">
                <Input id="ice" value={form.ice ?? ''} onChange={(e) => set('ice', e.target.value)} />
              </Field>
              {editing ? (
                <>
                  <Field label="Type de prix par défaut" htmlFor="price-type">
                    <Select
                      id="price-type"
                      value={form.price_type_id ?? ''}
                      onChange={(e) => set('price_type_id', e.target.value ? Number(e.target.value) : null)}
                    >
                      <option value="">— Détail (par défaut) —</option>
                      {priceTypes.map((t) => (
                        <option key={t.id} value={t.id}>{t.name}</option>
                      ))}
                    </Select>
                  </Field>
                  {canPickSeller ? (
                    <Field label="Vendeur référent" htmlFor="seller">
                      <Select
                        id="seller"
                        value={form.seller_id ?? ''}
                        onChange={(e) => set('seller_id', e.target.value ? Number(e.target.value) : null)}
                      >
                        <option value="">— Aucun —</option>
                        {sellers.map((s) => (
                          <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                      </Select>
                    </Field>
                  ) : null}
                  <Field label="Lieu de rattachement" htmlFor="cust-warehouse">
                    <Select
                      id="cust-warehouse"
                      value={form.warehouse_id ?? ''}
                      onChange={(e) => set('warehouse_id', e.target.value ? Number(e.target.value) : null)}
                    >
                      <option value="">— Aucun —</option>
                      {warehouses.map((w) => (
                        <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
                      ))}
                    </Select>
                  </Field>
                </>
              ) : null}
              <div className="flex items-center gap-6 self-end pb-2">
                <label className="flex items-center gap-2 text-sm text-ink">
                  <input
                    type="checkbox"
                    className="h-4 w-4 accent-sky"
                    checked={form.is_company ?? false}
                    onChange={(e) => set('is_company', e.target.checked)}
                  />
                  Entreprise
                </label>
                <label className="flex items-center gap-2 text-sm text-ink">
                  <input
                    type="checkbox"
                    className="h-4 w-4 accent-sky"
                    checked={form.is_active ?? true}
                    onChange={(e) => set('is_active', e.target.checked)}
                  />
                  Actif
                </label>
              </div>
              <div className="sm:col-span-2">
                <Field label="Adresse" htmlFor="address">
                  <Input id="address" value={form.address ?? ''} onChange={(e) => set('address', e.target.value)} />
                </Field>
              </div>
              <p className="text-xs text-faint sm:col-span-2">
                {editing
                  ? "Le plafond de crédit se règle via l'action « Crédit » (permission dédiée)."
                  : 'Le code client est généré automatiquement (CL-0001).'}
              </p>
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
          title="Liste des clients"
          hint={meta ? `${meta.total}` : undefined}
          action={
            <Input
              placeholder="Rechercher nom / code / tél…"
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
                  <SortableTh field="city" current={sort} onSort={setSort}>Ville</SortableTh>
                  <SortableTh field="credit_limit" current={sort} onSort={setSort} className="text-right" align="right">
                    Plafond
                  </SortableTh>
                  <SortableTh field="balance" current={sort} onSort={setSort} className="text-right" align="right">
                    Encours
                  </SortableTh>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {customers.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-5 py-8 text-center text-muted">
                      Aucun client.
                    </td>
                  </tr>
                ) : (
                  customers.map((c) => (
                    <tr key={c.id} className={`border-b border-line last:border-0 ${c.is_active ? '' : 'opacity-55'}`}>
                      <td className="mono px-5 py-3 text-muted">{c.code}</td>
                      <td className="px-5 py-3 text-ink">
                        {c.name}
                        {c.is_company ? <Badge tone="neutral" className="ml-2">Ese</Badge> : null}
                      </td>
                      <td className="px-5 py-3 text-muted">{c.city ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(c.credit_limit)}</td>
                      <td className="tabular px-5 py-3 text-right">
                        <span className={c.balance > c.credit_limit ? 'font-medium text-bad' : 'text-muted'}>
                          {formatNumber(c.balance)}
                        </span>
                      </td>
                      <td className="px-5 py-3">
                        {c.is_blocked ? (
                          <Badge tone="bad">Bloqué</Badge>
                        ) : c.is_active ? (
                          <Badge tone="ok">Actif</Badge>
                        ) : (
                          <Badge tone="neutral">Inactif</Badge>
                        )}
                      </td>
                      <td className="px-5 py-3">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => navigate(`/clients/${c.id}`)} title="Voir le détail">
                            <Eye className="h-4 w-4" />
                            Voir
                          </Button>
                          {canCredit ? (
                            <Button variant="ghost" size="sm" onClick={() => openCredit(c)} title="Plafond de crédit">
                              <CreditCard className="h-4 w-4" />
                            </Button>
                          ) : null}
                          {canCredit ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              className={c.is_blocked ? 'text-ok' : 'text-warn'}
                              onClick={() => blockMutation.mutate(c.id)}
                              title={c.is_blocked ? 'Débloquer' : 'Bloquer'}
                            >
                              {c.is_blocked ? <CircleCheck className="h-4 w-4" /> : <Ban className="h-4 w-4" />}
                            </Button>
                          ) : null}
                          {canUpdate ? (
                            <Button variant="ghost" size="sm" onClick={() => openEdit(c)}>
                              Modifier
                            </Button>
                          ) : null}
                          {canDelete ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-bad hover:bg-bad-bg"
                              onClick={() => setDeleting(c)}
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
              <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      ) : null}

      {/* Plafond de crédit */}
      {creditFor ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
          role="dialog"
          aria-modal="true"
          onMouseDown={() => setCreditFor(null)}
        >
          <div
            className="w-full max-w-sm rounded-lg border border-line bg-card p-5 shadow-[var(--shadow-pop)]"
            onMouseDown={(e) => e.stopPropagation()}
          >
            <h2 className="text-base font-semibold text-ink">Plafond de crédit — {creditFor.name}</h2>
            <p className="mt-1 text-sm text-muted">Encours actuel : {formatNumber(creditFor.balance)} DH</p>
            {creditMutation.isError ? (
              <p className="mt-3 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(creditMutation.error, 'Enregistrement impossible.')}
              </p>
            ) : null}
            <div className="mt-4">
              <Field label="Nouveau plafond (DH)" htmlFor="credit">
                <Input
                  id="credit"
                  type="number"
                  min={0}
                  value={creditValue}
                  onChange={(e) => setCreditValue(Number(e.target.value))}
                />
              </Field>
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <Button variant="ghost" onClick={() => setCreditFor(null)}>
                Annuler
              </Button>
              <Button
                disabled={creditMutation.isPending}
                onClick={() =>
                  creditMutation.mutate(
                    { id: creditFor.id, creditLimit: creditValue },
                    { onSuccess: () => setCreditFor(null) },
                  )
                }
              >
                Enregistrer
              </Button>
            </div>
          </div>
        </div>
      ) : null}

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer le client"
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
