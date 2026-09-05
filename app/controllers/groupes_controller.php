<?php
declare(strict_types=1);

$user = requireLogin();
$db = getDb();

if ($route === 'groupes/show') {
    $id = getInt('id');
    $stmt = $db->prepare(
        'SELECT g.*, t.nom AS type_nom FROM groupes g JOIN types t ON g.type_id = t.id WHERE g.id = ?'
    );
    $stmt->execute([$id]);
    $groupe = $stmt->fetch();
    if (!$groupe) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $stmt = $db->prepare('SELECT * FROM variantes WHERE groupe_id = ? AND active = 1 ORDER BY libelle');
    $stmt->execute([$id]);
    $variantes = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM images WHERE groupe_id = ? ORDER BY sort_order, id');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();

    $stmt = $db->prepare(
        "SELECT m.*, v.libelle AS variante_libelle, u.full_name AS user_full_name
         FROM mouvements m
         JOIN variantes v ON m.variante_id = v.id
         JOIN users u ON m.user_id = u.id
         WHERE v.groupe_id = ?
         ORDER BY m.created_at DESC, m.id DESC
         LIMIT 20"
    );
    $stmt->execute([$id]);
    $mouvements = $stmt->fetchAll();

    render('groupes/show', compact('groupe', 'variantes', 'images', 'mouvements'));
    exit;
}

if ($route === 'groupes/create') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $types = $db->query('SELECT id, nom FROM types ORDER BY nom')->fetchAll();
    $error = null;
    $typeId = getInt('type_id') ?: null;

    if (isPost()) {
        csrfVerify();
        $typeId = postInt('type_id');
        $nom = postString('nom');
        $description = postString('description');
        $seuil = postInt('seuil_alerte', 0);

        if ($typeId <= 0 || !isNonEmptyString($nom, 150)) {
            $error = t('groupes_error_required');
        } else {
            try {
                $stmt = $db->prepare(
                    'INSERT INTO groupes (type_id, nom, description, seuil_alerte, created_by) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$typeId, $nom, $description ?: null, $seuil, $user['id']]);
                $newId = (int)$db->lastInsertId();
                logAudit('create', 'groupe', $newId, $nom);
                flashSet('success', t('groupes_created', ['nom' => $nom]));
                redirect('groupes/show', ['id' => $newId]);
            } catch (PDOException $e) {
                $error = t('groupes_error_duplicate');
            }
        }
    }

    render('groupes/form', ['error' => $error, 'types' => $types, 'groupe' => null, 'preselectedTypeId' => $typeId]);
    exit;
}

if ($route === 'groupes/edit') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $id = getInt('id');
    $stmt = $db->prepare('SELECT * FROM groupes WHERE id = ?');
    $stmt->execute([$id]);
    $groupe = $stmt->fetch();
    if (!$groupe) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }
    $types = $db->query('SELECT id, nom FROM types ORDER BY nom')->fetchAll();
    $error = null;

    if (isPost()) {
        csrfVerify();
        $typeId = postInt('type_id');
        $nom = postString('nom');
        $description = postString('description');
        $seuil = postInt('seuil_alerte', 0);
        $active = postInt('active', 0) ? 1 : 0;

        if ($typeId <= 0 || !isNonEmptyString($nom, 150)) {
            $error = t('groupes_error_required');
        } else {
            try {
                $upd = $db->prepare(
                    'UPDATE groupes SET type_id = ?, nom = ?, description = ?, seuil_alerte = ?, active = ? WHERE id = ?'
                );
                $upd->execute([$typeId, $nom, $description ?: null, $seuil, $active, $id]);
                flashSet('success', t('groupes_updated'));
                redirect('groupes/show', ['id' => $id]);
            } catch (PDOException $e) {
                $error = t('groupes_error_duplicate');
            }
        }
    }

    render('groupes/form', ['error' => $error, 'types' => $types, 'groupe' => $groupe, 'preselectedTypeId' => $groupe['type_id']]);
    exit;
}

if ($route === 'groupes/delete') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    if (!isPost()) {
        redirect('types');
    }
    csrfVerify();

    $id = postInt('id');
    $stmt = $db->prepare('SELECT * FROM groupes WHERE id = ?');
    $stmt->execute([$id]);
    $groupe = $stmt->fetch();
    if (!$groupe) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $upd = $db->prepare('UPDATE groupes SET active = 0 WHERE id = ?');
    $upd->execute([$id]);
    logAudit('delete', 'groupe', $id, $groupe['nom']);
    flashSet('success', t('groupes_deleted', ['nom' => $groupe['nom']]));

    redirect('types');
}
