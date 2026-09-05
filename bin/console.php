<?php
declare(strict_types=1);

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/validate.php';

function usage(): never
{
    echo "Usage:\n";
    echo "  php bin/console.php create-user <username> <role: admin|manager|viewer> \"<Nom complet>\"\n";
    echo "  php bin/console.php reset-password <username>\n";
    echo "  php bin/console.php list-users\n";
    exit(1);
}

$command = $argv[1] ?? null;
if ($command === null) {
    usage();
}

$db = getDb();

function promptPassword(string $label = 'Mot de passe'): string
{
    echo "{$label}: ";
    system('stty -echo');
    $password = trim((string)fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $password;
}

switch ($command) {
    case 'create-user':
        [$username, $role, $fullName] = [$argv[2] ?? '', $argv[3] ?? '', $argv[4] ?? ''];
        if (!isValidUsername($username) || !isValidRole($role) || $fullName === '') {
            usage();
        }
        $password = promptPassword();
        $confirm = promptPassword('Confirmer le mot de passe');
        if ($password !== $confirm) {
            fwrite(STDERR, "Les mots de passe ne correspondent pas.\n");
            exit(1);
        }
        if (strlen($password) < 12) {
            fwrite(STDERR, "Le mot de passe doit faire au moins 12 caractères.\n");
            exit(1);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $hash, $role, $fullName]);
        echo "Utilisateur '{$username}' créé (rôle: {$role}).\n";
        break;

    case 'reset-password':
        $username = $argv[2] ?? '';
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row) {
            fwrite(STDERR, "Utilisateur introuvable.\n");
            exit(1);
        }
        $password = promptPassword();
        $confirm = promptPassword('Confirmer le mot de passe');
        if ($password !== $confirm || strlen($password) < 12) {
            fwrite(STDERR, "Mots de passe invalides ou non concordants (12 caractères min).\n");
            exit(1);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$hash, $row['id']]);
        echo "Mot de passe mis à jour pour '{$username}'.\n";
        break;

    case 'list-users':
        $rows = $db->query('SELECT id, username, role, full_name, active, last_login_at FROM users ORDER BY id')->fetchAll();
        foreach ($rows as $r) {
            printf(
                "#%d %-20s %-10s %-25s actif=%d dernière connexion=%s\n",
                $r['id'], $r['username'], $r['role'], $r['full_name'], $r['active'], $r['last_login_at'] ?? '-'
            );
        }
        break;

    default:
        usage();
}
