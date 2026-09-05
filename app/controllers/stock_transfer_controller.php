<?php
declare(strict_types=1);

requireRole(ROLE_ADMIN);
require_once APP_PATH . '/lib/stock_transfer.php';

if ($route === 'stock/export') {
    $zipPath = buildStockExportZip();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="stock-export-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . (string)filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

if ($route === 'stock/import') {
    if (!isPost()) {
        render('stock_transfer/import', ['error' => null]);
        exit;
    }

    csrfVerify();

    $typed = mb_strtoupper(trim(postString('confirm_word')));
    if ($typed !== mb_strtoupper(t('users_delete_confirm_word'))) {
        render('stock_transfer/import', ['error' => t('stock_import_error_word_mismatch')]);
        exit;
    }

    $file = $_FILES['package'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        render('stock_transfer/import', ['error' => t('stock_import_error_no_file')]);
        exit;
    }
    if ($file['size'] > STOCK_TRANSFER_MAX_BYTES) {
        render('stock_transfer/import', ['error' => t('stock_import_error_too_large')]);
        exit;
    }

    try {
        $summary = importStockZip($file['tmp_name']);
        flashSet('success', t('stock_import_success', $summary));
        redirect('dashboard');
    } catch (RuntimeException $e) {
        render('stock_transfer/import', ['error' => $e->getMessage()]);
        exit;
    }
}
