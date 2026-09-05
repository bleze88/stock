<?php
declare(strict_types=1);

function logAudit(string $action, string $entityType, int $entityId, string $label): void
{
    $user = currentUser();
    $stmt = getDb()->prepare(
        'INSERT INTO audit_log (user_id, action, entity_type, entity_id, entity_label) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$user['id'] ?? null, $action, $entityType, $entityId, $label]);
}
