import { useQuery } from '@tanstack/react-query'
import { Images } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Card, CardBody } from '@/components/ui/Card'
import { usePermission } from '@/hooks/usePermission'
import { api } from '@/lib/api'
import { cn, formatNumber } from '@/lib/utils'

interface Overview {
  configured: boolean
  counts: { sans_image: number; en_attente: number; validees: number; rejetees: number }
}

/**
 * Tuile du tableau de bord : l'état des visuels du catalogue, et l'accès à
 * la page de l'agent. Visible seulement pour qui gère les médias.
 */
export function AgentImagesTile() {
  const can = usePermission()
  const autorise = can('product.media_manage')

  const { data } = useQuery<Overview>({
    queryKey: ['agent-images-overview'],
    queryFn: async () => {
      const { data: r } = await api.get<{ data: Overview }>('/agent-images/overview')
      return r.data
    },
    enabled: autorise,
    staleTime: 60_000,
  })

  if (!autorise || !data) return null

  const aValider = data.counts.en_attente
  const sansImage = data.counts.sans_image

  return (
    <Link to="/agent-images" className="block">
      <Card className="h-full transition-colors hover:border-sky">
        <CardBody className="flex items-start gap-3 p-5">
          <span
            className={cn(
              'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
              aValider > 0 ? 'bg-sky-soft text-navy' : 'bg-surface-2 text-muted',
            )}
          >
            <Images className="h-5 w-5" />
          </span>
          <div className="min-w-0">
            <p className="text-xs font-medium text-muted">Agent images</p>
            <p className="text-2xl font-semibold text-ink">
              {formatNumber(aValider)}
              <span className="ml-1 text-sm font-normal text-muted">à valider</span>
            </p>
            <p className="text-xs text-muted">
              {sansImage > 0
                ? `${formatNumber(sansImage)} article(s) sans image · ${formatNumber(data.counts.validees)} validée(s)`
                : 'Tous les articles ont une image'}
              {!data.configured ? ' · agent non configuré' : ''}
            </p>
          </div>
        </CardBody>
      </Card>
    </Link>
  )
}
