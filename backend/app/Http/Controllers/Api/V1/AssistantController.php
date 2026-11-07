<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Assistant\Exceptions\AssistantIndisponibleException;
use App\Domain\Assistant\Services\ContexteAssistant;
use App\Domain\Assistant\Services\GeminiClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Assistant de la direction : une question, un module, une réponse appuyée
 * sur les chiffres du moment.
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

    public function ask(Request $request, ContexteAssistant $contexte, GeminiClient $gemini): JsonResponse
    {
        /** @var array{module: string, question: string, history?: list<array{role: string, content: string}>} $data */
        $data = $request->validate([
            'module' => ['required', 'string', Rule::in(ContexteAssistant::MODULES)],
            'question' => ['required', 'string', 'max:2000'],
            // La conversation précédente, pour que « et pour le lieu 2 ? » ait un sens.
            'history' => ['sometimes', 'array', 'max:12'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required', 'string', 'max:8000'],
        ]);

        $extrait = $contexte->construire($data['module']);

        // Le contexte accompagne la question du jour, pas tout l'historique :
        // il est recalculé à chaque question, donc toujours à jour.
        $messages = array_values($data['history'] ?? []);
        $messages[] = [
            'role' => 'user',
            'content' => "Données actuelles de l'entreprise (module « {$data['module']} ») :\n\n"
                .$extrait['texte']
                ."\n\nQuestion : ".$data['question'],
        ];

        try {
            $reponse = $gemini->repondre(self::INSTRUCTIONS, $messages);
        } catch (AssistantIndisponibleException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json(['data' => [
            'module' => $data['module'],
            'question' => $data['question'],
            'answer' => $reponse,
            'model' => $gemini->modele(),
            'context_lines' => $extrait['lignes'],
            'generated_at' => now()->toIso8601String(),
        ]]);
    }
}
