<?php

declare(strict_types=1);

namespace App\Domain\Access\Exceptions;

use RuntimeException;

/**
 * Violation d'une règle métier de gestion des utilisateurs
 * (rendue en HTTP 422 par le contrôleur).
 */
final class UserManagementException extends RuntimeException
{
    public static function warehouseRequired(): self
    {
        return new self('Un lieu de rattachement est obligatoire pour un utilisateur sans accès global.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('Vous ne pouvez pas désactiver votre propre compte.');
    }

    public static function cannotEditOwnRoles(): self
    {
        return new self('Vous ne pouvez pas modifier vos propres rôles.');
    }

    public static function lastActiveAdmin(): self
    {
        return new self("Impossible : c'est le dernier administrateur actif du système.");
    }

    public static function roleAboveOwnRank(): self
    {
        return new self('Vous ne pouvez attribuer que des rôles de rang inférieur ou égal au vôtre.');
    }

    public static function vehicleHasSeller(): self
    {
        return new self('Ce véhicule a déjà un vendeur rattaché. Un véhicule ne peut avoir qu\'un seul vendeur.');
    }
}
