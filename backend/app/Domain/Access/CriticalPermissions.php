<?php

declare(strict_types=1);

namespace App\Domain\Access;

/**
 * Permissions sans lesquelles plus personne ne peut administrer le système.
 * On ne verrouille jamais un rôle par son nom : on garantit qu'au moins
 * un rôle les conserve.
 */
final class CriticalPermissions
{
    /**
     * @var list<string>
     */
    public const NAMES = ['role.manage', 'user.assign_role'];
}
