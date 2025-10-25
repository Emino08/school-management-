<?php

namespace App\Models;

class Admin extends BaseModel
{
    protected $table = 'admins';

    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    public function createAdmin($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->create($data);
    }

    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }
}
