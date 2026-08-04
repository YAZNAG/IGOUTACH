import { ArrowLeft } from 'lucide-react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatNumber } from '@/lib/utils'
import { fetchStockExit, type StockExitRow } from '../api/stockExitsApi'

const TYPE_BADGES: Record<string, { label: string; tone: 'ok' | 'sky' | 'warn' | 'bad' | 'neutral' }> = {
  out: { label: 'Vente / Sortie', tone: 'bad' },
  transfer_out: { label: 'Transfert expédié', tone: 'sky' },
  adjustment: { label: 'Régularisation', tone: 'warn' },
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function StockExitDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const exitId = id ? Number(id) : 0
  const { data: exit, isLoading } = useQuery<StockExitRow>({
    queryKey: ['stock-exit', exitId],
    queryFn: () => fetchStockExit(exitId),
    enabled: exitId > 0,
  })

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-exits')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!exit) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-exits')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Sortie introuvable</h1>
      </div>
    )
  }

  const badge = TYPE_BADGES[exit.type.code ?? ''] ?? { label: exit.type.name ?? '—', tone: 'neutral' as const }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-exits')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-semibold text-ink">Sortie de stock n° {exit.id}</h1>
            <Badge tone={badge.tone}>{badge.label}</Badge>
          </div>
          <p className="text-sm text-muted">
            {exit.date
              ? new Date(exit.date).toLocaleDateString('fr-FR', {
                  weekday: 'long',
                  day: '2-digit',
                  month: 'long',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })
              : '—'}
          </p>
        </div>
      </div>

      <Card>
        <CardHeader title="Mouvement" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Article</p>
              {exit.product.id ? (
                <Link to={`/articles/${exit.product.id}`} className="text-sm text-sky hover:underline">
                  <span className="mono">{exit.product.sku}</span> — {exit.product.name}
                </Link>
              ) : (
                <p className="text-sm text-ink">—</p>
              )}
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Lieu</p>
              <p className="text-sm text-ink">
                {exit.warehouse.code} · {exit.warehouse.name}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Document source</p>
              <p className="mono text-sm text-ink">{exit.source?.label ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Auteur</p>
              <p className="text-sm text-ink">{exit.author ?? '—'}</p>
            </div>
          </div>

          {exit.note ? (
            <div className="mt-4 rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Note</p>
              <p className="text-sm text-ink">{exit.note}</p>
            </div>
          ) : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Valorisation (CMUP de sortie)" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Quantité sortie</p>
              <p className="text-2xl font-semibold text-bad">
                −{formatNumber(exit.quantity)}
                {exit.product.unit ? <span className="ml-1 text-sm text-muted">{exit.product.unit}</span> : null}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">CMUP au moment de la sortie</p>
              <p className="text-2xl font-semibold text-ink">{formatMoney(exit.unit_cost)} DH</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Valeur sortie</p>
              <p className="text-2xl font-semibold text-ink">{formatMoney(exit.line_value)} DH</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Solde du lieu après</p>
              <p className="text-2xl font-semibold text-ink">{formatNumber(exit.balance_after)}</p>
            </div>
          </div>
        </CardBody>
      </Card>
    </div>
  )
}
