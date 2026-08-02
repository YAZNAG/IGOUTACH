import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Plus, X } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface ExpenseRow {
  id: number
  label: string
  category: string | null
  warehouse: string | null
  user: string | null
  amount: number
  expense_date: string
  has_receipt: boolean
  status: string
}

interface CategoryOption {
  id: number
  name: string
}

const KEY = ['expenses'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Charges : saisie par lieu avec justificatif photo (facultatif),
 * validation ou rejet par le responsable.
 */
export function ExpensesPage() {
  const can = usePermission()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)

  const { data, isLoading } = useQuery<Paginated<ExpenseRow>>({
    queryKey: [...KEY, page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<ExpenseRow>>('/expenses', { params: { page } })
      return r
    },
  })

  const decide = useMutation({
    mutationFn: async ({ id, decision }: { id: number; decision: 'approved' | 'rejected' }) => {
      await ensureCsrfCookie()
      await api.patch(`/expenses/${id}/decide`, { decision })
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  const expenses = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Charges</h1>
          <p className="text-sm text-muted">Dépenses par lieu et par utilisateur, validées par le responsable.</p>
        </div>
        {can('expense.create') && !creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouvelle charge
          </Button>
        ) : null}
      </div>

      {creating ? <CreateExpensePanel onClose={() => setCreating(false)} /> : null}

      <Card>
        <CardHeader title="Charges" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Libellé</th>
                  <th className="px-5 py-3 font-medium">Catégorie</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium">Saisie par</th>
                  <th className="px-5 py-3 text-right font-medium">Montant (DH)</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {can('expense.approve') ? <th className="px-5 py-3 text-right font-medium">Décision</th> : null}
                </tr>
              </thead>
              <tbody>
                {expenses.length === 0 ? (
                  <tr><td colSpan={8} className="px-5 py-8 text-center text-muted">Aucune charge.</td></tr>
                ) : (
                  expenses.map((e) => (
                    <tr key={e.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-ink">
                        {e.label}
                        {e.has_receipt ? <span className="ml-2 text-xs text-faint">📎 justificatif</span> : null}
                      </td>
                      <td className="px-5 py-3 text-muted">{e.category}</td>
                      <td className="px-5 py-3 text-muted">{e.warehouse ?? '—'}</td>
                      <td className="px-5 py-3 text-muted">{e.user}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatNumber(e.amount)}</td>
                      <td className="px-5 py-3 text-muted">{e.expense_date}</td>
                      <td className="px-5 py-3">
                        {e.status === 'approved' ? <Badge tone="ok">Validée</Badge> : null}
                        {e.status === 'pending' ? <Badge tone="warn">En attente</Badge> : null}
                        {e.status === 'rejected' ? <Badge tone="bad">Rejetée</Badge> : null}
                      </td>
                      {can('expense.approve') ? (
                        <td className="px-5 py-3 text-right">
                          {e.status === 'pending' ? (
                            <div className="flex justify-end gap-1">
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-ok hover:bg-ok-bg"
                                onClick={() => decide.mutate({ id: e.id, decision: 'approved' })}
                                aria-label={`Valider ${e.label}`}
                              >
                                <Check className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-bad hover:bg-bad-bg"
                                onClick={() => decide.mutate({ id: e.id, decision: 'rejected' })}
                                aria-label={`Rejeter ${e.label}`}
                              >
                                <X className="h-4 w-4" />
                              </Button>
                            </div>
                          ) : null}
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

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}
    </div>
  )
}

function CreateExpensePanel({ onClose }: { onClose: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [categoryId, setCategoryId] = useState(0)
  const [newCategory, setNewCategory] = useState('')
  const [warehouseId, setWarehouseId] = useState(0)
  const [label, setLabel] = useState('')
  const [amount, setAmount] = useState('')
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10))
  const [receipt, setReceipt] = useState<File | null>(null)

  const { data: categories = [] } = useQuery<CategoryOption[]>({
    queryKey: ['expense-categories'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: CategoryOption[] }>('/expense-categories')
      return r.data
    },
  })

  const addCategory = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: CategoryOption }>('/expense-categories', { name: newCategory })
      return r.data
    },
    onSuccess: (created) => {
      setNewCategory('')
      setCategoryId(created.id)
      qc.invalidateQueries({ queryKey: ['expense-categories'] })
    },
  })

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const form = new FormData()
      form.append('expense_category_id', String(categoryId))
      if (warehouseId) form.append('warehouse_id', String(warehouseId))
      form.append('label', label)
      form.append('amount', amount)
      form.append('expense_date', date)
      if (receipt) form.append('receipt', receipt)
      await api.post('/expenses', form, { headers: { 'Content-Type': 'multipart/form-data' } })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      onClose()
    },
  })

  return (
    <Card>
      <CardHeader title="Nouvelle charge" />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Catégorie" htmlFor="exp-category">
            <div className="space-y-1">
              <Select id="exp-category" value={categoryId || ''} onChange={(e) => setCategoryId(Number(e.target.value))}>
                <option value="" disabled>Choisir…</option>
                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
              {can('expense.approve') ? (
                <div className="flex gap-1">
                  <Input placeholder="Nouvelle catégorie…" value={newCategory} onChange={(e) => setNewCategory(e.target.value)} />
                  <Button variant="outline" size="sm" onClick={() => addCategory.mutate()} disabled={addCategory.isPending || newCategory.trim() === ''}>
                    <Plus className="h-4 w-4" />
                  </Button>
                </div>
              ) : null}
            </div>
          </Field>
          <Field label="Lieu (facultatif)" htmlFor="exp-warehouse">
            <Select id="exp-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))}>
              <option value="">— Aucun —</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Date" htmlFor="exp-date">
            <Input id="exp-date" type="date" value={date} onChange={(e) => setDate(e.target.value)} />
          </Field>
          <Field label="Libellé" htmlFor="exp-label">
            <Input id="exp-label" value={label} onChange={(e) => setLabel(e.target.value)} placeholder="Carburant, loyer…" />
          </Field>
          <Field label="Montant (DH)" htmlFor="exp-amount">
            <Input id="exp-amount" type="number" min={0.01} step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
          </Field>
          <Field label="Justificatif photo (facultatif)" htmlFor="exp-receipt">
            <input
              id="exp-receipt"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={(e) => setReceipt(e.target.files?.[0] ?? null)}
              className="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border file:border-line file:bg-surface file:px-3 file:py-1.5 file:text-sm file:text-ink"
            />
          </Field>
        </div>

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Enregistrement impossible.')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button onClick={() => create.mutate()} disabled={create.isPending || !categoryId || label.trim() === '' || Number(amount) <= 0}>
            {create.isPending ? 'Enregistrement…' : 'Enregistrer la charge'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}
