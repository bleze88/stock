<!doctype html>
<html lang="<?= e(currentLocale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(getSetting('site_name', DEFAULT_SITE_NAME)) ?></title>
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= (int)@filemtime(ROOT_PATH . '/public/assets/css/app.css') ?>">
<?php $primaryColor = getSetting('primary_color'); ?>
<?php if ($primaryColor && isValidHexColor($primaryColor)): ?>
<style nonce="<?= e(cspNonce()) ?>">
:root {
    --color-primary: <?= e($primaryColor) ?>;
    --color-primary-dark: <?= e(darkenHexColor($primaryColor, 0.22)) ?>;
}
</style>
<?php endif; ?>
</head>
<body>
<?php require APP_PATH . '/views/partials/nav.php'; ?>
<main class="container">
<?php require APP_PATH . '/views/partials/flash.php'; ?>
<?php require $viewFile; ?>
</main>
<script src="/assets/js/app.js?v=<?= (int)@filemtime(ROOT_PATH . '/public/assets/js/app.js') ?>"></script>
</body>
</html>
