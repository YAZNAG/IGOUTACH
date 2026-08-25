import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
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

  /** Prévisualisation dont l'application attend confirmation. */
  const [aConfirmer, setAConfirmer] = useState<{ count: number } | null>(null)

  /**
   * Demande l'application. Sans prévisualisation préalable, on la déclenche
   * d'abord : l'utilisateur voit toujours ce qu'il change avant de l'écrire.
   */
  async function demanderApplication() {
    if (preview !== null) {
      setAConfirmer({ count: preview.count })

      return
    }

    try {
      const resultat = await mutation.mutateAsync({
        price_type_code: code,
        percent,
        category_id: categoryId || undefined,
        apply: false,
      })
      setAConfirmer({ count: resultat.count })
    } catch {
      // La previsualisation a echoue : le message d'erreur du panneau prend
      // le relais. Sans ce filet, la promesse rejetee ne remonterait nulle
      // part et le bouton paraitrait de nouveau sans effet — le defaut meme
      // que l'on corrige ici.
    }
  }

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

        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" onClick={() => run(false)} disabled={mutation.isPending}>
            Prévisualiser
          </Button>
          {/* « Appliquer » reste toujours actionnable : un bouton grisé dont
              la raison n'apparaît qu'au survol se lit comme un bouton cassé.
              Sans prévisualisation, le clic la déclenche puis demande
              confirmation — la sécurité est gardée, l'impasse disparaît. */}
          <Button onClick={demanderApplication} disabled={mutation.isPending}>
            {mutation.isPending
              ? 'En cours…'
              : `Appliquer${preview ? ` (${preview.count} tarifs)` : ''}`}
          </Button>
          <Button variant="ghost" onClick={onClose}>Fermer</Button>
          {preview === null && !mutation.isPending ? (
            <span className="text-xs text-muted">
              Le détail des changements s'affichera avant toute écriture.
            </span>
          ) : null}
        </div>

        <ConfirmDialog
          open={aConfirmer !== null}
          title="Appliquer la nouvelle grille"
          message={
            aConfirmer === null
              ? ''
              : `${aConfirmer.count} tarif(s) vont être recalculés de ${percent > 0 ? '+' : ''}${percent} %. ` +
                'Les tarifs actuels sont conservés en historique : la grille précédente reste consultable.'
          }
          confirmLabel="Appliquer"
          isPending={mutation.isPending}
          onCancel={() => setAConfirmer(null)}
          onConfirm={() => {
            setAConfirmer(null)
            run(true)
          }}
        />
      </CardBody>
    </Card>
  )
}
