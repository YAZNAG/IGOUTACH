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
  type StockFilters,
  type StockMeta,
  type StockMovement,
  type StockRow,
  type TableParams,
} from './api/stockApi'

export function useStock(warehouseId: number | null, filters: StockFilters) {
  return useQuery<{ data: StockRow[]; meta: StockMeta }>({
    // Chaque critère entre dans la clé : sans cela, changer de tri afficherait
    // le résultat mis en cache de l'ancien.
    queryKey: ['stock', warehouseId, filters],
    queryFn: () => fetchStock(warehouseId as number, filters),
    enabled: warehouseId !== null,
  })
}

export function useMovements(filters: MovementFilters, enabled: boolean) {
  return useQuery<{ data: StockMovement[]; meta: StockMeta }>({
    queryKey: ['stock-movements', filters],
    queryFn: () => fetchMovements(filters),
    enabled,
  })
}

export function useMatrix(q: string, params: TableParams) {
  return useQuery<{ warehouses: MatrixWarehouse[]; data: MatrixRow[]; meta: StockMeta }>({
    queryKey: ['stock-matrix', q, params],
    queryFn: () => fetchMatrix(q, params),
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
