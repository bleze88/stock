<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../lib/db.php';

$db = getDb();
$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL UNIQUE,
        applied_at TEXT NOT NULL DEFAULT (datetime(\'now\'))
    )'
);

$applied = $db->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }
    echo "Applying {$name}...\n";
    $sql = file_get_contents($file);
    $db->exec($sql);
    $stmt = $db->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
    $stmt->execute([$name]);
}

echo "Migrations à jour.\n";
