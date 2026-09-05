# Runbook — Gestion de stock

Remplacez `<domaine>`, `<ip-serveur>` et `<utilisateur>` par les valeurs de votre propre déploiement.

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
