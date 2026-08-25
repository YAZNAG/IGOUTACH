import { useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { formatNumber } from '@/lib/utils'
import type { Category } from '@/types'
import { useBulkMarginPrices } from '../hooks'

function toNumber(value: string): number | undefined {
  if (value.trim() === '') return undefined
  const n = Number(value)
  return Number.isFinite(n) && n >= 0 ? n : undefined
}

/**
 * Mise à jour en masse par MARGE sur le prix d'achat : une marge par niveau
 * (détail / demi-gros / gros), nouveau tarif = coût × (1 + marge %).
 * Prévisualisation obligatoire avant application.
 */
export function BulkMarginPanel({ categories, onClose }: { categories: Category[]; onClose: () => void }) {
  const [detailMargin, setDetailMargin] = useState('30')
  const [semiGrosMargin, setSemiGrosMargin] = useState('20')
  const [grosMargin, setGrosMargin] = useState('10')
  const [categoryId, setCategoryId] = useState(0)

  const mutation = useBulkMarginPrices()
  const preview = mutation.data && !mutation.data.applied ? mutation.data : null

  const margins = {
    detail: toNumber(detailMargin),
    semi_gros: toNumber(semiGrosMargin),
    gros: toNumber(grosMargin),
  }
  const hasMargin = Object.values(margins).some((m) => m !== undefined)

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
        margins,
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
      margins,
      category_id: categoryId || undefined,
      apply,
    })
  }

  return (
    <Card>
      <CardHeader
        title="Marges sur prix d'achat"
        hint="Nouveau tarif = prix d'achat (CMUP) × (1 + marge %). Laissez vide pour ne pas toucher un niveau."
      />
      <CardBody className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Field label="Marge détail (%)" htmlFor="margin-detail">
            <Input
              id="margin-detail"
              type="number"
              min={0}
              step="0.1"
              value={detailMargin}
              onChange={(e) => setDetailMargin(e.target.value)}
              placeholder="ex. 30"
            />
          </Field>
          <Field label="Marge demi-gros (%)" htmlFor="margin-semi">
            <Input
              id="margin-semi"
              type="number"
              min={0}
              step="0.1"
              value={semiGrosMargin}
              onChange={(e) => setSemiGrosMargin(e.target.value)}
              placeholder="ex. 20"
            />
          </Field>
          <Field label="Marge gros (%)" htmlFor="margin-gros">
            <Input
              id="margin-gros"
              type="number"
              min={0}
              step="0.1"
              value={grosMargin}
              onChange={(e) => setGrosMargin(e.target.value)}
              placeholder="ex. 10"
            />
          </Field>
          <Field label="Catégorie" htmlFor="margin-category">
            <Select id="margin-category" value={categoryId} onChange={(e) => setCategoryId(Number(e.target.value))}>
              <option value={0}>Toutes catégories</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </Select>
          </Field>
        </div>

        {mutation.isError ? (
          <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
            Opération impossible. Vérifiez les marges saisies.
          </p>
        ) : null}

        {mutation.data?.applied ? (
          <p className="rounded border border-line bg-ok-bg px-3 py-2 text-sm text-ok">
            {mutation.data.count} article(s) tarifé(s).
            {mutation.data.skipped > 0 ? ` ${mutation.data.skipped} sans prix d'achat ignoré(s).` : ''}
            {mutation.data.errors ? ` ${mutation.data.errors} en erreur (ordre des prix).` : ''}
          </p>
        ) : null}

        {preview ? (
          <>
            {preview.skipped > 0 ? (
              <p className="text-xs text-muted">
                {preview.skipped} article(s) sans prix d'achat seront ignorés.
              </p>
            ) : null}
            <div className="max-h-72 overflow-auto rounded border border-line">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-4 py-2 font-medium">Référence</th>
                    <th className="px-4 py-2 font-medium">Article</th>
                    <th className="px-4 py-2 text-right font-medium">Prix d'achat</th>
                    {margins.detail !== undefined ? (
                      <th className="px-4 py-2 text-right font-medium">Détail</th>
                    ) : null}
                    {margins.semi_gros !== undefined ? (
                      <th className="px-4 py-2 text-right font-medium">Demi-gros</th>
                    ) : null}
                    {margins.gros !== undefined ? (
                      <th className="px-4 py-2 text-right font-medium">Gros</th>
                    ) : null}
                  </tr>
                </thead>
                <tbody>
                  {preview.rows.map((r) => (
                    <tr key={r.product_id} className="border-b border-line last:border-0">
                      <td className="mono px-4 py-2 text-muted">{r.sku}</td>
                      <td className="px-4 py-2 text-ink">{r.name}</td>
                      <td className="tabular px-4 py-2 text-right text-muted">{formatNumber(r.cost)} DH</td>
                      {(['detail', 'semi_gros', 'gros'] as const).map((code) =>
                        margins[code] !== undefined ? (
                          <td key={code} className="tabular px-4 py-2 text-right">
                            {r.levels[code]?.current != null ? (
                              <span className="text-muted line-through">{formatNumber(r.levels[code]!.current!)}</span>
                            ) : null}{' '}
                            <span className="font-medium text-ink">{formatNumber(r.levels[code]?.next ?? 0)} DH</span>
                          </td>
                        ) : null,
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        ) : null}

        <div className="flex flex-wrap items-center gap-2">
          <Button variant="outline" onClick={() => run(false)} disabled={mutation.isPending || !hasMargin}>
            Prévisualiser
          </Button>
          {/* Même raison qu'ailleurs : un bouton grisé sans explication
              visible se lit comme un bouton cassé. */}
          <Button onClick={demanderApplication} disabled={mutation.isPending || !hasMargin}>
            {mutation.isPending
              ? 'En cours…'
              : `Appliquer${preview ? ` (${preview.count} articles)` : ''}`}
          </Button>
          <Button variant="ghost" onClick={onClose}>Fermer</Button>
          {!hasMargin ? (
            <span className="text-xs text-muted">Renseignez une marge pour continuer.</span>
          ) : preview === null && !mutation.isPending ? (
            <span className="text-xs text-muted">
              Le détail des changements s'affichera avant toute écriture.
            </span>
          ) : null}
        </div>

        <ConfirmDialog
          open={aConfirmer !== null}
          title="Appliquer les marges"
          message={
            aConfirmer === null
              ? ''
              : `${aConfirmer.count} article(s) vont voir leur prix de vente recalculé à partir de leur coût. ` +
                'Les tarifs actuels sont conservés en historique.'
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
