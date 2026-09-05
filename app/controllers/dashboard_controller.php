<?php
declare(strict_types=1);

$user = requireLogin();
$db = getDb();

$totalsByType = $db->query(
    "SELECT t.id, t.nom, COALESCE(SUM(v.quantite), 0) AS total_quantite, COUNT(DISTINCT g.id) AS nb_groupes
     FROM types t
     LEFT JOIN groupes g ON g.type_id = t.id AND g.active = 1
     LEFT JOIN variantes v ON v.groupe_id = g.id AND v.active = 1
     GROUP BY t.id, t.nom
     ORDER BY t.nom"
)->fetchAll();

$lowStock = $db->query(
    "SELECT v.id, v.libelle, v.quantite, v.seuil_alerte AS variante_seuil,
            g.id AS groupe_id, g.nom AS groupe_nom, g.seuil_alerte AS groupe_seuil,
            t.nom AS type_nom
     FROM variantes v
     JOIN groupes g ON v.groupe_id = g.id
     JOIN types t ON g.type_id = t.id
     WHERE v.active = 1 AND g.active = 1
       AND v.quantite <= COALESCE(v.seuil_alerte, g.seuil_alerte)
     ORDER BY v.quantite ASC
     LIMIT 25"
)->fetchAll();

$canSeeHistory = canManageStock();
$recentMouvements = $canSeeHistory ? $db->query(
    "SELECT m.id, m.delta, m.quantite_apres, m.motif, m.created_at,
            v.libelle AS variante_libelle, g.nom AS groupe_nom, u.full_name AS user_full_name
     FROM mouvements m
     JOIN variantes v ON m.variante_id = v.id
     JOIN groupes g ON v.groupe_id = g.id
     JOIN users u ON m.user_id = u.id
     ORDER BY m.created_at DESC, m.id DESC
     LIMIT 15"
)->fetchAll() : [];

$totalArticles = (int)$db->query('SELECT COALESCE(SUM(quantite), 0) AS n FROM variantes WHERE active = 1')->fetch()['n'];
$totalGroupes = (int)$db->query('SELECT COUNT(*) AS n FROM groupes WHERE active = 1')->fetch()['n'];

render('dashboard/index', [
    'totalsByType' => $totalsByType,
    'lowStock' => $lowStock,
    'recentMouvements' => $recentMouvements,
    'canSeeHistory' => $canSeeHistory,
    'totalArticles' => $totalArticles,
    'totalGroupes' => $totalGroupes,
]);
