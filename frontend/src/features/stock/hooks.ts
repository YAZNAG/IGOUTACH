import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  entryStock,
  fetchMatrix,
  fetchMovements,
  fetchMovementTypes,
  fetchStock,
  issueStock,
  type MatrixRow,
  type MatrixWarehouse,
  type MovementFilters,
  type MovementType,
  type StockMeta,
  type StockMovement,
  type StockRow,
} from './api/stockApi'

export function useStock(warehouseId: number | null, q: string, page: number) {
  return useQuery<{ data: StockRow[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>({
    queryKey: ['stock', warehouseId, q, page],
    queryFn: () => fetchStock(warehouseId as number, q, page),
    enabled: warehouseId !== null,
  })
}

export function useMovements(filters: MovementFilters, enabled: boolean) {
  return useQuery<{ data: StockMovement[]; meta: { current_page: number; last_page: number; per_page: number; total: number } }>({
    queryKey: ['stock-movements', filters],
    queryFn: () => fetchMovements(filters),
    enabled,
  })
}

export function useMatrix(q: string, page: number) {
  return useQuery<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }>({
    queryKey: ['stock-matrix', q, page],
    queryFn: () => fetchMatrix(q, page),
  })
}

export function useMovementTypes() {
  return useQuery<MovementType[]>({ queryKey: ['movement-types'], queryFn: fetchMovementTypes, staleTime: 5 * 60_000 })
}

export function useIssueStock() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: issueStock,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['stock-movements'] })
    },
  })
}

export function useEntryStock() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: entryStock,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['stock'] })
      qc.invalidateQueries({ queryKey: ['stock-movements'] })
      qc.invalidateQueries({ queryKey: ['stock-matrix'] })
    },
  })
}
