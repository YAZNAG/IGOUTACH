<?php

declare(strict_types=1);

namespace App\Domain\Access\Exceptions;

use RuntimeException;

final class RoleManagementException extends RuntimeException
{
    public static function systemRole(): self
    {
        return new self('Un rôle système ne peut pas être supprimé.');
    }

    public static function roleInUse(int $count): self
    {
        return new self("Ce rôle est attribué à {$count} utilisateur(s). Réattribuez-les avant de le supprimer.");
    }

    public static function wouldLockOutAdmins(string $permission): self
    {
        return new self("Impossible : au moins un rôle doit conserver la permission « {$permission} ».");
    }
}
