<?php

namespace App\Utils;

class Schema
{
    /**
     * Ensure new class placement columns exist to avoid runtime SQL errors.
     * Adds columns if missing: `capacity` INT NULL, `placement_min_average` DECIMAL(5,2) DEFAULT 0
     */
    public static function ensureClassCapacityColumns(\PDO $db): void
    {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM classes LIKE 'capacity'");
            if ($stmt && $stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE classes ADD COLUMN capacity INT NULL AFTER section");
            }
        } catch (\Exception $e) {
            // ignore — non-fatal guard
        }

        try {
            $stmt = $db->query("SHOW COLUMNS FROM classes LIKE 'placement_min_average'");
            if ($stmt && $stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE classes ADD COLUMN placement_min_average DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER capacity");
            }
        } catch (\Exception $e) {
            // ignore — non-fatal guard
        }
    }
}

