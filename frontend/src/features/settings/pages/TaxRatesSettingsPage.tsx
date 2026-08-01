import { Check, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import type { TaxRate, TaxRateInput } from '@/features/taxrates/api/taxRatesApi'
import { useCreateTaxRate, useDeleteTaxRate, useTaxRates, useUpdateTaxRate } from '@/features/taxrates/hooks'
import { usePermission } from '@/hooks/usePermission'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

const EMPTY: TaxRateInput = { rate: 0, label: '', is_default: false, is_active: true }

export function TaxRatesSettingsPage() {
  const can = usePermission()
  const canManage = can('tax_rate.manage')

  const { data: rates = [], isLoading } = useTaxRates()
  const createMutation = useCreateTaxRate()
  const updateMutation = useUpdateTaxRate()
  const deleteMutation = useDeleteTaxRate()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<TaxRate | null>(null)
  const [form, setForm] = useState<TaxRateInput>(EMPTY)
  const [deleting, setDeleting] = useState<TaxRate | null>(null)

  const isPending = createMutation.isPending || updateMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function openEdit(rate: TaxRate) {
    setEditing(rate)
    setForm({ rate: rate.rate, label: rate.label, is_default: rate.is_default, is_active: rate.is_active })
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
      <Card>
        <CardHeader
          title="Taux de TVA"
          hint={`${rates.length} taux`}
          action={
            canManage && !panelOpen ? (
              <Button size="sm" onClick={openCreate}>
                <Plus className="h-4 w-4" />
                Nouveau taux
              </Button>
            ) : undefined
          }
        />
        <CardBody className={panelOpen ? '' : 'p-0'}>
          {panelOpen ? (
            <form onSubmit={submit} className="mb-5 grid gap-4 sm:grid-cols-2">
              {saveError ? (
                <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad sm:col-span-2">
                  {errorMessage(createMutation.error ?? updateMutation.error, 'Enregistrement impossible (taux déjà existant ?).')}
                </p>
              ) : null}
              <Field label="Pourcentage (%)" htmlFor="rate">
                <Input
                  id="rate"
                  type="number"
                  step="0.01"
                  min={0}
                  max={100}
                  value={form.rate}
                  onChange={(e) => setForm({ ...form, rate: Number(e.target.value) })}
                  required
                />
              </Field>
              <Field label="Libellé" htmlFor="label">
                <Input id="label" value={form.label} onChange={(e) => setForm({ ...form, label: e.target.value })} required />
              </Field>
              <label className="flex items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_default ?? false}
                  onChange={(e) => setForm({ ...form, is_default: e.target.checked })}
                />
                Taux par défaut
              </label>
              <label className="flex items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_active ?? true}
                  onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                />
                Actif
              </label>
              <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={isPending}>
                  {isPending ? 'Enregistrement…' : 'Enregistrer'}
                </Button>
                <Button type="button" variant="ghost" onClick={() => setPanelOpen(false)}>
                  Annuler
                </Button>
              </div>
            </form>
          ) : null}

          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Taux</th>
                  <th className="px-5 py-3 font-medium">Libellé</th>
                  <th className="px-5 py-3 font-medium">Défaut</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {rates.map((rate) => (
                  <tr key={rate.id} className="border-b border-line last:border-0">
                    <td className="tabular px-5 py-3 font-medium text-ink">{rate.rate}%</td>
                    <td className="px-5 py-3 text-muted">{rate.label}</td>
                    <td className="px-5 py-3">
                      {rate.is_default ? (
                        <Badge tone="sky">
                          <Check className="mr-1 h-3 w-3" />
                          Par défaut
                        </Badge>
                      ) : (
                        <span className="text-faint">—</span>
                      )}
                    </td>
                    <td className="px-5 py-3">
                      {rate.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="bad">Inactif</Badge>}
                    </td>
                    {canManage ? (
                      <td className="px-5 py-3">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => openEdit(rate)}>
                            Modifier
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="text-bad hover:bg-bad-bg"
                            onClick={() => setDeleting(rate)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </td>
                    ) : null}
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer le taux"
        message={
          <>
            Supprimer le taux <strong>{deleting?.rate}%</strong> ? Les articles gardent leur valeur ; ce taux
            disparaîtra simplement de la liste.
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
