<?php

declare(strict_types=1);

return [
    /*
     * Compte administrateur créé par AdminSeeder. Défini ici (et non via env()
     * directement dans le seeder) pour rester compatible avec `config:cache`.
     */
    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
