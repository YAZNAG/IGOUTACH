<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    /**
     * Utilisateur authentifié, avec ses rôles et permissions effectives.
     */
    public function __invoke(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user->load('roles'));
    }
}
