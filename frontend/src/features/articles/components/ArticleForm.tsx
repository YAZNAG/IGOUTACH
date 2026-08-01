import { zodResolver } from '@hookform/resolvers/zod'
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
import type { ArticleInput } from '../api/articlesApi'

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
  initial?: Product
  isPending: boolean
  onSubmit: (input: ArticleInput) => void
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

  const submit = handleSubmit((values) =>
    onSubmit({
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
    }),
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
