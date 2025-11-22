<?php

namespace App\Models;

class MedicalStaff extends BaseModel
{
    protected $table = 'medical_staff';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function createStaff($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    public function getAllByAdmin($adminId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE admin_id = :admin_id ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll();
    }

    public function getActiveStaff($adminId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE admin_id = :admin_id AND is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll();
    }
}
