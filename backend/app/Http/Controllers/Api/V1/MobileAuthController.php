<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
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
        /** @var array{email: string, password: string, device_name: string} $data */
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
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
