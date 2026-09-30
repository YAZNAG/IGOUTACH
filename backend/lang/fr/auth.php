<?php

declare(strict_types=1);

/**
 * Messages d'authentification en français.
 *
 * « auth.failed » est appelé par LoginRequest ; sans traduction, l'écran de
 * connexion affichait la clé au lieu du message.
 */
return [
    'failed' => 'Ces identifiants ne correspondent à aucun compte.',
    'password' => 'Le mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives. Réessayez dans :seconds secondes.',
];
