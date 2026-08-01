<?php

declare(strict_types=1);

return [
    /*
     | Chemin complet vers l'exécutable mysqldump. Sur WAMP par exemple :
     | C:\wamp64\bin\mariadb\mariadb11.5.2\bin\mysqldump.exe
     | Laisser vide pour utiliser le mysqldump du PATH système.
     */
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', null),
];
