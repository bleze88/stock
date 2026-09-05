-- Etend le journal d'activite aux comptes utilisateurs (creation/suppression),
-- pas seulement aux articles de stock. SQLite ne permet pas d'alterer un
-- CHECK existant : on reconstruit la table a l'identique (memes id).

PRAGMA foreign_keys = OFF;

CREATE TABLE audit_log_new (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER REFERENCES users(id),
    action          TEXT NOT NULL CHECK (action IN ('create','delete')),
    entity_type     TEXT NOT NULL CHECK (entity_type IN ('type','groupe','variante','user')),
    entity_id       INTEGER NOT NULL,
    entity_label    TEXT NOT NULL,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO audit_log_new SELECT * FROM audit_log;

DROP TABLE audit_log;
ALTER TABLE audit_log_new RENAME TO audit_log;

CREATE INDEX IF NOT EXISTS idx_audit_log_date ON audit_log(created_at);

PRAGMA foreign_keys = ON;
