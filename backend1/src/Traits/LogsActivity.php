<?php

namespace App\Traits;

use App\Utils\ActivityLogger;

trait LogsActivity
{
    protected function logActivity(
        $request,
        string $activityType,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ) {
        try {
            $user = $request->getAttribute('user');
            if (!$user) return;

            $logger = new ActivityLogger(\App\Config\Database::getInstance()->getConnection());
            
            $logger->logFromRequest(
                $request,
                $user->id,
                $user->role ?? 'unknown',
                $activityType,
                $description,
                $entityType,
                $entityId,
                $metadata,
                $user->name ?? $user->email ?? 'Unknown User'
            );
        } catch (\Exception $e) {
            // Silently fail - don't break the main operation
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}
