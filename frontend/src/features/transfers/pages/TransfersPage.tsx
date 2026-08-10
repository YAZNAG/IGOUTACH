import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Plus, Printer, Trash2 } from 'lucide-react'
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
import { downloadFile } from '@/lib/download'
import type { Paginated } from '@/types'

interface TransferRow {
  id: number
  reference: string
  from: string | null
  to: string | null
  status: string
  status_name: string | null
  lines_count: number
  sent_at: string | null
  received_at: string | null
  days_in_transit: number | null
  is_late: boolean
}

interface TransferDetail {
  id: number
  reference: string
  from: string | null
  to: string | null
  status: string
  sent_at: string | null
  received_at: string | null
  note: string | null
  lines: { id: number; sku: string | null; name: string | null; quantity_sent: number; quantity_received: number | null }[]
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
}

interface ProductOption {
  id: number
  sku: string
  name: string
}

const KEY = ['transfers'] as const

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Transferts inter-lieux : liste avec alerte transit > 3 jours,
 * création (envoi) et réception avec saisie des écarts.
 */
export function TransfersPage() {
  const [detailId, setDetailId] = useState<number | null>(null)

  if (detailId !== null) {
    return <TransferDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  return <TransferList onOpen={setDetailId} />
}

function TransferList({ onOpen }: { onOpen: (id: number) => void }) {
  const can = usePermission()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)

  const { data, isLoading } = useQuery<Paginated<TransferRow>>({
    queryKey: [...KEY, page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<TransferRow>>('/transfers', { params: { page } })
      return r
    },
  })

  const transfers = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Transferts</h1>
          <p className="text-sm text-muted">Mouvements de marchandise entre lieux — le CMUP voyage avec la marchandise.</p>
        </div>
        {can('transfer.create') && !creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouveau transfert
          </Button>
        ) : null}
      </div>

      {creating ? <CreateTransferPanel onClose={() => setCreating(false)} /> : null}

      <Card>
        <CardHeader title="Historique" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Trajet</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes</th>
                  <th className="px-5 py-3 font-medium">Envoyé le</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {transfers.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-8 text-center text-muted">Aucun transfert.</td></tr>
                ) : (
                  transfers.map((t) => (
                    <tr key={t.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{t.reference}</td>
                      <td className="px-5 py-3 text-ink">{t.from} → {t.to}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{t.lines_count}</td>
                      <td className="px-5 py-3 text-muted">{t.sent_at ?? '—'}</td>
                      <td className="px-5 py-3">
                        {t.status === 'received' ? <Badge tone="ok">Reçu</Badge> : null}
                        {t.status === 'in_transit' && !t.is_late ? <Badge tone="sky">En transit</Badge> : null}
                        {t.status === 'in_transit' && t.is_late ? (
                          <Badge tone="bad">En transit {t.days_in_transit} j ⚠</Badge>
                        ) : null}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="sm"
                            title="Imprimer le bon de transfert"
                            aria-label={`Imprimer le bon ${t.reference}`}
                            onClick={() => imprimerBonDeTransfert(t.id, t.reference)}
                          >
                            <Printer className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="sm" onClick={() => onOpen(t.id)}>
                            {t.status === 'in_transit' && can('transfer.receive') ? 'Réceptionner' : 'Consulter'}
                          </Button>
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

function CreateTransferPanel({ onClose }: { onClose: () => void }) {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const [fromId, setFromId] = useState(0)
  const [toId, setToId] = useState(0)
  const [note, setNote] = useState('')
  const [lines, setLines] = useState<DraftLine[]>([])
  const [search, setSearch] = useState('')

  const { data: options = [] } = useQuery<ProductOption[]>({
    queryKey: ['transfer-product-search', search],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', { params: { search, per_page: 20 } })
      return r.data
    },
    enabled: search.trim().length >= 2,
  })

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post('/transfers', {
        from_warehouse_id: fromId,
        to_warehouse_id: toId,
        note: note || null,
        lines: lines.map((l) => ({ product_id: l.product_id, quantity: l.quantity })),
      })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      onClose()
    },
  })

  return (
    <Card>
      <CardHeader title="Nouveau transfert (envoi)" />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Lieu source" htmlFor="tr-from">
            <Select id="tr-from" value={fromId || ''} onChange={(e) => setFromId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Lieu destination" htmlFor="tr-to">
            <Select id="tr-to" value={toId || ''} onChange={(e) => setToId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.filter((w) => w.id !== fromId).map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Note" htmlFor="tr-note">
            <Input id="tr-note" value={note} onChange={(e) => setNote(e.target.value)} />
          </Field>
        </div>

        <div className="space-y-2 rounded-lg border border-line p-3">
          <Input placeholder="Rechercher un article à ajouter…" value={search} onChange={(e) => setSearch(e.target.value)} />
          {options.length > 0 && search.trim().length >= 2 ? (
            <ul className="max-h-40 overflow-auto rounded border border-line">
              {options.filter((o) => !lines.some((l) => l.product_id === o.id)).map((o) => (
                <li key={o.id}>
                  <button
                    type="button"
                    onClick={() => {
                      setLines((p) => [...p, { product_id: o.id, sku: o.sku, name: o.name, quantity: 1 }])
                      setSearch('')
                    }}
                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                  >
                    <span className="mono text-muted">{o.sku}</span>
                    <span className="text-ink">{o.name}</span>
                  </button>
                </li>
              ))}
            </ul>
          ) : null}

          {lines.map((l, i) => (
            <div key={l.product_id} className="flex items-center gap-2">
              <span className="mono w-28 text-sm text-muted">{l.sku}</span>
              <span className="flex-1 truncate text-sm text-ink">{l.name}</span>
              <Input
                type="number"
                min={1}
                value={l.quantity}
                onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, quantity: Number(e.target.value) } : x)))}
                className="w-24"
              />
              <Button
                variant="ghost"
                size="sm"
                className="text-bad hover:bg-bad-bg"
                onClick={() => setLines((p) => p.filter((_, j) => j !== i))}
                aria-label={`Retirer ${l.name}`}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          ))}
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article ajouté.</p> : null}
        </div>

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Envoi impossible (stock insuffisant ?).')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button
            onClick={() => create.mutate()}
            disabled={create.isPending || fromId === 0 || toId === 0 || lines.length === 0}
          >
            {create.isPending ? 'Envoi…' : 'Envoyer le transfert'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

/**
 * Télécharge le bon de transfert.
 *
 * La référence sert de nom de fichier quand elle est connue : un dossier plein
 * de « transfert.pdf » serait inexploitable.
 */
function imprimerBonDeTransfert(id: number, reference?: string): void {
  void downloadFile(`/transfers/${id}/pdf`, `${reference ?? `transfert-${id}`}.pdf`)
}

function TransferDetailView({ id, onBack }: { id: number; onBack: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()

  const { data: transfer } = useQuery<TransferDetail>({
    queryKey: [...KEY, 'detail', id],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: TransferDetail }>(`/transfers/${id}`)
      return r.data
    },
  })

  const [received, setReceived] = useState<Record<number, number>>({})

  const receive = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/transfers/${id}/receive`, { quantities: received })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
    },
  })

  const inTransit = transfer?.status === 'in_transit'

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">Transfert {transfer?.reference}</h1>
            <p className="text-sm text-muted">
              {transfer?.from} → {transfer?.to} · envoyé le {transfer?.sent_at}{' '}
              {transfer?.status === 'received' ? <Badge tone="ok">Reçu</Badge> : <Badge tone="sky">En transit</Badge>}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => imprimerBonDeTransfert(id, transfer?.reference)}
          >
            <Printer className="h-4 w-4" />
            Imprimer le bon
          </Button>
          {inTransit && can('transfer.receive') ? (
            <Button onClick={() => receive.mutate()} disabled={receive.isPending}>
              {receive.isPending ? 'Validation…' : 'Valider la réception'}
            </Button>
          ) : null}
        </div>
      </div>

      {receive.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(receive.error, 'Réception impossible.')}
        </p>
      ) : null}

      <Card>
        <CardHeader title={inTransit ? 'Réception — saisir les quantités reçues' : 'Lignes'} />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Référence</th>
                <th className="px-5 py-3 font-medium">Article</th>
                <th className="px-5 py-3 text-right font-medium">Envoyé</th>
                <th className="px-5 py-3 text-right font-medium">Reçu</th>
              </tr>
            </thead>
            <tbody>
              {(transfer?.lines ?? []).map((l) => (
                <tr key={l.id} className="border-b border-line last:border-0">
                  <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                  <td className="px-5 py-3 text-ink">{l.name}</td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.quantity_sent}</td>
                  <td className="px-5 py-3 text-right">
                    {inTransit && can('transfer.receive') ? (
                      <Input
                        type="number"
                        min={0}
                        max={l.quantity_sent}
                        value={received[l.id] ?? l.quantity_sent}
                        onChange={(e) => setReceived((p) => ({ ...p, [l.id]: Number(e.target.value) }))}
                        className="ml-auto w-24"
                      />
                    ) : (
                      <span className="tabular text-ink">{l.quantity_received ?? '—'}</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
