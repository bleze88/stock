CREATE TABLE IF NOT EXISTS audit_log (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id         INTEGER REFERENCES users(id),
    action          TEXT NOT NULL CHECK (action IN ('create','delete')),
    entity_type     TEXT NOT NULL CHECK (entity_type IN ('type','groupe','variante')),
    entity_id       INTEGER NOT NULL,
    entity_label    TEXT NOT NULL,
    created_at      TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_audit_log_date ON audit_log(created_at);
