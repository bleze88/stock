<?php
declare(strict_types=1);

function currentUser(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = getDb()->prepare('SELECT id, username, role, full_name, active, locale FROM users WHERE id = ? AND active = 1 AND locked = 0');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $user = $row ?: null;

    if ($user === null) {
        // Compte désactivé ou supprimé depuis la connexion : on invalide la session.
        session_unset();
        session_destroy();
    }

    return $user;
}

/**
 * Tente une connexion. Retourne 'ok', 'locked' (compte verrouillé après trop
 * d'échecs, déblocable uniquement par un admin) ou 'invalid'.
 */
function attemptLogin(string $username, string $password): string
{
    $stmt = getDb()->prepare('SELECT id, username, password_hash, role, active, locked, failed_attempts FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || (int)$row['active'] !== 1) {
        return 'invalid';
    }

    if ((int)$row['locked'] === 1) {
        return 'locked';
    }

    if (!password_verify($password, $row['password_hash'])) {
        $newFailedAttempts = (int)$row['failed_attempts'] + 1;
        $shouldLock = $newFailedAttempts >= LOGIN_MAX_ATTEMPTS;
        $upd = getDb()->prepare('UPDATE users SET failed_attempts = ?, locked = ? WHERE id = ?');
        $upd->execute([$newFailedAttempts, $shouldLock ? 1 : 0, $row['id']]);
        return $shouldLock ? 'locked' : 'invalid';
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = getDb()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $row['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf_token']); // nouveau token après élévation de privilège

    $upd = getDb()->prepare('UPDATE users SET last_login_at = datetime(\'now\'), failed_attempts = 0 WHERE id = ?');
    $upd->execute([$row['id']]);

    return 'ok';
}

function logoutUser(): void
{
    $_SESSION = [];
    session_unset();
    session_destroy();
}

function enforceIdleTimeout(): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }
    $last = $_SESSION['last_activity'] ?? time();
    if (time() - $last > SESSION_IDLE_TIMEOUT_SECONDS) {
        logoutUser();
        redirect('login');
    }
    $_SESSION['last_activity'] = time();
}

function requireLogin(): array
{
    $user = currentUser();
    if ($user === null) {
        redirect('login');
    }
    return $user;
}

function requireRole(string $role): array
{
    return requireAnyRole([$role]);
}

function requireAnyRole(array $roles): array
{
    $user = requireLogin();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        render('errors/403', []);
        exit;
    }
    return $user;
}

function canManageStock(): bool
{
    $user = currentUser();
    return $user !== null && in_array($user['role'], [ROLE_ADMIN, ROLE_MANAGER], true);
}
