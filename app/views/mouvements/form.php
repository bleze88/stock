<h1><?= e(t('mouvements_form_title')) ?></h1>
<p class="breadcrumb">
    <a href="/index.php?r=groupes/show&id=<?= (int)$variante['groupe_id'] ?>"><?= e($variante['groupe_nom']) ?></a>
    — <?= e($variante['libelle']) ?> <?= e(t('mouvements_current_qty', ['qty' => (int)$variante['quantite']])) ?>
</p>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<form method="post" action="/index.php?r=mouvements/create&variante_id=<?= (int)$variante['id'] ?>">
    <?= csrfField() ?>

    <fieldset class="radio-group">
        <legend><?= e(t('mouvements_type_legend')) ?></legend>
        <label><input type="radio" name="type_mouvement" value="entree" checked> <?= e(t('mouvements_type_in')) ?></label>
        <label><input type="radio" name="type_mouvement" value="sortie"> <?= e(t('mouvements_type_out')) ?></label>
    </fieldset>

    <label for="quantite"><?= e(t('th_qty')) ?></label>
    <input type="number" id="quantite" name="quantite" min="1" required autofocus>

    <label for="motif"><?= e(t('mouvements_reason_label')) ?></label>
    <input type="text" id="motif" name="motif" maxlength="255">

    <button type="submit" class="btn btn-primary"><?= e(t('mouvements_submit')) ?></button>
    <a class="btn btn-link" href="/index.php?r=groupes/show&id=<?= (int)$variante['groupe_id'] ?>"><?= e(t('common_cancel')) ?></a>
</form>
