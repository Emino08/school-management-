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

    public function getNoticeStats($adminId)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN target_audience = 'all' THEN 1 ELSE 0 END) as all_audience,
                    SUM(CASE WHEN target_audience = 'students' THEN 1 ELSE 0 END) as students_only,
                    SUM(CASE WHEN target_audience = 'teachers' THEN 1 ELSE 0 END) as teachers_only,
                    SUM(CASE WHEN target_audience = 'parents' THEN 1 ELSE 0 END) as parents_only,
                    SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN date < CURDATE() THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN DATEDIFF(date, CURDATE()) BETWEEN 0 AND 7 THEN 1 ELSE 0 END) as upcoming
                FROM {$this->table}
                WHERE admin_id = :admin_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetch();
    }
}
