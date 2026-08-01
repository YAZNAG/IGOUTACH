import { Download } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { usePermission } from '@/hooks/usePermission'
import { downloadFile } from '@/lib/download'
import { useAuditFilterOptions, useAuditLogs } from '../hooks'
import type { AuditFilters } from '../api/auditApi'

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('fr-FR')
}

export function AuditPage() {
  const can = usePermission()
  const canExport = can('audit.export')
  const [filters, setFilters] = useState<AuditFilters>({ page: 1, per_page: 50 })
  const { data, isLoading } = useAuditLogs(filters)
  const { data: options } = useAuditFilterOptions()

  const logs = data?.data ?? []
  const meta = data?.meta

  function patch(next: Partial<AuditFilters>) {
    setFilters((prev) => ({ ...prev, ...next, page: 1 }))
  }

  function exportXlsx() {
    downloadFile('/audit/export', 'journal-audit.xlsx', {
      action: filters.action,
      module: filters.module,
      from: filters.from,
      to: filters.to,
    })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold text-ink">Journal d'audit</h1>
          <p className="text-sm text-muted">Traçabilité des actions sensibles (accès, sécurité, paramètres…).</p>
        </div>
        {canExport ? (
          <Button variant="outline" onClick={exportXlsx}>
            <Download className="h-4 w-4" />
            Exporter
          </Button>
        ) : null}
      </div>

      <Card>
        <CardHeader title="Filtres" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-4">
            <Field label="Action" htmlFor="f-action">
              <Select id="f-action" value={filters.action ?? ''} onChange={(e) => patch({ action: e.target.value || undefined })}>
                <option value="">Toutes</option>
                {(options?.actions ?? []).map((a) => (
                  <option key={a} value={a}>{a}</option>
                ))}
              </Select>
            </Field>
            <Field label="Module" htmlFor="f-module">
              <Select id="f-module" value={filters.module ?? ''} onChange={(e) => patch({ module: e.target.value || undefined })}>
                <option value="">Tous</option>
                {(options?.modules ?? []).map((m) => (
                  <option key={m} value={m}>{m}</option>
                ))}
              </Select>
            </Field>
            <Field label="Du" htmlFor="f-from">
              <Input id="f-from" type="date" value={filters.from ?? ''} onChange={(e) => patch({ from: e.target.value || undefined })} />
            </Field>
            <Field label="Au" htmlFor="f-to">
              <Input id="f-to" type="date" value={filters.to ?? ''} onChange={(e) => patch({ to: e.target.value || undefined })} />
            </Field>
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Évènements" hint={meta ? String(meta.total) : undefined} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Date</th>
                  <th className="px-5 py-3 font-medium">Utilisateur</th>
                  <th className="px-5 py-3 font-medium">Action</th>
                  <th className="px-5 py-3 font-medium">Module</th>
                  <th className="px-5 py-3 font-medium">Description</th>
                  <th className="px-5 py-3 font-medium">IP</th>
                </tr>
              </thead>
              <tbody>
                {logs.length === 0 ? (
                  <tr><td colSpan={6} className="px-5 py-8 text-center text-muted">Aucun évènement.</td></tr>
                ) : (
                  logs.map((log) => (
                    <tr key={log.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-muted">{formatDate(log.created_at)}</td>
                      <td className="px-5 py-3 text-ink">{log.user?.name ?? '—'}</td>
                      <td className="px-5 py-3"><Badge tone="sky">{log.action}</Badge></td>
                      <td className="px-5 py-3 text-muted">{log.module ?? '—'}</td>
                      <td className="px-5 py-3 text-ink">{log.description ?? '—'}</td>
                      <td className="mono px-5 py-3 text-muted">{log.ip_address ?? '—'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-between text-sm text-muted">
          <span>Page {meta.current_page} / {meta.last_page}</span>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={meta.current_page <= 1}
              onClick={() => setFilters((p) => ({ ...p, page: (p.page ?? 1) - 1 }))}
            >
              Précédent
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={meta.current_page >= meta.last_page}
              onClick={() => setFilters((p) => ({ ...p, page: (p.page ?? 1) + 1 }))}
            >
              Suivant
            </Button>
          </div>
        </div>
      ) : null}
    </div>
  )
}
