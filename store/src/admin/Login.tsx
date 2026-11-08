import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { Button, Card, Erreur, Field, Input } from '../components/ui'
import { api, memoriserJeton, messageErreur } from '../lib/api'

/** Connexion au tableau de bord avec un compte de la gestion iGouTech. */
export function AdminLogin() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [identifiant, setIdentifiant] = useState('')
  const [motDePasse, setMotDePasse] = useState('')

  const connexion = useMutation({
    mutationFn: async () =>
      (await api<{ data: { token: string } }>('/v1/mobile/login', {
        method: 'POST',
        body: { identifiant, password: motDePasse, device_name: 'Boutique en ligne' },
      })).data,
    onSuccess: (r) => {
      memoriserJeton(r.token)
      qc.clear()
      navigate('/admin', { replace: true })
    },
  })

  function envoyer(e: FormEvent) {
    e.preventDefault()
    connexion.mutate()
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-paper p-4">
      <Card className="w-full max-w-sm p-6">
        <div className="mb-6 flex items-center gap-3">
          <span className="flex h-10 w-10 items-center justify-center rounded-full border-2 border-brand text-sm font-black text-brand">iG</span>
          <div>
            <p className="font-bold">iGouTech Store</p>
            <p className="text-xs text-muted">Tableau de bord de la boutique</p>
          </div>
        </div>
        <form onSubmit={envoyer} className="space-y-4">
          <Field label="Identifiant ou e-mail" htmlFor="l-id"><Input id="l-id" value={identifiant} onChange={(e) => setIdentifiant(e.target.value)} autoComplete="username" required /></Field>
          <Field label="Mot de passe" htmlFor="l-mdp"><Input id="l-mdp" type="password" value={motDePasse} onChange={(e) => setMotDePasse(e.target.value)} autoComplete="current-password" required /></Field>
          {connexion.isError ? <Erreur message={messageErreur(connexion.error, 'Connexion impossible.')} /> : null}
          <Button type="submit" className="w-full" disabled={connexion.isPending}>{connexion.isPending ? 'Connexion…' : 'Se connecter'}</Button>
        </form>
        <p className="mt-4 text-center text-xs text-faint">Même compte que l’application de gestion iGouTech.</p>
      </Card>
    </div>
  )
}
