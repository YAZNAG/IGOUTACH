import { forwardRef, type InputHTMLAttributes } from 'react'
import { cn } from '@/lib/utils'

export type InputProps = InputHTMLAttributes<HTMLInputElement>

export const Input = forwardRef<HTMLInputElement, InputProps>(({ className, ...props }, ref) => (
  <input
    ref={ref}
    className={cn(
      'h-10 w-full rounded border border-line-2 bg-card px-3 text-sm text-ink placeholder:text-faint',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky focus-visible:border-sky',
      className,
    )}
    {...props}
  />
))

Input.displayName = 'Input'
