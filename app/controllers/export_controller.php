<?php
declare(strict_types=1);

requireLogin();
require_once APP_PATH . '/lib/xlsx.php';

if ($route === 'export/inventory') {
    $db = getDb();

    $writer = new SimpleXlsxWriter();

    // Onglet "Inventaire total" : résumé par type, comme le tableau de bord.
    $totalsByType = $db->query(
        "SELECT t.nom AS type_nom, COUNT(DISTINCT g.id) AS nb_groupes, COALESCE(SUM(v.quantite), 0) AS total_quantite
         FROM types t
         LEFT JOIN groupes g ON g.type_id = t.id AND g.active = 1
         LEFT JOIN variantes v ON v.groupe_id = g.id AND v.active = 1
         GROUP BY t.id, t.nom
         ORDER BY t.nom"
    )->fetchAll();

    $totalRows = [[t('th_type'), t('th_groups'), t('th_total_qty')]];
    foreach ($totalsByType as $r) {
        $totalRows[] = [$r['type_nom'], (int)$r['nb_groupes'], (int)$r['total_quantite']];
    }
    $writer->addSheet(t('export_sheet_total'), $totalRows);

    // Un onglet par type : détail Groupe / Variante / Quantité / Seuil.
    $rows = $db->query(
        "SELECT t.nom AS type_nom, g.nom AS groupe_nom, v.libelle,
                v.quantite, COALESCE(v.seuil_alerte, g.seuil_alerte) AS seuil
         FROM variantes v
         JOIN groupes g ON v.groupe_id = g.id
         JOIN types t ON g.type_id = t.id
         WHERE v.active = 1 AND g.active = 1
         ORDER BY t.nom, g.nom, v.libelle"
    )->fetchAll();

    $byType = [];
    foreach ($rows as $r) {
        $byType[$r['type_nom']][] = $r;
    }
    foreach ($byType as $typeName => $typeRows) {
        $sheetRows = [[t('th_group'), t('th_variant'), t('th_qty'), t('th_threshold')]];
        foreach ($typeRows as $r) {
            $sheetRows[] = [$r['groupe_nom'], $r['libelle'], (int)$r['quantite'], (int)$r['seuil']];
        }
        $writer->addSheet($typeName, $sheetRows);
    }

    $writer->output('inventaire-' . date('Y-m-d') . '.xlsx');
}
