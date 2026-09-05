<h1><?= $type ? e(t('types_form_title_edit')) : e(t('types_form_title_new')) ?></h1>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/index.php?r=types/<?= $type ? 'edit&id=' . (int)$type['id'] : 'create' ?>">
    <?= csrfField() ?>
    <label for="nom"><?= e(t('types_name_label')) ?></label>
    <input type="text" id="nom" name="nom" maxlength="100" required value="<?= e($type['nom'] ?? '') ?>">

    <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    <a class="btn btn-link" href="/index.php?r=types"><?= e(t('common_cancel')) ?></a>
</form>
