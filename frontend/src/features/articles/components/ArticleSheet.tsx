import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Plus, Star, Trash2, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn } from '@/lib/utils'

interface AttributeRow {
  name: string
  value: string
}

interface ImageRow {
  id: number
  url: string
  is_main: boolean
}

interface SerialRow {
  id: number
  serial_number: string
  warehouse: string | null
  is_sold: boolean
}

type Tab = 'attributes' | 'images' | 'serials'

/**
 * Fiche article : fiche technique, médias et numéros de série.
 */
export function ArticleSheet({
  productId,
  sku,
  name,
  isSerialized,
  onClose,
}: {
  productId: number
  sku: string
  name: string
  isSerialized: boolean
  onClose: () => void
}) {
  const [tab, setTab] = useState<Tab>('attributes')

  return (
    <Card>
      <CardHeader
        title={`Fiche — ${sku} · ${name}`}
        action={
          <Button variant="ghost" size="sm" onClick={onClose} aria-label="Fermer la fiche">
            <X className="h-4 w-4" />
          </Button>
        }
      />
      <CardBody className="space-y-4">
        <div className="flex gap-1 border-b border-line">
          {(
            [
              { key: 'attributes', label: 'Fiche technique' },
              { key: 'images', label: 'Images' },
              ...(isSerialized ? [{ key: 'serials', label: 'N° de série' } as const] : []),
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

        {tab === 'attributes' ? <AttributesTab productId={productId} /> : null}
        {tab === 'images' ? <ImagesTab productId={productId} /> : null}
        {tab === 'serials' ? <SerialsTab productId={productId} /> : null}
      </CardBody>
    </Card>
  )
}

function AttributesTab({ productId }: { productId: number }) {
  const can = usePermission()
  const canManage = can('product.attributes_manage')
  const qc = useQueryClient()

  const { data } = useQuery<{ attributes: AttributeRow[]; template: string[] }>({
    queryKey: ['article-attributes', productId],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: { attributes: AttributeRow[]; template: string[] } }>(
        `/products/${productId}/attributes`,
      )
      return r.data
    },
  })

  const [rows, setRows] = useState<AttributeRow[]>([])

  useEffect(() => {
    if (data == null) return
    if (data.attributes.length > 0) {
      setRows(data.attributes)
    } else {
      // Pré-remplit avec le modèle de la catégorie (valeurs vides).
      setRows(data.template.map((n) => ({ name: n, value: '' })))
    }
  }, [data])

  const save = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      await api.put(`/products/${productId}/attributes`, {
        attributes: rows.filter((r) => r.name.trim() !== '' && r.value.trim() !== ''),
      })
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ['article-attributes', productId] }),
  })

  return (
    <div className="space-y-3">
      {rows.length === 0 ? <p className="text-sm text-muted">Aucun attribut. Ajoutez une caractéristique.</p> : null}
      {rows.map((row, i) => (
        <div key={i} className="flex gap-2">
          <Input
            value={row.name}
            placeholder="Caractéristique (ex. Processeur)"
            onChange={(e) => setRows((p) => p.map((r, j) => (j === i ? { ...r, name: e.target.value } : r)))}
            disabled={!canManage}
            className="w-64"
          />
          <Input
            value={row.value}
            placeholder="Valeur (ex. Intel i5-12400)"
            onChange={(e) => setRows((p) => p.map((r, j) => (j === i ? { ...r, value: e.target.value } : r)))}
            disabled={!canManage}
            className="flex-1"
          />
          {canManage ? (
            <Button
              variant="ghost"
              size="sm"
              className="text-bad hover:bg-bad-bg"
              onClick={() => setRows((p) => p.filter((_, j) => j !== i))}
              aria-label="Supprimer la ligne"
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          ) : null}
        </div>
      ))}
      {canManage ? (
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => setRows((p) => [...p, { name: '', value: '' }])}>
            <Plus className="h-4 w-4" />
            Ajouter
          </Button>
          <Button size="sm" onClick={() => save.mutate()} disabled={save.isPending}>
            {save.isPending ? 'Enregistrement…' : 'Enregistrer la fiche'}
          </Button>
          {save.isSuccess ? <span className="self-center text-sm text-ok">Fiche enregistrée.</span> : null}
        </div>
      ) : null}
    </div>
  )
}

function ImagesTab({ productId }: { productId: number }) {
  const can = usePermission()
  const canManage = can('product.media_manage')
  const qc = useQueryClient()
  const KEY = ['article-images', productId]

  const { data: images = [] } = useQuery<ImageRow[]>({
    queryKey: KEY,
    queryFn: async () => {
      const { data: r } = await api.get<{ data: ImageRow[] }>(`/products/${productId}/images`)
      return r.data
    },
  })

  const upload = useMutation({
    mutationFn: async (file: File) => {
      await ensureCsrfCookie()
      const form = new FormData()
      form.append('image', file)
      await api.post(`/products/${productId}/images`, form, { headers: { 'Content-Type': 'multipart/form-data' } })
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  const setMain = useMutation({
    mutationFn: async (imageId: number) => {
      await ensureCsrfCookie()
      await api.patch(`/products/${productId}/images/${imageId}/main`)
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  const remove = useMutation({
    mutationFn: async (imageId: number) => {
      await ensureCsrfCookie()
      await api.delete(`/products/${productId}/images/${imageId}`)
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  return (
    <div className="space-y-4">
      {canManage ? (
        <div>
          <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm text-ink hover:bg-surface-2">
            <Plus className="h-4 w-4" />
            {upload.isPending ? 'Envoi…' : 'Ajouter une image'}
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              className="hidden"
              onChange={(e) => {
                const file = e.target.files?.[0]
                if (file) upload.mutate(file)
                e.target.value = ''
              }}
            />
          </label>
          {upload.isError ? <p className="mt-2 text-sm text-bad">Envoi impossible (JPG/PNG/WebP, 4 Mo max).</p> : null}
        </div>
      ) : null}

      {images.length === 0 ? (
        <p className="text-sm text-muted">Aucune image — les images sont facultatives.</p>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {images.map((img) => (
            <div key={img.id} className="overflow-hidden rounded-lg border border-line">
              <img src={img.url} alt="" className="aspect-square w-full object-cover" />
              <div className="flex items-center justify-between px-2 py-1.5">
                {img.is_main ? (
                  <Badge tone="sky">Principale</Badge>
                ) : canManage ? (
                  <Button variant="ghost" size="sm" onClick={() => setMain.mutate(img.id)} aria-label="Définir comme principale">
                    <Star className="h-4 w-4" />
                  </Button>
                ) : (
                  <span />
                )}
                {canManage ? (
                  <Button
                    variant="ghost"
                    size="sm"
                    className="text-bad hover:bg-bad-bg"
                    onClick={() => remove.mutate(img.id)}
                    aria-label="Supprimer l'image"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                ) : null}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

function SerialsTab({ productId }: { productId: number }) {
  const can = usePermission()
  const canManage = can('product.update')
  const qc = useQueryClient()
  const KEY = ['article-serials', productId]

  const { data: serials = [] } = useQuery<SerialRow[]>({
    queryKey: KEY,
    queryFn: async () => {
      const { data: r } = await api.get<{ data: SerialRow[] }>(`/products/${productId}/serials`)
      return r.data
    },
  })

  const [input, setInput] = useState('')

  const add = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { created: number; skipped: number } }>(
        `/products/${productId}/serials`,
        { serials: input },
      )
      return r.data
    },
    onSuccess: () => {
      setInput('')
      qc.invalidateQueries({ queryKey: KEY })
    },
  })

  const remove = useMutation({
    mutationFn: async (serialId: number) => {
      await ensureCsrfCookie()
      await api.delete(`/products/${productId}/serials/${serialId}`)
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })

  return (
    <div className="space-y-4">
      {canManage ? (
        <div className="space-y-2">
          <textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            rows={3}
            placeholder={'Un numéro de série par ligne\nSN-0001\nSN-0002'}
            className="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-sky"
          />
          <div className="flex items-center gap-3">
            <Button size="sm" onClick={() => add.mutate()} disabled={add.isPending || input.trim() === ''}>
              <Plus className="h-4 w-4" />
              Ajouter les numéros
            </Button>
            {add.data ? (
              <span className="text-sm text-muted">
                {add.data.created} ajouté(s){add.data.skipped > 0 ? `, ${add.data.skipped} doublon(s) ignoré(s)` : ''}
              </span>
            ) : null}
          </div>
        </div>
      ) : null}

      {serials.length === 0 ? (
        <p className="text-sm text-muted">Aucun numéro de série enregistré.</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-line text-left text-muted">
              <th className="px-3 py-2 font-medium">Numéro</th>
              <th className="px-3 py-2 font-medium">Lieu</th>
              <th className="px-3 py-2 font-medium">Statut</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {serials.map((s) => (
              <tr key={s.id} className="border-b border-line last:border-0">
                <td className="mono px-3 py-2 text-ink">{s.serial_number}</td>
                <td className="px-3 py-2 text-muted">{s.warehouse ?? '—'}</td>
                <td className="px-3 py-2">
                  {s.is_sold ? <Badge tone="neutral">Vendu</Badge> : <Badge tone="ok">En stock</Badge>}
                </td>
                <td className="px-3 py-2 text-right">
                  {canManage && !s.is_sold ? (
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-bad hover:bg-bad-bg"
                      onClick={() => remove.mutate(s.id)}
                      aria-label="Supprimer le numéro"
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
