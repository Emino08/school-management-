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
        return $this->findAll(['admin_id' => $adminId], 'name ASC');
    }

    public function getActiveStaff($adminId)
    {
        return $this->findAll(['admin_id' => $adminId, 'is_active' => 1], 'name ASC');
    }
}
