import { api } from '@/lib/api'

export type ChequeDirection = 'in' | 'out'
export type ChequeOrigin = 'customer' | 'own' | 'third_party'
/** Chèque ou traite : mêmes champs, même cycle. */
export type ChequeInstrument = 'cheque' | 'traite'

export type ChequeStatus = 'portfolio' | 'handed_over' | 'cashed' | 'bounced'

export interface Cheque {
  id: number
  instrument: ChequeInstrument
  number: string
  cheque_date: string
  amount: number
  bank: string | null
  direction: ChequeDirection
  origin: ChequeOrigin
  drawer_name: string | null
  /** Nom effectivement porté sur le chèque, déjà résolu par le serveur. */
  signataire: string
  customer: { id: number; name: string } | null
  supplier: { id: number; name: string } | null
  image_url: string | null
  status: ChequeStatus
  note: string | null
  created_at: string | null
}

export interface ChequeInput {
  /** Par défaut « cheque » côté serveur. */
  instrument?: ChequeInstrument
  number: string
  cheque_date: string
  amount: number
  bank?: string | null
  direction: ChequeDirection
  origin: ChequeOrigin
  drawer_name?: string | null
  customer_id?: number | null
  supplier_id?: number | null
  note?: string | null
  image?: File | null
}

export interface ChequeFilters {
  direction?: ChequeDirection
  status?: ChequeStatus
  customer_id?: number
  /** Ne remonte que les chèques reçus encore mobilisables. */
  endorsable?: boolean
  search?: string
}

export async function fetchCheques(filters: ChequeFilters = {}): Promise<Cheque[]> {
  const { data } = await api.get<{ data: Cheque[] }>('/cheques', {
    params: { ...filters, endorsable: filters.endorsable ? 1 : undefined },
  })
  return data.data
}

export async function createCheque(input: ChequeInput): Promise<Cheque> {
  // L'image impose le multipart ; sans image un JSON simple suffit.
  if (input.image) {
    const form = new FormData()

    for (const [cle, valeur] of Object.entries(input)) {
      if (valeur === null || valeur === undefined || cle === 'image') continue
      form.append(cle, String(valeur))
    }

    form.append('image', input.image)

    const { data } = await api.post<{ data: Cheque }>('/cheques', form)
    return data.data
  }

  const { image: _image, ...reste } = input
  const { data } = await api.post<{ data: Cheque }>('/cheques', reste)
  return data.data
}

export async function endorseCheque(id: number, supplierId: number): Promise<Cheque> {
  const { data } = await api.post<{ data: Cheque }>(`/cheques/${id}/endorse`, {
    supplier_id: supplierId,
  })
  return data.data
}

export async function updateChequeStatus(id: number, status: ChequeStatus): Promise<Cheque> {
  const { data } = await api.patch<{ data: Cheque }>(`/cheques/${id}/status`, { status })
  return data.data
}

export async function deleteCheque(id: number): Promise<void> {
  await api.delete(`/cheques/${id}`)
}
