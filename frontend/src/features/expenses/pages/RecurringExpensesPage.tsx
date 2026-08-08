import { AlertTriangle, CheckCircle2, Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { usePaymentMethods } from '@/features/purchases/hooks'
import { useWarehouses } from '@/features/warehouses/hooks'
import { usePermission } from '@/hooks/usePermission'
import { formatCurrency } from '@/lib/utils'
import type { RecurringExpense, RecurringExpenseInput } from '../api/recurringApi'
import {
  useCreateRecurringExpense,
  useDeleteRecurringExpense,
  usePayOccurrence,
  usePendingOccurrences,
  useRecurringExpenses,
  useUpdateRecurringExpense,
} from '../recurringHooks'

function moisCourant(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function libellePeriode(periode: string): string {
  const [a, m] = periode.split('-')
  const mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']
  return `${mois[Number(m) - 1] ?? m} ${a}`
}

const VIDE: RecurringExpenseInput = {
  label: '',
  amount: 0,
  day_of_month: 1,
  start_period: moisCourant(),
  end_period: null,
  warehouse_id: null,
  expense_category_id: null,
  is_active: true,
  notes: null,
}

export function RecurringExpensesPage() {
  const can = usePermission()
  const canManage = can('expense.recurring_manage')
  const canPay = can('expense.approve')

  const { data: charges = [], isLoading } = useRecurringExpenses()
  const { data: enAttente } = usePendingOccurrences()
  // useWarehouses renvoie une page paginee, pas un tableau.
  const { data: pageLieux } = useWarehouses()
  const lieux = pageLieux?.data ?? []
  const { data: modes = [] } = usePaymentMethods()
  const { data: categories = [] } = useQuery<Array<{ id: number; name: string }>>({
    queryKey: ['expense-categories'],
    queryFn: async () => (await api.get<{ data: Array<{ id: number; name: string }> }>('/expense-categories')).data.data,
  })

  const creer = useCreateRecurringExpense()
  const modifier = useUpdateRecurringExpense()
  const supprimer = useDeleteRecurringExpense()
  const regler = usePayOccurrence()

  const [panneau, setPanneau] = useState(false)
  const [edition, setEdition] = useState<RecurringExpense | null>(null)
  const [form, setForm] = useState<RecurringExpenseInput>(VIDE)
  const [aSupprimer, setASupprimer] = useState<RecurringExpense | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [modePaiement, setModePaiement] = useState<number>(0)

  const echeances = enAttente?.data ?? []

  function ouvrirCreation() {
    setEdition(null)
    setForm(VIDE)
    setPanneau(true)
  }

  function ouvrirEdition(charge: RecurringExpense) {
    setEdition(charge)
    setForm({
      label: charge.label,
      amount: charge.amount,
      day_of_month: charge.day_of_month,
      start_period: charge.start_period,
      end_period: charge.end_period,
      warehouse_id: charge.warehouse?.id ?? null,
      expense_category_id: charge.category?.id ?? null,
      is_active: charge.is_active,
      notes: charge.notes,
    })
    setPanneau(true)
  }

  async function enregistrer() {
    if (edition) await modifier.mutateAsync({ id: edition.id, input: form })
    else await creer.mutateAsync(form)
    setPanneau(false)
  }

  const enregistrement = creer.isPending || modifier.isPending

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Charges fixes</h1>
          <p className="text-sm text-muted">
            Saisies une seule fois — une échéance est générée chaque mois et reste signalée tant qu'elle n'est pas réglée.
          </p>
        </div>
        {canManage ? (
          <Button onClick={ouvrirCreation}>
            <Plus className="h-4 w-4" />
            Nouvelle charge fixe
          </Button>
        ) : null}
      </div>

      {message ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">{message}</p>
      ) : null}

      {/* ── Échéances dues ─────────────────────────────────────────── */}
      <Card>
        <CardHeader
          title="À régler"
          hint="Échéances arrivées à terme et encore impayées."
          action={
            echeances.length > 0 ? (
              <span className="mono text-sm font-semibold text-bad">
                {formatCurrency(enAttente?.total ?? 0)}
              </span>
            ) : null
          }
        />
        <CardBody className="p-0">
          {echeances.length === 0 ? (
            <p className="flex items-center justify-center gap-2 py-10 text-sm text-ok">
              <CheckCircle2 className="h-4 w-4" />
              Aucune charge fixe en attente.
            </p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Charge</th>
                  <th className="px-5 py-3 font-medium">Période</th>
                  <th className="px-5 py-3 font-medium">Échéance</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 text-right font-medium">Montant</th>
                  {canPay ? <th className="px-5 py-3" /> : null}
                </tr>
              </thead>
              <tbody>
                {echeances.map((e) => (
                  <tr key={e.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink">{e.label}</td>
                    <td className="px-5 py-3 text-muted">{libellePeriode(e.period)}</td>
                    <td className="px-5 py-3">
                      <span className="flex items-center gap-2">
                        {new Date(e.due_date).toLocaleDateString('fr-FR')}
                        {e.is_overdue ? (
                          <Badge tone="bad">
                            <AlertTriangle className="mr-1 inline h-3 w-3" />
                            En retard
                          </Badge>
                        ) : null}
                      </span>
                    </td>
                    <td className="mono px-5 py-3 text-muted">{e.warehouse?.code ?? 'Société'}</td>
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">
                      {formatCurrency(e.amount)}
                    </td>
                    {canPay ? (
                      <td className="px-5 py-3 text-right">
                        <Button
                          size="sm"
                          variant="outline"
                          disabled={regler.isPending}
                          onClick={() =>
                            regler.mutate({
                              id: e.id,
                              input: { payment_method_id: modePaiement || null },
                            })
                          }
                        >
                          Déclarer payée
                        </Button>
                      </td>
                    ) : null}
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </CardBody>

        {echeances.length > 0 && canPay ? (
          <div className="flex items-center gap-3 border-t border-line px-5 py-3">
            <span className="text-sm text-muted">Mode de règlement appliqué :</span>
            <Select
              value={modePaiement}
              onChange={(e) => setModePaiement(Number(e.target.value))}
              className="max-w-[200px]"
            >
              <option value={0}>— Non précisé —</option>
              {modes.map((m) => (
                <option key={m.id} value={m.id}>{m.name}</option>
              ))}
            </Select>
          </div>
        ) : null}
      </Card>

      {/* ── Formulaire ─────────────────────────────────────────────── */}
      {panneau ? (
        <Card>
          <CardHeader title={edition ? `Modifier « ${edition.label} »` : 'Nouvelle charge fixe'} />
          <CardBody className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-3">
              <Field label="Libellé" htmlFor="cf-label">
                <Input
                  id="cf-label"
                  value={form.label}
                  onChange={(e) => setForm({ ...form, label: e.target.value })}
                  placeholder="Loyer du dépôt"
                />
              </Field>
              <Field label="Montant (DH)" htmlFor="cf-amount">
                <Input
                  id="cf-amount"
                  type="number"
                  min={0.01}
                  step="0.01"
                  value={form.amount || ''}
                  onChange={(e) => setForm({ ...form, amount: Number(e.target.value) })}
                />
              </Field>
              <Field label="Jour d'échéance" htmlFor="cf-day">
                <Input
                  id="cf-day"
                  type="number"
                  min={1}
                  max={31}
                  value={form.day_of_month}
                  onChange={(e) => setForm({ ...form, day_of_month: Number(e.target.value) })}
                />
              </Field>
              <Field label="Premier mois" htmlFor="cf-start">
                <Input
                  id="cf-start"
                  type="month"
                  value={form.start_period}
                  onChange={(e) => setForm({ ...form, start_period: e.target.value })}
                />
              </Field>
              <Field label="Dernier mois (optionnel)" htmlFor="cf-end">
                <Input
                  id="cf-end"
                  type="month"
                  value={form.end_period ?? ''}
                  onChange={(e) => setForm({ ...form, end_period: e.target.value || null })}
                />
              </Field>
              <Field label="Catégorie" htmlFor="cf-cat">
                <Select
                  id="cf-cat"
                  value={form.expense_category_id ?? 0}
                  onChange={(e) => setForm({ ...form, expense_category_id: Number(e.target.value) || null })}
                >
                  <option value={0}>— Choisir —</option>
                  {categories.map((c) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Lieu" htmlFor="cf-wh">
                <Select
                  id="cf-wh"
                  value={form.warehouse_id ?? 0}
                  onChange={(e) => setForm({ ...form, warehouse_id: Number(e.target.value) || null })}
                >
                  <option value={0}>Société (tous les lieux)</option>
                  {lieux.map((w) => (
                    <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
                  ))}
                </Select>
              </Field>
            </div>

            <p className="text-xs text-faint">
              Le jour 31 vaut « fin de mois » : l'échéance tombe au dernier jour pour les mois plus courts.
            </p>

            <div className="flex gap-2">
              <Button onClick={enregistrer} disabled={
                  enregistrement ||
                  !form.label.trim() ||
                  form.amount <= 0 ||
                  !form.expense_category_id
                }>
                {enregistrement ? 'Enregistrement…' : 'Enregistrer'}
              </Button>
              <Button variant="ghost" onClick={() => setPanneau(false)}>Annuler</Button>
            </div>
          </CardBody>
        </Card>
      ) : null}

      {/* ── Liste des charges ──────────────────────────────────────── */}
      <Card>
        <CardHeader title="Charges définies" />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="py-10 text-center text-sm text-muted">Chargement…</p>
          ) : charges.length === 0 ? (
            <p className="py-12 text-center text-sm text-muted">
              Aucune charge fixe. Créez-en une : loyer, abonnement, salaire…
            </p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Libellé</th>
                  <th className="px-5 py-3 font-medium">Catégorie</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium">Échéance</th>
                  <th className="px-5 py-3 font-medium">Période</th>
                  <th className="px-5 py-3 text-right font-medium">Montant</th>
                  <th className="px-5 py-3 font-medium">État</th>
                  {canManage ? <th className="px-5 py-3" /> : null}
                </tr>
              </thead>
              <tbody>
                {charges.map((c) => (
                  <tr key={c.id} className="border-b border-line last:border-0">
                    <td className="px-5 py-3 text-ink">{c.label}</td>
                    <td className="px-5 py-3 text-muted">{c.category?.name ?? '—'}</td>
                    <td className="mono px-5 py-3 text-muted">{c.warehouse?.code ?? 'Société'}</td>
                    <td className="px-5 py-3 text-muted">
                      le {c.day_of_month === 31 ? 'dernier jour' : c.day_of_month}
                    </td>
                    <td className="px-5 py-3 text-muted">
                      {libellePeriode(c.start_period)}
                      {c.end_period ? ` → ${libellePeriode(c.end_period)}` : ' → sans fin'}
                    </td>
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">
                      {formatCurrency(c.amount)}
                    </td>
                    <td className="px-5 py-3">
                      {!c.is_active ? (
                        <Badge tone="neutral">Inactive</Badge>
                      ) : c.pending_count > 0 ? (
                        <Badge tone="warn">{c.pending_count} à régler</Badge>
                      ) : (
                        <Badge tone="ok">À jour</Badge>
                      )}
                    </td>
                    {canManage ? (
                      <td className="px-5 py-3">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => ouvrirEdition(c)}>
                            <Pencil className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="text-bad"
                            onClick={() => setASupprimer(c)}
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
        open={aSupprimer !== null}
        title="Supprimer la charge fixe"
        message={
          <>
            Supprimer <strong>{aSupprimer?.label}</strong> ?
            <br />
            <br />
            Si des échéances ont déjà été réglées, la charge sera simplement désactivée : elle cessera de
            générer des échéances sans effacer les règlements passés.
          </>
        }
        confirmLabel="Supprimer"
        danger
        isPending={supprimer.isPending}
        onConfirm={async () => {
          if (!aSupprimer) return
          const msg = await supprimer.mutateAsync(aSupprimer.id)
          setMessage(msg)
          setASupprimer(null)
        }}
        onCancel={() => setASupprimer(null)}
      />
    </div>
  )
}
