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
import { usePermission } from '@/hooks/usePermission'
import { cn } from '@/lib/utils'
import { useApproveInventory, useCancelInventory, useCreateInventory, useInventories, useInventory, useSaveInventoryLines } from '../hooks'

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
  const [confirmApprove, setConfirmApprove] = useState(false)
  const [confirmCancel, setConfirmCancel] = useState(false)

  // Pré-remplit les quantités : celles déjà saisies au comptage sinon le théorique.
  useEffect(() => {
    const saved = inventory?.lines
    if (saved && saved.length > 0) {
      setCounts((prev) => {
        const next = { ...prev }
        for (const l of saved) if (!(l.product_id in next)) next[l.product_id] = l.counted_quantity
        return next
      })
    }
  }, [inventory])

  useEffect(() => {
    if (stock) {
      setCounts((prev) => {
        const next = { ...prev }
        for (const s of stock) if (!(s.product_id in next)) next[s.product_id] = s.quantity
        return next
      })
    }
  }, [stock])

  const rows = useMemo(() => stock ?? [], [stock])

  function save() {
    saveMutation.mutate({
      id,
      lines: rows.map((r) => ({ product_id: r.product_id, counted_quantity: counts[r.product_id] ?? r.quantity })),
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
            <Button variant="ghost" onClick={() => setConfirmCancel(true)} disabled={cancelMutation.isPending}>
              <XCircle className="h-4 w-4" />
              Annuler
            </Button>
            <Button variant="outline" onClick={save} disabled={saveMutation.isPending}>
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
          Comptage enregistré ({inventory?.lines_count ?? 0} article{(inventory?.lines_count ?? 0) > 1 ? 's' : ''}). Vous pouvez maintenant valider pour régulariser le stock.
        </p>
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
        <CardHeader title={isApproved ? 'Écarts régularisés' : isCancelled ? 'Comptage (annulé)' : 'Comptage'} />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Théorique</th>
                <th className="px-5 py-3 text-right font-medium">Compté</th>
                <th className="px-5 py-3 text-right font-medium">Écart</th>
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
                  </tr>
                ))
              ) : rows.length === 0 ? (
                <tr><td colSpan={5} className="px-5 py-8 text-center text-muted">Aucun article en stock dans ce lieu.</td></tr>
              ) : (
                rows.map((r) => {
                  const counted = counts[r.product_id] ?? r.quantity
                  const diff = counted - r.quantity
                  return (
                    <tr key={r.product_id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{r.sku}</td>
                      <td className="px-5 py-3 text-ink">{r.name}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{r.quantity}</td>
                      <td className="px-5 py-3 text-right">
                        <Input
                          type="number"
                          min={0}
                          value={counted}
                          onChange={(e) => setCounts((prev) => ({ ...prev, [r.product_id]: Number(e.target.value) }))}
                          className="ml-auto w-24"
                        />
                      </td>
                      <td className={cn('tabular px-5 py-3 text-right font-medium', diff === 0 ? 'text-muted' : diff > 0 ? 'text-ok' : 'text-bad')}>
                        {diff > 0 ? `+${diff}` : diff}
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
            Valider l'inventaire <strong>{inventory?.reference}</strong> ? Le stock du lieu sera
            <strong> régularisé automatiquement</strong> selon les écarts. Cette action est définitive.
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
