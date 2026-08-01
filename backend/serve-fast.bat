@echo off
REM Serveur de dev IGOUTECH rapide : OPcache + validate_timestamps=0.
REM ~10x plus rapide que "php artisan serve" (supprime le re-scan des fichiers
REM a chaque requete, tres couteux sur Windows a cause de l'antivirus).
REM
REM /!\ Avec validate_timestamps=0, une modification du code PHP backend n'est
REM     PAS prise en compte tant que le serveur n'est pas redemarre (Ctrl+C puis
REM     relancer ce script). Ne concerne pas le frontend (Vite a son propre HMR).
REM
REM NB : on ne met PAS la config en cache (config:cache) car cela ferait pointer
REM      les tests sur la base de dev. OPcache suffit pour la vitesse.

cd /d "%~dp0"

REM Nettoie tout cache susceptible de perturber tests/config.
call php artisan optimize:clear

php -d zend_extension=php_opcache.dll ^
    -d opcache.enable=1 ^
    -d opcache.enable_cli=1 ^
    -d opcache.validate_timestamps=0 ^
    -d opcache.memory_consumption=256 ^
    -d opcache.max_accelerated_files=30000 ^
    -d opcache.interned_strings_buffer=16 ^
    -S 127.0.0.1:8001 server.php
