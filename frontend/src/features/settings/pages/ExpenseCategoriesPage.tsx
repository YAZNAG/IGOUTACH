import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { usePermission } from '@/hooks/usePermission'
import { api, ensureCsrfCookie } from '@/lib/api'

interface ExpenseCategory {
  id: number
  name: string
  is_active: boolean
}

const CLE = ['expense-categories', 'management'] as const

function messageErreur(erreur: unknown, defaut: string): string {
  if (erreur && typeof erreur === 'object' && 'response' in erreur) {
    const reponse = (erreur as { response?: { data?: { message?: string } } }).response
    if (reponse?.data?.message) return reponse.data.message
  }
  return defaut
}

/**
 * Types de charge : loyer, carburant, restauration…
 *
 * Ils vivent ici plutôt que dans l'écran de saisie des charges : un référentiel
 * se tient à un seul endroit, sinon chacun crée son doublon au fil des saisies.
 */
export function ExpenseCategoriesPage() {
  const peut = usePermission()
  const peutGerer = peut('expense.approve')
  const qc = useQueryClient()

  const [nom, setNom] = useState('')
  const [enEdition, setEnEdition] = useState<number | null>(null)
  const [aSupprimer, setASupprimer] = useState<ExpenseCategory | null>(null)
  const [erreur, setErreur] = useState<string | null>(null)

  const { data: types = [], isLoading } = useQuery<ExpenseCategory[]>({
    queryKey: CLE,
    queryFn: async () => {
      // Les inactifs sont demandés explicitement : sans eux, un type désactivé
      // deviendrait impossible à retrouver, donc à réactiver.
      const { data } = await api.get<{ data: ExpenseCategory[] }>('/expense-categories', {
        params: { with_inactive: 1 },
      })
      return data.data
    },
  })

  function apresSucces() {
    qc.invalidateQueries({ queryKey: CLE })
    // La liste de saisie des charges lit le même référentiel.
    qc.invalidateQueries({ queryKey: ['expense-categories'] })
    setNom('')
    setEnEdition(null)
    setErreur(null)
  }

  const enregistrer = useMutation({
    mutationFn: async () => {
      await ensureCsrfCookie()
      if (enEdition !== null) {
        await api.put(`/expense-categories/${enEdition}`, { name: nom.trim() })
      } else {
        await api.post('/expense-categories', { name: nom.trim() })
      }
    },
    onSuccess: apresSucces,
    onError: (e) => setErreur(messageErreur(e, 'Enregistrement impossible.')),
  })

  const basculerActif = useMutation({
    mutationFn: async (type: ExpenseCategory) => {
      await ensureCsrfCookie()
      await api.put(`/expense-categories/${type.id}`, { is_active: !type.is_active })
    },
    onSuccess: apresSucces,
    onError: (e) => setErreur(messageErreur(e, 'Modification impossible.')),
  })

  const supprimer = useMutation({
    mutationFn: async (id: number) => {
      await ensureCsrfCookie()
      await api.delete(`/expense-categories/${id}`)
    },
    onSuccess: () => {
      setASupprimer(null)
      apresSucces()
    },
    onError: (e) => {
      setASupprimer(null)
      setErreur(messageErreur(e, 'Suppression impossible.'))
    },
  })

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-ink">Types de charge</h2>
        <p className="text-sm text-muted">Loyer, carburant, restauration, salaires…</p>
      </div>

      {erreur ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
      ) : null}

      {peutGerer ? (
        <Card>
          <CardHeader title={enEdition !== null ? 'Renommer le type' : 'Nouveau type de charge'} />
          <CardBody>
            <form
              onSubmit={(e) => {
                e.preventDefault()
                if (nom.trim() !== '') enregistrer.mutate()
              }}
              className="flex flex-col gap-3 sm:flex-row sm:items-end"
            >
              {/* Field n'expose pas de className : l'étirement est porté par
                  l'enveloppe, pas par le composant partagé. */}
              <div className="flex-1">
                <Field label="Libellé" htmlFor="type-nom">
                  <Input
                    id="type-nom"
                    value={nom}
                    onChange={(e) => setNom(e.target.value)}
                    placeholder="Frais de restauration"
                    required
                  />
                </Field>
              </div>
              <div className="flex gap-2">
                <Button type="submit" disabled={enregistrer.isPending || nom.trim() === ''}>
                  {enEdition !== null ? 'Enregistrer' : 'Ajouter'}
                </Button>
                {enEdition !== null ? (
                  <Button
                    type="button"
                    variant="ghost"
                    onClick={() => {
                      setEnEdition(null)
                      setNom('')
                    }}
                  >
                    Annuler
                  </Button>
                ) : null}
              </div>
            </form>
            {enEdition !== null ? (
              <p className="mt-3 text-xs text-muted">
                Renommer n'affecte pas les charges déjà saisies : elles restent rattachées à ce type.
              </p>
            ) : null}
          </CardBody>
        </Card>
      ) : null}

      <Card>
        <CardHeader title="Liste" hint={`${types.length} type(s)`} />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-line text-left text-muted">
                  <th className="px-5 py-3 font-medium">Libellé</th>
                  <th className="px-5 py-3 font-medium">Statut</th>
                  {peutGerer ? <th className="px-5 py-3 text-right font-medium">Actions</th> : null}
                </tr>
              </thead>
              <tbody>
                {types.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-5 py-8 text-center text-muted">
                      Aucun type de charge.
                    </td>
                  </tr>
                ) : (
                  types.map((t) => (
                    <tr key={t.id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3 text-ink">{t.name}</td>
                      <td className="px-5 py-3">
                        {t.is_active ? <Badge tone="ok">Actif</Badge> : <Badge tone="neutral">Retiré</Badge>}
                      </td>
                      {peutGerer ? (
                        <td className="px-5 py-3">
                          <div className="flex justify-end gap-1">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => basculerActif.mutate(t)}
                              disabled={basculerActif.isPending}
                            >
                              {t.is_active ? 'Retirer' : 'Réactiver'}
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label={`Renommer ${t.name}`}
                              onClick={() => {
                                setEnEdition(t.id)
                                setNom(t.name)
                                setErreur(null)
                              }}
                            >
                              <Pencil className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              aria-label={`Supprimer ${t.name}`}
                              onClick={() => setASupprimer(t)}
                            >
                              <Trash2 className="h-4 w-4 text-bad" />
                            </Button>
                          </div>
                        </td>
                      ) : null}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={aSupprimer !== null}
        title="Supprimer le type de charge"
        message={
          <>
            Supprimer <strong>{aSupprimer?.name}</strong> ? S'il est déjà utilisé par des charges, la
            suppression sera refusée — retirez-le plutôt du service.
          </>
        }
        confirmLabel="Supprimer"
        danger
        isPending={supprimer.isPending}
        onConfirm={() => aSupprimer && supprimer.mutate(aSupprimer.id)}
        onCancel={() => setASupprimer(null)}
      />
    </div>
  )
}
