<?php

declare(strict_types=1);

namespace App\Domain\Access\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rules\Password;

/**
 * Politique de mot de passe centralisée (longueur, casse, chiffres, symboles).
 * Une seule source de vérité, réutilisée par toutes les requêtes qui fixent un mot de passe.
 */
final class PasswordPolicy
{
    public static function rule(): Password
    {
        $rule = Password::min((int) Config::get('security.password.min_length', 8));

        if ((bool) Config::get('security.password.require_mixed_case', true)) {
            $rule->mixedCase();
        }

        if ((bool) Config::get('security.password.require_numbers', true)) {
            $rule->numbers();
        }

        if ((bool) Config::get('security.password.require_symbols', false)) {
            $rule->symbols();
        }

        return $rule;
    }
}
