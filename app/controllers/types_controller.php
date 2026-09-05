<?php
declare(strict_types=1);

$user = requireLogin();
$db = getDb();

if ($route === 'types') {
    $types = $db->query(
        "SELECT t.id, t.nom, COUNT(DISTINCT g.id) AS nb_groupes, COALESCE(SUM(v.quantite), 0) AS total_quantite
         FROM types t
         LEFT JOIN groupes g ON g.type_id = t.id AND g.active = 1
         LEFT JOIN variantes v ON v.groupe_id = g.id AND v.active = 1
         GROUP BY t.id, t.nom
         ORDER BY t.nom"
    )->fetchAll();

    $groupesByType = [];
    $stmt = $db->query('SELECT id, type_id, nom FROM groupes WHERE active = 1 ORDER BY nom');
    foreach ($stmt->fetchAll() as $g) {
        $groupesByType[$g['type_id']][] = $g;
    }

    render('types/index', ['types' => $types, 'groupesByType' => $groupesByType]);
    exit;
}

if ($route === 'types/create') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $error = null;

    if (isPost()) {
        csrfVerify();
        $nom = postString('nom');
        if (!isNonEmptyString($nom, 100)) {
            $error = t('types_error_name_required');
        } else {
            try {
                $stmt = $db->prepare('INSERT INTO types (nom, created_by) VALUES (?, ?)');
                $stmt->execute([$nom, $user['id']]);
                logAudit('create', 'type', (int)$db->lastInsertId(), $nom);
                flashSet('success', t('types_created', ['nom' => $nom]));
                redirect('types');
            } catch (PDOException $e) {
                $error = t('types_error_duplicate');
            }
        }
    }

    render('types/form', ['error' => $error, 'type' => null]);
    exit;
}

if ($route === 'types/edit') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $id = getInt('id');
    $stmt = $db->prepare('SELECT * FROM types WHERE id = ?');
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    if (!$type) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $error = null;

    if (isPost()) {
        csrfVerify();
        $nom = postString('nom');
        if (!isNonEmptyString($nom, 100)) {
            $error = t('types_error_name_required');
        } else {
            try {
                $upd = $db->prepare('UPDATE types SET nom = ? WHERE id = ?');
                $upd->execute([$nom, $id]);
                flashSet('success', t('types_updated'));
                redirect('types');
            } catch (PDOException $e) {
                $error = t('types_error_duplicate');
            }
        }
    }

    render('types/form', ['error' => $error, 'type' => $type]);
    exit;
}

if ($route === 'types/delete') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    if (!isPost()) {
        redirect('types');
    }
    csrfVerify();

    $id = postInt('id');
    $stmt = $db->prepare('SELECT * FROM types WHERE id = ?');
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    if (!$type) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    try {
        $del = $db->prepare('DELETE FROM types WHERE id = ?');
        $del->execute([$id]);
        logAudit('delete', 'type', $id, $type['nom']);
        flashSet('success', t('types_deleted', ['nom' => $type['nom']]));
    } catch (PDOException $e) {
        flashSet('error', t('types_error_has_groups'));
    }

    redirect('types');
}
