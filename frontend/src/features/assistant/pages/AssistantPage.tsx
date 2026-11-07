import { useMutation, useQuery } from '@tanstack/react-query'
import { Bot, Eraser, Send, Sparkles, User } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn } from '@/lib/utils'

interface Module {
  id: string
  label: string
  description: string
  suggestions: string[]
}

interface Message {
  role: 'user' | 'assistant'
  content: string
  /** Renseigné sur les réponses : le modèle qui a répondu et l'heure. */
  model?: string
  at?: string
}

interface Reponse {
  module: string
  question: string
  answer: string
  model: string
  context_lines: number
  generated_at: string
}

function messageErreur(e: unknown, repli: string): string {
  if (e && typeof e === 'object' && 'response' in e) {
    const r = (e as { response?: { data?: { message?: string } } }).response
    if (r?.data?.message) return r.data.message
  }
  return repli
}

/**
 * Assistant de la direction, appuyé sur Gemini.
 *
 * On choisit un module (achats, ventes, crédits, caisses, vue d'ensemble) :
 * le serveur joint à chaque question les chiffres du moment pour ce module,
 * et le modèle répond dessus. La conversation reste dans l'onglet ; changer
 * de module la vide, les chiffres n'étant plus les mêmes.
 */
export function AssistantPage() {
  const [moduleId, setModuleId] = useState('achats')
  const [question, setQuestion] = useState('')
  const [messages, setMessages] = useState<Message[]>([])
  const [erreur, setErreur] = useState<string | null>(null)
  const finDeListe = useRef<HTMLDivElement>(null)
  const champ = useRef<HTMLTextAreaElement>(null)

  const { data: modules } = useQuery<{ data: Module[]; meta: { configured: boolean; model: string } }>({
    queryKey: ['assistant-modules'],
    queryFn: async () => {
      const { data } = await api.get<{ data: Module[]; meta: { configured: boolean; model: string } }>('/assistant/modules')
      return data
    },
    staleTime: 10 * 60_000,
  })

  const moduleActif = modules?.data.find((m) => m.id === moduleId) ?? modules?.data[0]
  const configure = modules?.meta.configured ?? true

  const demander = useMutation({
    mutationFn: async (texte: string) => {
      await ensureCsrfCookie()
      const { data } = await api.post<{ data: Reponse }>('/assistant/ask', {
        module: moduleId,
        question: texte,
        // Les six derniers échanges suffisent au fil de la conversation.
        history: messages.slice(-6).map((m) => ({ role: m.role, content: m.content })),
      })
      return data.data
    },
    onMutate: (texte) => {
      setErreur(null)
      setMessages((prev) => [...prev, { role: 'user', content: texte }])
      setQuestion('')
    },
    onSuccess: (r) => {
      setMessages((prev) => [...prev, { role: 'assistant', content: r.answer, model: r.model, at: r.generated_at }])
    },
    onError: (e) => {
      setErreur(messageErreur(e, 'L’assistant n’a pas pu répondre.'))
      // La question reste dans le champ : on la renvoie sans la retaper.
      setMessages((prev) => prev.slice(0, -1))
    },
  })

  useEffect(() => {
    finDeListe.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages, demander.isPending])

  function envoyer(texte = question) {
    const t = texte.trim()
    if (t === '' || demander.isPending) return
    demander.mutate(t)
  }

  function changerDeModule(id: string) {
    if (id === moduleId) return
    setModuleId(id)
    setMessages([])
    setErreur(null)
    champ.current?.focus()
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="flex items-center gap-2 text-xl font-semibold text-ink">
          <Sparkles className="h-5 w-5 text-sky" />
          Assistant IA
        </h1>
        <p className="text-sm text-muted">
          Posez vos questions sur l’activité : l’assistant lit les chiffres du moment (stock, ventes, crédits,
          caisses) et vous conseille, par exemple quoi commander et en quelle quantité.
        </p>
      </div>

      {!configure ? (
        <p className="rounded border border-line bg-warn-bg px-3 py-2 text-sm text-warn">
          L’assistant n’est pas encore configuré : la clé Gemini manque sur le serveur.
        </p>
      ) : null}

      <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
        <Card>
          <CardHeader title="Module" hint={modules?.meta.model} />
          <CardBody className="space-y-1 p-2">
            {(modules?.data ?? []).map((m) => (
              <button
                key={m.id}
                type="button"
                onClick={() => changerDeModule(m.id)}
                className={cn(
                  'w-full rounded px-3 py-2 text-left transition-colors',
                  m.id === moduleActif?.id ? 'bg-sky-soft text-navy' : 'hover:bg-surface-2 text-ink',
                )}
              >
                <span className="block text-sm font-medium">{m.label}</span>
                <span className="block text-xs text-muted">{m.description}</span>
              </button>
            ))}
          </CardBody>
        </Card>

        <Card className="flex min-h-[560px] flex-col">
          <CardHeader
            title={moduleActif?.label ?? 'Conversation'}
            action={
              messages.length > 0 ? (
                <Button variant="ghost" size="sm" onClick={() => setMessages([])}>
                  <Eraser className="h-4 w-4" />
                  Nouvelle conversation
                </Button>
              ) : undefined
            }
          />
          <CardBody className="flex flex-1 flex-col gap-4">
            <div className="flex-1 space-y-4 overflow-y-auto">
              {messages.length === 0 ? (
                <div className="space-y-3">
                  <p className="text-sm text-muted">Pour commencer, une de ces questions, ou la vôtre :</p>
                  <div className="flex flex-wrap gap-2">
                    {(moduleActif?.suggestions ?? []).map((s) => (
                      <button
                        key={s}
                        type="button"
                        disabled={demander.isPending || !configure}
                        onClick={() => envoyer(s)}
                        className="rounded-full border border-line bg-card px-3 py-1.5 text-left text-sm text-ink transition-colors hover:border-sky hover:text-navy disabled:opacity-50"
                      >
                        {s}
                      </button>
                    ))}
                  </div>
                </div>
              ) : null}

              {messages.map((m, i) => (
                <div key={i} className={cn('flex gap-3', m.role === 'user' ? 'justify-end' : 'justify-start')}>
                  {m.role === 'assistant' ? (
                    <span className="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-soft text-navy">
                      <Bot className="h-4 w-4" />
                    </span>
                  ) : null}
                  <div
                    className={cn(
                      'max-w-[85%] rounded-lg px-4 py-3 text-sm',
                      m.role === 'user' ? 'bg-navy text-white' : 'border border-line bg-bg text-ink',
                    )}
                  >
                    {m.role === 'assistant' ? (
                      <div className="prose-igoutech">
                        <ReactMarkdown remarkPlugins={[remarkGfm]}>{m.content}</ReactMarkdown>
                      </div>
                    ) : (
                      <p className="whitespace-pre-wrap">{m.content}</p>
                    )}
                    {m.at ? (
                      <p className="mt-2 text-xs text-faint">
                        {m.model} · {new Date(m.at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                      </p>
                    ) : null}
                  </div>
                  {m.role === 'user' ? (
                    <span className="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-2 text-muted">
                      <User className="h-4 w-4" />
                    </span>
                  ) : null}
                </div>
              ))}

              {demander.isPending ? (
                <div className="flex items-center gap-3 text-sm text-muted">
                  <span className="flex h-7 w-7 items-center justify-center rounded-full bg-sky-soft text-navy">
                    <Bot className="h-4 w-4" />
                  </span>
                  L’assistant lit les chiffres et prépare sa réponse…
                </div>
              ) : null}
              <div ref={finDeListe} />
            </div>

            {erreur ? (
              <p className="rounded border border-line bg-bad-bg px-3 py-2 text-sm text-bad">{erreur}</p>
            ) : null}

            <form
              onSubmit={(e) => {
                e.preventDefault()
                envoyer()
              }}
              className="flex items-end gap-2 border-t border-line pt-4"
            >
              <textarea
                ref={champ}
                value={question}
                onChange={(e) => setQuestion(e.target.value)}
                onKeyDown={(e) => {
                  // Entrée envoie ; Maj+Entrée fait un retour à la ligne.
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault()
                    envoyer()
                  }
                }}
                rows={2}
                placeholder={configure ? 'Votre question…' : 'Assistant non configuré'}
                disabled={!configure || demander.isPending}
                className="min-h-[44px] flex-1 resize-y rounded border border-line-2 bg-card px-3 py-2 text-sm text-ink placeholder:text-faint focus-visible:border-sky focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky disabled:opacity-60"
              />
              <Button type="submit" disabled={!configure || demander.isPending || question.trim() === ''}>
                <Send className="h-4 w-4" />
                Envoyer
              </Button>
            </form>
          </CardBody>
        </Card>
      </div>
    </div>
  )
}
