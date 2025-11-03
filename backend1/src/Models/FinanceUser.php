<?php

namespace App\Models;

use PDO;
use PDOException;

class FinanceUser extends BaseModel
{
    protected $table = 'finance_users';

    /**
     * Find finance user by email
     */
    public function findByEmail($email)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding finance user by email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find finance user by ID and admin ID
     */
    public function findByIdAndAdmin($id, $adminId)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND admin_id = :admin_id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding finance user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all finance users for an admin
     */
    public function getAllByAdmin($adminId, $filters = [])
    {
        try {
            $query = "SELECT id, admin_id, name, email, phone, address, is_active,
                      can_approve_payments, can_generate_reports, can_manage_fees,
                      created_at, updated_at
                      FROM " . $this->table . "
                      WHERE admin_id = :admin_id";

            $params = [':admin_id' => $adminId];

            // Add filters
            if (!empty($filters['search'])) {
                $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (isset($filters['is_active'])) {
                $query .= " AND is_active = :is_active";
                $params[':is_active'] = $filters['is_active'];
            }

            $query .= " ORDER BY created_at DESC";

            if (isset($filters['limit'])) {
                $query .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if (isset($filters['limit'])) {
                $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)($filters['offset'] ?? 0), PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching finance users: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total count of finance users for an admin
     */
    public function getCountByAdmin($adminId, $filters = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE admin_id = :admin_id";
            $params = [':admin_id' => $adminId];

            if (!empty($filters['search'])) {
                $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            if (isset($filters['is_active'])) {
                $query .= " AND is_active = :is_active";
                $params[':is_active'] = $filters['is_active'];
            }

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting finance users: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create finance user
     */
    public function createFinanceUser($data)
    {
        try {
            $query = "INSERT INTO " . $this->table . "
                      (admin_id, name, email, password, phone, address, is_active,
                       can_approve_payments, can_generate_reports, can_manage_fees)
                      VALUES
                      (:admin_id, :name, :email, :password, :phone, :address, :is_active,
                       :can_approve_payments, :can_generate_reports, :can_manage_fees)";

            $stmt = $this->db->prepare($query);

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt->bindParam(':admin_id', $data['admin_id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1);
            $stmt->bindValue(':can_approve_payments', $data['can_approve_payments'] ?? 1);
            $stmt->bindValue(':can_generate_reports', $data['can_generate_reports'] ?? 1);
            $stmt->bindValue(':can_manage_fees', $data['can_manage_fees'] ?? 1);

            if ($stmt->execute()) {
                $data['id'] = $this->db->lastInsertId();
                unset($data['password']);
                return $data;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating finance user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update finance user
     */
    public function updateFinanceUser($id, $adminId, $data)
    {
        try {
            $query = "UPDATE " . $this->table . " SET
                      name = :name,
                      email = :email,
                      phone = :phone,
                      address = :address,
                      is_active = :is_active,
                      can_approve_payments = :can_approve_payments,
                      can_generate_reports = :can_generate_reports,
                      can_manage_fees = :can_manage_fees";

            // Only update password if provided
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }

            $query .= " WHERE id = :id AND admin_id = :admin_id";

            $stmt = $this->db->prepare($query);

            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1);
            $stmt->bindValue(':can_approve_payments', $data['can_approve_payments'] ?? 1);
            $stmt->bindValue(':can_generate_reports', $data['can_generate_reports'] ?? 1);
            $stmt->bindValue(':can_manage_fees', $data['can_manage_fees'] ?? 1);

            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                $stmt->bindParam(':password', $hashedPassword);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating finance user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete finance user
     */
    public function deleteFinanceUser($id, $adminId)
    {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':admin_id', $adminId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting finance user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify finance user password
     */
    public function verifyPassword($email, $password)
    {
        try {
            $user = $this->findByEmail($email);
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error verifying password: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActiveStatus($id, $adminId)
    {
        try {
            $query = "UPDATE " . $this->table . "
                      SET is_active = NOT is_active
                      WHERE id = :id AND admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':admin_id', $adminId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error toggling active status: " . $e->getMessage());
            return false;
        }
    }
}
