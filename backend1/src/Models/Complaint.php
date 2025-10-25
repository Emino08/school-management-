<?php

namespace App\Models;

class Complaint extends BaseModel
{
    protected $table = 'complaints';

    public function getComplaintsByAdmin($adminId)
    {
        return $this->findAll(['admin_id' => $adminId]);
    }

    public function getComplaintsByUser($userId, $userType)
    {
        return $this->findAll([
            'user_id' => $userId,
            'user_type' => $userType
        ]);
    }

    public function updateComplaintStatus($complaintId, $status, $response = null)
    {
        $data = ['status' => $status];
        if ($response) {
            $data['response'] = $response;
        }
        return $this->update($complaintId, $data);
    }
}
