PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT NOT NULL UNIQUE,
    password_hash   TEXT NOT NULL,
    role            TEXT NOT NULL CHECK (role IN ('admin','benevole')),
    full_name       TEXT NOT NULL,
    active          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at   TEXT
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT NOT NULL,
    ip_address      TEXT NOT NULL,
    success         INTEGER NOT NULL,
    attempted_at    TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup ON login_attempts(username, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip_address, attempted_at);

CREATE TABLE IF NOT EXISTS types (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    nom             TEXT NOT NULL UNIQUE,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    created_by      INTEGER REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS groupes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    type_id         INTEGER NOT NULL REFERENCES types(id) ON DELETE RESTRICT,
    nom             TEXT NOT NULL,
    description     TEXT,
    seuil_alerte    INTEGER NOT NULL DEFAULT 0,
    active          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    created_by      INTEGER REFERENCES users(id),
    UNIQUE(type_id, nom)
);
CREATE INDEX IF NOT EXISTS idx_groupes_type ON groupes(type_id);

CREATE TABLE IF NOT EXISTS variantes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    groupe_id       INTEGER NOT NULL REFERENCES groupes(id) ON DELETE RESTRICT,
    libelle         TEXT NOT NULL,
    sku             TEXT,
    quantite        INTEGER NOT NULL DEFAULT 0,
    seuil_alerte    INTEGER,
    active          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE(groupe_id, libelle)
);
CREATE INDEX IF NOT EXISTS idx_variantes_groupe ON variantes(groupe_id);
CREATE INDEX IF NOT EXISTS idx_variantes_lowstock ON variantes(quantite);

CREATE TABLE IF NOT EXISTS mouvements (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    variante_id     INTEGER NOT NULL REFERENCES variantes(id) ON DELETE RESTRICT,
    user_id         INTEGER NOT NULL REFERENCES users(id),
    delta           INTEGER NOT NULL,
    quantite_apres  INTEGER NOT NULL,
    motif           TEXT,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_mouvements_variante ON mouvements(variante_id, created_at);
CREATE INDEX IF NOT EXISTS idx_mouvements_date ON mouvements(created_at);

CREATE TABLE IF NOT EXISTS images (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    groupe_id       INTEGER NOT NULL REFERENCES groupes(id) ON DELETE CASCADE,
    original_path   TEXT NOT NULL,
    thumb_path      TEXT,
    original_name   TEXT,
    mime_type       TEXT NOT NULL,
    size_bytes      INTEGER NOT NULL,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    uploaded_by     INTEGER REFERENCES users(id),
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_images_groupe ON images(groupe_id);
