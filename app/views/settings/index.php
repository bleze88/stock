<h1><?= e(t('settings_title')) ?></h1>

<?php if ($error): ?>
<p class="flash flash--error"><?= e($error) ?></p>
<?php endif; ?>

<div class="card">
    <h2><?= e(t('settings_sitename_title')) ?></h2>
    <p class="field-hint"><?= e(t('settings_sitename_hint')) ?></p>
    <form method="post" action="/index.php?r=settings/update">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_name">
        <label for="site_name"><?= e(t('settings_sitename_label')) ?></label>
        <input type="text" id="site_name" name="site_name" maxlength="100" required value="<?= e($siteName) ?>">
        <button type="submit" class="btn btn-primary"><?= e(t('common_save')) ?></button>
    </form>
</div>

<div class="card">
    <h2><?= e(t('settings_logo_title')) ?></h2>
    <?php if ($logoPath): ?>
    <div class="logo-preview">
        <img src="/media/<?= e($logoPath) ?>" alt="<?= e(t('settings_logo_title')) ?>">
    </div>
    <form method="post" action="/index.php?r=settings/update" data-confirm="<?= e(t('settings_logo_confirm_remove')) ?>">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="remove_logo">
        <button type="submit" class="btn btn-link btn-danger"><?= e(t('settings_logo_remove_btn')) ?></button>
    </form>
    <?php else: ?>
    <p class="field-hint"><?= e(t('settings_logo_none')) ?></p>
    <?php endif; ?>

    <form method="post" action="/index.php?r=settings/update" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="upload_logo">
        <label for="logo"><?= $logoPath ? e(t('settings_logo_replace_label')) : e(t('settings_logo_import_label')) ?> <?= e(t('settings_logo_hint_suffix')) ?></label>
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" required>
        <button type="submit" class="btn btn-primary"><?= e(t('common_send')) ?></button>
    </form>
</div>

<div class="card">
    <h2><?= e(t('settings_color_title')) ?></h2>
    <p class="field-hint"><?= e(t('settings_color_hint')) ?></p>

    <div class="color-presets" data-color-presets>
        <?php
        // Les couleurs sont définies en CSS (classes .color-swatch--*), jamais en style inline (CSP).
        $presets = [
            'green'  => ['#2f6f4f', t('settings_color_preset_green')],
            'blue'   => ['#1d4ed8', t('settings_color_preset_blue')],
            'violet' => ['#7c3aed', t('settings_color_preset_violet')],
            'red'    => ['#be123c', t('settings_color_preset_red')],
            'orange' => ['#c2740c', t('settings_color_preset_orange')],
            'teal'   => ['#0f766e', t('settings_color_preset_teal')],
        ];
        ?>
        <?php foreach ($presets as $slug => [$hex, $label]): ?>
        <button type="button" class="color-swatch color-swatch--<?= e($slug) ?>"
                title="<?= e($label) ?>" data-color="<?= e($hex) ?>"></button>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/index.php?r=settings/update">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_color">
        <label for="primary_color"><?= e(t('settings_color_free_label')) ?></label>
        <input type="color" id="primary_color" name="primary_color" value="<?= e($primaryColor) ?>" data-color-input>
        <button type="submit" class="btn btn-primary"><?= e(t('common_apply')) ?></button>
    </form>
    <form method="post" action="/index.php?r=settings/update">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="reset_color">
        <button type="submit" class="btn btn-link"><?= e(t('settings_color_reset')) ?></button>
    </form>
</div>
