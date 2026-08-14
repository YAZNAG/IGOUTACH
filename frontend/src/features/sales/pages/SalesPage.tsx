import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Download, FileText, Lock, LockOpen, Pencil, Plus, Trash2 } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'

export interface SaleRow {
  id: number
  reference: string
  type: string
  status: string
  customer: string | null
  warehouse: string | null
  total: number
  paid_amount: number
  payment_status: string
  lines_count: number
  quote_id: number | null
  converted: boolean
  created_at: string | null
}

interface SaleDetail {
  id: number
  reference: string
  type: string
  status: string
  customer: { id: number; name: string; balance: number; credit_limit: number; is_blocked: boolean } | null
  warehouse: string | null
  subtotal: number
  discount_percent: number
  total: number
  paid_amount: number
  payment_status: string
  confirmed_at: string | null
  note: string | null
  lines: {
    product_id: number
    sku: string | null
    name: string | null
    quantity: number
    unit_price: number
    price_type_code: string | null
    line_total: number
  }[]
}

/** Ligne en cours de modification sur un document encore en brouillon. */
interface EditLine {
  product_id: number
  sku: string | null
  name: string | null
  quantity: number
  unit_price: number
}

interface CustomerOption {
  id: number
  code: string
  name: string
  is_blocked: boolean
}

interface ProductOption {
  id: number
  sku: string
  name: string
  current_stock?: number
}

interface DraftLine {
  product_id: number
  sku: string
  name: string
  quantity: number
  unit_price: number
  price_type_code: string | null
  /**
   * Plancher de prix, égal au prix d'achat. Vaut `null` quand l'utilisateur
   * n'a pas le droit de consulter les coûts : l'interface ne peut alors rien
   * afficher ni vérifier, et le serveur reste seul juge.
   */
  floor_price: number | null
  /** Stock disponible sur le lieu choisi. */
  current_stock: number
  /** Prix déverrouillé pour saisie manuelle. */
  manual: boolean
}

const KEY = ['sales'] as const

/** Valeur retardée : évite une requête serveur à chaque frappe. */
function useDebouncedValue(value: string, delayMs: number): string {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delayMs)
    return () => clearTimeout(timer)
  }, [value, delayMs])
  return debounced
}

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Ventes : devis et factures. Les prix sont résolus par le serveur
 * (type de prix du client puis paliers de quantité), le stock sort à la
 * confirmation et la créance alimente le crédit client.
 */
export function SalesPage() {
  const [detailId, setDetailId] = useState<number | null>(null)

  if (detailId !== null) {
    return <SaleDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  return <SalesList onOpen={setDetailId} />
}

function SalesList({ onOpen }: { onOpen: (id: number) => void }) {
  const can = usePermission()
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  const [pickingQuote, setPickingQuote] = useState(false)
  const [aSupprimer, setASupprimer] = useState<SaleRow | null>(null)
  const [erreurSuppression, setErreurSuppression] = useState<string | null>(null)

  const supprimer = useMutation({
    mutationFn: async (id: number) => {
      await ensureCsrfCookie()
      await api.delete(`/sales/${id}`)
    },
    onSuccess: () => {
      setASupprimer(null)
      setErreurSuppression(null)
      qc.invalidateQueries({ queryKey: KEY })
    },
    onError: (e) => {
      setASupprimer(null)
      setErreurSuppression(errorMessage(e, 'Suppression impossible.'))
    },
  })

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: [...KEY, 'invoices', page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { page, type: 'invoice' } })
      return r
    },
  })

  const sales = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Ventes</h1>
          <p className="text-sm text-muted">
            Factures — la validation sort le stock et génère le bon de livraison. Les devis se créent dans la page Devis.
          </p>
        </div>
        {can('sale.create') ? (
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => { setPickingQuote((v) => !v); setCreating(false) }}
            >
              <FileText className="h-4 w-4" />
              Depuis un devis
            </Button>
            <Button onClick={() => { setCreating((v) => !v); setPickingQuote(false) }}>
              <Plus className="h-4 w-4" />
              Vente directe
            </Button>
          </div>
        ) : null}
      </div>

      {erreurSuppression ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreurSuppression}</p>
      ) : null}

      {pickingQuote ? <QuotePicker onClose={() => setPickingQuote(false)} onConverted={onOpen} /> : null}

      {creating ? <CreateSalePanel fixedType="invoice" onClose={() => setCreating(false)} onCreated={onOpen} /> : null}

      <Card>
        <CardHeader title="Factures" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Client</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes</th>
                  <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 font-medium">Règlement</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {sales.length === 0 ? (
                  <tr><td colSpan={8} className="px-5 py-8 text-center text-muted">Aucune vente.</td></tr>
                ) : (
                  sales.map((s) => (
                    <tr key={s.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">
                        {s.reference}
                        {s.quote_id !== null ? <span className="ml-1 text-xs text-faint" title="Issue d'un devis">↩</span> : null}
                      </td>
                      <td className="px-5 py-3 text-muted">{s.created_at ?? '—'}</td>
                      <td className="px-5 py-3 text-ink">{s.customer ?? <span className="text-muted">Passager</span>}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{s.lines_count}</td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(s.total)}</td>
                      <td className="px-5 py-3">
                        {s.status === 'confirmed' ? <Badge tone="ok">Confirmé</Badge> : null}
                        {s.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
                        {s.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
                      </td>
                      <td className="px-5 py-3">
                        {s.status === 'confirmed' ? (
                          s.payment_status === 'paid' ? <Badge tone="ok">Payé</Badge>
                            : s.payment_status === 'partial' ? <Badge tone="warn">Partiel</Badge>
                              : <Badge tone="bad">Impayé</Badge>
                        ) : '—'}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => onOpen(s.id)} title="Consulter">
                            Ouvrir
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => downloadFile(`/sales/${s.id}/pdf`, `${s.reference}.pdf`)}
                            title="Facture PDF"
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                          {/* Réservé aux ventes annulées : le serveur refuse
                              toute autre, et celles qui ont laissé une trace. */}
                          {s.status === 'cancelled' && can('sale.cancel') ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label={`Supprimer ${s.reference}`}
                              title="Supprimer définitivement"
                              onClick={() => { setErreurSuppression(null); setASupprimer(s) }}
                            >
                              <Trash2 className="h-4 w-4 text-bad" />
                            </Button>
                          ) : null}
                          {s.status === 'confirmed' ? (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => downloadFile(`/sales/${s.id}/exit-pdf`, `BL-${s.reference}.pdf`)}
                              title="Bon de livraison PDF"
                            >
                              <FileText className="h-4 w-4" />
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

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Précédent</Button>
          <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>Suivant</Button>
        </div>
      ) : null}

      <ConfirmDialog
        open={aSupprimer !== null}
        title="Supprimer la vente annulée"
        message={
          <>
            Supprimer définitivement <strong>{aSupprimer?.reference}</strong> ? Cette vente
            disparaîtra de l'historique. L'opération est refusée si un règlement, une écriture
            de crédit ou un mouvement de stock s'y rattache.
          </>
        }
        confirmLabel="Supprimer"
        danger
        isPending={supprimer.isPending}
        onConfirm={() => aSupprimer && supprimer.mutate(aSupprimer.id)}
        onCancel={() => setASupprimer(null)}
      />
    </div>
  )
}

/**
 * Sélecteur de devis : liste des devis non convertis, un clic crée la
 * vente (facture brouillon) avec les mêmes lignes.
 */
function QuotePicker({ onClose, onConverted }: { onClose: () => void; onConverted: (invoiceId: number) => void }) {
  const qc = useQueryClient()
  const [error, setError] = useState<string | null>(null)

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: [...KEY, 'quotes-picker'],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { type: 'quote', per_page: 50 } })
      return r
    },
  })

  const convert = useMutation({
    mutationFn: async (quoteId: number) => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { id: number } }>(`/sales/${quoteId}/convert`)
      return r.data
    },
    onSuccess: (result) => {
      qc.invalidateQueries({ queryKey: KEY })
      onConverted(result.id)
    },
    onError: (e) => setError(errorMessage(e, 'Conversion impossible.')),
  })

  const quotes = (data?.data ?? []).filter((q) => q.status !== 'cancelled')

  return (
    <Card>
      <CardHeader title="Créer une vente à partir d'un devis" hint="Les lignes et le client du devis sont repris tels quels." />
      <CardBody className="space-y-3">
        {error ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{error}</p> : null}
        {isLoading ? (
          <p className="text-sm text-muted">Chargement…</p>
        ) : quotes.length === 0 ? (
          <p className="text-sm text-muted">Aucun devis disponible — créez-en un dans la page Devis.</p>
        ) : (
          <div className="max-h-80 overflow-auto rounded border border-line">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-4 py-2 font-medium">Référence</th>
                  <th className="px-4 py-2 font-medium">Date</th>
                  <th className="px-4 py-2 font-medium">Client</th>
                  <th className="px-4 py-2 text-right font-medium">Lignes</th>
                  <th className="px-4 py-2 text-right font-medium">Total (DH)</th>
                  <th className="px-4 py-2 text-right font-medium" />
                </tr>
              </thead>
              <tbody>
                {quotes.map((q) => (
                  <tr key={q.id} className={`border-b border-line last:border-0 ${q.converted ? 'opacity-50' : ''}`}>
                    <td className="mono px-4 py-2 text-muted">{q.reference}</td>
                    <td className="px-4 py-2 text-muted">{q.created_at ?? '—'}</td>
                    <td className="px-4 py-2 text-ink">{q.customer ?? <span className="text-muted">Passager</span>}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{q.lines_count}</td>
                    <td className="tabular px-4 py-2 text-right text-ink">{formatNumber(q.total)}</td>
                    <td className="px-4 py-2 text-right">
                      {q.converted ? (
                        <Badge tone="neutral">Déjà converti</Badge>
                      ) : (
                        <Button
                          size="sm"
                          onClick={() => convert.mutate(q.id)}
                          disabled={convert.isPending}
                        >
                          Convertir en vente
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <Button variant="ghost" onClick={onClose}>Fermer</Button>
      </CardBody>
    </Card>
  )
}

export function CreateSalePanel({
  onClose,
  onCreated,
  fixedType = 'invoice',
}: {
  onClose: () => void
  onCreated: (id: number) => void
  fixedType?: 'invoice' | 'quote'
}) {
  const qc = useQueryClient()
  const { data: warehouses = [] } = useWarehouseOptions()

  const type = fixedType
  // Client de passage : vente comptoir sans fiche client ni crédit.
  const [walkIn, setWalkIn] = useState(false)
  const [customerId, setCustomerId] = useState(0)
  const [warehouseId, setWarehouseId] = useState(0)
  const [discount, setDiscount] = useState(0)
  const [lines, setLines] = useState<DraftLine[]>([])
  const [customerSearch, setCustomerSearch] = useState('')
  const [productSearch, setProductSearch] = useState('')

  // Recherches retardées : une requête au repos de frappe, pas par caractère.
  const debouncedCustomerSearch = useDebouncedValue(customerSearch, 250)
  const debouncedProductSearch = useDebouncedValue(productSearch, 250)

  const { data: customers = [] } = useQuery<CustomerOption[]>({
    queryKey: ['sale-customer-search', debouncedCustomerSearch],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<CustomerOption>>('/customers', {
        params: { q: debouncedCustomerSearch || undefined, per_page: 20 },
      })
      return r.data
    },
    placeholderData: keepPreviousData,
    staleTime: 30_000,
  })

  const { data: products = [] } = useQuery<ProductOption[]>({
    queryKey: ['sale-product-search', debouncedProductSearch, warehouseId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', {
        // warehouse_id ajoute current_stock (stock du lieu) à chaque article.
        params: { search: debouncedProductSearch, warehouse_id: warehouseId || undefined, per_page: 20 },
      })
      return r.data
    },
    enabled: debouncedProductSearch.trim().length >= 2,
    placeholderData: keepPreviousData,
    staleTime: 30_000,
  })

  const [priceWarning, setPriceWarning] = useState<string | null>(null)

  async function addProduct(p: ProductOption) {
    setPriceWarning(null)

    // Article déjà saisi : sa quantité augmente et il remonte en tête plutôt
    // que d'apparaître deux fois sous le même libellé. C'est bien le dernier
    // article sur lequel le vendeur a agi.
    if (lines.some((l) => l.product_id === p.id)) {
      const existante = lines.find((l) => l.product_id === p.id)!
      setLines((prev) => [
        { ...existante, quantity: existante.quantity + 1 },
        ...prev.filter((l) => l.product_id !== p.id),
      ])
      return
    }

    try {
      // Prix résolu côté serveur : type de prix du client, puis paliers.
      const { data: r } = await api.get<{ data: { unit_price: number; price_type_code: string; floor_price: number | null } }>(
        '/sales/price',
        { params: { product_id: p.id, quantity: 1, customer_id: customerId || undefined } },
      )
      // En tete de liste : l'article vient d'etre choisi, c'est sur lui que
      // le vendeur va agir. En bas, il faudrait defiler pour le retrouver des
      // que la vente depasse un ecran.
      setLines((prev) => [{
        product_id: p.id,
        sku: p.sku,
        name: p.name,
        quantity: 1,
        unit_price: r.data.unit_price,
        price_type_code: r.data.price_type_code,
        floor_price: r.data.floor_price,
        current_stock: p.current_stock ?? 0,
        manual: false,
      }, ...prev])
    } catch (error) {
      // L'article est ajouté quand même, le prix se saisit manuellement.
      // En tete de liste : l'article vient d'etre choisi, c'est sur lui que
      // le vendeur va agir. En bas, il faudrait defiler pour le retrouver des
      // que la vente depasse un ecran.
      setLines((prev) => [{
        product_id: p.id,
        sku: p.sku,
        name: p.name,
        quantity: 1,
        unit_price: 0,
        price_type_code: null,
        floor_price: null,
        current_stock: p.current_stock ?? 0,
        manual: true,
      }, ...prev])
      const status =
        error && typeof error === 'object' && 'response' in error
          ? (error as { response?: { status?: number } }).response?.status
          : undefined
      // 422 = vraiment aucun tarif ; sinon (réseau, serveur) message honnête.
      setPriceWarning(
        status === 422
          ? `${p.sku} : aucun tarif défini — saisissez le prix manuellement.`
          : `${p.sku} : tarif non récupéré (serveur injoignable ?) — retirez la ligne et réessayez, ou saisissez le prix manuellement.`,
      )
    }
    setProductSearch('')
  }

  // Un timer de recalcul par article : la requête part 300 ms après la
  // dernière frappe, pas à chaque caractère.
  const priceTimers = useRef<Record<number, ReturnType<typeof setTimeout>>>({})

  /**
   * Quantité modifiée : le prix se recalcule selon les paliers du client
   * (détail / demi-gros / gros par ligne), sauf si la ligne est en saisie
   * manuelle (prix déverrouillé).
   */
  function changeQuantity(index: number, quantity: number) {
    const line = lines[index]
    if (!line) return
    const qty = Math.max(1, quantity)
    setLines((prev) => prev.map((x, j) => (j === index ? { ...x, quantity: qty } : x)))

    if (line.manual) return

    clearTimeout(priceTimers.current[line.product_id])
    priceTimers.current[line.product_id] = setTimeout(() => {
      void api
        .get<{ data: { unit_price: number; price_type_code: string; floor_price: number | null } }>('/sales/price', {
          params: { product_id: line.product_id, quantity: qty, customer_id: customerId || undefined },
        })
        .then(({ data: r }) => {
          setLines((prev) =>
            prev.map((x) =>
              x.product_id === line.product_id && !x.manual
                ? { ...x, unit_price: r.data.unit_price, price_type_code: r.data.price_type_code, floor_price: r.data.floor_price }
                : x,
            ),
          )
        })
        .catch(() => {
          // Palier non recalculé (erreur réseau) : on garde le prix courant.
        })
    }, 300)
  }

  // Une facture sort du stock : ce qui depasse ne pourra pas etre livre.
  // Un devis reste libre, il peut porter sur ce qu'on commandera ensuite.
  const lignesEnRupture = type === 'invoice'
    ? lines.filter((l) => l.quantity > l.current_stock)
    : []

  const create = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { id: number } }>('/sales', {
        type,
        customer_id: walkIn ? null : customerId,
        warehouse_id: warehouseId,
        discount_percent: discount,
        // Prix envoyé seulement en saisie manuelle : sinon le serveur applique
        // lui-même le palier (détail / demi-gros / gros) selon la quantité de
        // chaque ligne — l'affichage n'est qu'indicatif.
        lines: lines.map((l) => ({
          product_id: l.product_id,
          quantity: l.quantity,
          ...(l.manual ? { unit_price: l.unit_price } : {}),
        })),
      })
      return r.data
    },
    onSuccess: (result) => {
      qc.invalidateQueries({ queryKey: KEY })
      onClose()
      onCreated(result.id)
    },
  })

  const subtotal = lines.reduce((sum, l) => sum + l.quantity * l.unit_price, 0)
  const total = subtotal * (1 - discount / 100)
  const selectedCustomer = customers.find((c) => c.id === customerId)

  return (
    <Card>
      <CardHeader
        title={type === 'quote' ? 'Nouveau devis' : 'Nouvelle vente directe'}
        hint={`Total : ${formatNumber(total)} DH`}
      />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Client" htmlFor="sale-customer">
            <div className="space-y-1">
              <label className="flex items-center gap-2 pb-1 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={walkIn}
                  onChange={(e) => {
                    setWalkIn(e.target.checked)
                    if (e.target.checked) { setCustomerId(0); setCustomerSearch('') }
                  }}
                />
                Client de passage (comptoir, sans crédit)
              </label>
              <Input
                id="sale-customer"
                placeholder="Rechercher…"
                value={customerSearch}
                disabled={walkIn}
                onChange={(e) => { setCustomerSearch(e.target.value); setCustomerId(0) }}
              />
              {!walkIn && customerId === 0 && customers.length > 0 ? (
                <ul className="max-h-32 overflow-auto rounded border border-line">
                  {customers.map((c) => (
                    <li key={c.id}>
                      <button
                        type="button"
                        onClick={() => { setCustomerId(c.id); setCustomerSearch(`${c.code} · ${c.name}`) }}
                        className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-bg"
                      >
                        <span className="mono text-muted">{c.code}</span>
                        <span className="text-ink">{c.name}</span>
                        {c.is_blocked ? <Badge tone="bad">Bloqué</Badge> : null}
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </Field>
          <Field label="Lieu" htmlFor="sale-warehouse">
            <Select id="sale-warehouse" value={warehouseId || ''} onChange={(e) => setWarehouseId(Number(e.target.value))}>
              <option value="" disabled>Choisir…</option>
              {warehouses.map((w) => <option key={w.id} value={w.id}>{w.code} · {w.name}</option>)}
            </Select>
          </Field>
          <Field label="Remise globale (%)" htmlFor="sale-discount">
            <Input id="sale-discount" type="number" min={0} max={100} value={discount} onChange={(e) => setDiscount(Number(e.target.value))} />
          </Field>
        </div>

        {selectedCustomer?.is_blocked ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Ce client est bloqué (plafond de crédit dépassé) — la confirmation d'une facture sera refusée.
          </p>
        ) : null}

        <div className="space-y-2 rounded-lg border border-line p-3">
          <Input
            placeholder={customerId === 0 && !walkIn ? 'Sélectionnez d\'abord un client…' : 'Rechercher un article…'}
            value={productSearch}
            onChange={(e) => setProductSearch(e.target.value)}
            disabled={customerId === 0 && !walkIn}
          />
          {products.length > 0 && productSearch.trim().length >= 2 ? (
            <ul className="max-h-40 overflow-auto rounded border border-line">
              {products.filter((o) => !lines.some((l) => l.product_id === o.id)).map((o) => (
                <li key={o.id}>
                  <button
                    type="button"
                    onClick={() => void addProduct(o)}
                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-bg"
                  >
                    <span className="mono text-muted">{o.sku}</span>
                    <span className="flex-1 truncate text-ink">{o.name}</span>
                    {/* Le stock du lieu se lit avant de choisir : inutile
                        d'ajouter une ligne pour decouvrir qu'il est vide. */}
                    <span
                      className={`tabular shrink-0 text-xs ${
                        (o.current_stock ?? 0) <= 0 ? 'font-medium text-bad' : 'text-muted'
                      }`}
                    >
                      {(o.current_stock ?? 0) <= 0 ? 'rupture' : `${formatNumber(o.current_stock ?? 0)} en stock`}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          ) : null}

          {priceWarning ? (
            <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">{priceWarning}</p>
          ) : null}

          {lines.length > 0 ? (
            <div className="grid grid-cols-[6rem_1fr_5.5rem_5.5rem_4.5rem_8rem_6rem_2rem_2rem] items-center gap-2 px-1 text-xs font-medium text-muted">
              <span>Référence</span>
              <span>Article</span>
              <span className="text-right">Stock dispo</span>
              <span className="text-right">Quantité</span>
              <span>Niveau</span>
              <span className="text-right">Prix unitaire (DH)</span>
              <span className="text-right">Total ligne</span>
              <span />
              <span />
            </div>
          ) : null}

          {lines.map((l, i) => {
            const overStock = type === 'invoice' && l.quantity > l.current_stock
            return (
              <div
                key={l.product_id}
                className="grid grid-cols-[6rem_1fr_5.5rem_5.5rem_4.5rem_8rem_6rem_2rem_2rem] items-center gap-2 rounded border border-line bg-card p-2"
              >
                <span className="mono text-sm text-muted">{l.sku}</span>
                <span className="truncate text-sm text-ink">{l.name}</span>
                <span className={`tabular text-right text-sm ${overStock ? 'font-medium text-bad' : 'text-muted'}`}>
                  {formatNumber(l.current_stock)}
                </span>
                <Input
                  type="number"
                  min={1}
                  value={l.quantity}
                  onChange={(e) => changeQuantity(i, Number(e.target.value))}
                  className={`w-full text-right ${overStock ? 'border-bad' : ''}`}
                  aria-label="Quantité"
                  title={overStock ? `Stock insuffisant (${l.current_stock} disponibles)` : undefined}
                />
                <span>{l.price_type_code ? <Badge tone="sky">{l.price_type_code}</Badge> : <Badge tone="warn">manuel</Badge>}</span>
                <Input
                  type="number"
                  min={0}
                  step="0.01"
                  value={l.unit_price}
                  disabled={!l.manual}
                  onChange={(e) => setLines((p) => p.map((x, j) => (j === i ? { ...x, unit_price: Number(e.target.value) } : x)))}
                  className={`w-full text-right ${l.floor_price !== null && l.unit_price < l.floor_price ? 'border-bad' : ''} ${!l.manual ? 'opacity-70' : ''}`}
                  aria-label="Prix unitaire"
                  title={
                    l.floor_price !== null && l.unit_price < l.floor_price
                      ? `Sous le plancher (${formatNumber(l.floor_price)} DH)`
                      : l.manual
                        ? 'Prix manuel'
                        : 'Prix appliqué automatiquement — déverrouillez pour modifier'
                  }
                />
                <span className="tabular text-right text-sm font-medium text-ink">{formatNumber(l.quantity * l.unit_price)}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  className={l.manual ? 'text-warn' : 'text-muted'}
                  onClick={() => {
                    const wasManual = l.manual
                    setLines((p) => p.map((x, j) => (j === i ? { ...x, manual: !x.manual } : x)))
                    // Re-verrouillage : on reprend le prix automatique du palier.
                    if (wasManual) changeQuantity(i, l.quantity)
                  }}
                  title={l.manual ? 'Reverrouiller (reprendre le prix automatique)' : 'Modifier le prix manuellement'}
                  aria-label={l.manual ? 'Reverrouiller le prix' : 'Modifier le prix'}
                >
                  {l.manual ? <LockOpen className="h-4 w-4" /> : <Lock className="h-4 w-4" />}
                </Button>
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
            )
          })}
          {lines.length === 0 ? <p className="text-sm text-muted">Aucun article — le prix s'applique automatiquement selon le client et la quantité (paliers détail / demi-gros / gros).</p> : null}
        </div>

        {create.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            {errorMessage(create.error, 'Création impossible.')}
          </p>
        ) : null}

        {lignesEnRupture.length > 0 ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Stock insuffisant dans ce lieu :{' '}
            {lignesEnRupture
              .map((l) => `${l.name} (demandé ${formatNumber(l.quantity)}, disponible ${formatNumber(l.current_stock)})`)
              .join(' · ')}
          </p>
        ) : null}

        <div className="flex gap-2">
          <Button
            onClick={() => create.mutate()}
            disabled={
              create.isPending ||
              (!customerId && !walkIn) ||
              !warehouseId ||
              lines.length === 0 ||
              lignesEnRupture.length > 0
            }
            title={
              lignesEnRupture.length > 0
                ? `Stock insuffisant : ${lignesEnRupture.map((l) => l.name).join(', ')}`
                : undefined
            }
          >
            {create.isPending ? 'Création…' : 'Créer le document'}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}

export function SaleDetailView({ id, onBack }: { id: number; onBack: () => void }) {
  const can = usePermission()
  const qc = useQueryClient()
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [cancelOpen, setCancelOpen] = useState(false)

  // Modification d'un brouillon : lignes tenues en local jusqu'à
  // l'enregistrement, pour pouvoir abandonner sans rien avoir touché.
  const [editing, setEditing] = useState(false)
  const [editLines, setEditLines] = useState<EditLine[]>([])
  const [editDiscount, setEditDiscount] = useState(0)
  const [editSearch, setEditSearch] = useState('')
  const debouncedEditSearch = useDebouncedValue(editSearch, 250)

  // Règlement saisi au moment de la confirmation d'une facture.
  const [payMode, setPayMode] = useState<'unpaid' | 'paid'>('paid')
  const [payAmount, setPayAmount] = useState('')
  const [payMethodId, setPayMethodId] = useState(0)

  const { data: sale } = useQuery<SaleDetail>({
    queryKey: [...KEY, 'detail', id],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: SaleDetail }>(`/sales/${id}`)
      return r.data
    },
  })

  const canRecordPayment = can('payment.create')
  const { data: payMethods = [] } = useQuery<{ id: number; name: string }[]>({
    queryKey: ['payment-method-options'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: { id: number; name: string }[] }>('/payment-methods')
      return r.data
    },
    enabled: confirmOpen && canRecordPayment,
    staleTime: 5 * 60_000,
  })

  function openConfirm() {
    setPayMode('paid')
    setPayAmount(sale ? String(sale.total) : '')
    setPayMethodId(0)
    setConfirmOpen(true)
  }

  const confirm = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/sales/${id}/confirm`)

      // Règlement enregistré dans la foulée : le reste part au crédit client.
      const amount = Number(payAmount)
      if (
        sale?.type === 'invoice' &&
        canRecordPayment &&
        payMode === 'paid' &&
        Number.isFinite(amount) &&
        amount > 0 &&
        sale.customer
      ) {
        await api.post('/payments', {
          customer_id: sale.customer.id,
          amount: Math.min(amount, sale.total),
          payment_method_id: payMethodId || null,
          sale_id: id,
          received_at: new Date().toISOString().slice(0, 10),
          note: `Règlement à la validation de ${sale.reference}`,
        })
      }
    },
    onSuccess: () => {
      setConfirmOpen(false)
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['stock-exits'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
      qc.invalidateQueries({ queryKey: ['payments'] })
      // Bon de livraison généré automatiquement à la validation d'une facture.
      if (sale?.type === 'invoice') {
        void downloadFile(`/sales/${id}/exit-pdf`, `BL-${sale.reference}.pdf`)
      }
    },
  })

  const { data: editProducts = [] } = useQuery<ProductOption[]>({
    queryKey: ['sale-edit-product-search', debouncedEditSearch],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', {
        params: { search: debouncedEditSearch, per_page: 20 },
      })
      return r.data
    },
    enabled: editing && debouncedEditSearch.trim().length >= 2,
    placeholderData: keepPreviousData,
    staleTime: 30_000,
  })

  function ouvrirModification() {
    if (!sale) return
    setEditLines(sale.lines.map((l) => ({
      product_id: l.product_id,
      sku: l.sku,
      name: l.name,
      quantity: l.quantity,
      unit_price: l.unit_price,
    })))
    setEditDiscount(sale.discount_percent)
    setEditSearch('')
    setEditing(true)
  }

  async function ajouterLigne(p: ProductOption) {
    setEditSearch('')
    // Un article déjà présent voit sa quantité augmenter : créer une seconde
    // ligne pour le même article donnerait deux fois le même libellé. Il
    // remonte en tête, comme un article nouvellement choisi — c'est bien le
    // dernier sur lequel le vendeur a agi.
    if (editLines.some((l) => l.product_id === p.id)) {
      setEditLines((prev) => {
        const ligne = prev.find((l) => l.product_id === p.id)!
        return [
          { ...ligne, quantity: ligne.quantity + 1 },
          ...prev.filter((l) => l.product_id !== p.id),
        ]
      })
      return
    }

    // Prix résolu par le serveur, comme à la création : tarif du client puis
    // paliers de quantité.
    let unitPrice = 0
    try {
      const { data: r } = await api.get<{ data: { unit_price: number } }>('/sales/price', {
        params: { product_id: p.id, quantity: 1, customer_id: sale?.customer?.id ?? undefined },
      })
      unitPrice = r.data.unit_price
    } catch {
      // Article sans tarif : le vendeur saisira le prix à la main.
    }

    setEditLines((prev) => [...prev, {
      product_id: p.id,
      sku: p.sku,
      name: p.name,
      quantity: 1,
      unit_price: unitPrice,
    }])
  }

  const editSubtotal = editLines.reduce((somme, l) => somme + l.quantity * l.unit_price, 0)
  const editTotal = editSubtotal * (1 - editDiscount / 100)

  const save = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.put(`/sales/${id}`, {
        discount_percent: editDiscount,
        lines: editLines.map((l) => ({
          product_id: l.product_id,
          quantity: l.quantity,
          unit_price: l.unit_price,
        })),
      })
    },
    onSuccess: () => {
      setEditing(false)
      qc.invalidateQueries({ queryKey: KEY })
    },
  })

  const cancel = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/sales/${id}/cancel`)
    },
    onSuccess: () => {
      setCancelOpen(false)
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['customers'] })
    },
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="h-4 w-4" /></Button>
          <div>
            <h1 className="text-xl font-semibold text-ink">
              {sale?.type === 'quote' ? 'Devis' : 'Facture'} {sale?.reference}
            </h1>
            <p className="text-sm text-muted">
              {sale?.customer?.name ?? 'Client de passage'} · {sale?.warehouse}{' '}
              {sale?.status === 'confirmed' ? <Badge tone="ok">Confirmé</Badge> : null}
              {sale?.status === 'draft' ? <Badge tone="warn">Brouillon</Badge> : null}
              {sale?.status === 'cancelled' ? <Badge tone="bad">Annulé</Badge> : null}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          {sale ? (
            <>
              <Button
                variant="outline"
                size="sm"
                onClick={() => downloadFile(`/sales/${sale.id}/pdf`, `${sale.reference}.pdf`)}
                title={sale.type === 'quote' ? 'Devis PDF' : 'Facture PDF'}
              >
                <Download className="h-4 w-4" />
                {sale.type === 'quote' ? 'Devis PDF' : 'Facture PDF'}
              </Button>
              {sale.type === 'invoice' && sale.status === 'confirmed' ? (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => downloadFile(`/sales/${sale.id}/exit-pdf`, `BL-${sale.reference}.pdf`)}
                  title="Bon de livraison PDF"
                >
                  <FileText className="h-4 w-4" />
                  Bon de livraison
                </Button>
              ) : null}
            </>
          ) : null}
          {sale?.status === 'draft' && can('sale.create') && !editing ? (
            <Button variant="outline" onClick={ouvrirModification}>
              <Pencil className="h-4 w-4" />
              Modifier
            </Button>
          ) : null}
          {sale?.status === 'draft' && !editing ? (
            <Button onClick={openConfirm} disabled={confirm.isPending}>
              Confirmer {sale.type === 'invoice' ? '(sortie de stock)' : ''}
            </Button>
          ) : null}
          {sale && sale.status !== 'cancelled' && can('sale.cancel') ? (
            <Button variant="ghost" onClick={() => setCancelOpen(true)} disabled={cancel.isPending}>
              Annuler
            </Button>
          ) : null}
        </div>
      </div>

      {confirm.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(confirm.error, 'Confirmation impossible.')}
        </p>
      ) : null}
      {cancel.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          {errorMessage(cancel.error, 'Annulation impossible.')}
        </p>
      ) : null}

      {sale?.customer ? (
        <div className="grid gap-3 sm:grid-cols-3">
          <Card><CardBody><p className="text-xs uppercase text-faint">Encours client</p><p className="tabular text-lg font-semibold text-ink">{formatNumber(sale.customer.balance)} DH</p></CardBody></Card>
          <Card><CardBody><p className="text-xs uppercase text-faint">Plafond</p><p className="tabular text-lg font-semibold text-ink">{sale.customer.credit_limit > 0 ? `${formatNumber(sale.customer.credit_limit)} DH` : 'Illimité'}</p></CardBody></Card>
          <Card><CardBody><p className="text-xs uppercase text-faint">Règlement</p><p className="text-lg font-semibold text-ink">{formatNumber(sale.paid_amount)} / {formatNumber(sale.total)} DH</p></CardBody></Card>
        </div>
      ) : null}

      {editing ? (
        <Card>
          <CardHeader
            title={`Modifier ${sale?.reference ?? ''}`}
            hint={`Sous-total ${formatNumber(editSubtotal)} DH · total ${formatNumber(editTotal)} DH`}
            action={
              <div className="flex items-center gap-2">
                <label className="text-sm text-muted" htmlFor="edit-discount">Remise %</label>
                <Input
                  id="edit-discount"
                  type="number"
                  min={0}
                  max={100}
                  value={editDiscount}
                  onChange={(e) => setEditDiscount(Number(e.target.value))}
                  className="w-20"
                />
                <Button variant="ghost" onClick={() => setEditing(false)} disabled={save.isPending}>
                  Abandonner
                </Button>
                <Button
                  onClick={() => save.mutate()}
                  disabled={save.isPending || editLines.length === 0}
                >
                  {save.isPending ? 'Enregistrement…' : 'Enregistrer'}
                </Button>
              </div>
            }
          />
          <CardBody className="space-y-4">
            {save.isError ? (
              <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(save.error, 'Enregistrement impossible.')}
              </p>
            ) : null}

            <Input
              placeholder="Ajouter un article — réf ou nom…"
              value={editSearch}
              onChange={(e) => setEditSearch(e.target.value)}
            />

            {debouncedEditSearch.trim().length >= 2 && editProducts.length > 0 ? (
              <div className="max-h-52 overflow-y-auto rounded border border-line">
                {editProducts.map((p) => (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => void ajouterLigne(p)}
                    className="flex w-full items-center justify-between border-b border-line px-3 py-2 text-left text-sm last:border-0 hover:bg-bg"
                  >
                    <span>{p.name}</span>
                    <span className="mono text-xs text-faint">{p.sku}</span>
                  </button>
                ))}
              </div>
            ) : null}

            {editLines.length === 0 ? (
              <p className="text-sm text-muted">
                Aucune ligne. Un document doit garder au moins un article — ajoutez-en un ou abandonnez.
              </p>
            ) : (
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-3 py-2 font-medium">Article</th>
                    <th className="px-3 py-2 text-right font-medium">Qté</th>
                    <th className="px-3 py-2 text-right font-medium">PU (DH)</th>
                    <th className="px-3 py-2 text-right font-medium">Total (DH)</th>
                    <th className="px-3 py-2" />
                  </tr>
                </thead>
                <tbody>
                  {editLines.map((l, i) => (
                    <tr key={l.product_id} className="border-b border-line last:border-0">
                      <td className="px-3 py-2 text-ink">
                        <span className="mono text-xs text-faint">{l.sku}</span> {l.name}
                      </td>
                      <td className="px-3 py-2 text-right">
                        <Input
                          type="number"
                          min={1}
                          value={l.quantity}
                          onChange={(e) =>
                            setEditLines((prev) =>
                              prev.map((x, j) => (j === i ? { ...x, quantity: Math.max(1, Number(e.target.value)) } : x)),
                            )
                          }
                          className="w-24"
                        />
                      </td>
                      <td className="px-3 py-2 text-right">
                        <Input
                          type="number"
                          min={0}
                          step="0.01"
                          value={l.unit_price}
                          onChange={(e) =>
                            setEditLines((prev) =>
                              prev.map((x, j) => (j === i ? { ...x, unit_price: Number(e.target.value) } : x)),
                            )
                          }
                          className="w-28"
                        />
                      </td>
                      <td className="tabular px-3 py-2 text-right font-medium text-ink">
                        {formatNumber(l.quantity * l.unit_price)}
                      </td>
                      <td className="px-3 py-2 text-right">
                        <Button
                          variant="ghost"
                          size="sm"
                          className="text-bad hover:bg-bad-bg"
                          aria-label={`Retirer ${l.name ?? ''}`}
                          onClick={() => setEditLines((prev) => prev.filter((_, j) => j !== i))}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </CardBody>
        </Card>
      ) : (
        <Card>
          <CardHeader
            title="Lignes"
            hint={`Sous-total ${formatNumber(sale?.subtotal ?? 0)} DH · remise ${sale?.discount_percent ?? 0}% · total ${formatNumber(sale?.total ?? 0)} DH`}
          />
          <CardBody className="p-0">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Article</th>
                  <th className="px-5 py-3 font-medium">Niveau</th>
                  <th className="px-5 py-3 text-right font-medium">Qté</th>
                  <th className="px-5 py-3 text-right font-medium">PU (DH)</th>
                  <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
                </tr>
              </thead>
              <tbody>
                {(sale?.lines ?? []).map((l, i) => (
                  <tr key={i} className="border-b border-line last:border-0">
                    <td className="mono px-5 py-3 text-muted">{l.sku}</td>
                    <td className="px-5 py-3 text-ink">{l.name}</td>
                    <td className="px-5 py-3">{l.price_type_code ? <Badge tone="sky">{l.price_type_code}</Badge> : '—'}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{l.quantity}</td>
                    <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(l.unit_price)}</td>
                    <td className="tabular px-5 py-3 text-right font-medium text-ink">{formatNumber(l.line_total)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardBody>
        </Card>
      )}

      {/* Confirmation d'une facture : sortie de stock + règlement (total, partiel ou crédit). */}
      {confirmOpen && sale?.type === 'invoice' ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
          role="dialog"
          aria-modal="true"
          onMouseDown={() => setConfirmOpen(false)}
        >
          <div
            className="w-full max-w-lg rounded-lg border border-line bg-card p-5 shadow-lg"
            onMouseDown={(e) => e.stopPropagation()}
          >
            <h2 className="text-base font-semibold text-ink">Confirmer {sale.reference}</h2>
            <p className="mt-1 text-sm text-muted">
              Le stock sera sorti automatiquement. Total : <strong className="text-ink">{formatNumber(sale.total)} DH</strong>
            </p>

            {confirm.isError ? (
              <p className="mt-3 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(confirm.error, 'Confirmation impossible.')}
              </p>
            ) : null}

            {!sale.customer ? (
              <p className="mt-4 rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
                Client de passage : la vente sera marquée <strong>payée comptant intégralement</strong> — aucun crédit possible.
              </p>
            ) : null}

            {canRecordPayment && sale.customer ? (
              <div className="mt-4 space-y-4">
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => { setPayMode('paid'); setPayAmount(String(sale.total)) }}
                    className={`flex-1 rounded border px-3 py-2 text-sm font-medium transition-colors ${
                      payMode === 'paid' ? 'border-sky bg-bg text-sky' : 'border-line text-muted hover:text-ink'
                    }`}
                  >
                    💵 Payé (total ou partiel)
                  </button>
                  <button
                    type="button"
                    onClick={() => setPayMode('unpaid')}
                    className={`flex-1 rounded border px-3 py-2 text-sm font-medium transition-colors ${
                      payMode === 'unpaid' ? 'border-warn bg-warn-bg text-warn' : 'border-line text-muted hover:text-ink'
                    }`}
                  >
                    🕐 Non payé (tout au crédit)
                  </button>
                </div>

                {payMode === 'paid' ? (
                  <div className="grid gap-3 sm:grid-cols-2">
                    <Field label="Montant payé (DH)" htmlFor="confirm-pay-amount">
                      <div className="flex gap-2">
                        <Input
                          id="confirm-pay-amount"
                          type="number"
                          min={0}
                          step={0.01}
                          value={payAmount}
                          onChange={(e) => setPayAmount(e.target.value)}
                          className="text-right"
                        />
                        <Button variant="outline" onClick={() => setPayAmount(String(sale.total))} title="Tout payer">
                          Tout
                        </Button>
                      </div>
                    </Field>
                    <Field label="Méthode de paiement" htmlFor="confirm-pay-method">
                      <Select
                        id="confirm-pay-method"
                        value={payMethodId}
                        onChange={(e) => setPayMethodId(Number(e.target.value))}
                      >
                        <option value={0}>— Choisir —</option>
                        {payMethods.map((m) => (
                          <option key={m.id} value={m.id}>{m.name}</option>
                        ))}
                      </Select>
                    </Field>
                    <p className="text-sm sm:col-span-2">
                      {(() => {
                        const amount = Number(payAmount)
                        const rest = sale.total - (Number.isFinite(amount) ? Math.min(amount, sale.total) : 0)
                        return rest > 0.005 ? (
                          <span className="font-medium text-warn">
                            Reste {formatNumber(rest)} DH → ajouté au crédit du client
                          </span>
                        ) : (
                          <span className="font-medium text-ok">Facture entièrement réglée — rien au crédit.</span>
                        )
                      })()}
                    </p>
                  </div>
                ) : (
                  <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
                    La totalité ({formatNumber(sale.total)} DH) sera ajoutée au <strong>crédit du client</strong>
                    {sale.customer ? <> (encours actuel : {formatNumber(sale.customer.balance)} DH)</> : null}.
                  </p>
                )}
              </div>
            ) : null}

            <div className="mt-5 flex justify-end gap-2">
              <Button variant="ghost" onClick={() => setConfirmOpen(false)}>Annuler</Button>
              <Button onClick={() => confirm.mutate()} disabled={confirm.isPending}>
                {confirm.isPending ? 'Confirmation…' : 'Confirmer la vente'}
              </Button>
            </div>
          </div>
        </div>
      ) : null}

      {/* Confirmation d'un devis : simple figement. */}
      <ConfirmDialog
        open={confirmOpen && sale?.type !== 'invoice'}
        title="Confirmer le devis"
        message={
          <>
            Confirmer <strong>{sale?.reference}</strong> ? Le devis sera figé.
          </>
        }
        confirmLabel="Confirmer"
        danger={false}
        isPending={confirm.isPending}
        error={confirm.isError ? errorMessage(confirm.error, 'Confirmation impossible.') : undefined}
        onConfirm={() => confirm.mutate()}
        onCancel={() => setConfirmOpen(false)}
      />

      <ConfirmDialog
        open={cancelOpen}
        title="Annuler la vente"
        message={
          <>
            Annuler <strong>{sale?.reference}</strong> ?
            {sale?.status === 'confirmed' && sale.type === 'invoice' ? (
              <> La marchandise <strong>reviendra en stock</strong> (mouvement retour) et la créance sera contre-passée.</>
            ) : null}
          </>
        }
        confirmLabel="Annuler la vente"
        danger
        isPending={cancel.isPending}
        error={cancel.isError ? errorMessage(cancel.error, 'Annulation impossible.') : undefined}
        onConfirm={() => cancel.mutate()}
        onCancel={() => setCancelOpen(false)}
      />
    </div>
  )
}
