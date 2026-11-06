<h1><?= e(t('types_confirm_delete_title')) ?></h1>

<div class="card">
    <?php if ($blockingGroupes): ?>
    <p class="flash flash--error"><?= e(t('types_confirm_delete_cascade_warning', ['nom' => $type['nom']])) ?></p>

    <div class="table-responsive">
    <div class="table-scroll">
    <table class="table">
        <thead><tr><th><?= e(t('th_group')) ?></th><th><?= e(t('th_active')) ?></th><th><?= e(t('th_variant')) ?></th><th><?= e(t('th_qty')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($blockingGroupes as $g): ?>
            <tr>
                <td><?= e($g['nom']) ?></td>
                <td><?= $g['active'] ? e(t('common_yes')) : e(t('common_no')) ?></td>
                <td><?= (int)$g['nb_variantes'] ?></td>
                <td><?= (int)$g['total_quantite'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
    <p class="field-hint"><?= e(t('types_confirm_delete_cascade_hint')) ?></p>
    <?php else: ?>
    <p class="flash flash--error"><?= e(t('types_confirm_delete_warning', ['nom' => $type['nom']])) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
    <p class="flash flash--error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/index.php?r=types/delete">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$type['id'] ?>">

        <label for="confirm_word"><?= e(t('users_delete_confirm_label', ['word' => t('users_delete_confirm_word')])) ?></label>
        <input type="text" id="confirm_word" name="confirm_word" autocomplete="off" required autofocus>

        <button type="submit" class="btn btn-danger-solid"><?= e(t('common_delete')) ?></button>
        <a class="btn btn-link" href="/index.php?r=types"><?= e(t('common_cancel')) ?></a>
    </form>
</div>
