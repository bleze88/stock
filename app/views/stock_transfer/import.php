<h1><?= e(t('stock_import_title')) ?></h1>

<div class="card">
    <p class="flash flash--error"><?= e(t('stock_import_warning')) ?></p>

    <?php if ($error): ?>
    <p class="flash flash--error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/index.php?r=stock/import" enctype="multipart/form-data" data-confirm="<?= e(t('stock_import_confirm_submit')) ?>">
        <?= csrfField() ?>

        <label for="package"><?= e(t('stock_import_file_label')) ?></label>
        <input type="file" id="package" name="package" accept=".zip,application/zip" required>

        <label for="confirm_word"><?= e(t('users_delete_confirm_label', ['word' => t('users_delete_confirm_word')])) ?></label>
        <input type="text" id="confirm_word" name="confirm_word" autocomplete="off" required autofocus>

        <button type="submit" class="btn btn-danger-solid"><?= e(t('stock_import_submit_btn')) ?></button>
        <a class="btn btn-link" href="/index.php?r=settings"><?= e(t('common_cancel')) ?></a>
    </form>
</div>
