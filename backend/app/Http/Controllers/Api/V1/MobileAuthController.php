<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Auth\IdentifiantConnexion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Authentification mobile (Flutter) : jeton Sanctum porteur, sans cookie.
 * Pas d'inscription — les comptes sont créés par un administrateur.
 */
final class MobileAuthController extends Controller
{
    /**
     * POST /mobile/login — retourne un jeton d'accès + le profil.
     */
    public function login(Request $request): JsonResponse
    {
        // « identifiant » est le champ courant ; « email » reste accepté pour
        // les versions déjà installées, qui l'envoient encore sous ce nom.
        // La règle « email » a disparu : le champ peut désormais porter un
        // numéro de téléphone.
        $data = $request->validate([
            'identifiant' => ['required_without:email', 'string', 'max:190'],
            'email' => ['required_without:identifiant', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $saisie = (string) ($data['identifiant'] ?? $data['email'] ?? '');

        $user = IdentifiantConnexion::trouver($saisie);

        // Le hachage est calculé même sans compte trouvé : sans cela, une
        // réponse instantanée signalerait « ce numéro n'existe pas » et
        // permettrait d'énumérer les comptes.
        $empreinte = $user?->password ?? '$2y$12$'.str_repeat('0', 53);

        if (! Hash::check($data['password'], $empreinte) || $user === null) {
            return response()->json(['message' => 'Identifiants incorrects.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        if ($user->locked_until !== null && $user->locked_until->isFuture()) {
            return response()->json(['message' => 'Compte temporairement verrouillé.'], 423);
        }

        // Un jeton par appareil : l'ancien jeton du même appareil est révoqué.
        $user->tokens()->where('name', $data['device_name'])->delete();
        $token = $user->createToken($data['device_name'])->plainTextToken;

        $user->update(['last_login_at' => now(), 'failed_attempts' => 0]);

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => UserResource::make($user->load('roles')),
            ],
        ]);
    }

    /**
     * POST /mobile/logout — révoque le jeton de l'appareil courant.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}
