import logoWhite from '@/assets/logo/igoutech-white.svg'
import logoFull from '@/assets/logo/igoutech-full.svg'
import { LoginForm } from '../components/LoginForm'

export function LoginPage() {
  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      {/* Panneau gauche — seul dégradé de l'application */}
      <div
        className="relative hidden flex-col justify-between p-12 text-white lg:flex"
        style={{ background: 'var(--login-gradient)' }}
      >
        <img src={logoWhite} alt="IGOUTECH" className="h-11 w-auto" />
        <div className="max-w-md space-y-4">
          <h1 className="text-3xl font-semibold leading-tight">
            Gestion de stock multi-sites
          </h1>
          <p className="text-sky-soft">
            Dépôts, point de vente et véhicules vendeurs — chaque lieu suivi
            séparément, une vue consolidée pour la direction.
          </p>
        </div>
        <p className="text-sm text-sky-soft">IGOUTECH · Distribution informatique</p>
      </div>

      {/* Panneau droit — formulaire */}
      <div className="flex items-center justify-center bg-bg p-6">
        <div className="w-full max-w-sm space-y-8">
          <img src={logoFull} alt="IGOUTECH" className="h-10 w-auto lg:hidden" />
          <div className="space-y-1">
            <h2 className="text-xl font-semibold text-ink">Connexion</h2>
            <p className="text-sm text-muted">Accédez à votre espace de gestion.</p>
          </div>
          <LoginForm />
        </div>
      </div>
    </div>
  )
}
