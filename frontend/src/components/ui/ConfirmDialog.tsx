import type { ReactNode } from 'react'
import { Button } from './Button'

interface ConfirmDialogProps {
  open: boolean
  title: string
  message: ReactNode
  confirmLabel?: string
  danger?: boolean
  isPending?: boolean
  error?: string | null
  onConfirm: () => void
  onCancel: () => void
}

export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirmer',
  danger = true,
  isPending = false,
  error = null,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  if (!open) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4"
      role="dialog"
      aria-modal="true"
      onMouseDown={onCancel}
    >
      <div
        className="w-full max-w-md rounded-lg border border-line bg-card p-5 shadow-[var(--shadow-pop)]"
        onMouseDown={(e) => e.stopPropagation()}
      >
        <h2 className="text-base font-semibold text-ink">{title}</h2>
        <div className="mt-2 text-sm text-muted">{message}</div>

        {error ? (
          <p className="mt-3 rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{error}</p>
        ) : null}

        <div className="mt-5 flex justify-end gap-3">
          <Button variant="outline" onClick={onCancel} disabled={isPending}>
            Annuler
          </Button>
          <Button
            variant={danger ? 'accent' : 'primary'}
            className={danger ? 'bg-bad hover:bg-bad' : undefined}
            onClick={onConfirm}
            disabled={isPending}
          >
            {isPending ? 'Suppression…' : confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  )
}
