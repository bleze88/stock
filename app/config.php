<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('DB_PATH', STORAGE_PATH . '/database/stock.sqlite');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('PUBLIC_MEDIA_PATH', ROOT_PATH . '/public/media');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');

// Taille max d'une archive d'export/import du stock (données + images) : bien
// au-dessus d'un usage normal, juste un garde-fou anti "bombe de décompression".
define('STOCK_TRANSFER_MAX_BYTES', 200 * 1024 * 1024);

define('SESSION_COOKIE_NAME', 'asso_sess');
define('SESSION_IDLE_TIMEOUT_SECONDS', 30 * 60);

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_MINUTES', 15);
define('LOGIN_LOCKOUT_MINUTES', 15);

define('UPLOAD_MAX_BYTES', 16 * 1024 * 1024);
// Garde-fou anti "bombe de decompression", pas une limite fonctionnelle :
// les photos de telephone standard sont largement en dessous et sont
// redimensionnees, jamais rejetees. Calibre avec IMAGE_PROCESSING_MEMORY_LIMIT.
define('UPLOAD_MAX_PIXELS', 6000);
define('IMAGE_PROCESSING_MEMORY_LIMIT', '256M');
define('THUMB_MAX_DIMENSION', 1200);
define('THUMB_JPEG_QUALITY', 82);

define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_VIEWER', 'viewer');

// En local (hors HTTPS) ce flag peut être mis à false pour le dev ; en
// production le site tourne exclusivement en HTTPS (redirection Nginx).
define('FORCE_SECURE_COOKIES', true);
