import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Image as ImageIcon, Wallet, X } from 'lucide-react'
import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { api, ensureCsrfCookie } from '@/lib/api'
import { formatCurrency, formatNumber } from '@/lib/utils'

interface CaisseLieu {
  warehouse_id: number
  code: string
  name: string
  session_open: boolean
  opened_at: string | null
  opening: number
  cash_in: number
  cash_expenses: number
  remitted: number
  expected: number
  pending_count: number
  pending_total: number
  last_closed: { closed_at: string | null; closing_amount: number } | null
}

interface Transfert {
  id: number
  reference: string
  warehouse: string | null
  warehouse_name: string | null
  amount: number
  remitted_at: string | null
  created_at: string | null
  status: string
  note: string | null
  created_by: string | null
  received_by: string | null
  received_at: string | null
  proof_url: string | null
  refused_by: string | null
  refused_at: string | null
  refusal_reason: string | null
}

function messageErreur(e: unknown, repli: string): string {
  return (e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? repli
}

/**
 * Les caisses des lieux et les transferts qu'ils annoncent.
 *
 * La direction n'a pas de tiroir : ce qu'elle suit, c'est ce que chaque lieu
 * détient et ce qui lui a été annoncé sans être encore confirmé. Tant qu'un
 * transfert reste en attente, l'argent est « en route » et personne ne sait
 * qui l'a — d'où les deux boutons, ici, sur la page qu'elle regarde.
 */
export function CaissesEtTransferts() {
  const qc = useQueryClient()
  const [motif, setMotif] = useState('')
  const [aRefuser, setARefuser] = useState<Transfert | null>(null)
  const [erreur, setErreur] = useState<string | null>(null)

  const caisses = useQuery<{ data: CaisseLieu[]; meta: { total_expected: number; total_pending: number } }>({
    queryKey: ['caisses-overview'],
    queryFn: async () => {
      const { data } = await api.get('/cash-sessions/overview')
      return data
    },
    refetchInterval: 120_000,
  })

  const transferts = useQuery<{ data: Transfert[] }>({
    queryKey: ['cash-remittances', 'pending'],
    queryFn: async () => {
      const { data } = await api.get('/cash-remittances', { params: { status: 'pending' } })
      return data
    },
    refetchInterval: 60_000,
  })

  const rafraichir = () => {
    qc.invalidateQueries({ queryKey: ['cash-remittances'] })
    qc.invalidateQueries({ queryKey: ['caisses-overview'] })
  }

  const confirmer = useMutation({
    mutationFn: async (t: Transfert) => {
      await ensureCsrfCookie()
      await api.post(`/cash-remittances/${t.id}/receive`)
    },
    onSuccess: () => {
      setErreur(null)
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'Confirmation impossible.')),
  })

  const refuser = useMutation({
    mutationFn: async (t: Transfert) => {
      await ensureCsrfCookie()
      await api.post(`/cash-remittances/${t.id}/refuse`, { reason: motif.trim() || undefined })
    },
    onSuccess: () => {
      setARefuser(null)
      setMotif('')
      setErreur(null)
      rafraichir()
    },
    onError: (e) => setErreur(messageErreur(e, 'Refus impossible.')),
  })

  const lieux = caisses.data?.data ?? []
  const enAttente = transferts.data?.data ?? []
  const totalEnCaisse = caisses.data?.meta.total_expected ?? 0
  const totalEnRoute = caisses.data?.meta.total_pending ?? 0

  return (
    <div className="space-y-4">
      {erreur ? (
        <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
      ) : null}

      <Card>
        <CardHeader
          title="Caisses des lieux"
          hint={
            caisses.isLoading
              ? 'Chargement…'
              : `${formatCurrency(totalEnCaisse)} dans les tiroirs · ${formatCurrency(totalEnRoute)} annoncés et non confirmés`
          }
        />
        <CardBody className="p-0">
          {caisses.isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : lieux.length === 0 ? (
            <p className="p-5 text-sm text-muted">Aucun lieu actif.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 font-medium">Journée</th>
                    <th className="px-5 py-3 text-right font-medium">Fonds</th>
                    <th className="px-5 py-3 text-right font-medium">Entrées</th>
                    <th className="px-5 py-3 text-right font-medium">Charges</th>
                    <th className="px-5 py-3 text-right font-medium">Transféré</th>
                    <th className="px-5 py-3 text-right font-medium">En caisse</th>
                    <th className="px-5 py-3 text-right font-medium">En route</th>
                  </tr>
                </thead>
                <tbody>
                  {lieux.map((l) => (
                    <tr key={l.warehouse_id} className="border-b border-line last:border-0">
                      <td className="px-5 py-3">
                        <span className="font-medium text-ink">{l.code}</span>
                        <span className="block text-xs text-faint">{l.name}</span>
                      </td>
                      <td className="px-5 py-3">
                        {l.session_open ? (
                          <Badge tone="ok">Ouverte {l.opened_at ? `· ${l.opened_at.slice(11)}` : ''}</Badge>
                        ) : (
                          <Badge tone="warn">Fermée</Badge>
                        )}
                      </td>
                      <td className="tabular px-5 py-3 text-right text-muted">{formatNumber(l.opening)}</td>
                      <td className="tabular px-5 py-3 text-right text-ok">+{formatNumber(l.cash_in)}</td>
                      <td className="tabular px-5 py-3 text-right text-bad">−{formatNumber(l.cash_expenses)}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">−{formatNumber(l.remitted)}</td>
                      <td className="tabular px-5 py-3 text-right font-semibold text-ink">
                        {formatNumber(l.expected)}
                      </td>
                      <td className="tabular px-5 py-3 text-right">
                        {l.pending_count > 0 ? (
                          <span className="text-warn">
                            {formatNumber(l.pending_total)}
                            <span className="text-xs text-faint"> ({l.pending_count})</span>
                          </span>
                        ) : (
                          <span className="text-faint">—</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Transferts de caisse à traiter"
          hint={
            enAttente.length > 0
              ? `${enAttente.length} transfert(s) annoncé(s) par les lieux, en attente de votre confirmation`
              : 'Aucun transfert en attente'
          }
        />
        <CardBody className="p-0">
          {transferts.isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : enAttente.length === 0 ? (
            <div className="flex items-center gap-3 p-5 text-sm text-muted">
              <Wallet className="h-5 w-5 text-faint" />
              Aucun transfert n’attend de réponse.
            </div>
          ) : (
            <ul className="divide-y divide-line">
              {enAttente.map((t) => (
                <li key={t.id} className="space-y-3 px-5 py-4">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 flex-1 space-y-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <Badge tone="warn">En attente</Badge>
                        <span className="mono text-xs text-muted">{t.reference}</span>
                        <span className="text-sm font-medium text-ink">
                          {t.warehouse} → Caisse générale
                        </span>
                        <span className="tabular text-base font-semibold text-ink">
                          {formatCurrency(t.amount)}
                        </span>
                      </div>
                      <p className="text-sm text-muted">
                        Remis le {t.remitted_at ?? '—'} par {t.created_by ?? 'un responsable'}
                      </p>
                      {t.note ? <p className="text-sm italic text-muted">« {t.note} »</p> : null}
                      {t.proof_url ? (
                        <a
                          href={t.proof_url}
                          target="_blank"
                          rel="noreferrer"
                          className="inline-flex items-center gap-1 text-sm text-sky hover:underline"
                        >
                          <ImageIcon className="h-4 w-4" />
                          Voir le justificatif
                        </a>
                      ) : (
                        <p className="text-xs text-faint">Aucun justificatif joint.</p>
                      )}
                    </div>

                    <div className="flex shrink-0 gap-2">
                      <Button
                        size="sm"
                        disabled={confirmer.isPending}
                        onClick={() => {
                          setErreur(null)
                          confirmer.mutate(t)
                        }}
                      >
                        <Check className="h-4 w-4" />
                        J’ai reçu
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                          setErreur(null)
                          setMotif('')
                          setARefuser(aRefuser?.id === t.id ? null : t)
                        }}
                      >
                        <X className="h-4 w-4" />
                        Refuser
                      </Button>
                    </div>
                  </div>

                  {aRefuser?.id === t.id ? (
                    <div className="flex flex-wrap items-center gap-2 rounded border border-line bg-surface-2 p-3">
                      <Input
                        value={motif}
                        onChange={(e) => setMotif(e.target.value)}
                        placeholder="Motif : somme non reçue, montant différent…"
                        className="min-w-0 flex-1"
                      />
                      <Button
                        size="sm"
                        variant="outline"
                        className="text-bad"
                        disabled={refuser.isPending}
                        onClick={() => refuser.mutate(t)}
                      >
                        Confirmer le refus
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => setARefuser(null)}>
                        Annuler
                      </Button>
                    </div>
                  ) : null}
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
