<?php

declare(strict_types=1);
require_once __DIR__ . '/database.php';

function cmsAudit(string $action, string $entity = 'system', ?string $details = null): void
{
    try {
        $stmt = cmsPdo()->prepare('INSERT INTO audit_logs (admin_user_id, action_key, entity_type, details) VALUES (:admin, :action, :entity, :details)');
        $stmt->execute([
            ':admin' => $_SESSION['cms_admin_id'] ?? null,
            ':action' => $action,
            ':entity' => $entity,
            ':details' => $details,
        ]);
    } catch (Throwable $e) {
        // Audit failure must not break the requested admin operation.
    }
}
