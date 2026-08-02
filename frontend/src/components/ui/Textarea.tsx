import { forwardRef, type TextareaHTMLAttributes } from 'react'
import { cn } from '@/lib/utils'

export type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement>

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, ...props }, ref) => (
    <textarea
      ref={ref}
      className={cn(
        'w-full rounded border border-line-2 bg-card px-3 py-2 text-sm text-ink placeholder:text-faint',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky focus-visible:border-sky',
        className,
      )}
      {...props}
    />
  ),
)

Textarea.displayName = 'Textarea'
