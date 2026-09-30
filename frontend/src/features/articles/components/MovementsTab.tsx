import { useState } from 'react'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Field } from '@/components/ui/Field'
import { Input } from '@/components/ui/Input'
import { SearchInput } from '@/components/ui/SearchInput'
import { Select } from '@/components/ui/Select'
import { useWarehouseOptions } from '@/features/access/hooks'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { formatDateHeure, formatNumber } from '@/lib/utils'
import type { Movement, MovementSummaryRow } from '../api/articlesApi'
import { useProductMovements } from '../hooks'

const TYPE_LABELS: Record<string, string> = {
  in: 'Entrée',
  entry: 'Entrée',
  out: 'Sortie',
  exit: 'Sortie',
  transfer_out: 'Transfert sortant',
  transfer_in: 'Transfert entrant',
  inventory: 'Inventaire',
  adjustment: 'Ajustement',
  reservation: 'Réservation',
  unreservation: 'Déréservation',
  return_in: 'Retour entrant',
  return_out: 'Retour sortant',
}

const TYPE_TONES: Record<string, 'ok' | 'bad' | 'warn' | 'sky'> = {
  in: 'ok',
  entry: 'ok',
  out: 'bad',
  exit: 'bad',
  transfer_out: 'warn',
  transfer_in: 'sky',
  inventory: 'sky',
  adjustment: 'sky',
  reservation: 'warn',
  unreservation: 'ok',
  return_in: 'ok',
  return_out: 'bad',
}

/** Types proposés au filtre, dans l'ordre où ils se rencontrent. */
const TYPES_FILTRABLES = [
  'in',
  'out',
  'transfer_in',
  'transfer_out',
  'inventory',
  'adjustment',
] as const

function libelleType(code: string | null): string {
  if (code === null) return 'Mouvement'
  return TYPE_LABELS[code] ?? code
}

/** Récapitulatif par magasin, calculé côté serveur sur tout l'historique. */
function RecapMagasins({ lignes }: { lignes: MovementSummaryRow[] }) {
  if (lignes.length === 0) return null

  return (
    <Card>
      <CardHeader
        title="Par magasin"
        hint="Totaux calculés sur l'ensemble de l'historique, pas sur la page affichée."
      />
      <CardBody className="p-0">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-muted">
                <th className="px-5 py-3 font-medium">Lieu</th>
                <th className="px-5 py-3 text-right font-medium">Mouvements</th>
                <th className="px-5 py-3 text-right font-medium">Entrées</th>
                <th className="px-5 py-3 text-right font-medium">Sorties</th>
                <th className="px-5 py-3 text-right font-medium">Solde</th>
                <th className="px-5 py-3 font-medium">Dernier mouvement</th>
              </tr>
            </thead>
            <tbody>
              {lignes.map((l) => (
                <tr key={l.warehouse_code ?? 'sans-lieu'} className="border-b border-line last:border-0">
                  <td className="px-5 py-3 text-ink">
                    {l.warehouse_code ?? <span className="text-faint">Sans lieu</span>}
                    {l.warehouse_name ? (
                      <span className="ml-2 text-xs text-faint">{l.warehouse_name}</span>
                    ) : null}
                  </td>
                  <td className="tabular px-5 py-3 text-right text-muted">{l.movements}</td>
                  <td className="tabular px-5 py-3 text-right text-ok">+{formatNumber(l.entries)}</td>
                  <td className="tabular px-5 py-3 text-right text-bad">−{formatNumber(l.exits)}</td>
                  <td className="tabular px-5 py-3 text-right font-semibold text-ink">
                    {formatNumber(l.balance)}
                  </td>
                  <td className="px-5 py-3 text-faint">{formatDateHeure(l.last_movement)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardBody>
    </Card>
  )
}

function LigneJournal({ m }: { m: Movement }) {
  const sortant = m.quantity < 0

  return (
    <tr className="border-b border-line last:border-0">
      <td className="whitespace-nowrap px-5 py-3 text-muted">{formatDateHeure(m.created_at)}</td>
      <td className="px-5 py-3 text-ink">
        {m.warehouse_code ?? m.warehouse_name ?? <span className="text-faint">Sans lieu</span>}
      </td>
      <td className="px-5 py-3">
        <Badge tone={(m.type ? TYPE_TONES[m.type] : undefined) ?? 'sky'} className="text-xs">
          {libelleType(m.type)}
        </Badge>
      </td>
      {/* Le signe vient de la quantite elle-meme, qui est deja signee en
          base : le deduire du type se tromperait sur un ajustement negatif. */}
      <td className={`tabular px-5 py-3 text-right font-medium ${sortant ? 'text-bad' : 'text-ok'}`}>
        {sortant ? '−' : '+'}
        {formatNumber(Math.abs(m.quantity))}
      </td>
      <td className="tabular px-5 py-3 text-right text-ink">{formatNumber(m.balance_after)}</td>
      <td className="px-5 py-3 text-muted">
        {m.document?.label ?? <span className="text-faint">—</span>}
      </td>
      {/* Sans auteur connu, on le dit : un blanc laisserait croire a un
          defaut d'affichage. Un import initial n'a pas d'auteur. */}
      <td className="px-5 py-3 text-ink">
        {m.user?.name ?? <span className="text-faint">Non attribué</span>}
      </td>
    </tr>
  )
}

/**
 * Historique des mouvements d'un article.
 *
 * Le journal est chronologique et paginé par le serveur. Les filtres partent
 * eux aussi au serveur : les appliquer sur la page affichée ne fouillerait
 * que les cinquante dernières lignes et laisserait croire qu'un mouvement
 * n'existe pas.
 */
export function MovementsTab({ productId }: { productId: number }) {
  const [page, setPage] = useState(1)
  const [type, setType] = useState('')
  const [lieu, setLieu] = useState(0)
  const [du, setDu] = useState('')
  const [recherche, setRecherche] = useState('')

  const rechercheRetardee = useDebouncedValue(recherche, 300)
  const { data: lieux = [] } = useWarehouseOptions()

  const { data, isLoading, isError } = useProductMovements(productId, {
    page,
    per_page: 50,
    type: type || undefined,
    warehouse_id: lieu || undefined,
    date_from: du || undefined,
  })

  const mouvements = data?.data ?? []
  const meta = data?.meta
  const recap = meta?.summary ?? []

  // La recherche libre n'a pas d'equivalent serveur sur cet endpoint : elle
  // affine la page affichee. Le libelle du champ le dit, pour qu'on ne la
  // prenne pas pour une recherche sur tout l'historique.
  const terme = rechercheRetardee.trim().toLowerCase()
  const visibles =
    terme === ''
      ? mouvements
      : mouvements.filter((m) =>
          [m.document?.label, m.user?.name, libelleType(m.type), m.warehouse_code, m.note]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(terme),
        )

  function appliquer(action: () => void) {
    action()
    setPage(1)
  }

  const filtreActif = type !== '' || lieu > 0 || du !== ''

  return (
    <div className="space-y-4">
      <RecapMagasins lignes={recap} />

      <Card>
        <CardBody className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Field label="Chercher dans la page" htmlFor="mvt-recherche">
            <SearchInput
              id="mvt-recherche"
              value={recherche}
              onChange={setRecherche}
              placeholder="Document, auteur, lieu…"
              className="w-full"
            />
          </Field>

          <Field label="Type" htmlFor="mvt-type">
            <Select
              id="mvt-type"
              value={type}
              onChange={(e) => appliquer(() => setType(e.target.value))}
            >
              <option value="">Tous les types</option>
              {TYPES_FILTRABLES.map((t) => (
                <option key={t} value={t}>
                  {TYPE_LABELS[t]}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Lieu" htmlFor="mvt-lieu">
            <Select
              id="mvt-lieu"
              value={lieu}
              onChange={(e) => appliquer(() => setLieu(Number(e.target.value)))}
            >
              <option value={0}>Tous les lieux</option>
              {lieux.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.code} · {w.name}
                </option>
              ))}
            </Select>
          </Field>

          <Field label="Depuis le" htmlFor="mvt-du">
            <Input
              id="mvt-du"
              type="date"
              value={du}
              onChange={(e) => appliquer(() => setDu(e.target.value))}
            />
          </Field>

          <div className="flex items-end">
            <Button
              variant="ghost"
              className="w-full"
              disabled={!filtreActif}
              onClick={() =>
                appliquer(() => {
                  setType('')
                  setLieu(0)
                  setDu('')
                })
              }
            >
              Effacer les filtres
            </Button>
          </div>
        </CardBody>
      </Card>

      <Card>
        <CardHeader
          title="Historique des mouvements"
          hint={
            meta
              ? `${meta.total} mouvement(s)${filtreActif ? ' (filtrés)' : ''} — du plus récent au plus ancien`
              : undefined
          }
        />
        <CardBody className="p-0">
          {isError ? (
            <p className="p-5 text-sm text-bad">Impossible de charger les mouvements.</p>
          ) : isLoading ? (
            <p className="p-5 text-sm text-muted">Chargement…</p>
          ) : visibles.length === 0 ? (
            <p className="p-8 text-center text-sm text-muted">
              {mouvements.length > 0
                ? 'Aucun mouvement de cette page ne correspond à la recherche.'
                : filtreActif
                  ? 'Aucun mouvement pour ces filtres.'
                  : 'Aucun mouvement enregistré pour cet article.'}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-line text-left text-muted">
                    <th className="px-5 py-3 font-medium">Date et heure</th>
                    <th className="px-5 py-3 font-medium">Lieu</th>
                    <th className="px-5 py-3 font-medium">Type</th>
                    <th className="px-5 py-3 text-right font-medium">Qté</th>
                    <th className="px-5 py-3 text-right font-medium">Solde</th>
                    <th className="px-5 py-3 font-medium">Document</th>
                    <th className="px-5 py-3 font-medium">Auteur</th>
                  </tr>
                </thead>
                <tbody>
                  {visibles.map((m) => (
                    <LigneJournal key={m.id} m={m} />
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {meta && meta.last_page > 1 ? (
        <div className="flex items-center justify-end gap-2 text-sm text-muted">
          <span>
            Page {meta.current_page} / {meta.last_page}
          </span>
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            Précédent
          </Button>
          <Button
            variant="outline"
            size="sm"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Suivant
          </Button>
        </div>
      ) : null}
    </div>
  )
}
