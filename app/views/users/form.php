<h1><?= $targetUser ? e(t('users_form_title_edit')) : e(t('users_form_title_new')) ?></h1>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/index.php?r=users/<?= $targetUser ? 'edit&id=' . (int)$targetUser['id'] : 'create' ?>">
    <?= csrfField() ?>

    <?php if (!$targetUser): ?>
    <label for="username"><?= e(t('users_username_label')) ?></label>
    <input type="text" id="username" name="username" maxlength="50" required pattern="[a-zA-Z0-9._-]{3,50}">
    <?php endif; ?>

    <label for="full_name"><?= e(t('users_fullname_label')) ?></label>
    <input type="text" id="full_name" name="full_name" maxlength="150" required value="<?= e($targetUser['full_name'] ?? '') ?>">

    <label for="role"><?= e(t('users_role_label')) ?></label>
    <select id="role" name="role" required>
        <option value="viewer" <?= ($targetUser['role'] ?? '') === 'viewer' ? 'selected' : '' ?>><?= e(t('users_role_viewer')) ?></option>
        <option value="manager" <?= ($targetUser['role'] ?? '') === 'manager' ? 'selected' : '' ?>><?= e(t('users_role_manager')) ?></option>
        <option value="admin" <?= ($targetUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>><?= e(t('users_role_admin')) ?></option>
    </select>

    <?php if ($targetUser): ?>
    <label class="checkbox-label">
        <input type="checkbox" name="active" value="1" <?= $targetUser['active'] ? 'checked' : '' ?>>
        <?= e(t('users_active_label')) ?>
    </label>
    <?php endif; ?>

    <label for="password"><?= $targetUser ? e(t('users_password_label_edit')) : e(t('users_password_label_new')) ?></label>
    <input type="password" id="password" name="password" minlength="12" autocomplete="new-password" <?= $targetUser ? '' : 'required' ?>>
    <p class="field-hint"><?= e(t('users_password_hint')) ?></p>

    <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    <a class="btn btn-link" href="/index.php?r=users"><?= e(t('common_cancel')) ?></a>
</form>
