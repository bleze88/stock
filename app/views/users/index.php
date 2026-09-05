<div class="page-header">
    <h1><?= e(t('users_page_title')) ?></h1>
    <a class="btn btn-primary" href="/index.php?r=users/create"><?= e(t('users_new')) ?></a>
</div>

<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_username')) ?></th><th><?= e(t('th_name')) ?></th><th><?= e(t('th_role')) ?></th><th><?= e(t('th_active')) ?></th><th><?= e(t('th_last_login')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr class="<?= $u['locked'] ? 'table-row--warning' : '' ?>">
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e(roleLabel($u['role'])) ?></td>
            <td>
                <?php if ($u['locked']): ?>
                <span class="text-danger">🔒 <?= e(t('users_locked_label')) ?></span>
                <?php else: ?>
                <?= $u['active'] ? e(t('common_yes')) : e(t('common_no')) ?>
                <?php endif; ?>
            </td>
            <td><?= e($u['last_login_at'] ?? '—') ?></td>
            <td class="table-actions">
                <a class="btn btn-secondary" href="/index.php?r=users/edit&id=<?= (int)$u['id'] ?>"><?= e(t('common_edit')) ?></a>
                <?php if ($u['locked']): ?>
                <form method="post" action="/index.php?r=users/unlock" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-secondary"><?= e(t('users_unlock_button')) ?></button>
                </form>
                <?php endif; ?>
                <?php if ((int)$u['id'] !== $currentAdminId): ?>
                <a class="btn btn-secondary btn-danger" href="/index.php?r=users/delete&id=<?= (int)$u['id'] ?>"><?= e(t('common_delete')) ?></a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
