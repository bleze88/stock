<?php
declare(strict_types=1);

$user = requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
$db = getDb();

if ($route === 'images/upload') {
    if (!isPost()) {
        redirect('dashboard');
    }
    csrfVerify();

    $groupeId = postInt('groupe_id');
    $stmt = $db->prepare('SELECT id FROM groupes WHERE id = ?');
    $stmt->execute([$groupeId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $files = $_FILES['images'] ?? null;
    $errors = [];
    $successCount = 0;

    if ($files && is_array($files['tmp_name'])) {
        $count = count($files['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $file = [
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
                'name' => $files['name'][$i],
            ];
            try {
                $result = processGroupeImageUpload($file, $groupeId);
                $ins = $db->prepare(
                    'INSERT INTO images (groupe_id, original_path, thumb_path, original_name, mime_type, size_bytes, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $groupeId,
                    $result['original_path'],
                    $result['thumb_path'],
                    substr((string)$file['name'], 0, 255),
                    $result['mime_type'],
                    $result['size_bytes'],
                    $user['id'],
                ]);
                $successCount++;
            } catch (UploadRejected $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if ($successCount > 0) {
        flashSet('success', t('images_uploaded', ['count' => $successCount]));
    }
    foreach ($errors as $err) {
        flashSet('error', $err);
    }

    redirect('groupes/show', ['id' => $groupeId]);
}

if ($route === 'images/delete') {
    if (!isPost()) {
        redirect('dashboard');
    }
    csrfVerify();

    $id = postInt('id');
    $stmt = $db->prepare('SELECT * FROM images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    if (!$image) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $del = $db->prepare('DELETE FROM images WHERE id = ?');
    $del->execute([$id]);
    deleteGroupeImageFiles($image['original_path'], $image['thumb_path']);

    flashSet('success', t('images_deleted'));
    redirect('groupes/show', ['id' => $image['groupe_id']]);
}
