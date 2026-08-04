#!/usr/bin/env bash
# =============================================================================
# Déploiement IGOUTECH (backend Laravel + données de test) sur un VPS
# Debian/Ubuntu. Idempotent : relançable sans casser l'existant.
#
# Usage (en root sur le VPS) :
#   bash vps-setup.sh [REPO_URL]
#
# REPO_URL par défaut : https://github.com/YAZNAG/IGOUTACH.git
# (si le dépôt est privé, utiliser une URL avec token :
#  https://<TOKEN>@github.com/YAZNAG/IGOUTACH.git)
#
# À la fin :
#   API      : http://<IP_DU_VPS>/api/v1
#   Login    : admin@igoutech.ma / ChangeMe!2026  (à changer !)
#   APK      : compiler avec
#     flutter build apk --release --dart-define=API_URL=http://<IP_DU_VPS>/api/v1
# =============================================================================
set -euo pipefail

REPO_URL="${1:-https://github.com/YAZNAG/IGOUTACH.git}"
APP_DIR="/var/www/igoutech"
DB_NAME="igoutech"
DB_USER="igoutech"
DB_PASS="Igoutech!Db2026"
PHP_V="8.3"

echo "== 1/7 Paquets système =="
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq software-properties-common curl git unzip nginx mariadb-server >/dev/null
if ! command -v php >/dev/null || ! php -v | grep -q "PHP ${PHP_V}"; then
  add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true
  apt-get update -qq
  apt-get install -y -qq \
    php${PHP_V}-fpm php${PHP_V}-cli php${PHP_V}-mysql php${PHP_V}-mbstring \
    php${PHP_V}-xml php${PHP_V}-curl php${PHP_V}-zip php${PHP_V}-gd \
    php${PHP_V}-bcmath php${PHP_V}-intl >/dev/null
fi
command -v composer >/dev/null || {
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
}

echo "== 2/7 Base de données MariaDB =="
systemctl enable --now mariadb >/dev/null
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

echo "== 3/7 Clone / mise à jour du dépôt =="
if [ -d "${APP_DIR}/.git" ]; then
  git -C "${APP_DIR}" pull --ff-only
else
  git clone "${REPO_URL}" "${APP_DIR}"
fi
cd "${APP_DIR}/backend"

echo "== 4/7 Dépendances + .env =="
composer install --no-dev --optimize-autoloader --no-interaction --quiet
if [ ! -f .env ]; then
  cp .env.example .env 2>/dev/null || touch .env
  cat > .env <<ENV
APP_NAME=IGOUTECH
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Africa/Casablanca
APP_URL=http://$(hostname -I | awk '{print $1}')
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

ADMIN_EMAIL=admin@igoutech.ma
ADMIN_PASSWORD=ChangeMe!2026

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
SANCTUM_STATEFUL_DOMAINS=
ENV
  php artisan key:generate --force
fi

echo "== 5/7 Migrations + données de test =="
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class="Database\\Seeders\\TestDataSeeder" --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

echo "== 6/7 Droits + Nginx =="
chown -R www-data:www-data "${APP_DIR}/backend/storage" "${APP_DIR}/backend/bootstrap/cache"
cat > /etc/nginx/sites-available/igoutech <<'NGINX'
server {
    listen 80 default_server;
    server_name _;
    root /var/www/igoutech/backend/public;
    index index.php;
    client_max_body_size 20m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/igoutech /etc/nginx/sites-enabled/igoutech
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
systemctl enable --now php${PHP_V}-fpm >/dev/null

echo "== 7/7 Vérification =="
sleep 1
IP=$(hostname -I | awk '{print $1}')
CODE=$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1/api/v1/user")
echo ""
echo "============================================================"
echo " IGOUTECH déployé."
echo " API      : http://${IP}/api/v1   (test /user => HTTP ${CODE}, 401 = OK)"
echo " Admin    : admin@igoutech.ma / ChangeMe!2026  — CHANGEZ-LE"
echo " APK      : flutter build apk --release \\"
echo "              --dart-define=API_URL=http://${IP}/api/v1"
echo "============================================================"
