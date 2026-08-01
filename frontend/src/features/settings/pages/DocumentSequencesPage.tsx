import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import { useDocumentSequences, useUpdateDocumentSequence } from '../hooks'

const KEY_LABELS: Record<string, string> = {
  sale_invoice: 'Facture de vente',
  delivery_note: 'Bon de livraison',
  purchase_order: 'Bon de commande',
  goods_receipt: 'Bon de réception',
  stock_issue: 'Bon de sortie',
  transfer: 'Transfert',
}

export function DocumentSequencesPage() {
  const can = usePermission()
  const canManage = can('settings.manage')
  const { data: sequences = [], isLoading } = useDocumentSequences()
  const updateMutation = useUpdateDocumentSequence()

  const [drafts, setDrafts] = useState<Record<number, { prefix: string; current: number }>>({})

  useEffect(() => {
    const next: Record<number, { prefix: string; current: number }> = {}
    for (const s of sequences) next[s.id] = { prefix: s.prefix, current: s.current }
    setDrafts(next)
  }, [sequences])

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-ink">Numérotation des documents</h2>
        <p className="text-sm text-muted">Préfixe et compteur courant par type de document.</p>
      </div>

      <Card>
        <CardHeader title="Séquences" />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Document</th>
                  <th className="px-5 py-3 font-medium">Préfixe</th>
                  <th className="px-5 py-3 font-medium">Compteur</th>
                  {canManage ? <th className="px-5 py-3 text-right font-medium">Action</th> : null}
                </tr>
              </thead>
              <tbody>
                {sequences.map((s) => {
                  const draft = drafts[s.id] ?? { prefix: s.prefix, current: s.current }
                  return (
                    <tr key={s.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-ink">{KEY_LABELS[s.key] ?? s.key}</td>
                      <td className="px-5 py-3">
                        <Input
                          value={draft.prefix}
                          disabled={!canManage}
                          onChange={(e) => setDrafts((p) => ({ ...p, [s.id]: { ...draft, prefix: e.target.value } }))}
                          className="w-28"
                        />
                      </td>
                      <td className="px-5 py-3">
                        <Input
                          type="number"
                          min={0}
                          value={draft.current}
                          disabled={!canManage}
                          onChange={(e) => setDrafts((p) => ({ ...p, [s.id]: { ...draft, current: Number(e.target.value) } }))}
                          className="w-28"
                        />
                      </td>
                      {canManage ? (
                        <td className="px-5 py-3 text-right">
                          <Button
                            variant="outline"
                            size="sm"
                            disabled={updateMutation.isPending}
                            onClick={() => updateMutation.mutate({ id: s.id, input: draft })}
                          >
                            Enregistrer
                          </Button>
                        </td>
                      ) : null}
                    </tr>
                  )
                })}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
