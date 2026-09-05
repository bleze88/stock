<h1><?= e(t('mouvements_history_title')) ?></h1>

<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_date')) ?></th><th><?= e(t('th_group')) ?></th><th><?= e(t('th_variant')) ?></th><th><?= e(t('th_movement')) ?></th><th><?= e(t('th_qty_after')) ?></th><th><?= e(t('th_by')) ?></th><th><?= e(t('th_reason')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($mouvements as $m): ?>
        <tr>
            <td><?= e($m['created_at']) ?></td>
            <td><a href="/index.php?r=groupes/show&id=<?= (int)$m['groupe_id'] ?>"><?= e($m['groupe_nom']) ?></a></td>
            <td><?= e($m['variante_libelle']) ?></td>
            <td class="<?= $m['delta'] >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= $m['delta'] >= 0 ? '+' : '' ?><?= (int)$m['delta'] ?>
            </td>
            <td><?= (int)$m['quantite_apres'] ?></td>
            <td><?= e($m['user_full_name']) ?></td>
            <td><?= e($m['motif'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$mouvements): ?>
        <tr><td colspan="7"><?= e(t('dashboard_no_movements')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
