import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Inbox, X } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Input } from '@/components/ui/Input'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatDateHeure } from '@/lib/utils'
import type { Paginated } from '@/types'

interface DemandeRow {
  id: number
  reference: string
  from: string | null
  to: string | null
  lines_count: number
  products: { product_id: number; sku: string | null; name: string | null; quantity: number }[]
  requested_by: string | null
  requested_at: string | null
  created_at: string | null
  note: string | null
  can_arbitrate: boolean
}

/** Cle partagee : accorder une demande doit rafraichir aussi la liste des transferts. */
export const CLE_DEMANDES = ['transfers', 'requested'] as const

function messageErreur(e: unknown, repli: string): string {
  const r = (e as { response?: { data?: { message?: string } } })?.response
  return r?.data?.message ?? repli
}

/**
 * Demandes de reapprovisionnement envoyees par les responsables de lieu.
 *
 * Elles n'etaient visibles que dans l'application mobile : la direction, qui
 * travaille sur le site, ne savait meme pas qu'une demande attendait. Ce bloc
 * les expose la ou elle regarde — le tableau de bord et la page des
 * transferts — avec de quoi trancher sur place.
 *
 * `compact` : version tableau de bord, qui s'efface quand rien n'attend
 * plutot que d'occuper la page avec un bloc vide.
 */
export function DemandesEnAttente({ compact = false }: { compact?: boolean }) {
  const qc = useQueryClient()

  const { data, isLoading } = useQuery<Paginated<DemandeRow>>({
    queryKey: [...CLE_DEMANDES],
    queryFn: async () => {
      const { data: r } = await api.get<Paginated<DemandeRow>>('/transfers', {
        params: { status: 'requested' },
      })
      return r
    },
    // Une demande arrive a tout moment depuis un telephone : on revient voir
    // toutes les minutes, sans attendre que l'utilisateur recharge.
    refetchInterval: 60_000,
  })

  const [aAccorder, setAAccorder] = useState<DemandeRow | null>(null)
  const [quantites, setQuantites] = useState<Record<number, string>>({})
  const [aRefuser, setARefuser] = useState<DemandeRow | null>(null)
  const [motif, setMotif] = useState('')
  const [erreur, setErreur] = useState<string | null>(null)

  const rafraichir = () => {
    qc.invalidateQueries({ queryKey: ['transfers'] })
  }

  const accorder = useMutation({
    mutationFn: async (d: DemandeRow) => {
      await ensureCsrfCookie()
      // Les quantites saisies dans la boite : celui qui fournit peut ceder
      // moins que demande sans refuser tout le bon.
      const lignes = d.products.map((p, i) => ({
        product_id: p.product_id,
        quantity: Math.max(0, Math.floor(Number(quantites[i] ?? p.quantity) || 0)),
      }))
      await api.post(`/transfers/${d.id}/approve`, { lines: lignes })
    },
    onSuccess: () => {
      setAAccorder(null)
      setErreur(null)
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'Accord impossible.')),
  })

  const refuser = useMutation({
    mutationFn: async (d: DemandeRow) => {
      await ensureCsrfCookie()
      await api.post(`/transfers/${d.id}/refuse`, { reason: motif.trim() || undefined })
    },
    onSuccess: () => {
      setARefuser(null)
      setMotif('')
      setErreur(null)
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'Refus impossible.')),
  })

  const demandes = data?.data ?? []
  const total = data?.meta?.total ?? demandes.length

  if (compact && !isLoading && demandes.length === 0) return null

  return (
    <>
      <Card>
        <CardHeader
          title="Demandes de stock en attente"
          hint={
            total > 0
              ? `${total} demande${total > 1 ? 's' : ''} envoyée${total > 1 ? 's' : ''} par les responsables de lieu`
              : 'Aucune demande en attente'
          }
          action={
            compact && total > 0 ? (
              <Link to="/transferts" className="text-sm text-sky hover:underline">
                Tous les transferts
              </Link>
            ) : undefined
          }
        />
        <CardBody className="p-0">
          {isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : demandes.length === 0 ? (
            <div className="flex items-center gap-3 p-5 text-sm text-muted">
              <Inbox className="h-5 w-5 text-faint" />
              Aucune demande n’attend de réponse.
            </div>
          ) : (
            <ul className="divide-y divide-line">
              {(compact ? demandes.slice(0, 5) : demandes).map((d) => (
                <li key={d.id} className="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
                  <div className="min-w-0 flex-1 space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge tone="warn">En attente</Badge>
                      <span className="mono text-xs text-muted">{d.reference}</span>
                      <span className="text-sm font-medium text-ink">
                        {d.from} → {d.to}
                      </span>
                    </div>
                    <p className="text-sm text-muted">
                      {d.requested_by ?? 'Responsable'} · {formatDateHeure(d.requested_at ?? d.created_at)}
                    </p>
                    <p className="text-sm text-ink">
                      {d.products.map((p, i) => (
                        <span key={i}>
                          {i > 0 ? ', ' : ''}
                          {p.name ?? p.sku ?? '—'}
                          <span className="text-faint"> ×{p.quantity}</span>
                        </span>
                      ))}
                    </p>
                    {d.note ? <p className="text-sm italic text-muted">« {d.note} »</p> : null}
                  </div>

                  {d.can_arbitrate ? (
                    <div className="flex shrink-0 gap-2">
                      <Button
                        size="sm"
                        onClick={() => {
                          setErreur(null)
                          setQuantites(Object.fromEntries(d.products.map((p, i) => [i, String(p.quantity)])))
                          setAAccorder(d)
                        }}
                      >
                        <Check className="h-4 w-4" />
                        Accorder
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                          setErreur(null)
                          setMotif('')
                          setARefuser(d)
                        }}
                      >
                        <X className="h-4 w-4" />
                        Refuser
                      </Button>
                    </div>
                  ) : (
                    <span className="shrink-0 text-xs text-faint">
                      Traitée par la direction ou le lieu qui fournit
                    </span>
                  )}
                </li>
              ))}
            </ul>
          )}
          {compact && demandes.length > 5 ? (
            <p className="border-t border-line px-5 py-3 text-sm text-muted">
              et {demandes.length - 5} autre(s) —{' '}
              <Link to="/transferts" className="text-sky hover:underline">
                voir toutes les demandes
              </Link>
            </p>
          ) : null}
        </CardBody>
      </Card>

      <ConfirmDialog
        open={aAccorder !== null}
        title={`Accorder la demande ${aAccorder?.reference ?? ''}`}
        message={
          aAccorder ? (
            <div className="space-y-3">
              <p className="text-sm text-muted">
                La marchandise quittera <strong className="text-ink">{aAccorder.from}</strong> pour{' '}
                <strong className="text-ink">{aAccorder.to}</strong>. Vous pouvez céder moins que demandé :
                ajustez les quantités, ou mettez 0 pour écarter une ligne.
              </p>
              <div className="space-y-2">
                {aAccorder.products.map((p, i) => (
                  <div key={i} className="flex items-center justify-between gap-3">
                    <span className="text-sm text-ink">
                      {p.name ?? p.sku}
                      <span className="text-faint"> — demandé {p.quantity}</span>
                    </span>
                    <Input
                      type="number"
                      min={0}
                      className="w-24"
                      value={quantites[i] ?? ''}
                      onChange={(e) => setQuantites((q) => ({ ...q, [i]: e.target.value }))}
                    />
                  </div>
                ))}
              </div>
            </div>
          ) : (
            ''
          )
        }
        confirmLabel="Accorder et expédier"
        isPending={accorder.isPending}
        error={erreur}
        onCancel={() => setAAccorder(null)}
        onConfirm={() => aAccorder && accorder.mutate(aAccorder)}
      />

      <ConfirmDialog
        open={aRefuser !== null}
        title={`Refuser la demande ${aRefuser?.reference ?? ''}`}
        danger
        message={
          <div className="space-y-2">
            <p className="text-sm text-muted">
              Rien ne sera expédié. Le motif est transmis au responsable qui a fait la demande.
            </p>
            <Input
              placeholder="Motif (facultatif) — ex. stock réservé à un client"
              value={motif}
              maxLength={255}
              onChange={(e) => setMotif(e.target.value)}
            />
          </div>
        }
        confirmLabel="Refuser"
        isPending={refuser.isPending}
        error={erreur}
        onCancel={() => setARefuser(null)}
        onConfirm={() => aRefuser && refuser.mutate(aRefuser)}
      />
    </>
  )
}
