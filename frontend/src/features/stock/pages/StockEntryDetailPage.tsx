import { ArrowLeft } from 'lucide-react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatNumber } from '@/lib/utils'
import { fetchStockEntry, type StockEntryRow } from '../api/stockEntriesApi'

const TYPE_BADGES: Record<string, { label: string; tone: 'ok' | 'sky' | 'warn' | 'neutral' }> = {
  in: { label: 'Réception', tone: 'ok' },
  return_in: { label: 'Retour client', tone: 'sky' },
  transfer_in: { label: 'Transfert reçu', tone: 'sky' },
  adjustment: { label: 'Régularisation', tone: 'warn' },
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function StockEntryDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const entryId = id ? Number(id) : 0
  const { data: entry, isLoading } = useQuery<StockEntryRow>({
    queryKey: ['stock-entry', entryId],
    queryFn: () => fetchStockEntry(entryId),
    enabled: entryId > 0,
  })

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-entries')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (!entry) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-entries')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <h1 className="text-xl font-semibold text-ink">Entrée introuvable</h1>
      </div>
    )
  }

  const badge = TYPE_BADGES[entry.type.code ?? ''] ?? { label: entry.type.name ?? '—', tone: 'neutral' as const }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/stock-entries')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-semibold text-ink">Entrée de stock n° {entry.id}</h1>
            <Badge tone={badge.tone}>{badge.label}</Badge>
          </div>
          <p className="text-sm text-muted">
            {entry.date
              ? new Date(entry.date).toLocaleDateString('fr-FR', {
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
              {entry.product.id ? (
                <Link to={`/articles/${entry.product.id}`} className="text-sm text-sky hover:underline">
                  <span className="mono">{entry.product.sku}</span> — {entry.product.name}
                </Link>
              ) : (
                <p className="text-sm text-ink">—</p>
              )}
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Lieu</p>
              <p className="text-sm text-ink">
                {entry.warehouse.code} · {entry.warehouse.name}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Document source</p>
              {entry.source?.type === 'goods_receipt' && entry.source.id ? (
                <Link to={`/goods-receipts/${entry.source.id}`} className="mono text-sm text-sky hover:underline">
                  {entry.source.label}
                </Link>
              ) : (
                <p className="text-sm text-ink">{entry.source?.label ?? '—'}</p>
              )}
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Auteur</p>
              <p className="text-sm text-ink">{entry.author ?? '—'}</p>
            </div>
          </div>

          {entry.note ? (
            <div className="mt-4 rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Note</p>
              <p className="text-sm text-ink">{entry.note}</p>
            </div>
          ) : null}
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Valorisation" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Quantité entrée</p>
              <p className="text-2xl font-semibold text-ok">
                +{formatNumber(entry.quantity)}
                {entry.product.unit ? <span className="ml-1 text-sm text-muted">{entry.product.unit}</span> : null}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Prix unitaire</p>
              <p className="text-2xl font-semibold text-ink">{formatMoney(entry.unit_cost)} DH</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Valeur de la ligne</p>
              <p className="text-2xl font-semibold text-ink">{formatMoney(entry.line_value)} DH</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Solde du lieu après</p>
              <p className="text-2xl font-semibold text-ink">{formatNumber(entry.balance_after)}</p>
            </div>
          </div>
        </CardBody>
      </Card>
    </div>
  )
}
