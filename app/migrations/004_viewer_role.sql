-- Remplace le rôle "benevole" par "viewer" (lecture seule) : seuls deux rôles
-- subsistent, admin (création/modification/suppression) et viewer (consultation).
-- SQLite ne permet pas d'altérer une contrainte CHECK existante : on reconstruit
-- la table users à l'identique (mêmes id) avec la nouvelle contrainte.

PRAGMA foreign_keys = OFF;

CREATE TABLE users_new (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    role            TEXT NOT NULL CHECK (role IN ('admin','viewer')),
    full_name       TEXT NOT NULL,
    active          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at   TEXT,
    locale          TEXT NOT NULL DEFAULT 'fr'
);

INSERT INTO users_new (id, username, password_hash, role, full_name, active, created_at, last_login_at, locale)
SELECT id, username, password_hash,
       CASE WHEN role = 'benevole' THEN 'viewer' ELSE role END,
       full_name, active, created_at, last_login_at, locale
FROM users;

DROP TABLE users;
ALTER TABLE users_new RENAME TO users;

PRAGMA foreign_keys = ON;
