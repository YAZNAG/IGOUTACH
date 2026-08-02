import JsBarcode from 'jsbarcode'
import { Printer, X } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { formatNumber } from '@/lib/utils'

export interface LabelArticle {
  id: number
  sku: string
  name: string
  barcode: string | null
  sale_price: number
}

type Format = 'compact' | 'standard' | 'grand'

/** Dimensions (mm) et colonnes par planche A4 pour chaque format. */
const FORMATS: Record<Format, { label: string; w: number; h: number; cols: number; barH: number; font: number }> = {
  compact: { label: 'Compact 38×21 mm (planche 65)', w: 38, h: 21, cols: 5, barH: 22, font: 7 },
  standard: { label: 'Standard 63×38 mm (planche 21)', w: 63, h: 38, cols: 3, barH: 34, font: 9 },
  grand: { label: 'Grand 99×57 mm (planche 10)', w: 99, h: 57, cols: 2, barH: 48, font: 11 },
}

/**
 * Étiquettes code-barres (Code 128) : sélection déjà faite dans la liste,
 * choix du format et du nombre d'exemplaires, impression sur planche A4.
 */
export function LabelsPanel({ articles, onClose }: { articles: LabelArticle[]; onClose: () => void }) {
  const [format, setFormat] = useState<Format>('standard')
  const [copies, setCopies] = useState(1)
  const [withPrice, setWithPrice] = useState(true)
  const sheetRef = useRef<HTMLDivElement>(null)

  const spec = FORMATS[format]
  const labels = articles.flatMap((a) => Array.from({ length: Math.max(1, copies) }, () => a))

  // Génère les codes-barres après le rendu du DOM.
  useEffect(() => {
    const sheet = sheetRef.current
    if (sheet == null) return
    sheet.querySelectorAll<SVGSVGElement>('svg[data-code]').forEach((svg) => {
      const code = svg.dataset.code ?? ''
      try {
        JsBarcode(svg, code, {
          format: 'CODE128',
          height: spec.barH,
          width: 1.4,
          fontSize: spec.font + 1,
          margin: 0,
          displayValue: true,
        })
      } catch {
        // Code invalide : l'étiquette reste sans code-barres.
      }
    })
  }, [labels.length, format, withPrice, spec.barH, spec.font])

  function print() {
    window.print()
  }

  return (
    <>
      <Card className="print:hidden">
        <CardHeader
          title={`Étiquettes — ${articles.length} article(s)`}
          action={
            <Button variant="ghost" size="sm" onClick={onClose} aria-label="Fermer les étiquettes">
              <X className="h-4 w-4" />
            </Button>
          }
        />
        <CardBody className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-3">
            <Field label="Format" htmlFor="label-format">
              <Select id="label-format" value={format} onChange={(e) => setFormat(e.target.value as Format)}>
                {(Object.keys(FORMATS) as Format[]).map((f) => (
                  <option key={f} value={f}>{FORMATS[f].label}</option>
                ))}
              </Select>
            </Field>
            <Field label="Exemplaires par article" htmlFor="label-copies">
              <Input
                id="label-copies"
                type="number"
                min={1}
                max={50}
                value={copies}
                onChange={(e) => setCopies(Math.min(50, Math.max(1, Number(e.target.value))))}
              />
            </Field>
            <Field label="Prix affiché" htmlFor="label-price">
              <Select id="label-price" value={withPrice ? '1' : '0'} onChange={(e) => setWithPrice(e.target.value === '1')}>
                <option value="1">Avec prix de vente</option>
                <option value="0">Sans prix</option>
              </Select>
            </Field>
          </div>
          <Button onClick={print}>
            <Printer className="h-4 w-4" />
            Imprimer la planche A4
          </Button>
        </CardBody>
      </Card>

      {/* Planche : visible à l'écran en aperçu, seule zone imprimée. */}
      <div ref={sheetRef} className="labels-sheet rounded-xl border border-line bg-white p-4">
        <div
          className="grid"
          style={{ gridTemplateColumns: `repeat(${spec.cols}, ${spec.w}mm)`, gap: '2mm', justifyContent: 'center' }}
        >
          {labels.map((a, i) => (
            <div
              key={`${a.id}-${i}`}
              style={{ width: `${spec.w}mm`, height: `${spec.h}mm` }}
              className="flex flex-col items-center justify-center overflow-hidden border border-dashed border-gray-300 px-1 text-center"
            >
              <div
                className="w-full truncate font-medium text-black"
                style={{ fontSize: `${spec.font}pt` }}
                title={a.name}
              >
                {a.name}
              </div>
              <svg data-code={a.barcode ?? a.sku} />
              {withPrice ? (
                <div className="font-bold text-black" style={{ fontSize: `${spec.font + 1}pt` }}>
                  {formatNumber(a.sale_price)} DH
                </div>
              ) : null}
            </div>
          ))}
        </div>
      </div>
    </>
  )
}
