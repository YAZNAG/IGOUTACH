interface PlaceholderPageProps {
  title: string
}

export function PlaceholderPage({ title }: PlaceholderPageProps) {
  return (
    <div className="space-y-2">
      <h1 className="text-xl font-semibold text-ink">{title}</h1>
      <p className="text-sm text-muted">Ce module sera livré dans une prochaine étape.</p>
    </div>
  )
}
