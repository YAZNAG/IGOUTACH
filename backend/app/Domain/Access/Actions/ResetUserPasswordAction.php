<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Notifications\UserInvitationNotification;
use App\Models\User;

/**
 * (Ré)envoie une invitation / réinitialisation : l'utilisateur définit
 * lui-même son mot de passe via un lien signé. L'admin ne le voit jamais.
 */
final class ResetUserPasswordAction
{
    public function execute(User $user): void
    {
        $token = UserInvitationNotification::tokenFor($user);
        $user->notify(new UserInvitationNotification($token));
    }
}
