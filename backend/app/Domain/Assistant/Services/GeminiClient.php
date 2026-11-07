<?php

declare(strict_types=1);

namespace App\Domain\Assistant\Services;

use App\Domain\Assistant\Exceptions\AssistantIndisponibleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Appel du modèle Gemini (API Generative Language, generateContent).
 *
 * La clé vient de la configuration, jamais de la requête : c'est le serveur
 * qui parle à Google, le navigateur ne voit ni la clé ni le contexte brut.
 */
final class GeminiClient
{
    private const POINT_D_ENTREE = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function configure(): bool
    {
        return (string) config('services.gemini.key') !== '';
    }

    public function modele(): string
    {
        return (string) (config('services.gemini.model') ?: 'gemini-3.8-flash');
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages  la conversation, le dernier étant la question
     */
    public function repondre(string $instructions, array $messages): string
    {
        if (! $this->configure()) {
            throw new AssistantIndisponibleException(
                'Assistant non configuré : renseignez GEMINI_API_KEY dans le fichier .env du serveur.',
            );
        }

        $contenus = array_map(static fn (array $m): array => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        try {
            $reponse = Http::withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
                // IPv4 impose : depuis le serveur, la route IPv6 vers Google
                // accepte la connexion TLS mais ne renvoie jamais la reponse a
                // un POST (observe le 07/11/2026 : 0 octet recu apres 150 s,
                // la meme requete en IPv4 repond en 3 s).
                ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
                // Une réponse argumentée sur un gros contexte peut dépasser la
                // minute : on laisse 150 s avant de déclarer le service muet.
                ->timeout(150)
                // « High demand » et autres 503 sont passagers : on réessaie
                // quatre fois, en espaçant, avant de le dire à l'utilisateur.
                ->retry(4, 3000, function (\Throwable $e): bool {
                    return $e instanceof \Illuminate\Http\Client\RequestException
                        && in_array($e->response->status(), [500, 502, 503, 504], true);
                }, throw: false)
                ->post(self::POINT_D_ENTREE.'/'.$this->modele().':generateContent', [
                    'system_instruction' => ['parts' => [['text' => $instructions]]],
                    'contents' => $contenus,
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 4096,
                    ],
                ]);
        } catch (ConnectionException $e) {
            // La cause (délai dépassé, DNS, TLS…) va au journal : l'écran n'en
            // a pas besoin, le diagnostic si.
            Log::warning('Gemini injoignable', ['erreur' => $e->getMessage(), 'modele' => $this->modele()]);

            throw new AssistantIndisponibleException('Le service Gemini ne répond pas : réessayez dans un instant.');
        }

        if ($reponse->status() === 429) {
            throw new AssistantIndisponibleException('Quota Gemini atteint pour le moment : réessayez dans une minute.');
        }

        if ($reponse->status() === 503) {
            throw new AssistantIndisponibleException('Gemini est saturé en ce moment (forte demande) : réessayez dans un instant.');
        }

        if (! $reponse->successful()) {
            $detail = (string) ($reponse->json('error.message') ?? $reponse->body());

            throw new AssistantIndisponibleException('Gemini a refusé la demande : '.mb_substr($detail, 0, 300));
        }

        $parties = $reponse->json('candidates.0.content.parts') ?? [];
        $texte = trim(implode('', array_map(
            static fn ($p): string => (string) ($p['text'] ?? ''),
            is_array($parties) ? $parties : [],
        )));

        if ($texte === '') {
            $raison = (string) ($reponse->json('candidates.0.finishReason')
                ?? $reponse->json('promptFeedback.blockReason')
                ?? 'réponse vide');

            throw new AssistantIndisponibleException('Gemini n’a rien renvoyé ('.$raison.').');
        }

        return $texte;
    }
}
