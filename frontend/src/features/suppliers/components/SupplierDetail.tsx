import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatNumber } from '@/lib/utils'

interface ContactRow {
  id: number
  name: string
  role: string | null
  phone: string | null
  email: string | null
}

interface LinkedProduct {
  product_id: number
  sku: string
  name: string
  supplier_reference: string | null
  last_price: number | null
  lead_time_days: number | null
}

interface Stats {
  products_count: number
  contacts_count: number
  average_lead_time_days: number | null
}

interface ProductOption {
  id: number
  sku: string
  name: string
}

type Tab = 'contacts' | 'products'

/**
 * Détail fournisseur : statistiques, contacts multiples et articles
 * référencés (référence fournisseur, dernier prix, délai).
 */
export function SupplierDetail({ supplierId, name, onClose }: { supplierId: number; name: string; onClose: () => void }) {
  const [tab, setTab] = useState<Tab>('contacts')

  const { data: stats } = useQuery<Stats>({
    queryKey: ['supplier-stats', supplierId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: Stats }>(`/suppliers/${supplierId}/stats`)
      return r.data
    },
  })

  return (
    <Card>
      <CardHeader
        title={`Fournisseur — ${name}`}
        hint={
          stats
            ? `${stats.products_count} article(s) · ${stats.contacts_count} contact(s)` +
              (stats.average_lead_time_days !== null ? ` · délai moyen ${stats.average_lead_time_days} j` : '')
            : undefined
        }
        action={
          <Button variant="ghost" size="sm" onClick={onClose} aria-label="Fermer le détail">
            <X className="h-4 w-4" />
          </Button>
        }
      />
      <CardBody className="space-y-4">
        <div className="flex gap-1 overflow-x-auto border-b border-line">
          {(
            [
              { key: 'contacts', label: 'Contacts' },
              { key: 'products', label: 'Articles référencés' },
            ] as { key: Tab; label: string }[]
          ).map((t) => (
            <button
              key={t.key}
              type="button"
              onClick={() => setTab(t.key)}
              className={cn(
                'px-4 py-2 text-sm font-medium transition-colors',
                tab === t.key ? 'border-b-2 border-sky text-ink' : 'text-muted hover:text-ink',
              )}
            >
              {t.label}
            </button>
          ))}
        </div>

        {tab === 'contacts' ? <ContactsTab supplierId={supplierId} /> : null}
        {tab === 'products' ? <ProductsTab supplierId={supplierId} /> : null}
      </CardBody>
    </Card>
  )
}

const EMPTY_CONTACT = { name: '', role: '', phone: '', email: '' }

function ContactsTab({ supplierId }: { supplierId: number }) {
  const can = usePermission()
  const canManage = can('supplier.update')
  const qc = useQueryClient()
  const KEY = ['supplier-contacts', supplierId]

  const { data: contacts = [] } = useQuery<ContactRow[]>({
    queryKey: KEY,
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ContactRow[] }>(`/suppliers/${supplierId}/contacts`)
      return r.data
    },
  })

  const [form, setForm] = useState(EMPTY_CONTACT)

  const add = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.post(`/suppliers/${supplierId}/contacts`, {
        name: form.name,
        role: form.role || null,
        phone: form.phone || null,
        email: form.email || null,
      })
    },
    onSuccess: () => {
      setForm(EMPTY_CONTACT)
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['supplier-stats', supplierId] })
    },
  })

  const remove = useMutation({
    mutationFn: async (contactId: number) => {
      await ensureCsrfCookie()
      await api.delete(`/suppliers/${supplierId}/contacts/${contactId}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['supplier-stats', supplierId] })
    },
  })

  return (
    <div className="space-y-4">
      {canManage ? (
        <div className="flex flex-wrap items-end gap-2">
          <Input placeholder="Nom *" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="w-44" />
          <Input placeholder="Fonction" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="w-40" />
          <Input placeholder="Téléphone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="w-36" />
          <Input placeholder="E-mail" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="w-52" />
          <Button size="sm" onClick={() => add.mutate()} disabled={add.isPending || form.name.trim() === ''}>
            <Plus className="h-4 w-4" />
            Ajouter
          </Button>
        </div>
      ) : null}
      {add.isError ? <p className="text-sm text-bad">Ajout impossible — vérifiez les champs.</p> : null}

      {contacts.length === 0 ? (
        <p className="text-sm text-muted">Aucun contact enregistré.</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-line text-left text-muted">
              <th className="px-3 py-2 font-medium">Nom</th>
              <th className="px-3 py-2 font-medium">Fonction</th>
              <th className="px-3 py-2 font-medium">Téléphone</th>
              <th className="px-3 py-2 font-medium">E-mail</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {contacts.map((c) => (
              <tr key={c.id} className="border-b border-line last:border-0">
                <td className="px-3 py-2 text-ink">{c.name}</td>
                <td className="px-3 py-2 text-muted">{c.role ?? '—'}</td>
                <td className="mono px-3 py-2 text-muted">{c.phone ?? '—'}</td>
                <td className="px-3 py-2 text-muted">{c.email ?? '—'}</td>
                <td className="px-3 py-2 text-right">
                  {canManage ? (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-bad hover:bg-bad-bg"
                      onClick={() => remove.mutate(c.id)}
                      aria-label={`Supprimer ${c.name}`}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  ) : null}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

function ProductsTab({ supplierId }: { supplierId: number }) {
  const can = usePermission()
  const canManage = can('supplier.update')
  const qc = useQueryClient()
  const KEY = ['supplier-products', supplierId]

  const { data: linked = [] } = useQuery<LinkedProduct[]>({
    queryKey: KEY,
    queryFn: async () => {
      const { data: r } = await api.get<{ data: LinkedProduct[] }>(`/suppliers/${supplierId}/products`)
      return r.data
    },
  })

  const [search, setSearch] = useState('')
  const [picked, setPicked] = useState<ProductOption | null>(null)
  const [reference, setReference] = useState('')
  const [price, setPrice] = useState('')
  const [leadTime, setLeadTime] = useState('')

  const { data: options = [] } = useQuery<ProductOption[]>({
    queryKey: ['supplier-product-search', search],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ProductOption[] }>('/products', {
        params: { search, per_page: 20 },
      })
      return r.data
    },
    enabled: search.trim().length >= 2,
  })

  const attach = useMutation({
    mutationFn: async () => {
      if (picked == null) return
      await ensureCsrfCookie()
      await api.put(`/suppliers/${supplierId}/products/${picked.id}`, {
        supplier_reference: reference || null,
        last_price: price !== '' ? Number(price) : null,
        lead_time_days: leadTime !== '' ? Number(leadTime) : null,
      })
    },
    onSuccess: () => {
      setPicked(null)
      setSearch('')
      setReference('')
      setPrice('')
      setLeadTime('')
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['supplier-stats', supplierId] })
    },
  })

  const detach = useMutation({
    mutationFn: async (productId: number) => {
      await ensureCsrfCookie()
      await api.delete(`/suppliers/${supplierId}/products/${productId}`)
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: KEY })
      qc.invalidateQueries({ queryKey: ['supplier-stats', supplierId] })
    },
  })

  return (
    <div className="space-y-4">
      {canManage ? (
        <div className="space-y-2 rounded-lg border border-line p-3">
          {picked == null ? (
            <>
              <Input
                placeholder="Rechercher un article à référencer (min. 2 caractères)…"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
              {options.length > 0 ? (
                <ul className="max-h-44 overflow-auto rounded border border-line">
                  {options.map((o) => (
                    <li key={o.id}>
                      <button
                        type="button"
                        onClick={() => setPicked(o)}
                        className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm hover:bg-surface-2"
                      >
                        <span className="mono text-muted">{o.sku}</span>
                        <span className="text-ink">{o.name}</span>
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </>
          ) : (
            <div className="flex flex-wrap items-end gap-2">
              <span className="rounded bg-sky-soft px-2 py-1.5 text-sm text-navy">
                {picked.sku} · {picked.name}
              </span>
              <Input placeholder="Réf. fournisseur" value={reference} onChange={(e) => setReference(e.target.value)} className="w-40" />
              <Input placeholder="Dernier prix (DH)" type="number" min="0" value={price} onChange={(e) => setPrice(e.target.value)} className="w-36" />
              <Input placeholder="Délai (jours)" type="number" min="0" value={leadTime} onChange={(e) => setLeadTime(e.target.value)} className="w-32" />
              <Button size="sm" onClick={() => attach.mutate()} disabled={attach.isPending}>
                <Plus className="h-4 w-4" />
                Référencer
              </Button>
              <Button variant="ghost" size="sm" onClick={() => setPicked(null)}>Annuler</Button>
            </div>
          )}
        </div>
      ) : null}

      {linked.length === 0 ? (
        <p className="text-sm text-muted">Aucun article référencé chez ce fournisseur.</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-line text-left text-muted">
              <th className="px-3 py-2 font-medium">Référence</th>
              <th className="px-3 py-2 font-medium">Article</th>
              <th className="px-3 py-2 font-medium">Réf. fournisseur</th>
              <th className="px-3 py-2 text-right font-medium">Dernier prix</th>
              <th className="px-3 py-2 text-right font-medium">Délai</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {linked.map((p) => (
              <tr key={p.product_id} className="border-b border-line last:border-0">
                <td className="mono px-3 py-2 text-muted">{p.sku}</td>
                <td className="px-3 py-2 text-ink">{p.name}</td>
                <td className="mono px-3 py-2 text-muted">{p.supplier_reference ?? '—'}</td>
                <td className="tabular px-3 py-2 text-right text-ink">
                  {p.last_price !== null ? `${formatNumber(p.last_price)} DH` : '—'}
                </td>
                <td className="tabular px-3 py-2 text-right text-muted">
                  {p.lead_time_days !== null ? `${p.lead_time_days} j` : '—'}
                </td>
                <td className="px-3 py-2 text-right">
                  {canManage ? (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-bad hover:bg-bad-bg"
                      onClick={() => detach.mutate(p.product_id)}
                      aria-label={`Retirer ${p.name}`}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  ) : null}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}
