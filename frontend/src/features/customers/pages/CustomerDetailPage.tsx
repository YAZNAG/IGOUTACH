import { useQuery } from '@tanstack/react-query'
import { ArrowLeft } from 'lucide-react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { SearchInput } from '@/components/ui/SearchInput'
import { useRechercheLocale } from '@/hooks/useRechercheLocale'
import { EntityActivityCard } from '@/features/dashboard/components/EntityActivityCard'
import { api } from '@/lib/api'
import { formatDateHeure } from '@/lib/utils'
import {
  fetchCustomer,
  fetchCustomerStatement,
  type Customer,
  type CustomerStatement,
} from '../api/customersApi'

interface SaleRow {
  id: number
  reference: string
  type: string
  status: string
  warehouse: string | null
  total: number
  paid_amount: number
  payment_status: string
  created_at: string | null
}

interface SaleList {
  data: SaleRow[]
  meta: { total: number }
}

const SALE_STATUS: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  draft: { label: 'Brouillon', tone: 'neutral' },
  confirmed: { label: 'Confirmée', tone: 'ok' },
  cancelled: { label: 'Annulée', tone: 'bad' },
}

const PAY_STATUS: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' }> = {
  paid: { label: 'Payé', tone: 'ok' },
  partial: { label: 'Partiel', tone: 'warn' },
  unpaid: { label: 'Non payé', tone: 'bad' },
}

const LEDGER_TYPES: Record<string, { label: string; tone: 'ok' | 'warn' | 'bad' | 'sky' | 'neutral' }> = {
  sale: { label: 'Vente à crédit', tone: 'warn' },
  invoice: { label: 'Facture', tone: 'warn' },
  payment: { label: 'Règlement', tone: 'ok' },
  refund: { label: 'Remboursement', tone: 'sky' },
  adjustment: { label: 'Ajustement', tone: 'neutral' },
  cancel: { label: 'Annulation', tone: 'sky' },
}

function formatMoney(value: number): string {
  return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const customerId = id ? Number(id) : 0

  const { data: customer, isLoading, isError } = useQuery<Customer>({
    queryKey: ['customer', customerId],
    queryFn: () => fetchCustomer(customerId),
    enabled: customerId > 0,
  })

  const { data: statement } = useQuery<CustomerStatement>({
    queryKey: ['customer-statement', customerId],
    queryFn: () => fetchCustomerStatement(customerId),
    enabled: customerId > 0,
  })

  const { data: sales } = useQuery<SaleList>({
    queryKey: ['customer-sales', customerId],
    queryFn: async () => {
      const { data } = await api.get<SaleList>('/sales', { params: { customer_id: customerId } })
      return data
    },
    enabled: customerId > 0,
  })

  // Les deux recherches sont declarees AVANT tout retour anticipe : un hook
  // place apres le « if (isLoading) » n'est pas appele au premier rendu, et
  // React refuse le rendu suivant ou il l'est. La page tombait sur un acces
  // direct a l'URL, quand aucune donnee n'etait encore en cache.
  const releve = useRechercheLocale(statement?.entries ?? [], (e) => [e.type, e.note, e.date])
  const ventes = useRechercheLocale(sales?.data ?? [], (s) => [
    s.reference,
    s.warehouse,
    s.status,
    s.payment_status,
    s.created_at,
  ])

  if (isLoading) {
    return (
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/clients')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <p className="text-sm text-muted">Chargement…</p>
      </div>
    )
  }

  if (isError || !customer) {
    return (
      <div className="space-y-4">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={() => navigate('/clients')}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <h1 className="text-xl font-semibold text-ink">Client introuvable</h1>
        </div>
        <p className="rounded border border-line bg-bad-bg px-4 py-3 text-sm text-bad">
          Ce client n'existe pas, ou vous n'avez pas le droit de le consulter.
        </p>
      </div>
    )
  }

  // L'encours qui fait foi est celui des factures non soldees, servi par le
  // releve. La fiche client porte le meme montant, mais le releve donne en
  // plus sa ventilation par lieu.
  const encours = statement?.balance ?? customer.balance
  const parLieu = statement?.by_warehouse ?? []
  const cadreSurUnLieu = statement != null && statement.scoped_warehouse_id !== null
  const plafond = customer.credit_limit
  const sansPlafond = plafond <= 0
  const horsPlafond = !sansPlafond && encours > plafond
  const disponible = Math.max(plafond - encours, 0)

  // Le grand-livre s'ecarte du du sur factures des qu'un reglement n'a ete
  // impute a aucune facture. L'ecart est un signal, pas un detail : il se dit.
  const soldeComptable = statement?.ledger_balance
  const ecartComptable =
    soldeComptable !== undefined && Math.abs(soldeComptable - encours) >= 0.01

  const impayees = (sales?.data ?? []).filter(
    (s) => s.status === 'confirmed' && s.payment_status !== 'paid',
  ).length

  return (
    <div className="space-y-6">
      {/* En-tête */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={() => navigate('/clients')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-xl font-semibold text-ink">
              <span className="mono text-muted">{customer.code}</span> · {customer.name}
            </h1>
            {customer.is_company ? <Badge tone="neutral">Entreprise</Badge> : null}
            {customer.is_blocked ? (
              <Badge tone="bad">Bloqué</Badge>
            ) : customer.is_active ? (
              <Badge tone="ok">Actif</Badge>
            ) : (
              <Badge tone="neutral">Inactif</Badge>
            )}
          </div>
          <p className="text-sm text-muted">
            {[customer.contact_name, customer.phone, customer.email, customer.city]
              .filter(Boolean)
              .join(' · ') || '—'}
          </p>
        </div>
      </div>

      {/* Cartes crédit */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">
              {cadreSurUnLieu ? 'Encours sur vos ventes' : 'Encours (factures non soldées)'}
            </p>
            <p
              className={`text-2xl font-semibold ${
                horsPlafond ? 'text-bad' : encours > 0 ? 'text-warn' : 'text-ok'
              }`}
            >
              {formatMoney(encours)} DH
            </p>
            {horsPlafond ? <p className="text-xs text-bad">Plafond dépassé</p> : null}
            {ecartComptable ? (
              <p className="mt-1 text-xs text-muted">
                Solde comptable : {formatMoney(soldeComptable!)} DH — l'écart vient de règlements
                non imputés à une facture.
              </p>
            ) : null}
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Plafond de crédit</p>
            {sansPlafond ? (
              <>
                <p className="text-2xl font-semibold text-muted">—</p>
                <p className="text-xs text-warn">Aucun plafond fixé</p>
              </>
            ) : (
              <p className="text-2xl font-semibold text-ink">{formatMoney(plafond)} DH</p>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Crédit disponible</p>
            {sansPlafond ? (
              <>
                <p className="text-2xl font-semibold text-muted">—</p>
                <p className="text-xs text-muted">Sans plafond, rien à décompter.</p>
              </>
            ) : (
              <p
                className={`text-2xl font-semibold ${disponible <= 0 ? 'text-bad' : 'text-ink'}`}
              >
                {formatMoney(disponible)} DH
              </p>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <p className="text-xs font-medium text-muted">Documents de vente</p>
            <p className="text-2xl font-semibold text-ink">{sales?.meta.total ?? 0}</p>
            {impayees > 0 ? (
              <p className="text-xs text-warn">
                dont {impayees} non soldé{impayees > 1 ? 's' : ''}
              </p>
            ) : null}
          </CardBody>
        </Card>
      </div>

      {/* La dette, point de vente par point de vente. Sans cette ventilation,
          un encours global ne dit pas qui doit relancer le client. */}
      {parLieu.length > 1 ? (
        <Card>
          <CardHeader title="Où la créance a été laissée" />
          <CardBody className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 text-right font-medium">Factures</th>
                    <th className="px-5 py-3 text-right font-medium">Reste dû</th>
                  </tr>
                </thead>
                <tbody>
                  {parLieu.map((l) => (
                    <tr key={l.code} className="border-b border-line last:border-0">
                      <td className="mono px-5 py-3 text-ink">{l.code}</td>
                      <td className="tabular px-5 py-3 text-right text-muted">{l.invoices}</td>
                      <td className="tabular px-5 py-3 text-right font-medium text-warn">
                        {formatMoney(l.due)} DH
                      </td>
                    </tr>
                  ))}
                  <tr className="bg-bg">
                    <td className="px-5 py-3 font-medium text-ink">Total</td>
                    <td className="tabular px-5 py-3 text-right text-muted">
                      {parLieu.reduce((n, l) => n + l.invoices, 0)}
                    </td>
                    <td className="tabular px-5 py-3 text-right font-semibold text-ink">
                      {formatMoney(parLieu.reduce((n, l) => n + l.due, 0))} DH
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>
      ) : null}

      {/* Informations */}
      <Card>
        <CardHeader title="Informations" />
        <CardBody>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
              <p className="text-xs font-medium text-muted">Adresse</p>
              <p className="text-sm text-ink">
                {[customer.address, customer.city].filter(Boolean).join(', ') || '—'}
              </p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">ICE</p>
              <p className="mono text-sm text-ink">{customer.ice ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Type de prix</p>
              <p className="text-sm text-ink">{customer.price_type ?? 'Détail (défaut)'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Lieu de rattachement</p>
              <p className="mono text-sm text-ink">{customer.warehouse ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Créé par</p>
              <p className="text-sm text-ink">{customer.created_by ?? '—'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-muted">Vendeur attitré</p>
              <p className="text-sm text-ink">{customer.seller ?? '—'}</p>
            </div>
          </div>
          {customer.notes ? (
            <div className="mt-4 rounded-lg border border-line bg-bg p-3">
              <p className="mb-1 text-xs font-medium text-muted">Notes</p>
              <p className="whitespace-pre-wrap text-sm text-ink">{customer.notes}</p>
            </div>
          ) : null}
        </CardBody>
      </Card>

      {/* Ce que ce client pèse, et depuis quand. Le relevé dit ce qu'il doit ;
          ceci dit ce qu'il rapporte. */}
      <EntityActivityCard
        path={`/customers/${customerId}/stats`}
        measureKey="revenue"
        title="Chiffre d'affaires du client"
        countLabel="facture"
      />

      {/* Historique des sorties (ventes) */}
      <Card>
        <CardHeader
          title="Historique des sorties (ventes)"
          hint={
            sales
              ? ventes.actif
                ? `${ventes.resultats.length} sur ${sales.meta.total} document(s)`
                : `${sales.meta.total} document(s)`
              : undefined
          }
          action={
            <SearchInput
              value={ventes.terme}
              onChange={ventes.setTerme}
              placeholder="Référence, lieu, statut…"
            />
          }
        />
        <CardBody className="p-0">
          {ventes.resultats.length === 0 ? (
            <p className="p-5 text-center text-sm text-muted">
              {ventes.actif
                ? 'Aucune vente ne correspond à cette recherche.'
                : 'Aucune vente pour ce client.'}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Référence</th>
                    <th className="px-5 py-3 font-medium">Date et heure</th>
                    <th className="px-5 py-3 font-medium">Type</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 font-medium">Statut</th>
                    <th className="px-5 py-3 text-right font-medium">Total</th>
                    <th className="px-5 py-3 text-right font-medium">Payé</th>
                    <th className="px-5 py-3 text-right font-medium">Reste</th>
                    <th className="px-5 py-3 font-medium">Règlement</th>
                  </tr>
                </thead>
                <tbody>
                  {ventes.resultats.map((s) => {
                    const status = SALE_STATUS[s.status] ?? { label: s.status, tone: 'neutral' as const }
                    const pay = PAY_STATUS[s.payment_status] ?? {
                      label: s.payment_status,
                      tone: 'warn' as const,
                    }
                    // Une vente annulee ne doit rien : afficher son reste
                    // laisserait croire a une creance a recouvrer.
                    const reste =
                      s.status === 'cancelled' ? 0 : Math.max(s.total - s.paid_amount, 0)

                    return (
                      <tr key={s.id} className="border-b border-line last:border-0">
                        <td className="mono px-5 py-3 font-medium text-ink">{s.reference}</td>
                        <td className="px-5 py-3 text-muted">{formatDateHeure(s.created_at)}</td>
                        <td className="px-5 py-3 text-muted">
                          {s.type === 'invoice' ? 'Facture' : s.type === 'ticket' ? 'Ticket' : s.type}
                        </td>
                        <td className="mono px-5 py-3 text-muted">{s.warehouse ?? '—'}</td>
                        <td className="px-5 py-3">
                          <Badge tone={status.tone}>{status.label}</Badge>
                        </td>
                        <td className="tabular px-5 py-3 text-right font-medium text-ink">
                          {formatMoney(s.total)} DH
                        </td>
                        <td className="tabular px-5 py-3 text-right text-muted">
                          {formatMoney(s.paid_amount)} DH
                        </td>
                        <td
                          className={`tabular px-5 py-3 text-right font-medium ${
                            reste > 0 ? 'text-warn' : 'text-muted'
                          }`}
                        >
                          {reste > 0 ? `${formatMoney(reste)} DH` : '—'}
                        </td>
                        <td className="px-5 py-3">
                          <Badge tone={pay.tone}>{pay.label}</Badge>
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {/* Relevé de compte : crédits et règlements */}
      <Card>
        <CardHeader
          title="Relevé de compte — crédits et règlements"
          hint={
            statement
              ? cadreSurUnLieu
                ? `Vos ventes · encours ${formatMoney(encours)} DH`
                : `Encours actuel : ${formatMoney(encours)} DH`
              : undefined
          }
          action={
            <SearchInput
              value={releve.terme}
              onChange={releve.setTerme}
              placeholder="Opération, note, date…"
            />
          }
        />
        <CardBody className="p-0">
          {releve.resultats.length === 0 ? (
            <p className="p-5 text-center text-sm text-muted">
              {releve.actif
                ? 'Aucune écriture ne correspond à cette recherche.'
                : "Aucune écriture — ce client n'a pas d'historique de crédit."}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Date et heure</th>
                    <th className="px-5 py-3 font-medium">Opération</th>
                    <th className="px-5 py-3 font-medium">Document</th>
                    <th className="px-5 py-3 text-right font-medium">Montant</th>
                    <th className="px-5 py-3 text-right font-medium">Encours après</th>
                  </tr>
                </thead>
                <tbody>
                  {releve.resultats.map((e, i) => {
                    const type = LEDGER_TYPES[e.type] ?? { label: e.type, tone: 'neutral' as const }
                    return (
                      <tr key={i} className="border-b border-line last:border-0">
                        <td className="px-5 py-3 text-muted">{formatDateHeure(e.date)}</td>
                        <td className="px-5 py-3">
                          <Badge tone={type.tone}>{type.label}</Badge>
                        </td>
                        <td className="mono px-5 py-3 text-muted">{e.note ?? '—'}</td>
                        <td
                          className={`tabular px-5 py-3 text-right font-medium ${
                            e.amount > 0 ? 'text-warn' : 'text-ok'
                          }`}
                        >
                          {e.amount > 0 ? '+' : ''}
                          {formatMoney(e.amount)} DH
                        </td>
                        <td className="tabular px-5 py-3 text-right text-ink">
                          {formatMoney(e.balance_after)} DH
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      <p className="text-xs text-faint">
        Les règlements clients s'enregistrent depuis{' '}
        <Link to="/reglements" className="text-sky hover:underline">
          Règlements
        </Link>
        .
      </p>
    </div>
  )
}
