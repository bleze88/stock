<?php
declare(strict_types=1);

function recordLoginAttempt(string $username, string $ip, bool $success): void
{
    $stmt = getDb()->prepare(
        'INSERT INTO login_attempts (username, ip_address, success, attempted_at) VALUES (?, ?, ?, datetime(\'now\'))'
    );
    $stmt->execute([$username, $ip, $success ? 1 : 0]);

    // Purge légère et peu coûteuse des vieilles entrées (probabiliste, évite un cron dédié).
    if (random_int(1, 50) === 1) {
        getDb()->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-7 days')");
    }
}

function isLoginLocked(string $username, string $ip): bool
{
    $db = getDb();

    $stmt = $db->prepare(
        "SELECT COUNT(*) AS n FROM login_attempts
         WHERE username = ? AND success = 0
           AND attempted_at > datetime('now', ?)"
    );
    $stmt->execute([$username, '-' . LOGIN_WINDOW_MINUTES . ' minutes']);
    $byUser = (int)$stmt->fetch()['n'];

    $stmt = $db->prepare(
        "SELECT COUNT(*) AS n FROM login_attempts
         WHERE ip_address = ? AND success = 0
           AND attempted_at > datetime('now', ?)"
    );
    $stmt->execute([$ip, '-' . LOGIN_WINDOW_MINUTES . ' minutes']);
    $byIp = (int)$stmt->fetch()['n'];

    return $byUser >= LOGIN_MAX_ATTEMPTS || $byIp >= (LOGIN_MAX_ATTEMPTS * 3);
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
