<?php
declare(strict_types=1);

/**
 * Export / import du stock (types, groupes, variantes, images) sous forme
 * d'archive ZIP portable, pour changer de serveur ou de fournisseur sans
 * dépendre d'un accès SSH. Les utilisateurs, l'historique des mouvements,
 * le journal d'activité et les paramètres du site ne sont volontairement
 * pas inclus : ils sont propres à chaque instance et recréés sur place.
 */

const STOCK_IMAGE_PATH_PATTERN = '#^groupes/[0-9]+/(orig|thumb)-[0-9a-f]{32}\.jpg$#';

function buildStockExportZip(): string
{
    $db = getDb();

    $types = $db->query('SELECT id, nom, created_at FROM types ORDER BY id')->fetchAll();
    $groupes = $db->query(
        'SELECT id, type_id, nom, description, seuil_alerte, active, created_at FROM groupes ORDER BY id'
    )->fetchAll();
    $variantes = $db->query(
        'SELECT id, groupe_id, libelle, sku, quantite, seuil_alerte, active, location, created_at FROM variantes ORDER BY id'
    )->fetchAll();
    $images = $db->query(
        'SELECT id, groupe_id, original_path, thumb_path, original_name, mime_type, size_bytes, sort_order, created_at FROM images ORDER BY id'
    )->fetchAll();

    $data = [
        'format_version' => 1,
        'exported_at' => date('c'),
        'types' => $types,
        'groupes' => $groupes,
        'variantes' => $variantes,
        'images' => $images,
    ];

    $tmpFile = tempnam(sys_get_temp_dir(), 'stockexport');
    $zip = new ZipArchive();
    $zip->open($tmpFile, ZipArchive::OVERWRITE);
    $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    foreach ($images as $img) {
        $origAbsolute = UPLOADS_PATH . '/' . $img['original_path'];
        if (is_file($origAbsolute)) {
            $zip->addFile($origAbsolute, 'uploads/' . $img['original_path']);
        }
        if ($img['thumb_path']) {
            $thumbAbsolute = PUBLIC_MEDIA_PATH . '/' . $img['thumb_path'];
            if (is_file($thumbAbsolute)) {
                $zip->addFile($thumbAbsolute, 'media/' . $img['thumb_path']);
            }
        }
    }

    $zip->close();
    return $tmpFile;
}

function removeDirectoryContents(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
}

/**
 * @return array{types:int,groupes:int,variantes:int,images:int}
 */
function importStockZip(string $zipPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException(t('stock_import_error_invalid_zip'));
    }

    $dataJson = $zip->getFromName('data.json');
    if ($dataJson === false) {
        $zip->close();
        throw new RuntimeException(t('stock_import_error_missing_data'));
    }

    try {
        $data = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $zip->close();
        throw new RuntimeException(t('stock_import_error_invalid_data'));
    }

    if (!is_array($data)) {
        $zip->close();
        throw new RuntimeException(t('stock_import_error_invalid_data'));
    }
    foreach (['types', 'groupes', 'variantes', 'images'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            $zip->close();
            throw new RuntimeException(t('stock_import_error_invalid_data'));
        }
    }

    // Valide chaque chemin d'image (format strict, jamais de traversée de
    // répertoire) et vérifie que le fichier correspondant est bien dans l'archive.
    foreach ($data['images'] as $img) {
        $orig = $img['original_path'] ?? '';
        if (!is_string($orig) || !preg_match(STOCK_IMAGE_PATH_PATTERN, $orig)) {
            $zip->close();
            throw new RuntimeException(t('stock_import_error_invalid_data'));
        }
        if ($zip->locateName('uploads/' . $orig) === false) {
            $zip->close();
            throw new RuntimeException(t('stock_import_error_missing_file', ['file' => $orig]));
        }
        $thumb = $img['thumb_path'] ?? null;
        if ($thumb !== null) {
            if (!is_string($thumb) || !preg_match(STOCK_IMAGE_PATH_PATTERN, $thumb)) {
                $zip->close();
                throw new RuntimeException(t('stock_import_error_invalid_data'));
            }
            if ($zip->locateName('media/' . $thumb) === false) {
                $zip->close();
                throw new RuntimeException(t('stock_import_error_missing_file', ['file' => $thumb]));
            }
        }
    }

    // Filet de sécurité : capture l'état actuel avant de le remplacer.
    ensureDir(BACKUPS_PATH, 0750);
    $snapshotTmp = buildStockExportZip();
    $backupPath = BACKUPS_PATH . '/pre-import-' . date('Ymd-His') . '.zip';
    rename($snapshotTmp, $backupPath);
    chmod($backupPath, 0640);

    $db = getDb();
    $db->beginTransaction();
    try {
        $db->exec('DELETE FROM mouvements');
        $db->exec('DELETE FROM images');
        $db->exec('DELETE FROM variantes');
        $db->exec('DELETE FROM groupes');
        $db->exec('DELETE FROM types');

        $insType = $db->prepare('INSERT INTO types (id, nom, created_at, created_by) VALUES (?, ?, ?, NULL)');
        foreach ($data['types'] as $t) {
            $insType->execute([(int)$t['id'], (string)$t['nom'], (string)($t['created_at'] ?? date('c'))]);
        }

        $insGroupe = $db->prepare(
            'INSERT INTO groupes (id, type_id, nom, description, seuil_alerte, active, created_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL)'
        );
        foreach ($data['groupes'] as $g) {
            $insGroupe->execute([
                (int)$g['id'], (int)$g['type_id'], (string)$g['nom'], $g['description'] ?? null,
                (int)($g['seuil_alerte'] ?? 0), (int)($g['active'] ?? 1), (string)($g['created_at'] ?? date('c')),
            ]);
        }

        $insVariante = $db->prepare(
            'INSERT INTO variantes (id, groupe_id, libelle, sku, quantite, seuil_alerte, active, location, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data['variantes'] as $v) {
            $insVariante->execute([
                (int)$v['id'], (int)$v['groupe_id'], (string)$v['libelle'], $v['sku'] ?? null,
                (int)($v['quantite'] ?? 0), $v['seuil_alerte'] ?? null, (int)($v['active'] ?? 1),
                $v['location'] ?? null, (string)($v['created_at'] ?? date('c')),
            ]);
        }

        $insImage = $db->prepare(
            'INSERT INTO images (id, groupe_id, original_path, thumb_path, original_name, mime_type, size_bytes, sort_order, uploaded_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)'
        );
        foreach ($data['images'] as $img) {
            $insImage->execute([
                (int)$img['id'], (int)$img['groupe_id'], (string)$img['original_path'], $img['thumb_path'] ?? null,
                $img['original_name'] ?? null, (string)$img['mime_type'], (int)($img['size_bytes'] ?? 0),
                (int)($img['sort_order'] ?? 0), (string)($img['created_at'] ?? date('c')),
            ]);
        }

        foreach (['types', 'groupes', 'variantes', 'images'] as $table) {
            $max = (int)$db->query("SELECT COALESCE(MAX(id), 0) FROM {$table}")->fetchColumn();
            $db->prepare('DELETE FROM sqlite_sequence WHERE name = ?')->execute([$table]);
            if ($max > 0) {
                $db->prepare('INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?)')->execute([$table, $max]);
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        $zip->close();
        throw new RuntimeException(t('stock_import_error_database', ['error' => $e->getMessage()]));
    }

    // Remplace les fichiers images sur le disque par ceux de l'archive.
    removeDirectoryContents(UPLOADS_PATH . '/groupes');
    removeDirectoryContents(PUBLIC_MEDIA_PATH . '/groupes');

    foreach ($data['images'] as $img) {
        $origDest = UPLOADS_PATH . '/' . $img['original_path'];
        ensureDir(dirname($origDest), 0750);
        file_put_contents($origDest, $zip->getFromName('uploads/' . $img['original_path']));
        chmod($origDest, 0640);

        if (!empty($img['thumb_path'])) {
            $thumbDest = PUBLIC_MEDIA_PATH . '/' . $img['thumb_path'];
            ensureDir(dirname($thumbDest), 0755);
            file_put_contents($thumbDest, $zip->getFromName('media/' . $img['thumb_path']));
            chmod($thumbDest, 0644);
        }
    }

    $zip->close();

    return [
        'types' => count($data['types']),
        'groupes' => count($data['groupes']),
        'variantes' => count($data['variantes']),
        'images' => count($data['images']),
    ];
}
