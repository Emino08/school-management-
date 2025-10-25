<?php

namespace App\Models;

class ExamOfficer extends BaseModel
{
    protected $table = 'exam_officers';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function findByAdmin($adminId)
    {
        return $this->findAll(['admin_id' => $adminId, 'is_active' => 1]);
    }

    public function activate($id)
    {
        return $this->update($id, ['is_active' => 1]);
    }

    public function deactivate($id)
    {
        return $this->update($id, ['is_active' => 0]);
    }

    public function getApprovalStats($officerId, $academicYearId = null)
    {
        $sql = "SELECT
                    COUNT(*) as total_approvals,
                    COUNT(CASE WHEN er.approval_status = 'approved' THEN 1 END) as approved_count,
                    COUNT(CASE WHEN er.approval_status = 'rejected' THEN 1 END) as rejected_count
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                WHERE er.approved_by_officer_id = :officer_id";

        $params = [':officer_id' => $officerId];

        if ($academicYearId) {
            $sql .= " AND e.academic_year_id = :academic_year_id";
            $params[':academic_year_id'] = $academicYearId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
