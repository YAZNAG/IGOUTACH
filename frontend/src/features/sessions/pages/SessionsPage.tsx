import { LogOut, Monitor } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { useRevokeSession, useSessions } from '../hooks'
import type { ActiveSession } from '../api/sessionsApi'

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('fr-FR')
}

function shortAgent(agent: string | null): string {
  if (!agent) return '—'
  return agent.length > 60 ? `${agent.slice(0, 60)}…` : agent
}

export function SessionsPage() {
  const { data: sessions = [], isLoading } = useSessions()
  const revokeMutation = useRevokeSession()
  const [toRevoke, setToRevoke] = useState<ActiveSession | null>(null)

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-ink">Sessions actives</h1>
        <p className="text-sm text-muted">Visualisez et déconnectez de force les sessions ouvertes.</p>
      </div>

      <Card>
        <CardHeader title="Sessions" hint={String(sessions.length)} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Utilisateur</th>
                  <th className="px-5 py-3 font-medium">Adresse IP</th>
                  <th className="px-5 py-3 font-medium">Navigateur</th>
                  <th className="px-5 py-3 font-medium">Dernière activité</th>
                  <th className="px-5 py-3 text-right font-medium">Action</th>
                </tr>
              </thead>
              <tbody>
                {sessions.length === 0 ? (
                  <tr><td colSpan={5} className="px-5 py-8 text-center text-muted">Aucune session active.</td></tr>
                ) : (
                  sessions.map((s) => (
                    <tr key={s.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-ink">
                        <span className="inline-flex items-center gap-2">
                          <Monitor className="h-4 w-4 text-muted" />
                          {s.user_name ?? 'Invité'}
                          {s.is_current ? <Badge tone="ok">Session actuelle</Badge> : null}
                        </span>
                        {s.user_email ? <span className="block text-xs text-muted">{s.user_email}</span> : null}
                      </td>
                      <td className="mono px-5 py-3 text-muted">{s.ip_address ?? '—'}</td>
                      <td className="px-5 py-3 text-muted">{shortAgent(s.user_agent)}</td>
                      <td className="px-5 py-3 text-muted">{formatDate(s.last_activity)}</td>
                      <td className="px-5 py-3 text-right">
                        {!s.is_current ? (
                          <Button variant="ghost" size="sm" onClick={() => setToRevoke(s)}>
                            <LogOut className="h-4 w-4 text-bad" />
                            Déconnecter
                          </Button>
                        ) : (
                          <span className="text-xs text-muted">—</span>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={toRevoke !== null}
        title="Déconnecter la session"
        message={<>Déconnecter de force la session de <strong>{toRevoke?.user_name ?? 'cet utilisateur'}</strong> ?</>}
        confirmLabel="Déconnecter"
        danger
        isPending={revokeMutation.isPending}
        onConfirm={() => toRevoke && revokeMutation.mutate(toRevoke.id, { onSuccess: () => setToRevoke(null) })}
        onCancel={() => setToRevoke(null)}
      />
    </div>
  )
}
