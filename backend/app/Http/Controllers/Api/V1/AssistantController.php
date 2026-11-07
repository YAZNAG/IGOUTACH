<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Assistant\Exceptions\AssistantIndisponibleException;
use App\Domain\Assistant\Models\AssistantConversation;
use App\Domain\Assistant\Models\AssistantMessage;
use App\Domain\Assistant\Services\ContexteAssistant;
use App\Domain\Assistant\Services\GeminiClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Assistant de la direction : une question, un module, une réponse appuyée
 * sur les chiffres du moment. Les conversations sont conservées par
 * utilisateur et se suppriment à la demande.
 */
final class AssistantController extends Controller
{
    private const INSTRUCTIONS = <<<'TXT'
Tu es l'assistant de gestion d'IGOUTECH, un distributeur marocain (région d'Agadir) de matériel électronique, satellite, câblage et accessoires, qui vend en détail, demi-gros et gros depuis plusieurs lieux (dépôts, magasin, véhicules de tournée). Tu t'adresses à la direction.

Règles :
- Réponds en français, de façon directe et concrète, en dirhams (DH).
- Appuie-toi UNIQUEMENT sur les données fournies dans le contexte. Si une information manque, dis-le au lieu de l'inventer.
- Quand tu recommandes des achats, donne pour chaque article : référence, nom, stock actuel, rythme de vente, couverture en jours, quantité à commander et dernier fournisseur connu. Présente-les dans un tableau Markdown, du plus urgent au moins urgent.
- Quand tu parles de clients à relancer, donne le nom, le lieu, l'encours et l'ancienneté.
- Explique brièvement ton raisonnement (une ou deux phrases), puis la recommandation.
- Utilise des titres courts, des listes et des tableaux Markdown. Pas de longues introductions.
- Ne mentionne pas que tu es un modèle de langage ni les limites techniques du contexte.
TXT;

    /** Nombre d'échanges précédents renvoyés au modèle pour le fil de la conversation. */
    private const MEMOIRE = 8;

    public function modules(ContexteAssistant $contexte, GeminiClient $gemini): JsonResponse
    {
        return response()->json([
            'data' => $contexte->modules(),
            'meta' => [
                'configured' => $gemini->configure(),
                'model' => $gemini->modele(),
            ],
        ]);
    }

    /**
     * Les conversations de l'utilisateur, la plus récente en tête.
     */
    public function conversations(Request $request): JsonResponse
    {
        $liste = AssistantConversation::query()
            ->where('user_id', $request->user()?->id)
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $liste->map(fn (AssistantConversation $c): array => [
            'id' => $c->id,
            'module' => $c->module,
            'title' => $c->title,
            'messages_count' => (int) $c->getAttribute('messages_count'),
            'updated_at' => $c->updated_at?->toIso8601String(),
        ])->values()->all()]);
    }

    public function conversation(Request $request, AssistantConversation $conversation): JsonResponse
    {
        $this->assertOwner($request, $conversation);

        return response()->json(['data' => $this->serialiser($conversation->load('messages'))]);
    }

    public function destroyConversation(Request $request, AssistantConversation $conversation): JsonResponse
    {
        $this->assertOwner($request, $conversation);
        $conversation->delete();

        return response()->json(['message' => 'Conversation supprimée.']);
    }

    /**
     * Efface tout l'historique de l'utilisateur.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $n = AssistantConversation::query()->where('user_id', $request->user()?->id)->delete();

        return response()->json(['message' => $n > 0 ? "{$n} conversation(s) supprimée(s)." : 'Aucune conversation à supprimer.']);
    }

    public function ask(Request $request, ContexteAssistant $contexte, GeminiClient $gemini): JsonResponse
    {
        /** @var array{module: string, question: string, conversation_id?: int|null} $data */
        $data = $request->validate([
            'module' => ['required', 'string', Rule::in(ContexteAssistant::MODULES)],
            'question' => ['required', 'string', 'max:2000'],
            // Suite d'une conversation existante ; absent : on en ouvre une.
            'conversation_id' => ['nullable', 'integer', 'exists:assistant_conversations,id'],
        ]);

        $conversation = isset($data['conversation_id'])
            ? AssistantConversation::query()->findOrFail((int) $data['conversation_id'])
            : null;

        if ($conversation !== null) {
            $this->assertOwner($request, $conversation);
        }

        // Le fil précédent vient du serveur, pas du navigateur : il est
        // conservé tel quel, et reprend même depuis un autre poste.
        // Les derniers messages, puis remis dans l'ordre : la relation trie
        // deja par id croissant, d'ou le « reorder » avant de prendre la fin.
        $precedents = $conversation !== null
            ? $conversation->messages()->reorder()->orderByDesc('id')->limit(self::MEMOIRE)->get()->reverse()->values()
            : collect();

        $extrait = $contexte->construire($data['module']);

        $messages = $precedents->map(fn (AssistantMessage $m): array => ['role' => $m->role, 'content' => $m->content])->all();
        $messages[] = [
            'role' => 'user',
            'content' => "Données actuelles de l'entreprise (module « {$data['module']} ») :\n\n"
                .$extrait['texte']
                ."\n\nQuestion : ".$data['question'],
        ];

        // Une réponse argumentée prend 30 à 60 s : plus que la limite PHP
        // habituelle d'une requête web.
        set_time_limit(180);

        try {
            $reponse = $gemini->repondre(self::INSTRUCTIONS, $messages);
        } catch (AssistantIndisponibleException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        // La conversation n'est créée qu'une fois la réponse obtenue : un
        // échec ne laisse pas de fil vide dans l'historique.
        if ($conversation === null) {
            $conversation = AssistantConversation::query()->create([
                'user_id' => $request->user()?->id,
                'module' => $data['module'],
                'title' => Str::limit(trim($data['question']), 110, '…'),
            ]);
        }

        $conversation->messages()->create(['role' => AssistantMessage::ROLE_USER, 'content' => $data['question']]);
        $conversation->messages()->create(['role' => AssistantMessage::ROLE_ASSISTANT, 'content' => $reponse, 'model' => $gemini->modele()]);
        $conversation->touch();

        return response()->json(['data' => [
            'conversation_id' => $conversation->id,
            'module' => $data['module'],
            'question' => $data['question'],
            'answer' => $reponse,
            'model' => $gemini->modele(),
            'context_lines' => $extrait['lignes'],
            'generated_at' => now()->toIso8601String(),
        ]]);
    }

    private function assertOwner(Request $request, AssistantConversation $conversation): void
    {
        if ((int) $conversation->user_id !== (int) $request->user()?->id) {
            abort(403, 'Cette conversation appartient à un autre utilisateur.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiser(AssistantConversation $c): array
    {
        return [
            'id' => $c->id,
            'module' => $c->module,
            'title' => $c->title,
            'updated_at' => $c->updated_at?->toIso8601String(),
            'messages' => $c->messages->map(fn (AssistantMessage $m): array => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'model' => $m->model,
                'at' => $m->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
