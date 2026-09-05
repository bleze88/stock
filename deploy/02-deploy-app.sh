#!/bin/bash
# Déploiement de l'application : copie du code, config Nginx, HTTPS, migrations,
# premier compte administrateur.
# A executer avec sudo, après 01-install-stack.sh, depuis la racine du dépôt cloné
# sur le serveur (ce script se repère par rapport à son propre emplacement).
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
APP_ROOT=/var/www/asso-stock

DOMAIN="${DOMAIN:-}"
EMAIL="${EMAIL:-}"
if [ -z "$DOMAIN" ]; then
  read -rp "Nom de domaine (ex: stock.exemple.com) : " DOMAIN
fi
if [ -z "$EMAIL" ]; then
  read -rp "Adresse e-mail pour Let's Encrypt (expiration de certificat) : " EMAIL
fi

echo "== Copie du code applicatif vers ${APP_ROOT} =="
mkdir -p "${APP_ROOT}"
rsync -a "${REPO_ROOT}/app" "${REPO_ROOT}/public" "${REPO_ROOT}/bin" "${REPO_ROOT}/scripts" "${APP_ROOT}/"
mkdir -p "${APP_ROOT}/storage/database" "${APP_ROOT}/storage/uploads" "${APP_ROOT}/storage/logs" "${APP_ROOT}/public/media"
chown -R www-data:www-data "${APP_ROOT}"
chmod 750 "${APP_ROOT}/storage" "${APP_ROOT}/storage/database" "${APP_ROOT}/storage/uploads" "${APP_ROOT}/storage/logs"

echo "== Configuration Nginx pour ${DOMAIN} =="
PHP_VERSION=$(php -v | head -1 | awk '{print $2}' | cut -d. -f1,2)
mkdir -p /var/www/letsencrypt
sed -e "s/votre-domaine\.example/${DOMAIN}/g" \
    -e "s/PHP_FPM_SOCK/php${PHP_VERSION}-fpm.sock/g" \
    "${REPO_ROOT}/deploy/nginx-asso-stock.conf.template" > /etc/nginx/sites-available/asso-stock.conf
ln -sf /etc/nginx/sites-available/asso-stock.conf /etc/nginx/sites-enabled/asso-stock.conf
nginx -t
systemctl reload nginx

echo "== Certificat HTTPS (Let's Encrypt) =="
certbot --nginx --redirect --non-interactive --agree-tos -m "${EMAIL}" -d "${DOMAIN}"

echo "== Migrations de la base de données =="
sudo -u www-data php "${APP_ROOT}/app/migrations/migrate.php"

echo "== Création du premier compte administrateur =="
ADMIN_USER="${ADMIN_USER:-}"
ADMIN_NAME="${ADMIN_NAME:-}"
if [ -z "$ADMIN_USER" ]; then
  read -rp "Identifiant du compte admin (ex: admin) : " ADMIN_USER
fi
if [ -z "$ADMIN_NAME" ]; then
  read -rp "Nom complet : " ADMIN_NAME
fi
sudo -u www-data php "${APP_ROOT}/bin/console.php" create-user "${ADMIN_USER}" admin "${ADMIN_NAME}"

echo "== Déploiement terminé : https://${DOMAIN} =="
