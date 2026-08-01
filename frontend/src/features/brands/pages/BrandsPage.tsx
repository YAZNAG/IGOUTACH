import { Plus, Trash2, Upload } from 'lucide-react'
import { useRef, useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import type { Brand, BrandInput } from '../api/brandsApi'
import { useBrands, useCreateBrand, useDeleteBrand, useUpdateBrand, useUploadBrandLogo } from '../hooks'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

const EMPTY: BrandInput = { code: '', name: '', is_active: true }

export function BrandsPage() {
  const can = usePermission()
  const canManage = can('brand.manage')

  const { data: brands = [], isLoading } = useBrands()
  const createMutation = useCreateBrand()
  const updateMutation = useUpdateBrand()
  const deleteMutation = useDeleteBrand()
  const logoMutation = useUploadBrandLogo()

  const [panelOpen, setPanelOpen] = useState(false)
  const [editing, setEditing] = useState<Brand | null>(null)
  const [form, setForm] = useState<BrandInput>(EMPTY)
  const [deleting, setDeleting] = useState<Brand | null>(null)
  const logoInput = useRef<HTMLInputElement>(null)
  const [logoTarget, setLogoTarget] = useState<number | null>(null)

  const isPending = createMutation.isPending || updateMutation.isPending
  const saveError = createMutation.isError || updateMutation.isError

  function openCreate() {
    setEditing(null)
    setForm(EMPTY)
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function openEdit(brand: Brand) {
    setEditing(brand)
    setForm({ code: brand.code ?? '', name: brand.name, is_active: brand.is_active })
    setPanelOpen(true)
    createMutation.reset()
    updateMutation.reset()
  }

  function submit(e: React.FormEvent) {
    e.preventDefault()
    const payload: BrandInput = {
      ...form,
      code: form.code || null,
    }
    if (editing) {
      updateMutation.mutate({ id: editing.id, input: payload }, { onSuccess: () => setPanelOpen(false) })
    } else {
      createMutation.mutate(payload, { onSuccess: () => setPanelOpen(false) })
    }
  }

  function pickLogo(brandId: number) {
    setLogoTarget(brandId)
    logoInput.current?.click()
  }

  function onLogoFile(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (file && logoTarget !== null) {
      logoMutation.mutate({ id: logoTarget, file })
    }
    e.target.value = ''
  }

  return (
    <div className="space-y-6">
      <input ref={logoInput} type="file" accept=".png,.svg" className="hidden" onChange={onLogoFile} />

      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Marques</h1>
          <p className="text-sm text-muted">Référentiel des marques du catalogue. Logo facultatif (PNG/SVG).</p>
        </div>
        {canManage && !panelOpen ? (
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Nouvelle marque
          </Button>
        ) : null}
      </div>

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.name}` : 'Nouvelle marque'} />
          <CardBody>
            {saveError ? (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                {errorMessage(createMutation.error ?? updateMutation.error, 'Enregistrement impossible (nom/code déjà utilisé ?).')}
              </p>
            ) : null}
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
              <Field label="Nom" htmlFor="name">
                <Input id="name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
              </Field>
              <Field label="Code" htmlFor="code">
                <Input
                  id="code"
                  value={form.code ?? ''}
                  onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })}
                  placeholder="Optionnel"
                />
              </Field>
              <label className="flex items-center gap-2 self-end pb-2 text-sm text-ink">
                <input
                  type="checkbox"
                  className="h-4 w-4 accent-sky"
                  checked={form.is_active ?? true}
                  onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                />
                Active
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
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Liste des marques" hint={`${brands.length} marque(s)`} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Logo</th>
                  <th className="px-5 py-3 font-medium">Nom</th>
                  <th className="px-5 py-3 font-medium">Code</th>
                  <th className="px-5 py-3 text-right font-medium">Articles</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {brands.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-5 py-8 text-center text-muted">
                      Aucune marque.
                    </td>
                  </tr>
                ) : (
                  brands.map((brand) => (
                    <tr key={brand.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        {brand.logo_url ? (
                          <img src={brand.logo_url} alt={brand.name} className="h-6 max-w-[80px] object-contain" />
                        ) : (
                          <span className="text-xs text-faint">—</span>
                        )}
                      </td>
                      <td className="px-5 py-3 text-ink">{brand.name}</td>
                      <td className="mono px-5 py-3 text-muted">{brand.code ?? '—'}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{brand.products_count ?? 0}</td>
                      <td className="px-5 py-3">
                        {brand.is_active ? <Badge tone="ok">Active</Badge> : <Badge tone="bad">Inactive</Badge>}
                      </td>
                      {canManage ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="sm" onClick={() => pickLogo(brand.id)}>
                              <Upload className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => openEdit(brand)}>
                              Modifier
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              className="text-bad hover:bg-bad-bg"
                              onClick={() => setDeleting(brand)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
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

      <ConfirmDialog
        open={deleting !== null}
        title="Désactiver la marque"
        message={
          <>
            Désactiver <strong>{deleting?.name}</strong> ? Impossible si des articles y sont rattachés.
          </>
        }
        confirmLabel="Désactiver"
        isPending={deleteMutation.isPending}
        error={deleteMutation.isError ? errorMessage(deleteMutation.error, 'Désactivation impossible.') : null}
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
