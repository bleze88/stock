<?php
declare(strict_types=1);

$user = requireLogin();
$db = getDb();

function currentAccountViewData(array $user, ?string $error = null): array
{
    return [
        'error' => $error,
        'targetUser' => $user,
    ];
}

if ($route === 'account') {
    render('account/index', currentAccountViewData($user));
    exit;
}

if ($route === 'account/update') {
    if (!isPost()) {
        redirect('account');
    }
    csrfVerify();

    $action = postString('action');

    if ($action === 'update_profile') {
        $fullName = postString('full_name');
        $locale = postString('locale');

        if (!isNonEmptyString($fullName, 150)) {
            render('account/index', currentAccountViewData($user, t('account_error_fullname_required')));
            exit;
        }
        if (!isValidLocale($locale)) {
            $locale = DEFAULT_LOCALE;
        }

        $stmt = $db->prepare('UPDATE users SET full_name = ?, locale = ? WHERE id = ?');
        $stmt->execute([$fullName, $locale, $user['id']]);

        setcookie('lang', $locale, time() + 60 * 60 * 24 * 365, '/', '', FORCE_SECURE_COOKIES, true);

        flashSet('success', t('account_updated'));
        redirect('account');
    }

    if ($action === 'update_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            render('account/index', currentAccountViewData($user, t('account_error_current_password')));
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            render('account/index', currentAccountViewData($user, t('account_error_password_mismatch')));
            exit;
        }
        if (strlen($newPassword) < 12) {
            render('account/index', currentAccountViewData($user, t('account_error_password_length')));
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$hash, $user['id']]);

        flashSet('success', t('account_password_updated'));
        redirect('account');
    }

    redirect('account');
}
