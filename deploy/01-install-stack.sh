#!/bin/bash
# Installation de la stack applicative (Nginx + PHP-FPM + SQLite + Certbot)
# A executer avec sudo sur le VPS, apres 00-harden-system.sh
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

echo "== Installation Nginx + PHP-FPM + extensions + Certbot =="
apt-get update -y
apt-get install -y nginx php-fpm php-sqlite3 php-gd php-mbstring php-curl \
    certbot python3-certbot-nginx sqlite3 unzip git

PHP_VERSION=$(php -v | head -1 | awk '{print $2}' | cut -d. -f1,2)
echo "PHP version detectee: ${PHP_VERSION}"

echo "== Creation de l'arborescence applicative =="
APP_ROOT=/var/www/asso-stock
mkdir -p "${APP_ROOT}"/{public/assets/css,public/assets/js,public/media,app,storage/database,storage/uploads,storage/logs,scripts,bin}

chown -R www-data:www-data "${APP_ROOT}"
chmod 750 "${APP_ROOT}/storage" "${APP_ROOT}/storage/database" "${APP_ROOT}/storage/uploads" "${APP_ROOT}/storage/logs"

echo "== Reglages PHP-FPM (pool www) pour VPS 2 Go RAM =="
POOL_CONF="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
if [ -f "$POOL_CONF" ]; then
  cp -n "$POOL_CONF" "${POOL_CONF}.bak.$(date +%s)" || true
  sed -i -E "s/^pm = .*/pm = ondemand/" "$POOL_CONF"
  sed -i -E "s/^pm.max_children = .*/pm.max_children = 6/" "$POOL_CONF"
  sed -i -E "s/^;pm.process_idle_timeout = .*/pm.process_idle_timeout = 10s/" "$POOL_CONF"
  grep -q '^pm.max_requests' "$POOL_CONF" || echo "pm.max_requests = 500" >> "$POOL_CONF"
fi

echo "== Durcissement php.ini (upload / erreurs / expose) =="
for INI in /etc/php/${PHP_VERSION}/fpm/php.ini /etc/php/${PHP_VERSION}/cli/php.ini; do
  [ -f "$INI" ] || continue
  cp -n "$INI" "${INI}.bak.$(date +%s)" || true
  sed -i -E "s/^expose_php = .*/expose_php = Off/" "$INI"
  sed -i -E "s/^display_errors = .*/display_errors = Off/" "$INI"
  sed -i -E "s/^;?log_errors = .*/log_errors = On/" "$INI"
  sed -i -E "s/^upload_max_filesize = .*/upload_max_filesize = 16M/" "$INI"
  sed -i -E "s/^post_max_size = .*/post_max_size = 17M/" "$INI"
  sed -i -E "s/^memory_limit = .*/memory_limit = 256M/" "$INI"
  sed -i -E "s/^;?session.cookie_httponly = .*/session.cookie_httponly = 1/" "$INI"
  sed -i -E "s/^;?session.cookie_samesite = .*/session.cookie_samesite = Lax/" "$INI"
  sed -i -E "s/^;?session.use_strict_mode = .*/session.use_strict_mode = 1/" "$INI"
  sed -i -E "s/^disable_functions = .*/disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source/" "$INI"
done

systemctl enable --now "php${PHP_VERSION}-fpm"
systemctl restart "php${PHP_VERSION}-fpm"

echo "== Desactivation du site Nginx par defaut =="
rm -f /etc/nginx/sites-enabled/default

echo "== Stack installee. PHP-FPM socket attendu : /run/php/php${PHP_VERSION}-fpm.sock =="
echo "== Prochaine etape : deployer nginx-asso-stock.conf puis lancer certbot =="
