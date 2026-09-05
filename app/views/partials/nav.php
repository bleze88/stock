<?php
$navUser = currentUser();
$navLogo = getSetting('logo_path');
$navSiteName = getSetting('site_name', DEFAULT_SITE_NAME);
?>
<header class="site-header">
    <div class="site-header__bar">
        <a class="site-header__brand" href="/index.php?r=dashboard">
            <?php if ($navLogo): ?>
            <img src="/media/<?= e($navLogo) ?>" alt="<?= e($navSiteName) ?>" class="site-header__logo">
            <?php endif; ?>
            <span><?= e($navSiteName) ?></span>
        </a>
        <?php if ($navUser): ?>
        <button type="button" class="nav-toggle" aria-label="Menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>
    </div>
    <?php if ($navUser): ?>
    <nav class="site-nav" data-nav>
        <a href="/index.php?r=dashboard"><?= e(t('nav_dashboard')) ?></a>
        <a href="/index.php?r=types"><?= e(t('nav_inventory')) ?></a>
        <?php if (canManageStock()): ?>
        <a href="/index.php?r=mouvements/history"><?= e(t('nav_history')) ?></a>
        <?php endif; ?>
        <?php if ($navUser['role'] === ROLE_ADMIN): ?>
        <a href="/index.php?r=audit"><?= e(t('nav_audit')) ?></a>
        <?php endif; ?>
        <?php if ($navUser['role'] === ROLE_ADMIN): ?>
        <a href="/index.php?r=users"><?= e(t('nav_users')) ?></a>
        <a href="/index.php?r=settings"><?= e(t('nav_settings')) ?></a>
        <?php endif; ?>
        <span class="site-nav__spacer"></span>
        <a href="/index.php?r=account"><?= e(t('nav_account')) ?> (<?= e($navUser['full_name']) ?>)</a>
        <form method="post" action="/index.php?r=logout" class="site-nav__logout">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-link"><?= e(t('nav_logout')) ?></button>
        </form>
    </nav>
    <?php endif; ?>
</header>
