import { zodResolver } from '@hookform/resolvers/zod'
import { ImagePlus, X } from 'lucide-react'
import { useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Button } from '@/components/ui/Button'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import type { Brand } from '@/features/brands/api/brandsApi'
import type { TaxRate } from '@/features/taxrates/api/taxRatesApi'
import type { Unit } from '@/features/units/api/unitsApi'
import type { Category, Product } from '@/types'
import type { ArticleInput, ProductDetail } from '../api/articlesApi'

const schema = z.object({
  sku: z.string().min(1, 'Référence requise').max(100),
  name: z.string().min(1, 'Nom requis').max(191),
  category_id: z.coerce.number().int().positive('Catégorie requise'),
  brand_id: z.coerce.number().int().optional(),
  unit_id: z.coerce.number().int().optional(),
  tax_rate: z.coerce.number().min(0).max(100).optional(),
  description: z.string().optional(),
  barcode: z.string().optional(),
  min_stock: z.coerce.number().int().min(0).optional(),
  is_serialized: z.boolean(),
  is_active: z.boolean(),
})

type FormValues = z.input<typeof schema>

interface ArticleFormProps {
  categories: Category[]
  brands: Brand[]
  units: Unit[]
  taxRates: TaxRate[]
  initial?: Product | ProductDetail
  isPending: boolean
  /** Les images choisies sont transmises a part : en creation l'article
   *  n'a pas encore d'identifiant auquel les rattacher. */
  onSubmit: (input: ArticleInput, images: File[]) => void
  onCancel: () => void
}

export function ArticleForm({ categories, brands, units, taxRates, initial, isPending, onSubmit, onCancel }: ArticleFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      sku: initial?.sku ?? '',
      name: initial?.name ?? '',
      category_id: initial?.category_id ?? undefined,
      brand_id: initial?.brand_id ?? undefined,
      unit_id: initial?.unit_id ?? undefined,
      tax_rate:
        initial?.tax_rate !== undefined
          ? Number(initial.tax_rate)
          : (taxRates.find((t) => t.is_default)?.rate ?? 0),
      description: initial?.description ?? '',
      barcode: initial?.barcode ?? '',
      min_stock: initial?.min_stock ?? 0,
      is_serialized: initial?.is_serialized ?? false,
      is_active: initial?.is_active ?? true,
    },
  })

  // Images facultatives. En création elles partent après l'enregistrement,
  // en modification elles s'ajoutent à celles déjà en place.
  const imagesExistantes = (initial as ProductDetail | undefined)?.images ?? []
  const [imagesEnAttente, setImagesEnAttente] = useState<File[]>([])
  const [erreurImage, setErreurImage] = useState<string | null>(null)
  const inputImages = useRef<HTMLInputElement>(null)

  function ajouterImages(files: FileList | null) {
    if (!files) return
    setErreurImage(null)

    const retenues: File[] = []

    for (const file of Array.from(files)) {
      if (file.size > 4 * 1024 * 1024) {
        setErreurImage(`« ${file.name} » dépasse 4 Mo et a été ignorée.`)
        continue
      }
      retenues.push(file)
    }

    setImagesEnAttente((prev) => [...prev, ...retenues])
    if (inputImages.current) inputImages.current.value = ''
  }

  const submit = handleSubmit((values) =>
    onSubmit(
      {
        sku: values.sku,
        name: values.name,
        category_id: Number(values.category_id),
        brand_id: values.brand_id ? Number(values.brand_id) : null,
        unit_id: values.unit_id ? Number(values.unit_id) : null,
        tax_rate: values.tax_rate !== undefined ? Number(values.tax_rate) : null,
        description: values.description?.trim() ? values.description : null,
        barcode: values.barcode?.trim() ? values.barcode : null,
        min_stock: values.min_stock !== undefined ? Number(values.min_stock) : null,
        is_serialized: values.is_serialized,
        is_active: values.is_active,
      },
      imagesEnAttente,
    ),
  )

  return (
    <form onSubmit={submit} className="space-y-4" noValidate>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field label="Référence" htmlFor="sku" error={errors.sku?.message}>
          <Input id="sku" placeholder="RJ45-5M" {...register('sku')} />
        </Field>
        <Field label="Nom" htmlFor="name" error={errors.name?.message}>
          <Input id="name" placeholder="Câble RJ45 5M" {...register('name')} />
        </Field>
        <Field label="Catégorie" htmlFor="category" error={errors.category_id?.message}>
          <Select id="category" defaultValue="" {...register('category_id')}>
            <option value="" disabled>
              Choisir une catégorie…
            </option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Marque" htmlFor="brand">
          <Select id="brand" defaultValue="" {...register('brand_id')}>
            <option value="">— Aucune —</option>
            {brands.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Unité" htmlFor="unit">
          <Select id="unit" defaultValue="" {...register('unit_id')}>
            <option value="">Unité par défaut</option>
            {units.map((u) => (
              <option key={u.id} value={u.id}>
                {u.code} · {u.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="TVA" htmlFor="tax_rate" error={errors.tax_rate?.message}>
          <Select id="tax_rate" {...register('tax_rate')}>
            {taxRates
              .filter((t) => t.is_active)
              .map((t) => (
                <option key={t.id} value={t.rate}>
                  {t.rate}% — {t.label}
                </option>
              ))}
          </Select>
        </Field>
        <Field label="Seuil min (alerte stock)" htmlFor="min_stock" error={errors.min_stock?.message}>
          <Input id="min_stock" type="number" min={0} {...register('min_stock')} />
        </Field>
        <Field label="Code-barres" htmlFor="barcode">
          <Input id="barcode" placeholder="EAN-13 (optionnel)" {...register('barcode')} />
        </Field>
        <div className="sm:col-span-2">
          <Field label="Description" htmlFor="description">
            <Input id="description" {...register('description')} />
          </Field>
        </div>
      </div>

      <div className="flex flex-wrap gap-6">
        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" {...register('is_serialized')} className="h-4 w-4 accent-sky" />
          Suivi par numéro de série
        </label>
        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" {...register('is_active')} className="h-4 w-4 accent-sky" />
          Article actif
        </label>
      </div>

      <div className="space-y-2 border-t border-line pt-4">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-ink">Images</p>
            <p className="text-xs text-faint">
              Facultatif — la première déposée devient l'image principale. JPG, PNG ou WebP, 4 Mo max.
            </p>
          </div>
          <Button type="button" variant="outline" size="sm" onClick={() => inputImages.current?.click()}>
            <ImagePlus className="h-4 w-4" />
            Choisir
          </Button>
        </div>

        <input
          ref={inputImages}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          className="hidden"
          onChange={(e) => ajouterImages(e.target.files)}
        />

        {erreurImage ? <p className="text-xs text-bad">{erreurImage}</p> : null}

        {imagesExistantes.length > 0 ? (
          <p className="text-xs text-muted">
            {imagesExistantes.length} image{imagesExistantes.length > 1 ? 's' : ''} déjà associée
            {imagesExistantes.length > 1 ? 's' : ''} — gérez-les depuis l'onglet « Médias » de la fiche.
          </p>
        ) : null}

        {imagesEnAttente.length > 0 ? (
          <ul className="flex flex-wrap gap-2 pt-1">
            {imagesEnAttente.map((file, index) => (
              <li key={`${file.name}-${index}`} className="relative">
                {/* Aperçu local : l'image n'est envoyée qu'à l'enregistrement. */}
                <img
                  src={URL.createObjectURL(file)}
                  alt=""
                  className="h-16 w-16 rounded-lg border border-line bg-bg object-contain"
                />
                <button
                  type="button"
                  onClick={() => setImagesEnAttente((prev) => prev.filter((_, i) => i !== index))}
                  aria-label={`Retirer ${file.name}`}
                  className="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full border border-line bg-card text-muted hover:text-bad"
                >
                  <X className="h-3 w-3" />
                </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>

      <p className="text-xs text-faint">
        Les prix se gèrent dans « Tarifs de vente » ; les quantités dans le stock.
      </p>

      <div className="flex justify-end gap-3 border-t border-line pt-4">
        <Button type="button" variant="outline" onClick={onCancel}>
          Annuler
        </Button>
        <Button type="submit" disabled={isPending}>
          {isPending ? 'Enregistrement…' : 'Enregistrer'}
        </Button>
      </div>
    </form>
  )
}
