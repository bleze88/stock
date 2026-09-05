<?php
declare(strict_types=1);

requireLogin();
require_once APP_PATH . '/lib/xlsx.php';

if ($route === 'export/inventory') {
    $db = getDb();

    $rows = $db->query(
        "SELECT t.nom AS type_nom, g.nom AS groupe_nom, v.libelle,
                v.quantite, COALESCE(v.seuil_alerte, g.seuil_alerte) AS seuil
         FROM variantes v
         JOIN groupes g ON v.groupe_id = g.id
         JOIN types t ON g.type_id = t.id
         WHERE v.active = 1 AND g.active = 1
         ORDER BY t.nom, g.nom, v.libelle"
    )->fetchAll();

    $writer = new SimpleXlsxWriter();

    $totalRows = [[t('th_type'), t('th_group'), t('th_variant'), t('th_qty'), t('th_threshold')]];
    foreach ($rows as $r) {
        $totalRows[] = [$r['type_nom'], $r['groupe_nom'], $r['libelle'], (int)$r['quantite'], (int)$r['seuil']];
    }
    $writer->addSheet(t('export_sheet_total'), $totalRows);

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
