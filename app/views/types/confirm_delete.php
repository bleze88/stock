<h1><?= e(t('types_confirm_delete_title')) ?></h1>

<div class="card">
    <p class="flash flash--error"><?= e(t('types_confirm_delete_warning', ['nom' => $type['nom']])) ?></p>

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
