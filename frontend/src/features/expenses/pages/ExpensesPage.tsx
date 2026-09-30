import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Download, FileText, Plus, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePaymentMethods } from '@/features/purchases/hooks'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { formatDate, formatDateHeure, formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

interface ExpenseRow {
  id: number
  label: string
  category: string | null
  warehouse: string | null
  user: string | null
  amount: number
  expense_date: string
  /** Horodatage de la saisie, distinct de la date de la charge. */
  created_at: string | null
  has_receipt: boolean
  status: string
  payment_status: string
  payment_method: string | null
}

interface CategoryOption {
  id: number
  name: string
}

const KEY = ['expenses'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as {
      response?: { data?: { message?: string; errors?: Record<string, string[]> } }
    }).response

    // Les erreurs champ par champ priment sur le message global : le serveur
    // n'y met que la premiere, alors que le formulaire peut en compter
    // plusieurs, et l'utilisateur corrigerait alors une faute a la fois.
    const champs = response?.data?.errors
    if (champs) {
      const lignes = Object.values(champs).flat()
      if (lignes.length > 0) return lignes.join(' ')
    }

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
  const [recherche, setRecherche] = useState('')

  // Recherche cote serveur : les charges sont paginees, et « loyer » se
  // trouve aussi bien dans le libelle que dans la famille de la charge.
  const rechercheRetardee = useDebouncedValue(recherche, 300)

  // Filtres : lieu, periode (date de la charge) et statut. Les exports les
  // reprennent tels quels.
  const { data: lieux = [] } = useWarehouseOptions()
  const [lieu, setLieu] = useState(0)
  const [du, setDu] = useState('')
  const [au, setAu] = useState('')
  const [statut, setStatut] = useState('')
  const filtreActif = lieu > 0 || du !== '' || au !== '' || statut !== '' || recherche !== ''

  const filtres = {
    warehouse_id: lieu || undefined,
    date_from: du || undefined,
    date_to: au || undefined,
    status: statut || undefined,
    search: rechercheRetardee || undefined,
  }

  function appliquer(action: () => void) {
    action()
    setPage(1)
  }

  const { data, isLoading } = useQuery<Paginated<ExpenseRow> & { meta: { total_amount?: number } }>({
    queryKey: [...KEY, page, lieu, du, au, statut, rechercheRetardee],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<ExpenseRow> & { meta: { total_amount?: number } }>('/expenses', {
        params: { page, ...filtres },
      })
      return r
    },
  })

  const [exportEnCours, setExportEnCours] = useState<'xlsx' | 'pdf' | null>(null)
  const [erreurExport, setErreurExport] = useState<string | null>(null)

  async function exporter(format: 'xlsx' | 'pdf') {
    setExportEnCours(format)
    setErreurExport(null)
    try {
      const params = new URLSearchParams({ format })
      Object.entries(filtres).forEach(([k, v]) => {
        if (v !== undefined) params.set(k, String(v))
      })
      await downloadFile(`/expenses/export?${params.toString()}`, `IGOUTECH_charges.${format}`)
    } catch (e) {
      const corps = (e as { response?: { data?: unknown } })?.response?.data
      let msg = "L'export n'a pas abouti. Réessayez, ou resserrez la période."
      if (corps instanceof Blob) {
        try {
          msg = (JSON.parse(await corps.text()) as { message?: string }).message ?? msg
        } catch {
          // Corps illisible : message generique.
        }
      }
      setErreurExport(msg)
    } finally {
      setExportEnCours(null)
    }
  }

  /** Charge dont la suppression attend confirmation. */
  const [aConfirmer, setAConfirmer] = useState<ExpenseRow | null>(null)
  const [erreurSuppression, setErreurSuppression] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  const decide = useMutation({
    mutationFn: async ({ id, decision }: { id: number; decision: 'approved' | 'rejected' }) => {
      await ensureCsrfCookie()
      await api.patch(`/expenses/${id}/decide`, { decision })
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  const supprimer = useMutation({
    mutationFn: async (id: number) => {
      await ensureCsrfCookie()
      const { data: r } = await api.delete<{ message: string }>(`/expenses/${id}`)
      return r.message
    },
    onSuccess: (message) => {
      setAConfirmer(null)
      setErreurSuppression(null)
      setMessage(message)
      qc.invalidateQueries({ queryKey: KEY })
    },
    onError: (e) => {
      setAConfirmer(null)
      setErreurSuppression(errorMessage(e, 'Suppression impossible.'))
    },
  })

  const expenses = data?.data ?? []
  const meta = data?.meta
  const peutAgir = can('expense.approve') || can('expense.delete')


  return (
    <div className="space-y-6">
      {message ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">{message}</p>
      ) : null}
      {erreurSuppression ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreurSuppression}</p>
      ) : null}

      <ConfirmDialog
        open={aConfirmer !== null}
        title="Supprimer cette charge"
        // Le retour de la somme au tiroir est la consequence qui compte : elle
        // doit etre annoncee avant, pas decouverte apres.
        message={
          aConfirmer === null
            ? ''
            : `${aConfirmer.label} — ${formatNumber(aConfirmer.amount)} DH.` +
              (aConfirmer.payment_status === 'paid'
                ? ' Cette charge est réglée : son montant reviendra en caisse.'
                : ' Cette charge est portée au crédit : la caisse n’est pas concernée.')
        }
        confirmLabel="Supprimer"
        danger
        isPending={supprimer.isPending}
        onCancel={() => setAConfirmer(null)}
        onConfirm={() => { if (aConfirmer) supprimer.mutate(aConfirmer.id) }}
      />

      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Charges</h1>
          <p className="text-sm text-muted">Dépenses par lieu et par utilisateur, validées par le responsable.</p>
        </div>
        <div className="flex flex-wrap justify-end gap-2">
          <Button variant="outline" size="sm" onClick={() => exporter('xlsx')} disabled={exportEnCours !== null}>
            <Download className="h-4 w-4" />
            {exportEnCours === 'xlsx' ? 'Export…' : 'Excel'}
          </Button>
          <Button variant="outline" size="sm" onClick={() => exporter('pdf')} disabled={exportEnCours !== null}>
            <FileText className="h-4 w-4" />
            {exportEnCours === 'pdf' ? 'Export…' : 'PDF'}
          </Button>
          {can('expense.create') && !creating ? (
            <Button onClick={() => setCreating(true)}>
              <Plus className="h-4 w-4" />
              Nouvelle charge
            </Button>
          ) : null}
        </div>
      </div>

      {erreurExport ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreurExport}</p>
      ) : null}

      {creating ? <CreateExpensePanel onClose={() => setCreating(false)} /> : null}

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
          <Field label="Recherche" htmlFor="chg-recherche">
            <SearchInput
              id="chg-recherche"
              value={recherche}
              onChange={(v) => appliquer(() => setRecherche(v))}
              placeholder="Libellé ou catégorie…"
              className="w-full"
            />
          </Field>
          <Field label="Lieu" htmlFor="chg-lieu">
            <Select id="chg-lieu" value={lieu} onChange={(e) => appliquer(() => setLieu(Number(e.target.value)))}>
              <option value={0}>Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>
          <Field label="Du" htmlFor="chg-du">
            <Input id="chg-du" type="date" value={du} onChange={(e) => appliquer(() => setDu(e.target.value))} />
          </Field>
          <Field label="Au" htmlFor="chg-au">
            <Input id="chg-au" type="date" value={au} onChange={(e) => appliquer(() => setAu(e.target.value))} />
          </Field>
          <Field label="Statut" htmlFor="chg-statut">
            <Select id="chg-statut" value={statut} onChange={(e) => appliquer(() => setStatut(e.target.value))}>
              <option value="">Tous</option>
              <option value="approved">Validées</option>
              <option value="pending">En attente</option>
              <option value="rejected">Rejetées</option>
            </Select>
          </Field>
          <div className="flex items-end">
            <Button
              variant="ghost"
              className="w-full"
              disabled={!filtreActif}
              onClick={() =>
                appliquer(() => {
                  setLieu(0)
                  setDu('')
                  setAu('')
                  setStatut('')
                  setRecherche('')
                })
              }
            >
              Effacer les filtres
            </Button>
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Charges"
          hint={
            meta
              ? `${meta.total} charge(s) · total ${formatNumber(meta.total_amount ?? 0)} DH (hors rejetées)`
              : undefined
          }
        />
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
                  <th className="px-5 py-3 font-medium">Date de la charge</th>
                  <th className="px-5 py-3 font-medium">Saisie le</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {peutAgir ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {expenses.length === 0 ? (
                  <tr><td colSpan={9} className="px-5 py-8 text-center text-muted">Aucune charge.</td></tr>
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
                      <td className="px-5 py-3 text-muted">{formatDate(e.expense_date)}</td>
                      <td className="px-5 py-3 text-faint">{formatDateHeure(e.created_at)}</td>
                      <td className="px-5 py-3">
                        {e.status === 'approved' ? <Badge tone="ok">Validée</Badge> : null}
                        {e.status === 'pending' ? <Badge tone="warn">En attente</Badge> : null}
                        {e.status === 'rejected' ? <Badge tone="bad">Rejetée</Badge> : null}
                      </td>
                      {peutAgir ? (
                        <td className="px-5 py-3 text-right">
                          <div className="flex justify-end gap-1">
                            {can('expense.approve') && e.status === 'pending' ? (
                              <>
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
                              </>
                            ) : null}
                            {can('expense.delete') ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-bad hover:bg-bad-bg"
                                onClick={() => { setErreurSuppression(null); setAConfirmer(e) }}
                                aria-label={`Supprimer ${e.label}`}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            ) : null}
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

  // 0 = rien de choisi, -1 = « Autre », qui fait apparaitre le champ libre.
  const AUTRE = -1
  const [categoryId, setCategoryId] = useState(0)
  const [categoryName, setCategoryName] = useState('')
  const [warehouseId, setWarehouseId] = useState(0)
  const [label, setLabel] = useState('')
  const [amount, setAmount] = useState('')
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10))
  const [receipt, setReceipt] = useState<File | null>(null)

  // Une charge est reglee par defaut : c'est le cas courant au comptoir.
  // Le mode de reglement devient alors obligatoire — le serveur le refusait
  // sans le dire, et le formulaire ne le demandait pas.
  const [reglee, setReglee] = useState(true)
  const [methodId, setMethodId] = useState(0)

  const { data: modes = [] } = usePaymentMethods()

  // Le premier mode de la liste evite un champ vide sur le cas courant.
  useEffect(() => {
    if (methodId === 0 && modes.length > 0) setMethodId(modes[0].id)
  }, [modes, methodId])

  // Un responsable ne voit que son lieu : on le choisit pour lui. Laisse sur
  // « Aucun », la charge n'etait rattachee a aucun lieu et disparaissait de
  // ses propres chiffres.
  useEffect(() => {
    if (warehouseId === 0 && warehouses.length === 1) setWarehouseId(warehouses[0].id)
  }, [warehouses, warehouseId])

  const [tentative, setTentative] = useState(false)

  /** Ce qui manque encore, dit en clair. Vide quand la charge peut partir. */
  const manques = [
    !categoryId ? 'le type de charge' : null,
    categoryId === AUTRE && categoryName.trim() === '' ? 'le nom du type « Autre »' : null,
    label.trim() === '' ? 'le libellé' : null,
    !(Number(amount) > 0) ? 'un montant supérieur à 0' : null,
    reglee && !methodId
      ? modes.length === 0
        ? 'un mode de règlement (aucun n’est disponible : choisissez « À crédit »)'
        : 'le mode de règlement'
      : null,
  ].filter((m): m is string => m !== null)

  const { data: categories = [] } = useQuery<CategoryOption[]>({
    queryKey: ['expense-categories'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: CategoryOption[] }>('/expense-categories')
      return r.data
    },
  })

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const form = new FormData()
      if (categoryId === AUTRE) {
        form.append('category_name', categoryName.trim())
      } else {
        form.append('expense_category_id', String(categoryId))
      }
      form.append('payment_status', reglee ? 'paid' : 'unpaid')
      if (reglee) form.append('payment_method_id', String(methodId))
      if (warehouseId) form.append('warehouse_id', String(warehouseId))
      form.append('label', label)
      form.append('amount', amount)
      form.append('expense_date', date)
      if (receipt) form.append('receipt', receipt)
      await api.post('/expenses', form, { headers: { 'Content-Type': 'multipart/form-data' } })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      // Le referentiel a pu s'enrichir d'un type saisi a la main : la liste
      // doit le montrer a la prochaine ouverture du formulaire.
      qc.invalidateQueries({ queryKey: ['expense-categories'] })
      onClose()
    },
  })

  return (
    <Card>
      <CardHeader title="Nouvelle charge" />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Type de charge" htmlFor="exp-category">
            <div className="space-y-1">
              <Select id="exp-category" value={categoryId || ''} onChange={(e) => setCategoryId(Number(e.target.value))}>
                <option value="" disabled>Choisir…</option>
                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                <option value={AUTRE}>Autre — à nommer ci-dessous</option>
              </Select>
              {categoryId === AUTRE ? (
                <Input
                  aria-label="Nom du type de charge"
                  value={categoryName}
                  onChange={(e) => setCategoryName(e.target.value)}
                  placeholder="Nommez ce type de charge…"
                />
              ) : null}
              {/* La création se fait dans Paramètres › Types de charge. Deux
                  endroits pour alimenter le même référentiel finissaient par
                  produire des doublons au fil des saisies. */}
              {can('expense.approve') ? (
                <p className="text-xs text-muted">
                  <Link to="/parametres/types-charge" className="underline">
                    Gérer les types de charge
                  </Link>
                </p>
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
          <Field label="Règlement" htmlFor="exp-reglee">
            <Select
              id="exp-reglee"
              value={reglee ? 'paid' : 'unpaid'}
              onChange={(e) => setReglee(e.target.value === 'paid')}
            >
              <option value="paid">Réglée</option>
              <option value="unpaid">À crédit (non réglée)</option>
            </Select>
          </Field>
          {reglee ? (
            <Field label="Mode de règlement" htmlFor="exp-method">
              <Select id="exp-method" value={methodId || ''} onChange={(e) => setMethodId(Number(e.target.value))}>
                <option value="" disabled>Choisir…</option>
                {modes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
              </Select>
            </Field>
          ) : null}
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
          {/* Le bouton ne se grise plus selon le contenu du formulaire : un
              bouton inerte sans explication faisait croire a une panne. Au
              clic, on dit ce qui manque ; sinon, on enregistre. */}
          <Button
            onClick={() => {
              setTentative(true)
              if (manques.length === 0) create.mutate()
            }}
            disabled={create.isPending}
          >
            {create.isPending ? 'Enregistrement…' : 'Enregistrer la charge'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
        {tentative && manques.length > 0 ? (
          <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
            Il manque {manques.join(', ')}.
          </p>
        ) : null}
      </CardBody>
    </Card>
  )
}
