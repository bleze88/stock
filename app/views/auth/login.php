<div class="auth-card">
    <h1><?= e(t('login_title')) ?></h1>
    <?php if ($error): ?>
    <p class="flash flash--error"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/index.php?r=login" novalidate>
        <?= csrfField() ?>
        <label for="username"><?= e(t('login_username')) ?></label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>

        <label for="password"><?= e(t('login_password')) ?></label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary btn-block"><?= e(t('login_submit')) ?></button>
    </form>
</div>
