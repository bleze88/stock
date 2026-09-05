# Stock

Système de gestion de stock : inventaire hiérarchique (Type → Groupe → Variante), suivi des mouvements de stock, gestion des utilisateurs par rôle, interface multilingue (FR/EN/DE/IT), personnalisable (logo, couleur, nom).

Logiciel libre et gratuit, sans aucune licence d'utilisation restrictive : voir [LICENSE](LICENSE). Prenez-le, modifiez-le, utilisez-le comme vous voulez.

## Stack technique

- **PHP natif** (sans framework) — PDO, sessions, `password_hash`/`password_verify`
- **SQLite** comme base de données (fichier unique, facile à sauvegarder/déplacer)
- **Nginx + PHP-FPM** pour le serveur web
- **Let's Encrypt** pour le HTTPS

Choix assumé : pas de Docker, pas de framework lourd — la stack la plus simple et la plus portable possible (fonctionne sur n'importe quel hébergement PHP/SQLite standard, mutualisé ou VPS).

## Fonctionnalités

- **Authentification sécurisée** : verrouillage après échecs répétés, sessions durcies, CSRF sur toutes les actions
- **Inventaire** : Types → Groupes → Variantes, avec quantités et seuils d'alerte de stock bas
- **Mouvements de stock** : entrées/sorties tracées avec historique complet
- **Images produits** : upload avec redimensionnement et compression automatiques (les photos de téléphone, même très grandes, sont acceptées et optimisées)
- **3 rôles** : Admin (tout), Gestionnaire de stock (créer/modifier/supprimer le stock), Lecteur (consultation seule)
- **Journal d'activité** : trace toute création/suppression d'article
- **Multilingue** : français, anglais, allemand, italien — au choix par utilisateur
- **Personnalisation** : nom du site, logo, couleur principale (menu Paramètres, admin)
- **Export/import du stock** : sauvegarde et migration de l'inventaire (types/groupes/variantes/images) vers un autre serveur en un fichier, sans accès SSH
- **Responsive** : utilisable sur mobile/tablette

## Structure du projet

```
app/
├── bootstrap.php       # session, headers de sécurité, point d'entrée commun
├── config.php          # constantes de configuration
├── lib/                # auth, CSRF, i18n, uploads, audit, paramètres...
├── controllers/        # un fichier par domaine (types, groupes, mouvements...)
├── views/              # templates PHP (layout + pages)
├── lang/               # traductions fr/en/de/it
└── migrations/         # schéma SQL, appliqué via migrate.php

public/                 # webroot Nginx (seul dossier exposé)
├── index.php           # front controller / routeur
└── assets/             # CSS/JS

storage/                # hors webroot — base SQLite + images uploadées (non versionné)
bin/console.php         # création d'utilisateurs en CLI
scripts/                # sauvegarde, test de fumée
deploy/                 # scripts de provisionnement serveur (durcissement, stack, déploiement)
install.sh              # installation complète en une commande (voir ci-dessous)
```

## Documentation

- [RUNBOOK.md](RUNBOOK.md) — accès, mises à jour, sauvegardes, migration vers un autre hébergeur

## Installation sur un serveur (Debian 13)

Sur un VPS Debian 13 fraîchement provisionné, avec un nom de domaine pointant déjà dessus :

```bash
git clone https://github.com/bleze88/stock.git
cd stock
sudo ./install.sh
```

Le script demande le domaine et un e-mail (pour Let's Encrypt), puis enchaîne durcissement système, installation de la stack (Nginx/PHP-FPM/SQLite/Certbot), déploiement de l'application, HTTPS et création du premier compte admin. Les étapes peuvent aussi être lancées séparément (`deploy/00-harden-system.sh`, `deploy/01-install-stack.sh`, `deploy/02-deploy-app.sh`) — voir [RUNBOOK.md](RUNBOOK.md) pour le détail et les mises à jour ultérieures.

## Développement local

Nécessite PHP 8.2+ avec les extensions `pdo_sqlite`, `gd`, `fileinfo`, `mbstring`.

```bash
php app/migrations/migrate.php
php bin/console.php create-user admin admin "Administrateur"
php -S localhost:8000 -t public
```

## Licence

Domaine public / Unlicense — voir [LICENSE](LICENSE). Aucune restriction d'utilisation, de modification ou de redistribution.
