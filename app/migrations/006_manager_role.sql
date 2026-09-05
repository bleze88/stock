-- Ajoute le role "manager" (gestionnaire de stock : peut creer/modifier/supprimer
-- le stock mais pas gerer les utilisateurs ni les parametres du site).
-- Meme technique que 004_viewer_role.sql : SQLite ne permet pas d'alterer un CHECK.

PRAGMA foreign_keys = OFF;

CREATE TABLE users_new (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    role            TEXT NOT NULL CHECK (role IN ('admin','manager','viewer')),
    full_name       TEXT NOT NULL,
    active          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at   TEXT,
    locale          TEXT NOT NULL DEFAULT 'fr'
);

INSERT INTO users_new (id, username, password_hash, role, full_name, active, created_at, last_login_at, locale)
SELECT id, username, password_hash, role, full_name, active, created_at, last_login_at, locale
FROM users;

DROP TABLE users;
ALTER TABLE users_new RENAME TO users;

PRAGMA foreign_keys = ON;
