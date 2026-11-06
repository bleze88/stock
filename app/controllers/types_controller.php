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

    $id = isPost() ? postInt('id') : getInt('id');
    $stmt = $db->prepare('SELECT * FROM types WHERE id = ?');
    $stmt->execute([$id]);
    $type = $stmt->fetch();
    if (!$type) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    // Un groupe "supprimé" n'est en réalité que désactivé (pour préserver
    // l'historique des mouvements) : il référence toujours son type, qui ne
    // peut donc jamais être supprimé tant qu'il en reste, même inactif. On
    // les liste ici pour que l'admin puisse les purger définitivement en même
    // temps que le type, plutôt que de rester bloqué sans recours dans l'UI.
    $blockingGroupes = $db->prepare(
        "SELECT g.id, g.nom, g.active, COUNT(v.id) AS nb_variantes, COALESCE(SUM(v.quantite), 0) AS total_quantite
         FROM groupes g
         LEFT JOIN variantes v ON v.groupe_id = g.id
         WHERE g.type_id = ?
         GROUP BY g.id, g.nom, g.active
         ORDER BY g.nom"
    );
    $blockingGroupes->execute([$id]);
    $blockingGroupes = $blockingGroupes->fetchAll();

    if (!isPost()) {
        render('types/confirm_delete', ['type' => $type, 'blockingGroupes' => $blockingGroupes, 'error' => null]);
        exit;
    }

    csrfVerify();

    $typed = mb_strtoupper(trim(postString('confirm_word')));
    if ($typed !== mb_strtoupper(t('users_delete_confirm_word'))) {
        render('types/confirm_delete', [
            'type' => $type, 'blockingGroupes' => $blockingGroupes,
            'error' => t('users_error_confirm_word_mismatch'),
        ]);
        exit;
    }

    if ($blockingGroupes) {
        // Purge définitive de groupes/variantes/mouvements existants (même
        // inactifs) : plus sensible qu'une simple suppression de type vide,
        // réservée aux admins, et précédée d'une sauvegarde de sécurité.
        requireRole(ROLE_ADMIN);

        require_once APP_PATH . '/lib/stock_transfer.php';
        ensureDir(BACKUPS_PATH, 0750);
        $snapshotTmp = buildStockExportZip();
        $backupPath = BACKUPS_PATH . '/pre-type-delete-' . date('Ymd-His') . '.zip';
        rename($snapshotTmp, $backupPath);
        chmod($backupPath, 0640);

        $groupeIds = array_map(fn($g) => (int)$g['id'], $blockingGroupes);
        $placeholders = implode(',', array_fill(0, count($groupeIds), '?'));
        $nbVariantes = (int)array_sum(array_column($blockingGroupes, 'nb_variantes'));

        $db->beginTransaction();
        try {
            $db->prepare(
                "DELETE FROM mouvements WHERE variante_id IN (SELECT id FROM variantes WHERE groupe_id IN ($placeholders))"
            )->execute($groupeIds);
            $db->prepare("DELETE FROM variantes WHERE groupe_id IN ($placeholders)")->execute($groupeIds);
            $db->prepare("DELETE FROM groupes WHERE id IN ($placeholders)")->execute($groupeIds);
            $db->prepare('DELETE FROM types WHERE id = ?')->execute([$id]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            render('types/confirm_delete', [
                'type' => $type, 'blockingGroupes' => $blockingGroupes,
                'error' => t('stock_import_error_database', ['error' => $e->getMessage()]),
            ]);
            exit;
        }

        logAudit('delete', 'type', $id, $type['nom']);
        flashSet('success', t('types_deleted_cascade', [
            'nom' => $type['nom'], 'groupes' => count($groupeIds), 'variantes' => $nbVariantes,
        ]));
        redirect('types');
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
