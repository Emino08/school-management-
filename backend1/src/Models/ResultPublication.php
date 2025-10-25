<?php

namespace App\Models;

class ResultPublication extends BaseModel
{
    protected $table = 'result_publications';

    public function createPublication($data)
    {
        return $this->create($data);
    }

    public function getPublicationByExam($examId)
    {
        return $this->findOne(['exam_id' => $examId]);
    }

    public function isResultPublished($examId)
    {
        $publication = $this->findOne(['exam_id' => $examId, 'is_active' => 1]);

        if (!$publication) {
            return false;
        }

        // Check if publication date has passed
        $publicationDate = new \DateTime($publication['publication_date']);
        $today = new \DateTime();

        return $today >= $publicationDate;
    }

    public function getUpcomingPublications($adminId, $limit = 5)
    {
        $sql = "SELECT rp.*, e.exam_name, ay.year_name
                FROM {$this->table} rp
                JOIN exams e ON rp.exam_id = e.id
                JOIN academic_years ay ON rp.academic_year_id = ay.id
                WHERE rp.admin_id = :admin_id
                AND rp.publication_date > CURDATE()
                AND rp.is_active = 1
                ORDER BY rp.publication_date ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':admin_id', $adminId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPublicationStats($examId)
    {
        $sql = "SELECT
                    COUNT(*) as total_results,
                    COUNT(CASE WHEN approval_status = 'approved' THEN 1 END) as approved_count,
                    COUNT(CASE WHEN approval_status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN approval_status = 'rejected' THEN 1 END) as rejected_count
                FROM exam_results
                WHERE exam_id = :exam_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':exam_id' => $examId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updatePublicationStats($publicationId, $stats)
    {
        return $this->update($publicationId, [
            'total_students' => $stats['total_results'],
            'approved_results' => $stats['approved_count'],
            'pending_results' => $stats['pending_count']
        ]);
    }

    public function hideResults($examId)
    {
        $publication = $this->findOne(['exam_id' => $examId]);
        if ($publication) {
            return $this->update($publication['id'], ['is_active' => 0]);
        }
        return false;
    }

    public function showResults($examId)
    {
        $publication = $this->findOne(['exam_id' => $examId]);
        if ($publication) {
            return $this->update($publication['id'], ['is_active' => 1]);
        }
        return false;
    }
}
