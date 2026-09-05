<?php $canManage = canManageStock(); ?>
<div class="page-header">
    <div>
        <p class="breadcrumb"><a href="/index.php?r=types"><?= e($groupe['type_nom']) ?></a></p>
        <h1><?= e($groupe['nom']) ?></h1>
        <?php if ($groupe['description']): ?><p><?= e($groupe['description']) ?></p><?php endif; ?>
    </div>
    <?php if ($canManage): ?>
    <div class="card__actions">
        <a class="btn btn-secondary" href="/index.php?r=groupes/edit&id=<?= (int)$groupe['id'] ?>"><?= e(t('common_edit')) ?></a>
        <form method="post" action="/index.php?r=groupes/delete" class="inline-form" data-confirm="<?= e(t('groupes_confirm_delete')) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$groupe['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-danger"><?= e(t('common_delete')) ?></button>
        </form>
    </div>
    <?php endif; ?>
</div>

<h2><?= e(t('groupes_images_title')) ?></h2>
<div class="image-grid">
<?php foreach ($images as $img): ?>
    <div class="image-tile">
        <img src="/media/<?= e($img['thumb_path']) ?>" alt="<?= e($groupe['nom']) ?>" loading="lazy">
        <?php if ($canManage): ?>
        <form method="post" action="/index.php?r=images/delete" class="inline-form" data-confirm="<?= e(t('groupes_confirm_delete_image')) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-danger"><?= e(t('common_delete')) ?></button>
        </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (!$images): ?>
    <p><?= e(t('groupes_no_images')) ?></p>
<?php endif; ?>
</div>

<?php if ($canManage): ?>
<form method="post" action="/index.php?r=images/upload" enctype="multipart/form-data" class="upload-form">
    <?= csrfField() ?>
    <input type="hidden" name="groupe_id" value="<?= (int)$groupe['id'] ?>">
    <label for="images"><?= e(t('groupes_add_images_label')) ?></label>
    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <button type="submit" class="btn btn-primary"><?= e(t('common_send')) ?></button>
</form>
<?php endif; ?>

<div class="page-header">
    <h2><?= e(t('groupes_variants_title')) ?></h2>
    <?php if ($canManage): ?>
    <a class="btn btn-secondary" href="/index.php?r=variantes/create&groupe_id=<?= (int)$groupe['id'] ?>"><?= e(t('groupes_add_variant')) ?></a>
    <?php endif; ?>
</div>
<?php if (!$variantes && $canManage): ?>
<p class="flash flash--error"><?= e(t('groupes_no_variants_warning')) ?></p>
<?php endif; ?>
<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_label')) ?></th><th><?= e(t('th_qty')) ?></th><th><?= e(t('th_threshold')) ?></th><th><?= e(t('th_actions')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($variantes as $v): ?>
        <tr class="<?= $v['quantite'] <= ($v['seuil_alerte'] ?? $groupe['seuil_alerte']) ? 'table-row--warning' : '' ?>">
            <td><?= e($v['libelle']) ?></td>
            <td><?= (int)$v['quantite'] ?></td>
            <td><?= (int)($v['seuil_alerte'] ?? $groupe['seuil_alerte']) ?></td>
            <td class="table-actions">
                <?php if ($canManage): ?>
                <a class="btn btn-secondary" href="/index.php?r=mouvements/create&variante_id=<?= (int)$v['id'] ?>"><?= e(t('groupes_movement_btn')) ?></a>
                <a class="btn btn-secondary" href="/index.php?r=variantes/edit&id=<?= (int)$v['id'] ?>"><?= e(t('common_edit')) ?></a>
                <form method="post" action="/index.php?r=variantes/delete" class="inline-form" data-confirm="<?= e(t('variantes_confirm_delete')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-danger"><?= e(t('common_delete')) ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$variantes): ?>
        <tr><td colspan="4"><?= e(t('groupes_no_variants')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<div class="page-header">
    <h2><?= e(t('groupes_recent_movements')) ?></h2>
    <a class="btn btn-link" href="/index.php?r=mouvements/history&groupe_id=<?= (int)$groupe['id'] ?>"><?= e(t('groupes_view_all_history')) ?></a>
</div>
<div class="table-responsive">
<div class="table-scroll">
<table class="table">
    <thead><tr><th><?= e(t('th_date')) ?></th><th><?= e(t('th_variant')) ?></th><th><?= e(t('th_movement')) ?></th><th><?= e(t('th_by')) ?></th><th><?= e(t('th_reason')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($mouvements as $m): ?>
        <tr>
            <td><?= e($m['created_at']) ?></td>
            <td><?= e($m['variante_libelle']) ?></td>
            <td class="<?= $m['delta'] >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= $m['delta'] >= 0 ? '+' : '' ?><?= (int)$m['delta'] ?>
            </td>
            <td><?= e($m['user_full_name']) ?></td>
            <td><?= e($m['motif'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$mouvements): ?>
        <tr><td colspan="5"><?= e(t('dashboard_no_movements')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
