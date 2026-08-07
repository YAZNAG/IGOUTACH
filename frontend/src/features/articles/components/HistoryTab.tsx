import { ArrowLeftRight, ClipboardList, FileText, PackageCheck, Store } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { formatCurrency, formatNumber } from '@/lib/utils'
import { useProductHistory } from '../hooks'
import type { HistoryModule } from '../api/articlesApi'

interface HistoryTabProps {
  productId: number
}

const MODULES: Record<HistoryModule, { icon: typeof Store; tone: 'ok' | 'sky' | 'warn' | 'neutral' }> = {
  sale: { icon: Store, tone: 'sky' },
  quote: { icon: FileText, tone: 'neutral' },
  receipt: { icon: PackageCheck, tone: 'ok' },
  transfer: { icon: ArrowLeftRight, tone: 'neutral' },
  inventory: { icon: ClipboardList, tone: 'warn' },
}

const FILTRES: Array<{ id: 'all' | HistoryModule; label: string }> = [
  { id: 'all', label: 'Tout' },
  { id: 'sale', label: 'Ventes' },
  { id: 'receipt', label: 'Réceptions' },
  { id: 'transfer', label: 'Transferts' },
  { id: 'inventory', label: 'Inventaires' },
]

function dateCourte(valeur: string): string {
  const d = new Date(valeur)
  return Number.isNaN(d.getTime()) ? valeur : d.toLocaleDateString('fr-FR')
}

export function HistoryTab({ productId }: HistoryTabProps) {
  const { data: entries, isLoading } = useProductHistory(productId)
  const [filtre, setFiltre] = useState<'all' | HistoryModule>('all')

  // Les devis suivent le filtre « Ventes » : c'en est l'amont, pas un module à part.
  const visibles = (entries ?? []).filter((e) => {
    if (filtre === 'all') return true
    if (filtre === 'sale') return e.module === 'sale' || e.module === 'quote'
    return e.module === filtre
  })

  return (
    <Card>
      <CardHeader
        title="Historique de l'article"
        hint="Ventes, réceptions, transferts et inventaires, du plus récent au plus ancien."
        action={
          <div className="flex flex-wrap gap-1">
            {FILTRES.map((f) => (
              <button
                key={f.id}
                type="button"
                onClick={() => setFiltre(f.id)}
                className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-colors ${
                  filtre === f.id ? 'bg-sky-soft text-navy' : 'text-muted hover:bg-bg hover:text-ink'
                }`}
              >
                {f.label}
              </button>
            ))}
          </div>
        }
      />
      <CardBody className="p-0">
        {isLoading ? (
          <p className="py-10 text-center text-sm text-muted">Chargement…</p>
        ) : visibles.length === 0 ? (
          <p className="py-12 text-center text-sm text-muted">
            {entries && entries.length > 0
              ? 'Aucun mouvement pour ce filtre.'
              : 'Cet article n’a encore été utilisé dans aucun module.'}
          </p>
        ) : (
          <div className="max-h-[520px] overflow-y-auto">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-card">
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Module</th>
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Référence</th>
                  <th className="px-5 py-3 font-medium">Tiers / motif</th>
                  <th className="px-5 py-3 font-medium">Lieu</th>
                  <th className="px-5 py-3 text-right font-medium">Quantité</th>
                  <th className="px-5 py-3 text-right font-medium">Montant</th>
                </tr>
              </thead>
              <tbody>
                {visibles.map((entry, index) => {
                  const meta = MODULES[entry.module]
                  const Icon = meta.icon

                  return (
                    <tr
                      key={`${entry.module}-${entry.reference}-${index}`}
                      className="border-b border-line last:border-0"
                    >
                      <td className="px-5 py-3">
                        <span className="flex items-center gap-2">
                          <Icon className="h-4 w-4 shrink-0 text-muted" />
                          <Badge tone={meta.tone}>{entry.label}</Badge>
                        </span>
                      </td>
                      <td className="px-5 py-3 text-muted">{dateCourte(entry.date)}</td>
                      <td className="mono px-5 py-3">
                        <Link to={entry.link} className="text-sky hover:underline">
                          {entry.reference}
                        </Link>
                      </td>
                      <td className="px-5 py-3 text-ink">{entry.party}</td>
                      <td className="mono px-5 py-3 text-muted">{entry.warehouse ?? '—'}</td>
                      {/* Signe conservé : une vente sort du stock, une réception y entre. */}
                      <td
                        className={`tabular px-5 py-3 text-right font-medium ${
                          entry.quantity < 0 ? 'text-bad' : entry.quantity > 0 ? 'text-ok' : 'text-muted'
                        }`}
                      >
                        {entry.quantity > 0 ? '+' : ''}
                        {formatNumber(entry.quantity)}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-ink">
                        {entry.amount !== null ? formatCurrency(entry.amount) : '—'}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </CardBody>
    </Card>
  )
}
