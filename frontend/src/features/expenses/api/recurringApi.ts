import { api } from '@/lib/api'

export interface RecurringExpense {
  id: number
  label: string
  amount: number
  day_of_month: number
  start_period: string
  end_period: string | null
  is_active: boolean
  notes: string | null
  category: { id: number; name: string } | null
  warehouse: { id: number; code: string; name: string } | null
  /** Échéances non réglées, échues ou à venir. */
  pending_count: number
}

export interface RecurringOccurrence {
  id: number
  recurring_expense_id: number
  label: string | null
  period: string
  due_date: string
  amount: number
  status: 'pending' | 'paid' | 'skipped'
  is_overdue: boolean
  paid_at: string | null
  payment_method: { id: number; name: string } | null
  warehouse: { id: number; code: string; name: string } | null
  category: { id: number; name: string } | null
  note: string | null
}

export interface RecurringExpenseInput {
  label: string
  expense_category_id?: number | null
  warehouse_id?: number | null
  amount: number
  day_of_month: number
  start_period: string
  end_period?: string | null
  is_active?: boolean
  notes?: string | null
}

export async function fetchRecurringExpenses(): Promise<RecurringExpense[]> {
  const { data } = await api.get<{ data: RecurringExpense[] }>('/recurring-expenses')
  return data.data
}

export async function fetchPendingOccurrences(): Promise<{ data: RecurringOccurrence[]; total: number }> {
  const { data } = await api.get<{ data: RecurringOccurrence[]; total: number }>('/recurring-expenses/pending')
  return data
}

export async function fetchOccurrences(id: number): Promise<RecurringOccurrence[]> {
  const { data } = await api.get<{ data: RecurringOccurrence[] }>(`/recurring-expenses/${id}/occurrences`)
  return data.data
}

export async function createRecurringExpense(input: RecurringExpenseInput): Promise<RecurringExpense> {
  const { data } = await api.post<{ data: RecurringExpense }>('/recurring-expenses', input)
  return data.data
}

export async function updateRecurringExpense(id: number, input: RecurringExpenseInput): Promise<RecurringExpense> {
  const { data } = await api.put<{ data: RecurringExpense }>(`/recurring-expenses/${id}`, input)
  return data.data
}

export async function deleteRecurringExpense(id: number): Promise<string> {
  const { data } = await api.delete<{ message: string }>(`/recurring-expenses/${id}`)
  return data.message
}

export interface PayOccurrenceInput {
  payment_method_id?: number | null
  paid_at?: string | null
  note?: string | null
}

export async function payOccurrence(id: number, input: PayOccurrenceInput): Promise<RecurringOccurrence> {
  const { data } = await api.post<{ data: RecurringOccurrence }>(
    `/recurring-expense-occurrences/${id}/pay`,
    input,
  )
  return data.data
}
