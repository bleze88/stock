<?php
declare(strict_types=1);

$currentAdmin = requireRole(ROLE_ADMIN);
$db = getDb();

if ($route === 'users') {
    $users = $db->query('SELECT id, username, role, full_name, active, last_login_at FROM users ORDER BY username')->fetchAll();
    render('users/index', ['users' => $users, 'currentAdminId' => (int)$currentAdmin['id']]);
    exit;
}

if ($route === 'users/create') {
    $error = null;

    if (isPost()) {
        csrfVerify();
        $username = postString('username');
        $fullName = postString('full_name');
        $role = postString('role');
        $password = (string)($_POST['password'] ?? '');

        if (!isValidUsername($username)) {
            $error = t('users_error_username_invalid');
        } elseif (!isNonEmptyString($fullName, 150)) {
            $error = t('users_error_fullname_required');
        } elseif (!isValidRole($role)) {
            $error = t('users_error_role_invalid');
        } elseif (strlen($password) < 12) {
            $error = t('users_error_password_length');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare(
                    'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$username, $hash, $role, $fullName]);
                flashSet('success', t('users_created', ['username' => $username]));
                redirect('users');
            } catch (PDOException $e) {
                $error = t('users_error_username_duplicate');
            }
        }
    }

    render('users/form', ['error' => $error, 'targetUser' => null]);
    exit;
}

if ($route === 'users/edit') {
    $id = getInt('id');
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $targetUser = $stmt->fetch();
    if (!$targetUser) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $error = null;

    if (isPost()) {
        csrfVerify();
        $fullName = postString('full_name');
        $role = postString('role');
        $active = postInt('active', 0) ? 1 : 0;
        $newPassword = (string)($_POST['password'] ?? '');

        if ((int)$targetUser['id'] === (int)$currentAdmin['id'] && $active === 0) {
            $error = t('users_error_cannot_disable_self');
        } elseif (!isNonEmptyString($fullName, 150)) {
            $error = t('users_error_fullname_required');
        } elseif (!isValidRole($role)) {
            $error = t('users_error_role_invalid');
        } elseif ($newPassword !== '' && strlen($newPassword) < 12) {
            $error = t('users_error_password_length');
        } else {
            if ($newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $db->prepare('UPDATE users SET full_name = ?, role = ?, active = ?, password_hash = ? WHERE id = ?');
                $upd->execute([$fullName, $role, $active, $hash, $id]);
            } else {
                $upd = $db->prepare('UPDATE users SET full_name = ?, role = ?, active = ? WHERE id = ?');
                $upd->execute([$fullName, $role, $active, $id]);
            }
            flashSet('success', t('users_updated'));
            redirect('users');
        }
    }

    render('users/form', ['error' => $error, 'targetUser' => $targetUser]);
    exit;
}

if ($route === 'users/delete') {
    if (!isPost()) {
        redirect('users');
    }
    csrfVerify();

    $id = postInt('id');
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $targetUser = $stmt->fetch();
    if (!$targetUser) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    if ((int)$targetUser['id'] === (int)$currentAdmin['id']) {
        flashSet('error', t('users_error_cannot_delete_self'));
        redirect('users');
    }

    try {
        $del = $db->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$id]);
        flashSet('success', t('users_deleted', ['username' => $targetUser['username']]));
    } catch (PDOException $e) {
        flashSet('error', t('users_error_has_activity'));
    }

    redirect('users');
}
