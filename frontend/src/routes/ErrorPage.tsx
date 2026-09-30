import { ArrowLeft, Compass, TriangleAlert } from 'lucide-react'
import { Link, isRouteErrorResponse, useRouteError } from 'react-router-dom'
import { Button } from '@/components/ui/Button'
import { Card, CardBody } from '@/components/ui/Card'

/**
 * Page affichée quand une adresse n'existe pas, ou quand un écran tombe.
 *
 * Sans elle, React Router montre son écran de développement — « Unexpected
 * Application Error », en anglais, avec un conseil adressé au développeur.
 * Un utilisateur qui a suivi un ancien favori ou fait une faute de frappe se
 * retrouvait devant un message qui ne lui disait ni ce qui s'est passé, ni
 * comment revenir.
 */
export function ErrorPage() {
  const error = useRouteError()
  const introuvable = isRouteErrorResponse(error) && error.status === 404

  // Le détail technique n'aide pas l'utilisateur, mais il aide celui à qui il
  // le rapportera : on le garde, en petit, plutôt que de l'effacer.
  const detail = isRouteErrorResponse(error)
    ? `${error.status} ${error.statusText}`
    : error instanceof Error
      ? error.message
      : null

  return (
    <div className="flex min-h-[60vh] items-center justify-center p-6">
      <Card className="w-full max-w-lg">
        <CardBody className="space-y-4 text-center">
          <div className="flex justify-center">
            {introuvable ? (
              <Compass className="h-10 w-10 text-muted" />
            ) : (
              <TriangleAlert className="h-10 w-10 text-warn" />
            )}
          </div>

          <div>
            <h1 className="text-xl font-semibold text-ink">
              {introuvable ? 'Cette page n’existe pas' : 'Cette page n’a pas pu s’afficher'}
            </h1>
            <p className="mt-2 text-sm text-muted">
              {introuvable
                ? 'L’adresse demandée ne correspond à aucun écran. Elle a peut-être changé, ou le lien que vous avez suivi est ancien.'
                : 'Une erreur est survenue pendant l’affichage. Réessayez ; si cela se reproduit, signalez le détail ci-dessous.'}
            </p>
          </div>

          <div className="flex justify-center gap-2">
            <Button onClick={() => window.location.assign('/')}>
              <ArrowLeft className="h-4 w-4" />
              Revenir au tableau de bord
            </Button>
            {!introuvable ? (
              <Button variant="outline" onClick={() => window.location.reload()}>
                Recharger
              </Button>
            ) : null}
          </div>

          {detail ? <p className="mono text-xs text-faint">{detail}</p> : null}

          <p className="text-xs text-muted">
            Les écrans disponibles sont listés dans le menu de gauche, depuis le{' '}
            <Link to="/" className="text-sky hover:underline">
              tableau de bord
            </Link>
            .
          </p>
        </CardBody>
      </Card>
    </div>
  )
}
