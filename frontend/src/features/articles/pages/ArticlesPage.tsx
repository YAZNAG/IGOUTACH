import { useQueryClient } from '@tanstack/react-query'
import { Download, FileText, Plus, Tag, Trash2, Upload } from 'lucide-react'
import { useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { paginationInfo, SortableTh, type SortState } from '@/components/ui/SortableTh'
import { useBrands } from '@/features/brands/hooks'
import { useTaxRates } from '@/features/taxrates/hooks'
import { useUnits } from '@/features/units/hooks'
import { usePermission } from '@/hooks/usePermission'
import type { Product } from '@/types'
import {
  exportArticles,
  uploadProductImage,
  type ArticleInput,
  type BulkDeleteResult,
  type ImportResult,
} from '../api/articlesApi'
import { ArticleForm } from '../components/ArticleForm'
import { ArticleSheet } from '../components/ArticleSheet'
import { LabelsPanel } from '../components/LabelsPanel'
import {
  useArticles,
  useBulkDeleteArticles,
  useCategoryOptions,
  useCreateArticle,
  useDeleteArticle,
  useImportArticles,
  useUpdateArticle,
} from '../hooks'

function errorMessage(error: unknown): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return 'Suppression impossible.'
}

export function ArticlesPage() {
  const navigate = useNavigate()
  const can = usePermission()
  const canCreate = can('product.create')
  const canUpdate = can('product.update')
  const canDelete = can('product.delete')
  const canLabels = can('product.labels_print')
  const canSelect = canDelete || canLabels
  const canImport = can('product.import')

  const [search, setSearch] = useState('')
  const [categoryId, setCategoryId] = useState<number>(0)
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(20)
  const [sort, setSort] = useState<SortState>({ sort: 'name', direction: 'asc' })
  const fileInput = useRef<HTMLInputElement>(null)

  const { data: categories = [] } = useCategoryOptions()
  const { data: brands = [] } = useBrands()
  const { data: units = [] } = useUnits()
  const { data: taxRates = [] } = useTaxRates()
  const { data, isLoading } = useArticles({
    search: search || undefined,
    category_id: categoryId || undefined,
    page,
    per_page: perPage,
    sort: sort.sort,
    direction: sort.direction,
  })

  const queryClient = useQueryClient()
  const [uploading, setUploading] = useState(false)
  const [imageError, setImageError] = useState<string | null>(null)
  const createMutation = useCreateArticle()
  const updateMutation = useUpdateArticle()
  const deleteMutation = useDeleteArticle()
  const bulkMutation = useBulkDeleteArticles()
  const importMutation = useImportArticles()

  const [editing, setEditing] = useState<Product | null>(null)
  const [panelOpen, setPanelOpen] = useState(false)
  const [deleting, setDeleting] = useState<Product | null>(null)
  const [selected, setSelected] = useState<Set<number>>(new Set())
  const [bulkOpen, setBulkOpen] = useState(false)
  const [bulkResult, setBulkResult] = useState<BulkDeleteResult | null>(null)
  const [importResult, setImportResult] = useState<ImportResult | null>(null)
  const [sheet, setSheet] = useState<Product | null>(null)
  const [labelsOpen, setLabelsOpen] = useState(false)

  const articles = data?.data ?? []
  const meta = data?.meta
  const isPending = createMutation.isPending || updateMutation.isPending || uploading
  const currentFilters = { search: search || undefined, category_id: categoryId || undefined }

  const visibleIds = articles.map((a) => a.id)
  const allSelected = visibleIds.length > 0 && visibleIds.every((id) => selected.has(id))

  function toggleAll() {
    setSelected((prev) => {
      const next = new Set(prev)
      if (allSelected) {
        visibleIds.forEach((id) => next.delete(id))
      } else {
        visibleIds.forEach((id) => next.add(id))
      }
      return next
    })
  }

  function toggleOne(id: number) {
    setSelected((prev) => {
      const next = new Set(prev)
      next.has(id) ? next.delete(id) : next.add(id)
      return next
    })
  }

  function closePanel() {
    setPanelOpen(false)
    setEditing(null)
    createMutation.reset()
    updateMutation.reset()
    setImageError(null)
  }

  /**
   * Les images partent après l'enregistrement : en création l'article n'a pas
   * d'identifiant avant. Un échec d'envoi ne remet pas l'article en cause —
   * il est déjà enregistré, les images se rattrapent depuis l'onglet Médias.
   */
  async function handleSubmit(input: ArticleInput, images: File[]) {
    setImageError(null)

    const product = editing
      ? await updateMutation.mutateAsync({ id: editing.id, input })
      : await createMutation.mutateAsync(input)

    if (images.length > 0) {
      setUploading(true)
      try {
        for (const file of images) {
          await uploadProductImage(product.id, file)
        }
        queryClient.invalidateQueries({ queryKey: ['articles'] })
      } catch {
        setImageError(
          'L’article est enregistré, mais l’envoi des images a échoué. Reprenez depuis l’onglet « Médias ».',
        )
        setUploading(false)
        return
      }
      setUploading(false)
    }

    closePanel()
  }

  function confirmBulk() {
    bulkMutation.mutate(Array.from(selected), {
      onSuccess: (result) => {
        setBulkResult(result)
        setSelected(new Set())
        setBulkOpen(false)
      },
    })
  }

  function onImportFile(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    if (!file) return
    setImportResult(null)
    importMutation.mutate(file, { onSuccess: (result) => setImportResult(result) })
    event.target.value = ''
  }

  const categoryName = (id: number) => categories.find((c) => c.id === id)?.name ?? '—'

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Articles</h1>
          <p className="text-sm text-muted">Informations fixes : réf, nom, description, seuil min.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => exportArticles('xlsx', currentFilters)}>
            <Download className="h-4 w-4" />
            Excel
          </Button>
          <Button variant="outline" size="sm" onClick={() => exportArticles('pdf', currentFilters)}>
            <FileText className="h-4 w-4" />
            PDF
          </Button>
          {canImport ? (
            <>
              <input
                ref={fileInput}
                type="file"
                accept=".xlsx,.xls,.csv"
                className="hidden"
                onChange={onImportFile}
              />
              <Button
                variant="outline"
                size="sm"
                onClick={() => fileInput.current?.click()}
                disabled={importMutation.isPending}
              >
                <Upload className="h-4 w-4" />
                {importMutation.isPending ? 'Import…' : 'Importer'}
              </Button>
            </>
          ) : null}
          {canCreate && !panelOpen ? (
            <Button
              onClick={() => {
                setEditing(null)
                setPanelOpen(true)
              }}
            >
              <Plus className="h-4 w-4" />
              Nouvel article
            </Button>
          ) : null}
        </div>
      </div>

      {importResult ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          Import terminé : {importResult.created} créé(s), {importResult.updated} mis à jour
          {importResult.skipped > 0 ? `, ${importResult.skipped} ignoré(s)` : ''}.
        </p>
      ) : null}
      {importMutation.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          Import impossible. Vérifiez le format du fichier (xlsx, xls ou csv).
        </p>
      ) : null}
      {bulkResult ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          {bulkResult.deleted} article(s) supprimé(s)
          {bulkResult.blocked.length > 0
            ? ` — ${bulkResult.blocked.length} conservé(s) (engagés dans le stock)`
            : ''}
          .
        </p>
      ) : null}

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.sku}` : 'Nouvel article'} />
          <CardBody>
            {(createMutation.isError || updateMutation.isError) && (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                Enregistrement impossible (la référence doit être unique).
              </p>
            )}
            {/* L'article a bien été enregistré : l'avertissement porte sur les
                seules images, il ne doit pas se lire comme un échec global. */}
            {imageError ? (
              <p className="mb-4 rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
                {imageError}
              </p>
            ) : null}
            <ArticleForm
              categories={categories}
              brands={brands}
              units={units}
              taxRates={taxRates}
              initial={editing ?? undefined}
              isPending={isPending}
              onSubmit={handleSubmit}
              onCancel={closePanel}
            />
          </CardBody>
        </Card>
      ) : null}

      {canSelect && selected.size > 0 ? (
        <div className="flex items-center justify-between rounded-lg border border-sky bg-sky-soft px-4 py-2">
          <span className="text-sm font-medium text-navy">{selected.size} sélectionné(s)</span>
          <div className="flex gap-2">
            <Button variant="ghost" size="sm" onClick={() => setSelected(new Set())}>
              Annuler
            </Button>
            {canLabels ? (
              <Button variant="outline" size="sm" onClick={() => setLabelsOpen(true)}>
                <Tag className="h-4 w-4" />
                Étiquettes
              </Button>
            ) : null}
            {canDelete ? (
              <Button size="sm" className="bg-bad hover:bg-bad" onClick={() => setBulkOpen(true)}>
                <Trash2 className="h-4 w-4" />
                Supprimer la sélection
              </Button>
            ) : null}
          </div>
        </div>
      ) : null}

      {labelsOpen && selected.size > 0 ? (
        <LabelsPanel
          articles={articles
            .filter((a) => selected.has(a.id))
            .map((a) => ({ id: a.id, sku: a.sku, name: a.name, barcode: a.barcode, sale_price: Number(a.sale_price) }))}
          onClose={() => setLabelsOpen(false)}
        />
      ) : null}

      {sheet !== null ? (
        <ArticleSheet
          productId={sheet.id}
          sku={sheet.sku}
          name={sheet.name}
          isSerialized={sheet.is_serialized}
          onClose={() => setSheet(null)}
        />
      ) : null}

      <Card>
        <CardHeader
          title="Liste des articles"
          hint={meta ? `${meta.total} article(s)` : undefined}
          action={
            <div className="flex gap-2">
              <Select
                value={perPage}
                onChange={(e) => {
                  setPerPage(Number(e.target.value))
                  setPage(1)
                }}
                className="w-28"
                aria-label="Articles par page"
              >
                <option value={20}>20 / page</option>
                <option value={50}>50 / page</option>
                <option value={100}>100 / page</option>
              </Select>
              <Select
                value={categoryId}
                onChange={(e) => {
                  setCategoryId(Number(e.target.value))
                  setPage(1)
                }}
                className="w-44"
              >
                <option value={0}>Toutes catégories</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </Select>
              <Input
                placeholder="Rechercher réf / nom…"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value)
                  setPage(1)
                }}
                className="w-56"
              />
            </div>
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  {canSelect ? (
                    <th className="w-10 px-5 py-3">
                      <input
                        type="checkbox"
                        className="h-4 w-4 accent-sky"
                        checked={allSelected}
                        onChange={toggleAll}
                        aria-label="Tout sélectionner"
                      />
                    </th>
                  ) : null}
                  <SortableTh field="sku" current={sort} onSort={setSort}>Référence</SortableTh>
                  <SortableTh field="name" current={sort} onSort={setSort}>Nom</SortableTh>
                  <th className="px-5 py-3 font-medium">Catégorie</th>
                  <SortableTh field="min_stock" current={sort} onSort={setSort} className="text-right" align="right">
                    Seuil min
                  </SortableTh>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canUpdate || canDelete ? (
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  ) : null}
                </tr>
              </thead>
              <tbody>
                {articles.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-5 py-8 text-center text-muted">
                      Aucun article.
                    </td>
                  </tr>
                ) : (
                  articles.map((a) => (
                    <tr key={a.id} className="border-b border-line last:border-0">
                      {canSelect ? (
                        <td className="px-5 py-3">
                          <input
                            type="checkbox"
                            className="h-4 w-4 accent-sky"
                            checked={selected.has(a.id)}
                            onChange={() => toggleOne(a.id)}
                            aria-label={`Sélectionner ${a.name}`}
                          />
                        </td>
                      ) : null}
                      <td className="mono px-5 py-3 text-muted">{a.sku}</td>
                      <td className="px-5 py-3">
                        <button
                          onClick={() => navigate(`/articles/${a.id}`)}
                          className="text-ink hover:underline"
                        >
                          {a.name}
                        </button>
                      </td>
                      <td className="px-5 py-3">
                        <Badge tone="sky">{categoryName(a.category_id)}</Badge>
                      </td>
                      <td className="tabular px-5 py-3 text-muted">{a.min_stock ?? '—'}</td>
                      <td className="px-5 py-3">
                        {a.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="bad">Inactif</Badge>}
                      </td>
                      {canUpdate || canDelete ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            <Button variant="ghost" size="sm" onClick={() => setSheet(a)}>
                              Fiche
                            </Button>
                            {canUpdate ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                  setEditing(a)
                                  setPanelOpen(true)
                                }}
                              >
                                Modifier
                              </Button>
                            ) : null}
                            {canDelete ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                className="text-bad hover:bg-bad-bg"
                                onClick={() => setDeleting(a)}
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

      {meta ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <span>{paginationInfo(meta)}</span>
          {meta.last_page > 1 ? (
            <div className="flex items-center gap-2">
              <span>
                Page {meta.current_page} / {meta.last_page}
              </span>
              <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                Précédent
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Suivant
              </Button>
            </div>
          ) : null}
        </div>
      ) : null}

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer l'article"
        message={
          <>
            Supprimer <strong>{deleting?.name}</strong> ? Possible uniquement si l'article n'a aucun
            mouvement de stock, transfert ou stock en cours.
          </>
        }
        confirmLabel="Supprimer"
        isPending={deleteMutation.isPending}
        error={deleteMutation.isError ? errorMessage(deleteMutation.error) : null}
        onConfirm={() => {
          if (deleting) deleteMutation.mutate(deleting.id, { onSuccess: () => setDeleting(null) })
        }}
        onCancel={() => {
          setDeleting(null)
          deleteMutation.reset()
        }}
      />

      <ConfirmDialog
        open={bulkOpen}
        title="Supprimer la sélection"
        message={
          <>
            Supprimer les <strong>{selected.size}</strong> article(s) sélectionné(s) ? Ceux engagés
            dans le stock seront conservés.
          </>
        }
        confirmLabel="Supprimer"
        isPending={bulkMutation.isPending}
        onConfirm={confirmBulk}
        onCancel={() => setBulkOpen(false)}
      />
    </div>
  )
}
