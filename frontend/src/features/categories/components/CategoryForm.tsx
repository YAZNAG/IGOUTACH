import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Button } from '@/components/ui/Button'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import type { Category } from '@/types'
import type { CategoryInput } from '../api/categoriesApi'

const schema = z.object({
  name: z.string().min(1, 'Nom requis').max(191),
  requires_serial: z.boolean(),
  is_active: z.boolean(),
})

type FormValues = z.input<typeof schema>

interface CategoryFormProps {
  initial?: Category
  isPending: boolean
  onSubmit: (input: CategoryInput) => void
  onCancel: () => void
}

export function CategoryForm({ initial, isPending, onSubmit, onCancel }: CategoryFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: initial?.name ?? '',
      requires_serial: initial?.requires_serial ?? false,
      is_active: initial?.is_active ?? true,
    },
  })

  const submit = handleSubmit((values) =>
    onSubmit({
      name: values.name,
      requires_serial: values.requires_serial,
      is_active: values.is_active,
    }),
  )

  return (
    <form onSubmit={submit} className="space-y-4" noValidate>
      <Field label="Nom de la catégorie" htmlFor="cat-name" error={errors.name?.message}>
        <Input id="cat-name" placeholder="RESEAUX" {...register('name')} />
      </Field>

      <div className="flex flex-col gap-2">
        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" {...register('requires_serial')} className="h-4 w-4 accent-sky" />
          Articles avec numéro de série
        </label>
        <label className="flex items-center gap-2 text-sm text-ink">
          <input type="checkbox" {...register('is_active')} className="h-4 w-4 accent-sky" />
          Catégorie active
        </label>
      </div>

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
