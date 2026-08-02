import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { formatNumber } from '@/lib/utils'
import type { Category } from '@/types'
import { useBulkUpdatePrices } from '../hooks'

const LEVELS = [
  { code: 'detail', label: 'Détail' },
  { code: 'semi_gros', label: 'Demi-gros' },
  { code: 'gros', label: 'Gros' },
] as const

/**
 * Mise à jour des tarifs en masse : choix du niveau, % de variation et
 * catégorie, avec prévisualisation obligatoire avant application.
 */
export function BulkUpdatePanel({ categories, onClose }: { categories: Category[]; onClose: () => void }) {
  const [code, setCode] = useState<string>('detail')
  const [percent, setPercent] = useState<number>(5)
  const [categoryId, setCategoryId] = useState<number>(0)

  const mutation = useBulkUpdatePrices()
  const preview = mutation.data && !mutation.data.applied ? mutation.data : null

  function run(apply: boolean) {
    mutation.mutate({
      price_type_code: code,
      percent,
      category_id: categoryId || undefined,
      apply,
    })
  }

  return (
    <Card>
      <CardHeader title="Mise à jour des tarifs en masse" />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Niveau de prix" htmlFor="bulk-level">
            <Select id="bulk-level" value={code} onChange={(e) => setCode(e.target.value)}>
              {LEVELS.map((l) => (
                <option key={l.code} value={l.code}>{l.label}</option>
              ))}
            </Select>
          </Field>
          <Field label="Variation (%)" htmlFor="bulk-percent">
            <Input
              id="bulk-percent"
              type="number"
              step="0.1"
              value={percent}
              onChange={(e) => setPercent(Number(e.target.value))}
            />
          </Field>
          <Field label="Catégorie" htmlFor="bulk-category">
            <Select id="bulk-category" value={categoryId} onChange={(e) => setCategoryId(Number(e.target.value))}>
              <option value={0}>Toutes catégories</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </Select>
          </Field>
        </div>

        {mutation.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Opération impossible. Vérifiez les valeurs saisies.
          </p>
        ) : null}

        {mutation.data?.applied ? (
          <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
            {mutation.data.count} tarif(s) mis à jour.
          </p>
        ) : null}

        {preview ? (
          <div className="max-h-72 overflow-auto rounded border border-line">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-4 py-2 font-medium">Référence</th>
                  <th className="px-4 py-2 font-medium">Article</th>
                  <th className="px-4 py-2 text-right font-medium">Actuel</th>
                  <th className="px-4 py-2 text-right font-medium">Nouveau</th>
                </tr>
              </thead>
              <tbody>
                {preview.rows.map((r) => (
                  <tr key={r.product_id} className="border-b border-line last:border-0">
                    <td className="mono px-4 py-2 text-muted">{r.sku}</td>
                    <td className="px-4 py-2 text-ink">{r.name}</td>
                    <td className="tabular px-4 py-2 text-right text-muted">{formatNumber(r.current)} DH</td>
                    <td className="tabular px-4 py-2 text-right font-medium text-ink">{formatNumber(r.next)} DH</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : null}

        <div className="flex gap-2">
          <Button variant="outline" onClick={() => run(false)} disabled={mutation.isPending}>
            Prévisualiser
          </Button>
          <Button
            onClick={() => run(true)}
            disabled={mutation.isPending || preview === null}
            title={preview === null ? "Prévisualisez d'abord" : undefined}
          >
            Appliquer {preview ? `(${preview.count} tarifs)` : ''}
          </Button>
          <Button variant="ghost" onClick={onClose}>Fermer</Button>
        </div>
      </CardBody>
    </Card>
  )
}
