import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Button } from '@/components/ui/Button'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { useLogin } from '../hooks'

const schema = z.object({
  email: z.string().email('Adresse e-mail invalide'),
  password: z.string().min(1, 'Mot de passe requis'),
})

type LoginValues = z.infer<typeof schema>

export function LoginForm() {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  })

  const loginMutation = useLogin()

  const onSubmit = handleSubmit((values) => {
    loginMutation.mutate(values)
  })

  return (
    <form onSubmit={onSubmit} className="space-y-5" noValidate>
      <Field label="Adresse e-mail" htmlFor="email" error={errors.email?.message}>
        <Input id="email" type="email" autoComplete="username" {...register('email')} />
      </Field>

      <Field label="Mot de passe" htmlFor="password" error={errors.password?.message}>
        <Input
          id="password"
          type="password"
          autoComplete="current-password"
          {...register('password')}
        />
      </Field>

      {loginMutation.isError ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">
          Identifiants incorrects. Vérifiez votre e-mail et votre mot de passe.
        </p>
      ) : null}

      <Button type="submit" size="lg" className="w-full" disabled={loginMutation.isPending}>
        {loginMutation.isPending ? 'Connexion…' : 'Se connecter'}
      </Button>
    </form>
  )
}
