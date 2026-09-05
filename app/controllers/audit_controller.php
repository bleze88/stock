<?php
declare(strict_types=1);

requireAnyRole([ROLE_ADMIN, ROLE_MANAGER]);
$db = getDb();

$entries = $db->query(
    "SELECT a.*, u.full_name AS user_full_name
     FROM audit_log a
     LEFT JOIN users u ON a.user_id = u.id
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT 300"
)->fetchAll();

render('audit/index', ['entries' => $entries]);
