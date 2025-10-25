<?php

namespace App\Models;

class Notice extends BaseModel
{
    protected $table = 'notices';

    public function getNoticesByAdmin($adminId, $limit = null)
    {
        return $this->findAll(['admin_id' => $adminId], $limit);
    }

    public function getNoticesByAudience($adminId, $targetAudience)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE admin_id = :admin_id
                  AND (target_audience = :target OR target_audience = 'all')
                ORDER BY date DESC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':target' => $targetAudience
        ]);
        return $stmt->fetchAll();
    }
}
