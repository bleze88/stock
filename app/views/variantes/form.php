<h1><?= $variante ? e(t('variantes_form_title_edit')) : e(t('variantes_form_title_new')) ?></h1>
<p class="breadcrumb"><a href="/index.php?r=groupes/show&id=<?= (int)$groupe['id'] ?>"><?= e($groupe['nom']) ?></a></p>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/index.php?r=variantes/<?= $variante ? 'edit&id=' . (int)$variante['id'] : 'create&groupe_id=' . (int)$groupe['id'] ?>">
    <?= csrfField() ?>

    <label for="libelle"><?= e(t('variantes_label_label')) ?></label>
    <input type="text" id="libelle" name="libelle" maxlength="100" required value="<?= e($variante['libelle'] ?? '') ?>">

    <?php if (!$variante): ?>
    <label for="quantite"><?= e(t('variantes_qty_initial_label')) ?></label>
    <input type="number" id="quantite" name="quantite" min="0" value="0" required>
    <p class="field-hint"><?= e(t('variantes_qty_hint_new')) ?></p>
    <?php else: ?>
    <p class="field-hint"><?= t('variantes_qty_hint_current', ['qty' => (int)$variante['quantite']]) ?></p>
    <?php endif; ?>

    <label for="seuil_alerte"><?= e(t('variantes_threshold_label')) ?></label>
    <input type="number" id="seuil_alerte" name="seuil_alerte" min="0" value="<?= e((string)($variante['seuil_alerte'] ?? '')) ?>">

    <label for="location"><?= e(t('variantes_location_label')) ?></label>
    <input type="text" id="location" name="location" maxlength="150" placeholder="<?= e(t('variantes_location_placeholder')) ?>" value="<?= e($variante['location'] ?? '') ?>">
    <p class="field-hint"><?= e(t('variantes_location_hint')) ?></p>

    <?php if ($variante): ?>
    <label class="checkbox-label">
        <input type="checkbox" name="active" value="1" <?= $variante['active'] ? 'checked' : '' ?>>
        <?= e(t('variantes_active_label')) ?>
    </label>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    <a class="btn btn-link" href="/index.php?r=groupes/show&id=<?= (int)$groupe['id'] ?>"><?= e(t('common_cancel')) ?></a>
</form>
