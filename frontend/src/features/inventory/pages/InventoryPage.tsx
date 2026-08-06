import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, CheckCircle2, Plus, Save, XCircle } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { api } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { usePermission } from '@/hooks/usePermission'
import { cn, formatNumber } from '@/lib/utils'
import {
  useApproveInventory,
  useCancelInventory,
  useCreateInventory,
  useInventories,
  useInventory,
  useRemoveInventoryLine,
  useSaveInventoryLines,
  useUpdateInventory,
} from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

export function InventoryPage() {
  const can = usePermission()
  const canApprove = can('inventory.approve')

  const { data: warehouses = [] } = useWarehouseOptions()
  const [selectedId, setSelectedId] = useState<number | null>(null)

  if (selectedId !== null) {
    return <InventoryDetail id={selectedId} canApprove={canApprove} onBack={() => setSelectedId(null)} />
  }

  return <InventoryList warehouses={warehouses} onOpen={setSelectedId} />
}

function InventoryList({
  warehouses,
  onOpen,
}: {
  warehouses: { id: number; code: string; name: string }[]
  onOpen: (id: number) => void
}) {
  const [warehouseId, setWarehouseId] = useState<number | undefined>(undefined)
  const [page, setPage] = useState(1)
  const { data, isLoading } = useInventories(warehouseId, page)
  const createMutation = useCreateInventory()

  const [creating, setCreating] = useState(false)
  const [form, setForm] = useState({ warehouse_id: 0, counted_at: new Date().toISOString().slice(0, 10), note: '' })

  const inventories = data?.data ?? []
  const meta = data?.meta

  function submit(e: React.FormEvent) {
    e.preventDefault()
    if (!form.warehouse_id) return
    createMutation.mutate(
      { warehouse_id: form.warehouse_id, counted_at: form.counted_at, note: form.note || null },
      { onSuccess: (inv) => { setCreating(false); onOpen(inv.id) } },
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Inventaires</h1>
          <p className="text-sm text-muted">Comptage physique par lieu et régularisation du stock.</p>
        </div>
        {!creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouvel inventaire
          </Button>
        ) : null}
      </div>

      {creating ? (
        <Card>
          <CardHeader title="Nouvel inventaire" />
          <CardBody>
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-3">
              <Field label="Lieu" htmlFor="inv-wh">
                <Select id="inv-wh" value={form.warehouse_id || ''} onChange={(e) => setForm({ ...form, warehouse_id: Number(e.target.value) })} required>
                  <option value="" disabled>Choisir un lieu…</option>
                  {warehouses.map((w) => (
                    <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
                  ))}
                </Select>
              </Field>
              <Field label="Date de l'inventaire" htmlFor="inv-date">
                <Input id="inv-date" type="date" value={form.counted_at} onChange={(e) => setForm({ ...form, counted_at: e.target.value })} required />
              </Field>
              <Field label="Note" htmlFor="inv-note">
                <Input id="inv-note" value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} />
              </Field>
              <div className="flex gap-2 sm:col-span-3">
                <Button type="submit" disabled={createMutation.isPending}>Créer et compter</Button>
                <Button type="button" variant="ghost" onClick={() => setCreating(false)}>Annuler</Button>
              </div>
            </form>
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader
          title="Historique"
          hint={meta ? `${meta.total}` : undefined}
          action={
            <Select value={warehouseId ?? ''} onChange={(e) => { setWarehouseId(e.target.value ? Number(e.target.value) : undefined); setPage(1) }} className="w-52">
              <option value="">Tous les lieux</option>
              {warehouses.map((w) => (
                <option key={w.id} value={w.id}>{w.code} · {w.name}</option>
              ))}
            </Select>
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {inventories.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-8 text-center text-muted">Aucun inventaire.</td></tr>
                ) : (
                  inventories.map((inv) => (
                    <tr key={inv.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{inv.reference}</td>
                      <td className="px-5 py-3 text-ink">{inv.warehouse?.code ?? '—'}</td>
                      <td className="px-5 py-3 text-muted">{inv.counted_at ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{inv.lines_count ?? 0}</td>
                      <td className="px-5 py-3">
                        {inv.status === 'approved' ? <Badge tone="ok">Validé</Badge> : null}
                        {inv.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
                        {inv.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <Button variant="ghost" size="sm" onClick={() => onOpen(inv.id)}>
                          {inv.status === 'draft' ? 'Compter' : 'Consulter'}
                        </Button>
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

interface StockLine {
  product_id: number
  sku: string
  name: string
  quantity: number
}

function InventoryDetail({ id, canApprove, onBack }: { id: number; canApprove: boolean; onBack: () => void }) {
  const { data: inventory } = useInventory(id)
  const saveMutation = useSaveInventoryLines()
  const approveMutation = useApproveInventory()
  const cancelMutation = useCancelInventory()
  const updateMutation = useUpdateInventory()
  const removeLineMutation = useRemoveInventoryLine()

  const isApproved = inventory?.status === 'approved'
  const isCancelled = inventory?.status === 'cancelled'
  const isDraft = inventory?.status === 'draft'

  // Stock actuel du lieu (base du comptage) — chargé pour un brouillon.
  const { data: stock } = useQuery<StockLine[]>({
    queryKey: ['inventory-stock', inventory?.warehouse_id],
    queryFn: async () => {
      const { data } = await api.get<{ data: StockLine[] }>('/stock', {
        params: { warehouse_id: inventory?.warehouse_id, per_page: 1000 },
      })
      return data.data
    },
    enabled: inventory != null && isDraft,
  })

  const [counts, setCounts] = useState<Record<number, number>>({})
  const [reasons, setReasons] = useState<Record<number, string>>({})
  const [confirmApprove, setConfirmApprove] = useState(false)
  const [confirmCancel, setConfirmCancel] = useState(false)
  // Reprise du comptage : recherche + filtre sur ce qui reste à compter.
  const [search, setSearch] = useState('')
  const [onlyRemaining, setOnlyRemaining] = useState(false)
  const [editDate, setEditDate] = useState('')

  // Pré-remplit uniquement depuis un comptage déjà enregistré — jamais depuis
  // le théorique : le compteur doit saisir son chiffre sans le recopier.
  useEffect(() => {
    const saved = inventory?.lines
    if (saved && saved.length > 0) {
      setCounts((prev) => {
        const next = { ...prev }
        for (const l of saved) if (!(l.product_id in next)) next[l.product_id] = l.counted_quantity
        return next
      })
      setReasons((prev) => {
        const next = { ...prev }
        for (const l of saved) if (l.reason && !(l.product_id in next)) next[l.product_id] = l.reason
        return next
      })
    }
  }, [inventory])

  const allRows = useMemo(() => stock ?? [], [stock])
  const countedRows = allRows.filter((r) => counts[r.product_id] !== undefined)
  // Le motif d'écart est facultatif : il explique un écart sans bloquer la saisie.
  const gapsWithoutReason = countedRows.filter(
    (r) => (counts[r.product_id] ?? 0) !== r.quantity && (reasons[r.product_id] ?? '').trim() === '',
  ).length
  // Lignes déjà enregistrées côté serveur (comptage des jours précédents).
  const savedCount = inventory?.lines?.length ?? inventory?.lines_count ?? 0

  const rows = useMemo(() => {
    const term = search.trim().toLowerCase()
    return allRows.filter((r) => {
      if (onlyRemaining && counts[r.product_id] !== undefined) return false
      if (term === '') return true
      return r.sku.toLowerCase().includes(term) || r.name.toLowerCase().includes(term)
    })
  }, [allRows, search, onlyRemaining, counts])
  function save() {
    saveMutation.mutate({
      id,
      lines: countedRows.map((r) => ({
        product_id: r.product_id,
        counted_quantity: counts[r.product_id] ?? 0,
        reason: (reasons[r.product_id] ?? '').trim() || null,
      })),
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">Inventaire {inventory?.reference}</h1>
            <p className="text-sm text-muted">
              {inventory?.warehouse?.code} · {inventory?.counted_at}{' '}
              {isApproved ? <Badge tone="ok">Validé</Badge> : null}
              {isCancelled ? <Badge tone="bad">Annulé</Badge> : null}
              {isDraft ? <Badge tone="warn">Brouillon</Badge> : null}
            </p>
          </div>
        </div>
        {isDraft ? (
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => downloadFile(`/inventories/${id}/sheet`, `comptage-${inventory?.reference}.xlsx`)}>
              Feuille de comptage
            </Button>
            <Button variant="ghost" onClick={() => setConfirmCancel(true)} disabled={cancelMutation.isPending}>
              <XCircle className="h-4 w-4" />
              Annuler
            </Button>
            <Button
              variant="outline"
              onClick={save}
              disabled={saveMutation.isPending || countedRows.length === 0}
              title={countedRows.length === 0 ? 'Saisissez au moins un comptage' : undefined}
            >
              <Save className="h-4 w-4" />
              {saveMutation.isPending ? 'Enregistrement…' : 'Enregistrer le comptage'}
            </Button>
            {canApprove ? (
              <Button
                onClick={() => setConfirmApprove(true)}
                disabled={approveMutation.isPending || (inventory?.lines_count ?? 0) === 0}
                title={(inventory?.lines_count ?? 0) === 0 ? 'Enregistrez le comptage d\'abord' : undefined}
              >
                <CheckCircle2 className="h-4 w-4" />
                Valider et régulariser
              </Button>
            ) : null}
          </div>
        ) : null}
      </div>

      {saveMutation.isSuccess && !saveMutation.isPending ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          Comptage enregistré ({savedCount} article{savedCount > 1 ? 's' : ''}). Vous pouvez continuer plus tard
          ou valider dès maintenant pour régulariser le stock.
        </p>
      ) : null}

      {/* Comptage en plusieurs fois : progression + reprise à une autre date. */}
      {isDraft ? (
        <Card>
          <CardBody className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-4">
              <div>
                <p className="text-sm text-ink">
                  <strong>{countedRows.length}</strong> article{countedRows.length > 1 ? 's' : ''} compté
                  {countedRows.length > 1 ? 's' : ''} sur {allRows.length} —{' '}
                  <span className="text-muted">
                    {allRows.length - countedRows.length} restant{allRows.length - countedRows.length > 1 ? 's' : ''}
                  </span>
                </p>
                <p className="text-xs text-muted">
                  Vous n'êtes pas obligé de tout compter : les articles laissés vides ne seront pas régularisés
                  et garderont leur stock actuel.
                </p>
                {gapsWithoutReason > 0 ? (
                  <p className="text-xs text-warn">
                    {gapsWithoutReason} écart{gapsWithoutReason > 1 ? 's' : ''} sans motif — le motif est facultatif,
                    il sert seulement à expliquer l'écart plus tard.
                  </p>
                ) : null}
              </div>
              <div className="h-2 w-40 overflow-hidden rounded-full bg-bg">
                <div
                  className="h-full rounded-full bg-ok"
                  style={{ width: `${allRows.length ? (countedRows.length / allRows.length) * 100 : 0}%` }}
                />
              </div>
            </div>

            <div className="flex flex-wrap items-end gap-3">
              <Field label="Rechercher un article" htmlFor="inv-search">
                <Input
                  id="inv-search"
                  placeholder="Référence ou nom…"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-64"
                />
              </Field>
              <label className="flex h-10 items-center gap-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={onlyRemaining}
                  onChange={(e) => setOnlyRemaining(e.target.checked)}
                />
                Restant à compter uniquement
              </label>
              <Field label="Date du comptage" htmlFor="inv-date">
                <div className="flex gap-2">
                  <Input
                    id="inv-date"
                    type="date"
                    value={editDate || (inventory?.counted_at ?? '')}
                    onChange={(e) => setEditDate(e.target.value)}
                    className="w-40"
                  />
                  {editDate && editDate !== inventory?.counted_at ? (
                    <Button
                      variant="outline"
                      onClick={() =>
                        updateMutation.mutate(
                          { id, input: { counted_at: editDate } },
                          { onSuccess: () => setEditDate('') },
                        )
                      }
                      disabled={updateMutation.isPending}
                    >
                      {updateMutation.isPending ? '…' : 'Changer'}
                    </Button>
                  ) : null}
                </div>
              </Field>
            </div>
          </CardBody>
        </Card>
      ) : null}

      {saveMutation.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(saveMutation.error, 'Enregistrement impossible.')}
        </p>
      ) : null}

      {approveMutation.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(approveMutation.error, 'Validation impossible.')}
        </p>
      ) : null}

      <Card>
        <CardHeader
          title={isApproved ? 'Écarts régularisés' : isCancelled ? 'Comptage (annulé)' : 'Comptage'}
          hint={
            !isDraft && (inventory?.lines ?? []).some((l) => l.variance_value !== null)
              ? `Valorisation des écarts : ${formatNumber((inventory?.lines ?? []).reduce((s, l) => s + (l.variance_value ?? 0), 0))} DH`
              : isDraft
                ? `${countedRows.length} / ${rows.length} comptés`
                : undefined
          }
        />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Théorique</th>
                <th className="px-5 py-3 text-right font-medium">Compté</th>
                <th className="px-5 py-3 text-right font-medium">Écart</th>
                <th className="px-5 py-3 font-medium">{isDraft ? 'Motif (facultatif)' : 'Motif'}</th>
                {!isDraft ? <th className="px-5 py-3 text-right font-medium">Valorisation</th> : null}
                {isDraft ? <th className="px-5 py-3 text-right font-medium">État</th> : null}
              </tr>
            </thead>
            <tbody>
              {!isDraft ? (
                (inventory?.lines ?? []).map((l) => (
                  <tr key={l.product_id} className="border-b border-line last:border-0">
                    <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                    <td className="px-5 py-3 text-ink">{l.name}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{l.system_quantity}</td>
                    <td className="tabular px-5 py-3 text-right text-ink">{l.counted_quantity}</td>
                    <td className={cn('tabular px-5 py-3 text-right font-medium', l.difference === 0 ? 'text-muted' : l.difference > 0 ? 'text-ok' : 'text-bad')}>
                      {l.difference > 0 ? `+${l.difference}` : l.difference}
                    </td>
                    <td className="px-5 py-3 text-muted">{l.reason ?? '—'}</td>
                    <td className={cn('tabular px-5 py-3 text-right', (l.variance_value ?? 0) === 0 ? 'text-muted' : (l.variance_value ?? 0) > 0 ? 'text-ok' : 'text-bad')}>
                      {l.variance_value !== null ? `${formatNumber(l.variance_value)} DH` : '—'}
                    </td>
                  </tr>
                ))
              ) : rows.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-5 py-8 text-center text-muted">
                    {allRows.length === 0
                      ? 'Aucun article en stock dans ce lieu.'
                      : onlyRemaining
                        ? 'Tous les articles affichés ont été comptés. ✓'
                        : 'Aucun article ne correspond à la recherche.'}
                  </td>
                </tr>
              ) : (
                rows.map((r) => {
                  const entered = counts[r.product_id] !== undefined
                  const counted = counts[r.product_id] ?? 0
                  const diff = entered ? counted - r.quantity : 0
                  return (
                    <tr key={r.product_id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                      <td className="px-5 py-3 text-ink">{r.name}</td>
                      {/* Théorique masqué tant que le compteur n'a pas saisi son chiffre. */}
                      <td className="tabular px-5 py-3 text-right text-muted">{entered ? r.quantity : '•••'}</td>
                      <td className="px-5 py-3 text-right">
                        <Input
                          type="number"
                          min={0}
                          value={entered ? counted : ''}
                          placeholder="—"
                          onChange={(e) => {
                            const v = e.target.value
                            setCounts((prev) => {
                              const next = { ...prev }
                              if (v === '') {
                                delete next[r.product_id]
                              } else {
                                next[r.product_id] = Number(v)
                              }
                              return next
                            })
                          }}
                          className="ml-auto w-24"
                        />
                      </td>
                      <td className={cn('tabular px-5 py-3 text-right font-medium', !entered || diff === 0 ? 'text-muted' : diff > 0 ? 'text-ok' : 'text-bad')}>
                        {entered ? (diff > 0 ? `+${diff}` : diff) : '—'}
                      </td>
                      <td className="px-5 py-3">
                        {entered && diff !== 0 ? (
                          <Input
                            value={reasons[r.product_id] ?? ''}
                            placeholder="Motif (facultatif)…"
                            onChange={(e) => setReasons((prev) => ({ ...prev, [r.product_id]: e.target.value }))}
                            className="w-44"
                          />
                        ) : (
                          <span className="text-muted">—</span>
                        )}
                      </td>
                      <td className="px-5 py-3 text-right">
                        {entered ? (
                          <Button
                            variant="ghost"
                            size="sm"
                            className="text-muted"
                            title="Retirer ce comptage (l'article ne sera pas régularisé)"
                            onClick={() => {
                              setCounts((prev) => {
                                const next = { ...prev }
                                delete next[r.product_id]
                                return next
                              })
                              setReasons((prev) => {
                                const next = { ...prev }
                                delete next[r.product_id]
                                return next
                              })
                              // Supprime aussi côté serveur si déjà enregistré.
                              if (inventory?.lines?.some((l) => l.product_id === r.product_id)) {
                                removeLineMutation.mutate({ id, productId: r.product_id })
                              }
                            }}
                          >
                            <XCircle className="h-4 w-4" />
                          </Button>
                        ) : (
                          <span className="text-xs text-faint">à compter</span>
                        )}
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </CardBody>
      </Card>

      <ConfirmDialog
        open={confirmApprove}
        title="Valider l'inventaire"
        message={
          <>
            Valider l'inventaire <strong>{inventory?.reference}</strong> ?
            <br />
            <br />
            <strong>{savedCount}</strong> article{savedCount > 1 ? 's' : ''} compté{savedCount > 1 ? 's' : ''}
            {' '}ser{savedCount > 1 ? 'ont' : 'a'} régularisé{savedCount > 1 ? 's' : ''} selon les écarts.
            {allRows.length > savedCount ? (
              <>
                {' '}Les <strong>{allRows.length - savedCount}</strong> autres articles du lieu, non comptés,
                <strong> ne seront pas modifiés</strong> et garderont leur stock actuel.
              </>
            ) : null}
            <br />
            <br />
            Cette action est définitive.
          </>
        }
        confirmLabel="Valider et régulariser"
        danger={false}
        isPending={approveMutation.isPending}
        onConfirm={() => approveMutation.mutate(id, { onSuccess: () => setConfirmApprove(false) })}
        onCancel={() => setConfirmApprove(false)}
      />

      <ConfirmDialog
        open={confirmCancel}
        title="Annuler l'inventaire"
        message={
          <>
            Annuler l'inventaire <strong>{inventory?.reference}</strong> ? Le comptage saisi sera conservé
            pour consultation mais le stock ne sera pas régularisé.
          </>
        }
        confirmLabel="Annuler l'inventaire"
        danger
        isPending={cancelMutation.isPending}
        error={cancelMutation.isError ? errorMessage(cancelMutation.error, 'Annulation impossible.') : undefined}
        onConfirm={() => cancelMutation.mutate(id, { onSuccess: () => setConfirmCancel(false) })}
        onCancel={() => setConfirmCancel(false)}
      />
    </div>
  )
}
