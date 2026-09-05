#!/bin/bash
# Installation complète, en une commande, sur un serveur Debian 13 fraîchement
# provisionné : durcissement système, installation de la stack (Nginx/PHP-FPM/
# SQLite/Certbot), déploiement de l'application, HTTPS et premier compte admin.
#
# Usage : sur le serveur cible, après avoir cloné ce dépôt,
#   cd stock
#   sudo ./install.sh
#
# Peut aussi être lancé de façon non interactive en pré-remplissant DOMAIN et EMAIL :
#   sudo DOMAIN=stock.exemple.com EMAIL=admin@exemple.com ./install.sh
#
# Script idempotent : peut être relancé sans casse (ex: pour changer de domaine
# ou re-tester une étape).
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Ce script doit être lancé avec sudo (ou en root)." >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -z "${DOMAIN:-}" ]; then
  read -rp "Nom de domaine pointant vers ce serveur (ex: stock.exemple.com) : " DOMAIN
fi
if [ -z "${EMAIL:-}" ]; then
  read -rp "Adresse e-mail pour Let's Encrypt (expiration de certificat) : " EMAIL
fi
export DOMAIN EMAIL

echo "############################################"
echo "# 1/3 - Durcissement système"
echo "############################################"
"${SCRIPT_DIR}/deploy/00-harden-system.sh"

echo "############################################"
echo "# 2/3 - Installation de la stack applicative"
echo "############################################"
"${SCRIPT_DIR}/deploy/01-install-stack.sh"

echo "############################################"
echo "# 3/3 - Déploiement de l'application"
echo "############################################"
"${SCRIPT_DIR}/deploy/02-deploy-app.sh"

cat <<EOF

============================================
Installation terminée : https://${DOMAIN}
============================================

Pour les mises à jour ultérieures et la sauvegarde, voir RUNBOOK.md.
EOF
