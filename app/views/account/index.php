<h1><?= e(t('account_title')) ?></h1>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<div class="card">
    <h2><?= e(t('account_info_title')) ?></h2>
    <form method="post" action="/index.php?r=account/update">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profile">

        <label for="username"><?= e(t('account_username_label')) ?></label>
        <input type="text" id="username" value="<?= e($targetUser['username']) ?>" disabled>

        <label for="full_name"><?= e(t('account_fullname_label')) ?></label>
        <input type="text" id="full_name" name="full_name" maxlength="150" required value="<?= e($targetUser['full_name']) ?>">

        <label for="role_display"><?= e(t('account_role_label')) ?></label>
        <input type="text" id="role_display" value="<?= e(roleLabel($targetUser['role'])) ?>" disabled>

        <label for="locale"><?= e(t('account_language_title')) ?></label>
        <p class="field-hint"><?= e(t('account_language_hint')) ?></p>
        <select id="locale" name="locale">
            <?php foreach (LOCALE_LABELS as $code => $label): ?>
            <option value="<?= e($code) ?>" <?= $targetUser['locale'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    </form>
</div>

<div class="card">
    <h2><?= e(t('account_password_title')) ?></h2>
    <form method="post" action="/index.php?r=account/update">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_password">

        <label for="current_password"><?= e(t('account_current_password_label')) ?></label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>

        <label for="new_password"><?= e(t('account_new_password_label')) ?></label>
        <input type="password" id="new_password" name="new_password" minlength="12" autocomplete="new-password" required>

        <label for="confirm_password"><?= e(t('account_confirm_password_label')) ?></label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="12" autocomplete="new-password" required>
        <p class="field-hint"><?= e(t('account_password_hint')) ?></p>

        <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    </form>
</div>
