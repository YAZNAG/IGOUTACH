import { useQuery } from '@tanstack/react-query'
import { AlertTriangle, ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { api } from '@/lib/api'

interface AlertRow {
  key: string
  label: string
  count: number
  severity: 'ok' | 'warn' | 'bad' | 'sky'
}

/** Écran vers lequel chaque alerte renvoie pour agir. */
const ALERT_LINKS: Record<string, string> = {
  low_stock: '/achats',
  below_floor: '/tarifs',
  over_credit: '/clients',
  late_transfers: '/transferts',
  overdue_invoices: '/reglements',
  draft_inventories: '/inventaire',
  pending_expenses: '/charges',
}

export function AlertsPage() {
  const { data: alerts = [], isLoading } = useQuery<AlertRow[]>({
    queryKey: ['alerts'],
    queryFn: async () => {
      const { data } = await api.get<{ data: AlertRow[] }>('/alerts')
      return data.data
    },
    refetchInterval: 60_000,
  })

  const active = alerts.filter((a) => a.count > 0)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Alertes</h1>
        <p className="text-sm text-muted">
          Surveillance consolidée : stock, marges, crédits, transferts et documents en attente.
        </p>
      </div>

      <Card>
        <CardHeader
          title="État de surveillance"
          hint={isLoading ? 'Chargement…' : `${active.length} alerte${active.length > 1 ? 's' : ''} active${active.length > 1 ? 's' : ''}`}
        />
        <CardBody className="p-0">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Contrôle</th>
                <th className="px-5 py-3 text-right font-medium">Éléments</th>
                <th className="px-5 py-3 font-medium">État</th>
                <th className="px-5 py-3 text-right font-medium">Action</th>
              </tr>
            </thead>
            <tbody>
              {alerts.map((alert) => (
                <tr key={alert.key} className="border-b border-line last:border-0">
                  <td className="px-5 py-3 text-ink">
                    <span className="flex items-center gap-2">
                      {alert.count > 0 ? <AlertTriangle className="h-4 w-4 text-warn" /> : null}
                      {alert.label}
                    </span>
                  </td>
                  <td className="tabular px-5 py-3 text-right font-medium text-ink">{alert.count}</td>
                  <td className="px-5 py-3">
                    {alert.count === 0 ? (
                      <Badge tone="ok">OK</Badge>
                    ) : alert.severity === 'bad' ? (
                      <Badge tone="bad">Critique</Badge>
                    ) : alert.severity === 'warn' ? (
                      <Badge tone="warn">À surveiller</Badge>
                    ) : (
                      <Badge tone="sky">En attente</Badge>
                    )}
                  </td>
                  <td className="px-5 py-3 text-right">
                    {alert.count > 0 && ALERT_LINKS[alert.key] ? (
                      <Link
                        to={ALERT_LINKS[alert.key]}
                        className="inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline"
                      >
                        Traiter <ArrowRight className="h-3.5 w-3.5" />
                      </Link>
                    ) : (
                      <span className="text-muted">—</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardBody>
      </Card>
    </div>
  )
}
