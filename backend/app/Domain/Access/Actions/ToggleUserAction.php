<?php

declare(strict_types=1);

namespace App\Domain\Access\Actions;

use App\Domain\Access\Contracts\UserRepositoryInterface;
use App\Domain\Access\Events\UserDeactivated;
use App\Domain\Access\Exceptions\UserManagementException;
use App\Models\User;

/**
 * Active / désactive un utilisateur. Jamais de suppression.
 * La désactivation révoque tokens et sessions.
 */
final class ToggleUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function execute(User $user, User $author): User
    {
        $willDeactivate = $user->is_active === true;

        if ($willDeactivate) {
            if ($user->is($author)) {
                throw UserManagementException::cannotDeactivateSelf();
            }

            // Le dernier administrateur actif ne peut pas être désactivé.
            if ($this->isAdmin($user) && $this->users->otherActiveAdminsCount($user) === 0) {
                throw UserManagementException::lastActiveAdmin();
            }
        }

        $user->update(['is_active' => ! $user->is_active]);

        if ($willDeactivate) {
            $user->tokens()->delete();
            UserDeactivated::dispatch($user);
        }

        return $user->refresh();
    }

    private function isAdmin(User $user): bool
    {
        return $user->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', 'role.manage'))
            ->exists();
    }
}
