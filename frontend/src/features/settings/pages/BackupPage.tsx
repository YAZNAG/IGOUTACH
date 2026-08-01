import { Database, Download } from 'lucide-react'
import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { downloadFile } from '@/lib/download'

export function BackupPage() {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function backup() {
    setBusy(true)
    setError(null)
    try {
      const stamp = new Date().toISOString().slice(0, 10)
      await downloadFile('/backup', `igoutech-backup-${stamp}.sql`)
    } catch {
      setError('Sauvegarde impossible. Vérifiez que mysqldump est accessible (BACKUP_MYSQLDUMP_PATH).')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-ink">Sauvegarde &amp; restauration</h2>
        <p className="text-sm text-muted">Exportez une copie complète de la base de données.</p>
      </div>

      <Card>
        <CardHeader title="Sauvegarde de la base" />
        <CardBody>
          <div className="flex items-start gap-4">
            <div className="rounded-lg bg-sky-soft p-3 text-navy">
              <Database className="h-6 w-6" />
            </div>
            <div className="space-y-3">
              <p className="text-sm text-muted">
                Génère un fichier <code>.sql</code> contenant l'intégralité des données. Conservez-le en lieu sûr.
                La <strong>restauration</strong> se fait en important ce fichier dans la base (opération réalisée
                hors ligne par un administrateur).
              </p>
              <Button onClick={backup} disabled={busy}>
                <Download className="h-4 w-4" />
                {busy ? 'Génération…' : 'Télécharger la sauvegarde'}
              </Button>
              {error ? <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{error}</p> : null}
            </div>
          </div>
        </CardBody>
      </Card>
    </div>
  )
}
