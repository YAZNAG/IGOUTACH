import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bot, History, MessageSquarePlus, Send, Sparkles, Trash2, User } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Button } from '@/components/ui/Button'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { api, ensureCsrfCookie } from '@/lib/api'
import { cn, formatDateHeure } from '@/lib/utils'

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
  model?: string | null
  at?: string | null
}

interface ConversationResume {
  id: number
  module: string
  title: string
  messages_count: number
  updated_at: string | null
}

interface ConversationDetail extends Omit<ConversationResume, 'messages_count'> {
  messages: (Message & { id: number })[]
}

interface Reponse {
  conversation_id: number
  module: string
  question: string
  answer: string
  model: string
  context_lines: number
  generated_at: string
}

const CONVERSATIONS = ['assistant-conversations'] as const

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
 * et le modèle répond dessus. Les conversations sont conservées sur le
 * serveur, par utilisateur : on les rouvre depuis l'historique, on les
 * supprime une par une ou toutes.
 */
export function AssistantPage() {
  const qc = useQueryClient()
  const [moduleId, setModuleId] = useState('achats')
  const [conversationId, setConversationId] = useState<number | null>(null)
  const [question, setQuestion] = useState('')
  const [messages, setMessages] = useState<Message[]>([])
  const [erreur, setErreur] = useState<string | null>(null)
  const [aSupprimer, setASupprimer] = useState<ConversationResume | 'tout' | null>(null)
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

  const { data: historique = [] } = useQuery<ConversationResume[]>({
    queryKey: CONVERSATIONS,
    queryFn: async () => {
      const { data } = await api.get<{ data: ConversationResume[] }>('/assistant/conversations')
      return data.data
    },
  })

  const moduleActif = modules?.data.find((m) => m.id === moduleId) ?? modules?.data[0]
  const configure = modules?.meta.configured ?? true
  const libelleModule = (id: string) => modules?.data.find((m) => m.id === id)?.label ?? id

  const demander = useMutation({
    mutationFn: async (texte: string) => {
      await ensureCsrfCookie()
      const { data } = await api.post<{ data: Reponse }>('/assistant/ask', {
        module: moduleId,
        question: texte,
        conversation_id: conversationId,
      })
      return data.data
    },
    onMutate: (texte) => {
      setErreur(null)
      setMessages((prev) => [...prev, { role: 'user', content: texte }])
      setQuestion('')
    },
    onSuccess: (r) => {
      setConversationId(r.conversation_id)
      setMessages((prev) => [...prev, { role: 'assistant', content: r.answer, model: r.model, at: r.generated_at }])
      qc.invalidateQueries({ queryKey: CONVERSATIONS })
    },
    onError: (e) => {
      setErreur(messageErreur(e, 'L’assistant n’a pas pu répondre.'))
      // La question reste dans le champ : on la renvoie sans la retaper.
      setMessages((prev) => prev.slice(0, -1))
    },
  })

  const ouvrir = useMutation({
    mutationFn: async (id: number) => {
      const { data } = await api.get<{ data: ConversationDetail }>(`/assistant/conversations/${id}`)
      return data.data
    },
    onSuccess: (c) => {
      setConversationId(c.id)
      setModuleId(c.module)
      setMessages(c.messages.map((m) => ({ role: m.role, content: m.content, model: m.model, at: m.at })))
      setErreur(null)
    },
    onError: (e) => setErreur(messageErreur(e, 'Conversation introuvable.')),
  })

  const supprimer = useMutation({
    mutationFn: async (cible: ConversationResume | 'tout') => {
      await ensureCsrfCookie()
      await api.delete(cible === 'tout' ? '/assistant/conversations' : `/assistant/conversations/${cible.id}`)
      return cible
    },
    onSuccess: (cible) => {
      // La conversation ouverte disparaît avec elle : on repart à vide.
      if (cible === 'tout' || cible.id === conversationId) nouvelleConversation()
      setASupprimer(null)
      qc.invalidateQueries({ queryKey: CONVERSATIONS })
    },
    onError: (e) => setErreur(messageErreur(e, 'Suppression impossible.')),
  })

  useEffect(() => {
    finDeListe.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages, demander.isPending])

  function envoyer(texte = question) {
    const t = texte.trim()
    if (t === '' || demander.isPending) return
    demander.mutate(t)
  }

  function nouvelleConversation() {
    setConversationId(null)
    setMessages([])
    setErreur(null)
    champ.current?.focus()
  }

  function changerDeModule(id: string) {
    if (id === moduleId) return
    setModuleId(id)
    // Les chiffres joints ne sont plus les mêmes : nouveau fil.
    nouvelleConversation()
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

      <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
        <div className="space-y-6">
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

          <Card>
            <CardHeader
              title="Historique"
              hint={historique.length > 0 ? `${historique.length}` : undefined}
              action={
                historique.length > 0 ? (
                  <Button variant="ghost" size="sm" className="text-bad" onClick={() => setASupprimer('tout')}>
                    <Trash2 className="h-4 w-4" />
                    Tout supprimer
                  </Button>
                ) : undefined
              }
            />
            <CardBody className="max-h-[420px] space-y-1 overflow-y-auto p-2">
              {historique.length === 0 ? (
                <p className="px-3 py-2 text-sm text-muted">Aucune conversation enregistrée.</p>
              ) : (
                historique.map((c) => (
                  <div
                    key={c.id}
                    className={cn(
                      'group flex items-start gap-2 rounded px-3 py-2 transition-colors',
                      c.id === conversationId ? 'bg-sky-soft' : 'hover:bg-surface-2',
                    )}
                  >
                    <button
                      type="button"
                      onClick={() => ouvrir.mutate(c.id)}
                      className="min-w-0 flex-1 text-left"
                      title={c.title}
                    >
                      <span className="block truncate text-sm text-ink">{c.title}</span>
                      <span className="block text-xs text-muted">
                        {libelleModule(c.module)} · {c.updated_at ? formatDateHeure(c.updated_at) : ''}
                      </span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setASupprimer(c)}
                      aria-label={`Supprimer la conversation ${c.title}`}
                      className="shrink-0 rounded p-1 text-faint opacity-0 transition-opacity hover:text-bad group-hover:opacity-100 focus-visible:opacity-100"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                ))
              )}
            </CardBody>
          </Card>
        </div>

        <Card className="flex min-h-[560px] flex-col">
          <CardHeader
            title={moduleActif?.label ?? 'Conversation'}
            hint={conversationId !== null ? 'Conversation enregistrée' : undefined}
            action={
              messages.length > 0 ? (
                <Button variant="ghost" size="sm" onClick={nouvelleConversation}>
                  <MessageSquarePlus className="h-4 w-4" />
                  Nouvelle conversation
                </Button>
              ) : undefined
            }
          />
          <CardBody className="flex flex-1 flex-col gap-4">
            <div className="flex-1 space-y-4 overflow-y-auto">
              {messages.length === 0 && !ouvrir.isPending ? (
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
                  {historique.length > 0 ? (
                    <p className="flex items-center gap-1 text-xs text-faint">
                      <History className="h-3.5 w-3.5" />
                      Vos conversations précédentes sont dans l’historique, à gauche.
                    </p>
                  ) : null}
                </div>
              ) : null}

              {ouvrir.isPending ? <p className="text-sm text-muted">Ouverture de la conversation…</p> : null}

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
                        {m.model ? `${m.model} · ` : ''}
                        {formatDateHeure(m.at)}
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
                  L’assistant lit les chiffres et prépare sa réponse… (jusqu’à une minute)
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

      <ConfirmDialog
        open={aSupprimer !== null}
        title={aSupprimer === 'tout' ? 'Supprimer tout l’historique' : 'Supprimer la conversation'}
        message={
          aSupprimer === 'tout' ? (
            <>Toutes vos conversations avec l’assistant seront effacées. Cette action est définitive.</>
          ) : (
            <>
              Supprimer <strong>{aSupprimer?.title}</strong> ? Cette action est définitive.
            </>
          )
        }
        confirmLabel="Supprimer"
        isPending={supprimer.isPending}
        error={supprimer.isError ? messageErreur(supprimer.error, 'Suppression impossible.') : null}
        onConfirm={() => {
          if (aSupprimer !== null) supprimer.mutate(aSupprimer)
        }}
        onCancel={() => {
          setASupprimer(null)
          supprimer.reset()
        }}
      />
    </div>
  )
}
