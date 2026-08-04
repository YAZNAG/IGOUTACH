import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowRight, Download, Plus, X } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { formatNumber } from '@/lib/utils'
import type { Paginated } from '@/types'
import { CreateSalePanel, SaleDetailView, type SaleRow } from './SalesPage'

function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === 'object' && 'response' in error) {
    const response = (error as { response?: { data?: { message?: string } } }).response
    if (response?.data?.message) return response.data.message
  }
  return fallback
}

/**
 * Devis : création et suivi, séparés des ventes. Un devis se convertit en
 * vente d'un clic (les lignes sont reprises), sans effet sur le stock
 * tant que la vente n'est pas validée.
 */
export function QuotesPage() {
  const navigate = useNavigate()
  const can = usePermission()
  const qc = useQueryClient()

  const [page, setPage] = useState(1)
  const [creating, setCreating] = useState(false)
  const [detailId, setDetailId] = useState<number | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const { data, isLoading } = useQuery<Paginated<SaleRow>>({
    queryKey: ['sales', 'quotes', page],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<SaleRow>>('/sales', { params: { page, type: 'quote' } })
      return r
    },
  })

  const convert = useMutation({
    mutationFn: async (quoteId: number) => {
      await ensureCsrfCookie()
      const { data: r } = await api.post<{ data: { id: number; reference: string; quote_reference: string } }>(
        `/sales/${quoteId}/convert`,
      )
      return r.data
    },
    onSuccess: (result) => {
      qc.invalidateQueries({ queryKey: ['sales'] })
      setError(null)
      setSuccessMessage(`Vente ${result.reference} créée à partir du devis ${result.quote_reference}. Retrouvez-la dans Ventes pour la valider.`)
    },
    onError: (e) => setError(errorMessage(e, 'Conversion impossible.')),
  })

  if (detailId !== null) {
    return <SaleDetailView id={detailId} onBack={() => setDetailId(null)} />
  }

  const quotes = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Devis</h1>
          <p className="text-sm text-muted">Sans effet sur le stock — convertissez un devis en vente d'un clic.</p>
        </div>
        {can('sale.create') && !creating ? (
          <Button onClick={() => setCreating(true)}>
            <Plus className="h-4 w-4" />
            Nouveau devis
          </Button>
        ) : null}
      </div>

      {successMessage ? (
        <p className="flex items-center justify-between rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
          <span>
            {successMessage}{' '}
            <button type="button" className="underline" onClick={() => navigate('/ventes')}>
              Aller aux ventes
            </button>
          </span>
          <button type="button" onClick={() => setSuccessMessage(null)} aria-label="Fermer">
            <X className="h-4 w-4" />
          </button>
        </p>
      ) : null}

      {error ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{error}</p> : null}

      {creating ? (
        <CreateSalePanel fixedType="quote" onClose={() => setCreating(false)} onCreated={setDetailId} />
      ) : null}

      <Card>
        <CardHeader title="Devis créés" hint={meta ? `${meta.total}` : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Date de création</th>
                  <th className="px-5 py-3 font-medium">Client</th>
                  <th className="px-5 py-3 text-right font-medium">Lignes produits</th>
                  <th className="px-5 py-3 text-right font-medium">Total (DH)</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  <th className="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {quotes.length === 0 ? (
                  <tr><td colSpan={7} className="px-5 py-8 text-center text-muted">Aucun devis — créez le premier.</td></tr>
                ) : (
                  quotes.map((q) => (
                    <tr key={q.id} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-muted">{q.reference}</td>
                      <td className="px-5 py-3 text-muted">{q.created_at ?? '—'}</td>
                      <td className="px-5 py-3 text-ink">{q.customer}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{q.lines_count}</td>
                      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(q.total)}</td>
                      <td className="px-5 py-3">
                        {q.converted ? (
                          <Badge tone="ok">Converti en vente</Badge>
                        ) : q.status === 'cancelled' ? (
                          <Badge tone="bad">Annulé</Badge>
                        ) : (
                          <Badge tone="sky">Devis</Badge>
                        )}
                      </td>
                      <td className="px-5 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => setDetailId(q.id)} title="Consulter les lignes">
                            Ouvrir
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => downloadFile(`/sales/${q.id}/pdf`, `${q.reference}.pdf`)}
                            title="Devis PDF"
                          >
                            <Download className="h-4 w-4" />
                          </Button>
                          {!q.converted && q.status !== 'cancelled' && can('sale.create') ? (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => convert.mutate(q.id)}
                              disabled={convert.isPending}
                              title="Convertir en vente"
                            >
                              <ArrowRight className="h-4 w-4" />
                              Vente
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
    </div>
  )
}
