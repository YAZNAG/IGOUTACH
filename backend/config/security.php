<?php

declare(strict_types=1);

return [
    // Verrouillage du compte après trop d'échecs de connexion.
    'max_login_attempts' => (int) env('SECURITY_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes' => (int) env('SECURITY_LOCKOUT_MINUTES', 15),

    // Politique de mot de passe.
    'password' => [
        'min_length' => (int) env('SECURITY_PASSWORD_MIN', 8),
        'require_mixed_case' => (bool) env('SECURITY_PASSWORD_MIXED', true),
        'require_numbers' => (bool) env('SECURITY_PASSWORD_NUMBERS', true),
        'require_symbols' => (bool) env('SECURITY_PASSWORD_SYMBOLS', false),
    ],
];
