# Runbook — Gestion de stock

Remplacez `<domaine>`, `<ip-serveur>` et `<utilisateur>` par les valeurs de votre propre déploiement.

## Installation initiale (nouveau serveur)

Sur un VPS Debian 13 neuf, avec le DNS du domaine déjà pointé dessus :

```
ssh -i ~/.ssh/<votre-clé> <utilisateur>@<ip-serveur>
git clone https://github.com/bleze88/stock.git
cd stock
sudo ./install.sh
```

`install.sh` demande le domaine et un e-mail (pour Let's Encrypt), puis enchaîne :
1. `deploy/00-harden-system.sh` — pare-feu (ufw), durcissement SSH, fail2ban, mises à jour automatiques, NTP
2. `deploy/01-install-stack.sh` — Nginx, PHP-FPM, SQLite, Certbot, réglages PHP adaptés à un petit VPS
3. `deploy/02-deploy-app.sh` — copie du code dans `/var/www/asso-stock/`, config Nginx + HTTPS, migrations de la base, création du premier compte admin

Les trois scripts sont idempotents et peuvent aussi être relancés individuellement (ex: pour ne refaire que le durcissement système, ou re-déployer l'application après un `git pull`). Pour une installation non interactive :
```
sudo DOMAIN=stock.exemple.com EMAIL=admin@exemple.com ADMIN_USER=admin ADMIN_NAME="Prénom Nom" ./install.sh
```

## Accès

- **Admin système** : utilisateur dédié avec clé SSH et sudo.
  `ssh -i ~/.ssh/<votre-clé> <utilisateur>@<ip-serveur>`
- **Admin applicatif** (compte web) : identifiant `admin`, mot de passe défini à la création. À changer dès la première connexion via le menu Utilisateurs.
- Code source de l'application déployé dans `/var/www/asso-stock/` (app/ et bin/ hors webroot, public/ = webroot Nginx).

## Mises à jour système

```
sudo apt update && sudo apt upgrade -y
```
Les correctifs de sécurité Debian peuvent être appliqués automatiquement chaque nuit (`unattended-upgrades`).

## Déployer une mise à jour du code

Depuis votre machine, dans le dossier du projet :
```
rsync -az --rsync-path="sudo rsync" -e "ssh -i ~/.ssh/<votre-clé>" \
  app public bin scripts <utilisateur>@<ip-serveur>:/var/www/asso-stock/
ssh -i ~/.ssh/<votre-clé> <utilisateur>@<ip-serveur> '
  sudo find /var/www/asso-stock/app /var/www/asso-stock/public /var/www/asso-stock/bin /var/www/asso-stock/scripts -exec chown www-data:www-data {} +
  sudo -u www-data php /var/www/asso-stock/app/migrations/migrate.php
'
```

## Gestion des utilisateurs (en ligne de commande, si besoin)

```
ssh -i ~/.ssh/<votre-clé> <utilisateur>@<ip-serveur>
sudo -u www-data php /var/www/asso-stock/bin/console.php list-users
sudo -u www-data php /var/www/asso-stock/bin/console.php create-user <identifiant> <admin|manager|viewer> "Nom complet"
sudo -u www-data php /var/www/asso-stock/bin/console.php reset-password <identifiant>
```
(La gestion normale des utilisateurs se fait cependant directement dans l'interface web, menu Utilisateurs, réservé aux admins.)

## Sauvegardes

- Sauvegarde automatique chaque nuit (cron root) : `/var/www/asso-stock/scripts/backup.sh`
- Stockées dans `/var/backups/asso-stock/` (base SQLite + archive des images), conservées 30 jours.
- Restauration : copier le fichier `.sqlite` souhaité vers `/var/www/asso-stock/storage/database/stock.sqlite` (service arrêté ou site en maintenance le temps de la copie), puis `sudo chown www-data:www-data` + `sudo chmod 640` dessus.
- Recommandé : copier périodiquement `/var/backups/asso-stock/` hors du serveur pour survivre à une panne totale.

## Migration vers un autre hébergeur

1. Copier `app/`, `public/`, `bin/`, `scripts/`, le fichier `storage/database/stock.sqlite` et le dossier `storage/uploads/` vers le nouveau serveur.
2. Installer Nginx + PHP-FPM (extensions : sqlite3, gd, fileinfo, mbstring, zip) sur le nouveau serveur.
3. Reprendre la config Nginx dans `deploy/nginx-asso-stock.conf.template` et relancer `certbot --nginx`.
4. Adapter les permissions (`storage/` en 0750, `stock.sqlite` en 0640, propriétaire `www-data`).

## Certificat HTTPS

Renouvellement automatique via le timer systemd de Certbot (`systemctl list-timers | grep certbot`).

## Test de bon fonctionnement après une modification

```
./scripts/smoke_test.sh https://<domaine>
```
