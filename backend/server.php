<?php

declare(strict_types=1);

/**
 * Routeur pour le serveur PHP intégré (dev).
 * Permet de lancer le serveur avec OPcache activé :
 *
 *   php -d zend_extension=php_opcache.dll -d opcache.enable=1 -d opcache.enable_cli=1 \
 *       -d opcache.validate_timestamps=1 -d opcache.revalidate_freq=2 \
 *       -S 127.0.0.1:8001 server.php
 *
 * OPcache garde le bytecode compilé entre les requêtes (le serveur intégré est
 * un process persistant) — ce qui supprime le surcoût de compilation par requête.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Sert les fichiers statiques existants tels quels.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
