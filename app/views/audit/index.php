<h1><?= e(t('audit_title')) ?></h1>

<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_date')) ?></th><th><?= e(t('th_action')) ?></th><th><?= e(t('th_entity')) ?></th><th><?= e(t('th_label')) ?></th><th><?= e(t('th_by')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($entries as $entry): ?>
        <tr>
            <td><?= e($entry['created_at']) ?></td>
            <td class="<?= $entry['action'] === 'delete' ? 'text-danger' : 'text-success' ?>">
                <?= $entry['action'] === 'delete' ? e(t('audit_action_delete')) : e(t('audit_action_create')) ?>
            </td>
            <td><?= e(t('audit_entity_type_' . $entry['entity_type'])) ?></td>
            <td><?= e($entry['entity_label']) ?></td>
            <td><?= e($entry['user_full_name'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$entries): ?>
        <tr><td colspan="5"><?= e(t('audit_none')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
