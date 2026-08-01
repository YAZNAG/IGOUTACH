import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createUnit, deleteUnit, fetchUnits, updateUnit, type Unit, type UnitInput } from './api/unitsApi'

const KEY = ['units'] as const

export function useUnits() {
  return useQuery<Unit[]>({ queryKey: KEY, queryFn: fetchUnits })
}

export function useCreateUnit() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: UnitInput) => createUnit(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useUpdateUnit() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: UnitInput }) => updateUnit(id, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}

export function useDeleteUnit() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteUnit(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: KEY }),
  })
}
