import { Download, FileText, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Input } from '@/components/ui/Input'
import { SortableTh, type SortState } from '@/components/ui/SortableTh'
import { usePermission } from '@/hooks/usePermission'
import type { Category } from '@/types'
import { exportCategories, type BulkDeleteResult, type CategoryInput } from '../api/categoriesApi'
import { CategoryForm } from '../components/CategoryForm'
import {
  useBulkDeleteCategories,
  useCategories,
  useCreateCategory,
  useDeleteCategory,
  useUpdateCategory,
} from '../hooks'

function errorMessage(error: unknown): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return 'Suppression impossible.'
}

export function CategoriesPage() {
  const can = usePermission()
  const canCreate = can('category.create')
  const canUpdate = can('category.update')
  const canDelete = can('category.delete')

  const [search, setSearch] = useState('')
  const [sort, setSort] = useState<SortState>({ sort: 'name', direction: 'asc' })
  const { data: rawCategories = [], isLoading } = useCategories(search)

  const categories = [...rawCategories].sort((a, b) => {
    const dir = sort.direction === 'asc' ? 1 : -1
    if (sort.sort === 'products_count') {
      return ((a.products_count ?? 0) - (b.products_count ?? 0)) * dir
    }
    return a.name.localeCompare(b.name) * dir
  })
  const createMutation = useCreateCategory()
  const updateMutation = useUpdateCategory()
  const deleteMutation = useDeleteCategory()
  const bulkMutation = useBulkDeleteCategories()

  const [editing, setEditing] = useState<Category | null>(null)
  const [panelOpen, setPanelOpen] = useState(false)
  const [deleting, setDeleting] = useState<Category | null>(null)
  const [selected, setSelected] = useState<Set<number>>(new Set())
  const [bulkOpen, setBulkOpen] = useState(false)
  const [bulkResult, setBulkResult] = useState<BulkDeleteResult | null>(null)
  const isPending = createMutation.isPending || updateMutation.isPending

  const visibleIds = categories.map((c) => c.id)
  const allSelected = visibleIds.length > 0 && visibleIds.every((id) => selected.has(id))

  function toggleAll() {
    setSelected(allSelected ? new Set() : new Set(visibleIds))
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
  }

  function handleSubmit(input: CategoryInput) {
    if (editing) {
      updateMutation.mutate({ id: editing.id, input }, { onSuccess: closePanel })
    } else {
      createMutation.mutate(input, { onSuccess: closePanel })
    }
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

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Catégories</h1>
          <p className="text-sm text-muted">Référentiel des familles d'articles.</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => exportCategories('xlsx')}>
            <Download className="h-4 w-4" />
            Excel
          </Button>
          <Button variant="outline" size="sm" onClick={() => exportCategories('pdf')}>
            <FileText className="h-4 w-4" />
            PDF
          </Button>
          {canCreate && !panelOpen ? (
            <Button
              onClick={() => {
                setEditing(null)
                setPanelOpen(true)
              }}
            >
              <Plus className="h-4 w-4" />
              Nouvelle catégorie
            </Button>
          ) : null}
        </div>
      </div>

      {bulkResult ? (
        <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          {bulkResult.deleted} catégorie(s) supprimée(s)
          {bulkResult.blocked.length > 0
            ? ` — ${bulkResult.blocked.length} non supprimée(s) (contiennent des articles)`
            : ''}
          .
        </p>
      ) : null}

      {panelOpen ? (
        <Card>
          <CardHeader title={editing ? `Modifier ${editing.name}` : 'Nouvelle catégorie'} />
          <CardBody>
            {(createMutation.isError || updateMutation.isError) && (
              <p className="mb-4 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
                Enregistrement impossible (le nom doit être unique).
              </p>
            )}
            <CategoryForm
              initial={editing ?? undefined}
              isPending={isPending}
              onSubmit={handleSubmit}
              onCancel={closePanel}
            />
          </CardBody>
        </Card>
      ) : null}

      {canDelete && selected.size > 0 ? (
        <div className="flex items-center justify-between rounded-lg border border-sky bg-sky-soft px-4 py-2">
          <span className="text-sm font-medium text-navy">{selected.size} sélectionnée(s)</span>
          <div className="flex gap-2">
            <Button variant="ghost" size="sm" onClick={() => setSelected(new Set())}>
              Annuler
            </Button>
            <Button size="sm" className="bg-bad hover:bg-bad" onClick={() => setBulkOpen(true)}>
              <Trash2 className="h-4 w-4" />
              Supprimer la sélection
            </Button>
          </div>
        </div>
      ) : null}

      <Card>
        <CardHeader
          title="Liste des catégories"
          hint={`${categories.length} catégorie(s)`}
          action={
            <Input
              placeholder="Rechercher…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-56"
            />
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  {canDelete ? (
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
                  <SortableTh field="name" current={sort} onSort={setSort}>Nom</SortableTh>
                  <SortableTh field="products_count" current={sort} onSort={setSort} className="text-right" align="right">
                    Articles
                  </SortableTh>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {canUpdate || canDelete ? (
                    <th className="px-5 py-3 text-right font-medium">Actions</th>
                  ) : null}
                </tr>
              </thead>
              <tbody>
                {categories.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-5 py-8 text-center text-muted">
                      Aucune catégorie.
                    </td>
                  </tr>
                ) : (
                  categories.map((category) => (
                    <tr key={category.id} className="border-b border-line last:border-0">
                      {canDelete ? (
                        <td className="px-5 py-3">
                          <input
                            type="checkbox"
                            className="h-4 w-4 accent-sky"
                            checked={selected.has(category.id)}
                            onChange={() => toggleOne(category.id)}
                            aria-label={`Sélectionner ${category.name}`}
                          />
                        </td>
                      ) : null}
                      <td className="px-5 py-3 font-medium text-ink">{category.name}</td>
                      <td className="tabular px-5 py-3 text-muted">{category.products_count ?? 0}</td>
                      <td className="px-5 py-3">
                        {category.is_active ? (
                          <Badge tone="ok">Active</Badge>
                        ) : (
                          <Badge tone="bad">Inactive</Badge>
                        )}
                      </td>
                      {canUpdate || canDelete ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            {canUpdate ? (
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                  setEditing(category)
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
                                onClick={() => setDeleting(category)}
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

      <ConfirmDialog
        open={deleting !== null}
        title="Supprimer la catégorie"
        message={
          <>
            Supprimer <strong>{deleting?.name}</strong> ? Possible uniquement si la catégorie ne
            contient aucun article.
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
            Supprimer les <strong>{selected.size}</strong> catégorie(s) sélectionnée(s) ? Celles
            contenant des articles seront conservées.
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
