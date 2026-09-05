<?php
declare(strict_types=1);

if ($route === 'logout') {
    requireLogin();
    if (isPost()) {
        csrfVerify();
    }
    logoutUser();
    redirect('login');
}

// $route === 'login'
if (currentUser() !== null) {
    redirect('dashboard');
}

$error = null;

if (isPost()) {
    csrfVerify();

    $username = postString('username');
    $password = (string)($_POST['password'] ?? '');
    $ip = clientIp();

    if (isLoginLocked($username, $ip)) {
        $error = t('login_error_locked');
    } elseif ($username === '' || $password === '') {
        $error = t('login_error_invalid');
        recordLoginAttempt($username, $ip, false);
    } elseif (attemptLogin($username, $password)) {
        recordLoginAttempt($username, $ip, true);
        redirect('dashboard');
    } else {
        recordLoginAttempt($username, $ip, false);
        $error = t('login_error_invalid');
    }
}

render('auth/login', ['error' => $error]);
