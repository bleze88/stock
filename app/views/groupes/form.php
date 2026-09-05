<h1><?= $groupe ? e(t('groupes_form_title_edit')) : e(t('groupes_form_title_new')) ?></h1>

<?php if (!$groupe): ?>
<p class="field-hint"><?= t('groupes_form_hint_new') ?></p>
<?php endif; ?>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/index.php?r=groupes/<?= $groupe ? 'edit&id=' . (int)$groupe['id'] : 'create' ?>">
    <?= csrfField() ?>

    <label for="type_id"><?= e(t('groupes_type_label')) ?></label>
    <select id="type_id" name="type_id" required>
        <option value=""><?= e(t('common_choose')) ?></option>
        <?php foreach ($types as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= (int)$preselectedTypeId === (int)$t['id'] ? 'selected' : '' ?>>
            <?= e($t['nom']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <label for="nom"><?= e(t('groupes_name_label')) ?></label>
    <input type="text" id="nom" name="nom" maxlength="150" required value="<?= e($groupe['nom'] ?? '') ?>">

    <label for="description"><?= e(t('groupes_description_label')) ?></label>
    <textarea id="description" name="description" rows="3"><?= e($groupe['description'] ?? '') ?></textarea>

    <label for="seuil_alerte"><?= e(t('groupes_threshold_label')) ?></label>
    <input type="number" id="seuil_alerte" name="seuil_alerte" min="0" value="<?= (int)($groupe['seuil_alerte'] ?? 0) ?>">
    <p class="field-hint"><?= e(t('groupes_threshold_hint')) ?></p>

    <?php if ($groupe): ?>
    <label class="checkbox-label">
        <input type="checkbox" name="active" value="1" <?= $groupe['active'] ? 'checked' : '' ?>>
        <?= e(t('groupes_active_label')) ?>
    </label>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    <a class="btn btn-link" href="/index.php?r=types"><?= e(t('common_cancel')) ?></a>
</form>
