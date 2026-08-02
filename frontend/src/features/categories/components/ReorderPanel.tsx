import { ArrowDown, ArrowUp, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import type { Category } from '@/types'
import { useReorderCategories } from '../hooks'

/**
 * Réorganisation de l'ordre d'affichage des catégories (boutons monter /
 * descendre, enregistrement en une fois).
 */
export function ReorderPanel({ categories, onClose }: { categories: Category[]; onClose: () => void }) {
  const [order, setOrder] = useState<Category[]>([])
  const mutation = useReorderCategories()

  useEffect(() => {
    setOrder([...categories].sort((a, b) => (a.position ?? 0) - (b.position ?? 0) || a.name.localeCompare(b.name)))
  }, [categories])

  function move(index: number, delta: -1 | 1) {
    setOrder((prev) => {
      const next = [...prev]
      const target = index + delta
      if (target < 0 || target >= next.length) return prev
      ;[next[index], next[target]] = [next[target], next[index]]
      return next
    })
  }

  function save() {
    mutation.mutate(
      order.map((c, i) => ({ id: c.id, position: i })),
      { onSuccess: onClose },
    )
  }

  return (
    <Card>
      <CardHeader
        title="Réorganiser les catégories"
        hint="L'ordre définit l'affichage dans les listes et les exports."
        action={
          <Button variant="ghost" size="sm" onClick={onClose} aria-label="Fermer la réorganisation">
            <X className="h-4 w-4" />
          </Button>
        }
      />
      <CardBody className="space-y-3">
        <ul className="divide-y divide-line rounded-lg border border-line">
          {order.map((c, i) => (
            <li key={c.id} className="flex items-center justify-between px-4 py-2">
              <span className="text-sm text-ink">
                <span className="tabular mr-3 text-muted">{i + 1}.</span>
                {c.name}
              </span>
              <span className="flex gap-1">
                <Button variant="ghost" size="sm" onClick={() => move(i, -1)} disabled={i === 0} aria-label={`Monter ${c.name}`}>
                  <ArrowUp className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => move(i, 1)}
                  disabled={i === order.length - 1}
                  aria-label={`Descendre ${c.name}`}
                >
                  <ArrowDown className="h-4 w-4" />
                </Button>
              </span>
            </li>
          ))}
        </ul>
        {mutation.isError ? <p className="text-sm text-bad">Enregistrement impossible.</p> : null}
        <div className="flex gap-2">
          <Button onClick={save} disabled={mutation.isPending}>
            {mutation.isPending ? 'Enregistrement…' : "Enregistrer l'ordre"}
          </Button>
          <Button variant="ghost" onClick={onClose}>Annuler</Button>
        </div>
      </CardBody>
    </Card>
  )
}
