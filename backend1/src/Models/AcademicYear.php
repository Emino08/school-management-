<?php

namespace App\Models;

class AcademicYear extends BaseModel
{
    protected $table = 'academic_years';

    public function getCurrentYear($adminId)
    {
        return $this->findOne(['admin_id' => $adminId, 'is_current' => 1]);
    }

    public function setCurrentYear($adminId, $yearId)
    {
        // First, unset all current years for this admin
        $sql = "UPDATE {$this->table} SET is_current = 0 WHERE admin_id = :admin_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);

        // Then set the new current year
        return $this->update($yearId, ['is_current' => 1]);
    }

    public function completeYear($yearId)
    {
        return $this->update($yearId, ['status' => 'completed', 'is_current' => 0]);
    }
}
