import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Button } from '@/components/ui/Button'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { useLogin } from '../hooks'

const schema = z.object({
  // Le champ accepte une adresse OU un numero de telephone : exiger une
  // adresse rejetterait le numero avant meme l'envoi. La verification de
  // fond appartient au serveur, qui seul sait quels comptes existent.
  email: z
    .string()
    .trim()
    .min(1, 'Saisissez votre e-mail ou votre numero de telephone')
    .refine(
      (v) => (v.includes('@') ? /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v) : /\d{9,}/.test(v.replace(/\D/g, ''))),
      'Adresse e-mail ou numero de telephone invalide',
    ),
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
      <Field label="E-mail ou telephone" htmlFor="email" error={errors.email?.message}>
        <Input
          id="email"
          type="text"
          inputMode="email"
          autoComplete="username"
          placeholder="nom@entreprise.ma ou 06 12 34 56 78"
          {...register('email')}
        />
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
