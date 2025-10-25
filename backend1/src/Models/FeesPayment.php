<?php

namespace App\Models;

class FeesPayment extends BaseModel
{
    protected $table = 'fees_payments';

    public function getStudentPayments($studentId, $academicYearId)
    {
        return $this->findAll([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId
        ]);
    }

    public function getPaymentsByTerm($adminId, $academicYearId, $term)
    {
        $sql = "SELECT fp.*, s.name as student_name, s.id_number as id_number
                FROM {$this->table} fp
                INNER JOIN students s ON fp.student_id = s.id
                WHERE s.admin_id = :admin_id
                  AND fp.academic_year_id = :academic_year_id
                  AND fp.term = :term
                ORDER BY fp.payment_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':admin_id' => $adminId,
            ':academic_year_id' => $academicYearId,
            ':term' => $term
        ]);
        return $stmt->fetchAll();
    }

    public function getPaymentStats($academicYearId, $term = null)
    {
        $sql = "SELECT
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_amount
                FROM {$this->table}
                WHERE academic_year_id = :academic_year_id";

        $params = [':academic_year_id' => $academicYearId];

        if ($term) {
            $sql .= " AND term = :term";
            $params[':term'] = $term;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
