<h1><?= e(t('dashboard_title')) ?></h1>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-card__value"><?= (int)$totalArticles ?></span>
        <span class="stat-card__label"><?= e(t('dashboard_stat_articles')) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= (int)$totalGroupes ?></span>
        <span class="stat-card__label"><?= e(t('dashboard_stat_groups')) ?></span>
    </div>
    <div class="stat-card <?= count($lowStock) > 0 ? 'stat-card--warning' : '' ?>">
        <span class="stat-card__value"><?= count($lowStock) ?></span>
        <span class="stat-card__label"><?= e(t('dashboard_stat_alerts')) ?></span>
    </div>
</div>

<h2><?= e(t('dashboard_stock_by_type')) ?></h2>
<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_type')) ?></th><th><?= e(t('th_groups')) ?></th><th><?= e(t('th_total_qty')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($totalsByType as $t): ?>
        <tr>
            <td><?= e($t['nom']) ?></td>
            <td><?= (int)$t['nb_groupes'] ?></td>
            <td><?= (int)$t['total_quantite'] ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$totalsByType): ?>
        <tr><td colspan="3"><?= e(t('dashboard_no_types')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<h2><?= e(t('dashboard_low_stock')) ?></h2>
<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_type')) ?></th><th><?= e(t('th_group')) ?></th><th><?= e(t('th_variant')) ?></th><th><?= e(t('th_qty')) ?></th><th><?= e(t('th_threshold')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($lowStock as $ls): ?>
        <tr class="table-row--warning">
            <td><?= e($ls['type_nom']) ?></td>
            <td><a href="/index.php?r=groupes/show&id=<?= (int)$ls['groupe_id'] ?>"><?= e($ls['groupe_nom']) ?></a></td>
            <td><?= e($ls['libelle']) ?></td>
            <td><?= (int)$ls['quantite'] ?></td>
            <td><?= (int)($ls['variante_seuil'] ?? $ls['groupe_seuil']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lowStock): ?>
        <tr><td colspan="5"><?= e(t('dashboard_no_alerts')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<h2><?= e(t('dashboard_recent_movements')) ?></h2>
<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_date')) ?></th><th><?= e(t('th_group_variant')) ?></th><th><?= e(t('th_movement')) ?></th><th><?= e(t('th_qty_after')) ?></th><th><?= e(t('th_by')) ?></th><th><?= e(t('th_reason')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($recentMouvements as $m): ?>
        <tr>
            <td><?= e($m['created_at']) ?></td>
            <td><?= e($m['groupe_nom']) ?> — <?= e($m['variante_libelle']) ?></td>
            <td class="<?= $m['delta'] >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= $m['delta'] >= 0 ? '+' : '' ?><?= (int)$m['delta'] ?>
            </td>
            <td><?= (int)$m['quantite_apres'] ?></td>
            <td><?= e($m['user_full_name']) ?></td>
            <td><?= e($m['motif'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$recentMouvements): ?>
        <tr><td colspan="6"><?= e(t('dashboard_no_movements')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
