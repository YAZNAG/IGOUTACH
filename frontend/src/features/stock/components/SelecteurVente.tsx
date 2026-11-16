import { useQuery } from '@tanstack/react-query'
import { useEffect } from 'react'
import { Select } from '@/components/ui/Select'
import { api } from '@/lib/api'
import { formatCurrency } from '@/lib/utils'

/** Une vente qui contient l'article, et ce qu'il reste à retourner dessus. */
export interface VenteRetournable {
  sale_id: number
  sale_line_id: number
  reference: string | null
  date: string | null
  customer: string
  customer_code: string | null
  warehouse_id: number | null
  warehouse: string | null
  quantity: number
  unit_price: number
  already_returned: number
  returnable: number
  payment_status: string | null
}

interface Props {
  productId: number
  warehouseId: number
  /** Ligne de vente choisie, ou null pour un retour sans vente rattachée. */
  value: number | null
  onChange: (vente: VenteRetournable | null) => void
  id?: string
}

export function useVentesRetournables(productId: number, warehouseId: number) {
  return useQuery<VenteRetournable[]>({
    queryKey: ['customer-returns-sales', productId, warehouseId],
    queryFn: async () => {
      const { data } = await api.get<{ data: VenteRetournable[] }>('/customer-returns/sales', {
        params: { product_id: productId, warehouse_id: warehouseId || undefined },
      })
      return data.data
    },
    enabled: productId > 0,
  })
}

function dateCourte(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso.replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString('fr-FR')
}

/**
 * La vente d'où revient l'article.
 *
 * Le même article se vend à des prix différents selon le client et le jour :
 * le retour doit dire laquelle revient, pour rembourser au bon prix et
 * retirer la bonne somme du bénéfice. La plus récente est proposée d'office.
 */
export function SelecteurVente({ productId, warehouseId, value, onChange, id }: Props) {
  const { data: ventes = [], isLoading } = useVentesRetournables(productId, warehouseId)

  // Dès que les ventes arrivent, la plus récente encore retournable est
  // choisie : c'est le cas courant, et il ne demande aucun clic.
  useEffect(() => {
    if (value !== null || ventes.length === 0) return
    const premiere = ventes.find((v) => v.returnable > 0)
    if (premiere) onChange(premiere)
  }, [ventes, value, onChange])

  if (isLoading) {
    return <span className="text-xs text-muted">Recherche des ventes…</span>
  }

  if (ventes.length === 0) {
    return (
      <span className="text-xs text-warn">
        Aucune vente confirmée ne contient cet article : le retour sera enregistré sans vente rattachée.
      </span>
    )
  }

  return (
    <Select
      id={id}
      value={value ?? ''}
      onChange={(e) => {
        const choisi = ventes.find((v) => v.sale_line_id === Number(e.target.value)) ?? null
        onChange(choisi)
      }}
      className="w-full sm:w-[26rem]"
      aria-label="Vente d'origine"
    >
      <option value="">— Sans vente rattachée —</option>
      {ventes.map((v) => (
        <option key={v.sale_line_id} value={v.sale_line_id} disabled={v.returnable === 0}>
          {v.reference} · {dateCourte(v.date)} · {v.customer} · {v.quantity} × {formatCurrency(v.unit_price)}
          {v.returnable === 0 ? ' · tout retourné' : v.already_returned > 0 ? ` · reste ${v.returnable}` : ''}
        </option>
      ))}
    </Select>
  )
}
