<?php
declare(strict_types=1);

$user = requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
$db = getDb();

if ($route === 'variantes/create') {
    $groupeId = getInt('groupe_id');
    $stmt = $db->prepare('SELECT id, nom FROM groupes WHERE id = ?');
    $stmt->execute([$groupeId]);
    $groupe = $stmt->fetch();
    if (!$groupe) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $error = null;

    if (isPost()) {
        csrfVerify();
        $libelle = postString('libelle');
        $quantite = postInt('quantite', 0);
        $seuilRaw = postString('seuil_alerte');
        $seuil = $seuilRaw === '' ? null : (int)$seuilRaw;
        $location = postString('location');
        $prixRaw = trim(str_replace(',', '.', postString('prix_vente')));
        $prix = $prixRaw === '' ? null : (float)$prixRaw;

        if (!isNonEmptyString($libelle, 100) || $quantite < 0) {
            $error = t('variantes_error_required');
        } elseif ($prixRaw !== '' && (!is_numeric($prixRaw) || $prix < 0)) {
            $error = t('variantes_error_price_invalid');
        } else {
            try {
                $stmt = $db->prepare(
                    'INSERT INTO variantes (groupe_id, libelle, quantite, seuil_alerte, location, prix_vente) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$groupeId, $libelle, $quantite, $seuil, $location ?: null, $prix]);
                $newVarianteId = (int)$db->lastInsertId();
                logAudit('create', 'variante', $newVarianteId, $libelle);

                if ($quantite > 0) {
                    $ins = $db->prepare(
                        'INSERT INTO mouvements (variante_id, user_id, delta, quantite_apres, motif) VALUES (?, ?, ?, ?, ?)'
                    );
                    $ins->execute([$newVarianteId, $user['id'], $quantite, $quantite, t('mouvements_initial_stock')]);
                }

                flashSet('success', t('variantes_created', ['libelle' => $libelle]));
                redirect('groupes/show', ['id' => $groupeId]);
            } catch (PDOException $e) {
                $error = t('variantes_error_duplicate');
            }
        }
    }

    render('variantes/form', ['error' => $error, 'groupe' => $groupe, 'variante' => null]);
    exit;
}

if ($route === 'variantes/edit') {
    $id = getInt('id');
    $stmt = $db->prepare(
        'SELECT v.*, g.nom AS groupe_nom FROM variantes v JOIN groupes g ON v.groupe_id = g.id WHERE v.id = ?'
    );
    $stmt->execute([$id]);
    $variante = $stmt->fetch();
    if (!$variante) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }
    $groupe = ['id' => $variante['groupe_id'], 'nom' => $variante['groupe_nom']];

    $error = null;

    if (isPost()) {
        csrfVerify();
        $libelle = postString('libelle');
        $seuilRaw = postString('seuil_alerte');
        $seuil = $seuilRaw === '' ? null : (int)$seuilRaw;
        $active = postInt('active', 0) ? 1 : 0;
        $location = postString('location');
        $prixRaw = trim(str_replace(',', '.', postString('prix_vente')));
        $prix = $prixRaw === '' ? null : (float)$prixRaw;

        if (!isNonEmptyString($libelle, 100)) {
            $error = t('variantes_error_required_edit');
        } elseif ($prixRaw !== '' && (!is_numeric($prixRaw) || $prix < 0)) {
            $error = t('variantes_error_price_invalid');
        } else {
            try {
                $upd = $db->prepare(
                    'UPDATE variantes SET libelle = ?, seuil_alerte = ?, active = ?, location = ?, prix_vente = ? WHERE id = ?'
                );
                $upd->execute([$libelle, $seuil, $active, $location ?: null, $prix, $id]);
                flashSet('success', t('variantes_updated'));
                redirect('groupes/show', ['id' => $variante['groupe_id']]);
            } catch (PDOException $e) {
                $error = t('variantes_error_duplicate');
            }
        }
    }

    render('variantes/form', ['error' => $error, 'groupe' => $groupe, 'variante' => $variante]);
    exit;
}

if ($route === 'variantes/delete') {
    if (!isPost()) {
        redirect('types');
    }
    csrfVerify();

    $id = postInt('id');
    $stmt = $db->prepare('SELECT * FROM variantes WHERE id = ?');
    $stmt->execute([$id]);
    $variante = $stmt->fetch();
    if (!$variante) {
        http_response_code(404);
        render('errors/404', []);
        exit;
    }

    $upd = $db->prepare('UPDATE variantes SET active = 0 WHERE id = ?');
    $upd->execute([$id]);
    logAudit('delete', 'variante', $id, $variante['libelle']);
    flashSet('success', t('variantes_deleted', ['libelle' => $variante['libelle']]));

    redirect('groupes/show', ['id' => $variante['groupe_id']]);
}
