import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  createRecurringExpense,
  deleteRecurringExpense,
  fetchOccurrences,
  fetchPendingOccurrences,
  fetchRecurringExpenses,
  payOccurrence,
  updateRecurringExpense,
  type PayOccurrenceInput,
  type RecurringExpense,
  type RecurringExpenseInput,
  type RecurringOccurrence,
} from './api/recurringApi'

const KEY = ['recurring-expenses'] as const
const PENDING_KEY = ['recurring-expenses', 'pending'] as const

export function useRecurringExpenses() {
  return useQuery<RecurringExpense[]>({ queryKey: KEY, queryFn: fetchRecurringExpenses })
}

export function usePendingOccurrences() {
  return useQuery({ queryKey: PENDING_KEY, queryFn: fetchPendingOccurrences })
}

export function useOccurrences(id: number) {
  return useQuery<RecurringOccurrence[]>({
    queryKey: [...KEY, id, 'occurrences'],
    queryFn: () => fetchOccurrences(id),
    enabled: id > 0,
  })
}

/** Toute écriture peut faire bouger les échéances dues et les alertes. */
function useRafraichir() {
  const queryClient = useQueryClient()
  return () => {
    queryClient.invalidateQueries({ queryKey: KEY })
    queryClient.invalidateQueries({ queryKey: ['alerts'] })
    queryClient.invalidateQueries({ queryKey: ['expenses'] })
  }
}

export function useCreateRecurringExpense() {
  const rafraichir = useRafraichir()
  return useMutation({ mutationFn: createRecurringExpense, onSuccess: rafraichir })
}

export function useUpdateRecurringExpense() {
  const rafraichir = useRafraichir()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: RecurringExpenseInput }) =>
      updateRecurringExpense(id, input),
    onSuccess: rafraichir,
  })
}

export function useDeleteRecurringExpense() {
  const rafraichir = useRafraichir()
  return useMutation({ mutationFn: deleteRecurringExpense, onSuccess: rafraichir })
}

export function usePayOccurrence() {
  const rafraichir = useRafraichir()
  return useMutation({
    mutationFn: ({ id, input }: { id: number; input: PayOccurrenceInput }) => payOccurrence(id, input),
    onSuccess: rafraichir,
  })
}
