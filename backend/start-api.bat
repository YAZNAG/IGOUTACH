@echo off
REM Serveur de dev de l'API IGOUTECH (port 8001).
REM Le frontend local (.env : VITE_API_URL=http://localhost:8001/api/v1) tape ici.
cd /d "%~dp0"
php artisan serve --port=8001
