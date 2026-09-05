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
        <tr>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e(roleLabel($u['role'])) ?></td>
            <td><?= $u['active'] ? e(t('common_yes')) : e(t('common_no')) ?></td>
            <td><?= e($u['last_login_at'] ?? '—') ?></td>
            <td><a class="btn btn-secondary" href="/index.php?r=users/edit&id=<?= (int)$u['id'] ?>"><?= e(t('common_edit')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>
