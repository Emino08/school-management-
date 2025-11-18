<?php

namespace App\Models;

class House extends BaseModel
{
    protected $table = 'houses';

    public function getAllByAdmin($adminId)
    {
        return $this->findAll(['admin_id' => $adminId], 'house_name ASC');
    }

    public function getHouseWithBlocks($houseId)
    {
        $sql = "SELECT h.*, 
                       (SELECT COUNT(*) FROM students WHERE house_id = h.id AND is_registered = 1) as total_students,
                       (SELECT COUNT(*) FROM house_masters WHERE house_id = h.id AND is_active = 1) as house_master_count
                FROM {$this->table} h
                WHERE h.id = :house_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':house_id' => $houseId]);
        $house = $stmt->fetch();

        if ($house) {
            $sql = "SELECT hb.*,
                           (SELECT COUNT(*) FROM students WHERE house_block_id = hb.id AND is_registered = 1) as current_occupancy
                    FROM house_blocks hb
                    WHERE hb.house_id = :house_id
                    ORDER BY hb.block_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':house_id' => $houseId]);
            $house['blocks'] = $stmt->fetchAll();
        }

        return $house;
    }

    public function getHouseMasters($houseId)
    {
        $sql = "SELECT hm.*, t.name as teacher_name, t.email, t.phone
                FROM house_masters hm
                JOIN teachers t ON hm.teacher_id = t.id
                WHERE hm.house_id = :house_id AND hm.is_active = 1
                ORDER BY hm.assigned_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':house_id' => $houseId]);
        return $stmt->fetchAll();
    }

    public function getHouseStudents($houseId, $blockId = null)
    {
        $sql = "SELECT s.*, hb.block_name, c.class_name, c.section,
                       fp.amount as fees_paid, fp.payment_status
                FROM students s
                LEFT JOIN house_blocks hb ON s.house_block_id = hb.id
                LEFT JOIN student_enrollments se ON s.id = se.student_id
                LEFT JOIN classes c ON se.class_id = c.id
                LEFT JOIN fees_payments fp ON s.id = fp.student_id AND fp.is_tuition_fee = 1
                WHERE s.house_id = :house_id";

        if ($blockId) {
            $sql .= " AND s.house_block_id = :block_id";
        }

        $sql .= " ORDER BY hb.block_name, s.name";

        $stmt = $this->db->prepare($sql);
        $params = [':house_id' => $houseId];
        if ($blockId) {
            $params[':block_id'] = $blockId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createDefaultBlocks($houseId, $adminId)
    {
        $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
        $success = true;

        foreach ($blocks as $block) {
            $sql = "INSERT INTO house_blocks (house_id, admin_id, block_name, capacity)
                    VALUES (:house_id, :admin_id, :block_name, 50)
                    ON DUPLICATE KEY UPDATE block_name = block_name";

            $stmt = $this->db->prepare($sql);
            if (!$stmt->execute([
                ':house_id' => $houseId,
                ':admin_id' => $adminId,
                ':block_name' => $block
            ])) {
                $success = false;
            }
        }

        return $success;
    }
}
