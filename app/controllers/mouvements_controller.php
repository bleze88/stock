<?php
declare(strict_types=1);

$db = getDb();

if ($route === 'mouvements/create') {
    $user = requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $variantId = getInt('variante_id');
    $stmt = $db->prepare(
        'SELECT v.*, g.nom AS groupe_nom, g.id AS groupe_id FROM variantes v JOIN groupes g ON v.groupe_id = g.id WHERE v.id = ?'
    );
    $stmt->execute([$variantId]);
    $variante = $stmt->fetch();
    if (!$variante) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $error = null;

    if (isPost()) {
        csrfVerify();
        $type = postString('type_mouvement'); // 'entree' | 'sortie'
        $quantite = postInt('quantite', 0);
        $motif = postString('motif', '');

        if (!in_array($type, ['entree', 'sortie'], true) || $quantite <= 0) {
            $error = t('mouvements_error_invalid_qty');
        } else {
            $delta = $type === 'entree' ? $quantite : -$quantite;
            $nouvelleQuantite = $variante['quantite'] + $delta;

            if ($nouvelleQuantite < 0) {
                $error = t('mouvements_error_insufficient_stock');
            } else {
                $db->beginTransaction();
                try {
                    $upd = $db->prepare('UPDATE variantes SET quantite = ? WHERE id = ?');
                    $upd->execute([$nouvelleQuantite, $variantId]);

                    $ins = $db->prepare(
                        'INSERT INTO mouvements (variante_id, user_id, delta, quantite_apres, motif) VALUES (?, ?, ?, ?, ?)'
                    );
                    $ins->execute([$variantId, $user['id'], $delta, $nouvelleQuantite, $motif ?: null]);

                    $db->commit();
                    flashSet('success', t('mouvements_created'));
                    redirect('groupes/show', ['id' => $variante['groupe_id']]);
                } catch (Throwable $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
        }
    }

    render('mouvements/form', ['error' => $error, 'variante' => $variante]);
    exit;
}

if ($route === 'mouvements/history') {
    requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
    $groupeId = getInt('groupe_id') ?: null;
    $varianteId = getInt('variante_id') ?: null;

    $sql = "SELECT m.*, v.libelle AS variante_libelle, g.id AS groupe_id, g.nom AS groupe_nom, u.full_name AS user_full_name
            FROM mouvements m
            JOIN variantes v ON m.variante_id = v.id
            JOIN groupes g ON v.groupe_id = g.id
            JOIN users u ON m.user_id = u.id
            WHERE 1=1";
    $params = [];
    if ($groupeId) {
        $sql .= ' AND g.id = ?';
        $params[] = $groupeId;
    }
    if ($varianteId) {
        $sql .= ' AND v.id = ?';
        $params[] = $varianteId;
    }
    $sql .= ' ORDER BY m.created_at DESC, m.id DESC LIMIT 200';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $mouvements = $stmt->fetchAll();

    render('mouvements/history', ['mouvements' => $mouvements, 'groupeId' => $groupeId, 'varianteId' => $varianteId]);
    exit;
}
