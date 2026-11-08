import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes } from 'react'

export function cn(...classes: (string | false | null | undefined)[]): string {
  return classes.filter(Boolean).join(' ')
}

type Variante = 'primary' | 'outline' | 'ghost' | 'danger'

export function Button({
  variant = 'primary',
  className,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variante }) {
  const styles: Record<Variante, string> = {
    primary: 'bg-brand text-white hover:bg-brand-strong shadow-sm',
    outline: 'border border-line bg-white text-ink hover:border-brand hover:text-brand',
    ghost: 'text-muted hover:bg-black/5 hover:text-ink',
    danger: 'bg-bad-soft text-bad hover:bg-bad hover:text-white',
  }
  return (
    <button
      className={cn(
        'inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        styles[variant],
        className,
      )}
      {...props}
    />
  )
}

export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return (
    <input
      className={cn(
        'h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink placeholder:text-faint focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20',
        className,
      )}
      {...props}
    />
  )
}

export function Select({ className, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select
      className={cn(
        'h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20',
        className,
      )}
      {...props}
    />
  )
}

export function Field({ label, htmlFor, children, hint }: { label: string; htmlFor?: string; children: ReactNode; hint?: string }) {
  return (
    <label htmlFor={htmlFor} className="block space-y-1">
      <span className="text-xs font-medium text-muted">{label}</span>
      {children}
      {hint ? <span className="block text-xs text-faint">{hint}</span> : null}
    </label>
  )
}

export function Badge({ tone = 'neutral', children }: { tone?: 'neutral' | 'sky' | 'ok' | 'warn' | 'bad'; children: ReactNode }) {
  const tons = {
    neutral: 'bg-paper text-muted',
    sky: 'bg-brand-soft text-brand-strong',
    ok: 'bg-ok-soft text-ok',
    warn: 'bg-warn-soft text-warn',
    bad: 'bg-bad-soft text-bad',
  }
  return <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', tons[tone])}>{children}</span>
}

export function Card({ className, children }: { className?: string; children: ReactNode }) {
  return <div className={cn('rounded-2xl border border-line bg-white shadow-sm', className)}>{children}</div>
}

export function Spinner({ label = 'Chargement…' }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-2 py-12 text-sm text-muted">
      <span className="h-5 w-5 animate-spin rounded-full border-2 border-line border-t-brand" />
      {label}
    </div>
  )
}

export function Erreur({ message }: { message: string }) {
  return <p className="rounded-lg border border-bad/20 bg-bad-soft px-4 py-3 text-sm text-bad">{message}</p>
}

export function Succes({ message }: { message: string }) {
  return <p className="rounded-lg border border-ok/20 bg-ok-soft px-4 py-3 text-sm text-ok">{message}</p>
}

/** Pastille de disponibilité d'un article. */
export function Dispo({ niveau }: { niveau: 'in' | 'low' | 'out' }) {
  if (niveau === 'out') return <Badge tone="bad">Rupture de stock</Badge>
  if (niveau === 'low') return <Badge tone="warn">Dernières pièces</Badge>
  return <Badge tone="ok">En stock</Badge>
}
