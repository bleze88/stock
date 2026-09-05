<?php $canManage = canManageStock(); ?>
<div class="page-header">
    <h1><?= e(t('types_page_title')) ?></h1>
    <div class="card__actions">
        <a class="btn btn-secondary" href="/index.php?r=export/inventory"><?= e(t('export_button')) ?></a>
        <?php if ($canManage): ?>
        <a class="btn btn-primary" href="/index.php?r=types/create"><?= e(t('types_new')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card-grid">
<?php foreach ($types as $t): ?>
    <div class="card">
        <h2><?= e($t['nom']) ?></h2>
        <p><?= e(t('types_summary', ['groups' => (int)$t['nb_groupes'], 'qty' => (int)$t['total_quantite']])) ?></p>
        <div class="card__actions">
            <?php if ($canManage): ?>
            <a class="btn btn-secondary" href="/index.php?r=groupes/create&type_id=<?= (int)$t['id'] ?>"><?= e(t('types_add_group')) ?></a>
            <a class="btn btn-secondary" href="/index.php?r=types/edit&id=<?= (int)$t['id'] ?>"><?= e(t('common_edit')) ?></a>
            <form method="post" action="/index.php?r=types/delete" class="inline-form" data-confirm="<?= e(t('types_confirm_delete')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-danger"><?= e(t('common_delete')) ?></button>
            </form>
            <?php endif; ?>
        </div>
        <?php $groupesDuType = $groupesByType[$t['id']] ?? []; ?>
        <?php if ($groupesDuType): ?>
        <ul class="card__list">
            <?php foreach ($groupesDuType as $g): ?>
            <li><a href="/index.php?r=groupes/show&id=<?= (int)$g['id'] ?>"><?= e($g['nom']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (!$types): ?>
    <p><?= e(t('types_none')) ?></p>
<?php endif; ?>
</div>
