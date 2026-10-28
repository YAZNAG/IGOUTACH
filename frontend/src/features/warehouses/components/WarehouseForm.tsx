import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Button } from '@/components/ui/Button'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import type { Warehouse, WarehouseInput, WarehouseType } from '../types'

const schema = z.object({
  code: z.string().min(1, 'Code requis').max(50),
  name: z.string().min(1, 'Nom requis'),
  warehouse_type_id: z.coerce.number().int().positive('Type requis'),
  city: z.string().optional(),
  address: z.string().optional(),
  phone: z.string().optional(),
  parent_id: z.string().optional(),
  is_active: z.boolean(),
  // Saisi en texte par le formulaire, converti a l'envoi.
  vat_rate: z.string().optional(),
  prices_include_vat: z.boolean(),
})

type FormValues = z.input<typeof schema>

interface WarehouseFormProps {
  types: WarehouseType[]
  parents: Warehouse[]
  initial?: Warehouse
  isPending: boolean
  onSubmit: (input: WarehouseInput) => void
  onCancel: () => void
}

export function WarehouseForm({
  types,
  parents,
  initial,
  isPending,
  onSubmit,
  onCancel,
}: WarehouseFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      code: initial?.code ?? '',
      name: initial?.name ?? '',
      warehouse_type_id: initial?.warehouse_type_id ?? undefined,
      city: initial?.city ?? '',
      address: initial?.address ?? '',
      phone: initial?.phone ?? '',
      parent_id: initial?.parent_id ? String(initial.parent_id) : '',
      is_active: initial?.is_active ?? true,
      vat_rate: initial?.vat_rate != null ? String(initial.vat_rate) : '0',
      prices_include_vat: initial?.prices_include_vat ?? false,
    },
  })

  const submit = handleSubmit((values) => {
    onSubmit({
      code: values.code,
      name: values.name,
      warehouse_type_id: Number(values.warehouse_type_id),
      city: values.city?.trim() ? values.city : null,
      address: values.address?.trim() ? values.address : null,
      phone: values.phone?.trim() ? values.phone : null,
      parent_id: values.parent_id ? Number(values.parent_id) : null,
      is_active: values.is_active,
      vat_rate: Number(values.vat_rate ?? 0) || 0,
      prices_include_vat: values.prices_include_vat,
    })
  })

  return (
    <form onSubmit={submit} className="space-y-4" noValidate>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field label="Code" htmlFor="code" error={errors.code?.message}>
          <Input id="code" placeholder="DEP-03" {...register('code')} />
        </Field>
        <Field label="Nom" htmlFor="name" error={errors.name?.message}>
          <Input id="name" placeholder="Dépôt Nord" {...register('name')} />
        </Field>
        <Field label="Type de lieu" htmlFor="type" error={errors.warehouse_type_id?.message}>
          <Select id="type" defaultValue="" {...register('warehouse_type_id')}>
            <option value="" disabled>
              Choisir un type…
            </option>
            {types.map((type) => (
              <option key={type.id} value={type.id}>
                {type.name}
              </option>
            ))}
          </Select>
        </Field>
        <Field label="Rattaché à (dépôt parent)" htmlFor="parent">
          <Select id="parent" {...register('parent_id')}>
            <option value="">Aucun</option>
            {parents
              .filter((parent) => parent.id !== initial?.id)
              .map((parent) => (
                <option key={parent.id} value={parent.id}>
                  {parent.code} — {parent.name}
                </option>
              ))}
          </Select>
        </Field>
        <Field label="Ville" htmlFor="city">
          <Input id="city" {...register('city')} />
        </Field>
        <Field label="Téléphone" htmlFor="phone">
          <Input id="phone" {...register('phone')} />
        </Field>
        <div className="sm:col-span-2">
          <Field label="Adresse" htmlFor="address">
            <Input id="address" {...register('address')} />
          </Field>
        </div>

        {/* La TVA se porte par le lieu : un dépôt déclaré à 20 % facture ses
            ventes et ses réceptions avec TVA, les autres restent hors taxe. */}
        <Field label="TVA du lieu (%)" htmlFor="vat_rate" error={errors.vat_rate?.message}>
          <Input id="vat_rate" type="number" min={0} max={100} step={0.01} {...register('vat_rate')} />
          <p className="text-xs text-muted">
            0 = pas de TVA. 20 = toutes les ventes et réceptions de ce lieu sont taxées.
          </p>
        </Field>
      </div>

      <label className="flex items-center gap-2 text-sm text-ink">
        <input type="checkbox" {...register('prices_include_vat')} className="h-4 w-4 accent-sky" />
        Les prix de ce lieu sont saisis TVA comprise
      </label>
      <p className="-mt-2 text-xs text-muted">
        Décoché : vous saisissez 100, le client paie 120. Coché : vous saisissez 120, dont 20 de TVA.
      </p>

      <label className="flex items-center gap-2 text-sm text-ink">
        <input type="checkbox" {...register('is_active')} className="h-4 w-4 accent-sky" />
        Lieu actif
      </label>

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
