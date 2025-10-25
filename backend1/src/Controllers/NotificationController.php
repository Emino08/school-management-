<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

class NotificationController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Create and send notification
    public function create($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications
                (title, message, sender_id, sender_role, recipient_type, recipient_id, class_id, priority, status, scheduled_at, sent_at)
                VALUES (:title, :message, :sender_id, :sender_role, :recipient_type, :recipient_id, :class_id, :priority, :status, :scheduled_at, :sent_at)
            ");

            $sentAt = ($data['status'] ?? 'Sent') === 'Sent' ? date('Y-m-d H:i:s') : null;

            $stmt->execute([
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':sender_id' => $data['sender_id'],
                ':sender_role' => $data['sender_role'] ?? 'Admin',
                ':recipient_type' => $data['recipient_type'],
                ':recipient_id' => $data['recipient_id'] ?? null,
                ':class_id' => $data['class_id'] ?? null,
                ':priority' => $data['priority'] ?? 'Medium',
                ':status' => $data['status'] ?? 'Sent',
                ':scheduled_at' => $data['scheduled_at'] ?? null,
                ':sent_at' => $sentAt
            ]);

            return [
                'success' => true,
                'message' => 'Notification created successfully',
                'id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating notification: ' . $e->getMessage()
            ];
        }
    }

    // Get all notifications (for admin dashboard)
    public function getAll($filters = [])
    {
        try {
            $sql = "
                SELECT n.*,
                       CONCAT(a.first_name, ' ', a.last_name) as sender_name,
                       c.class_name
                FROM notifications n
                JOIN admins a ON n.sender_id = a.id
                LEFT JOIN classes c ON n.class_id = c.id
                WHERE 1=1
            ";

            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND n.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['recipient_type'])) {
                $sql .= " AND n.recipient_type = :recipient_type";
                $params[':recipient_type'] = $filters['recipient_type'];
            }

            if (isset($filters['priority'])) {
                $sql .= " AND n.priority = :priority";
                $params[':priority'] = $filters['priority'];
            }

            $sql .= " ORDER BY n.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get read count for each notification
            foreach ($notifications as &$notification) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification_reads WHERE notification_id = :notification_id");
                $stmt->execute([':notification_id' => $notification['id']]);
                $notification['read_count'] = $stmt->fetchColumn();
            }

            return [
                'success' => true,
                'notifications' => $notifications
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching notifications: ' . $e->getMessage()
            ];
        }
    }

    // Get notifications for a specific user (student, teacher, parent)
    public function getForUser($userId, $userRole)
    {
        try {
            $sql = "
                SELECT n.*,
                       CONCAT(a.first_name, ' ', a.last_name) as sender_name,
                       nr.read_at,
                       CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END as is_read
                FROM notifications n
                JOIN admins a ON n.sender_id = a.id
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id
                    AND nr.user_id = :user_id AND nr.user_role = :user_role
                WHERE n.status = 'Sent'
                AND (
                    n.recipient_type = 'All'
            ";

            $params = [
                ':user_id' => $userId,
                ':user_role' => $userRole
            ];

            // Add role-specific conditions
            if ($userRole === 'Student') {
                $sql .= " OR n.recipient_type = 'Students'
                         OR (n.recipient_type = 'Individual' AND n.recipient_id = :user_id)
                         OR (n.recipient_type = 'Specific Class' AND n.class_id = (SELECT class_id FROM students WHERE id = :user_id))
                ";
            } elseif ($userRole === 'Teacher') {
                $sql .= " OR n.recipient_type = 'Teachers'
                         OR (n.recipient_type = 'Individual' AND n.recipient_id = :user_id)
                ";
            } elseif ($userRole === 'Parent') {
                $sql .= " OR n.recipient_type = 'Parents'
                ";
            }

            $sql .= ") ORDER BY n.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'notifications' => $notifications
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching notifications: ' . $e->getMessage()
            ];
        }
    }

    // Mark notification as read
    public function markAsRead($notificationId, $userId, $userRole)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_reads (notification_id, user_id, user_role)
                VALUES (:notification_id, :user_id, :user_role)
                ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':notification_id' => $notificationId,
                ':user_id' => $userId,
                ':user_role' => $userRole
            ]);

            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error marking notification as read: ' . $e->getMessage()
            ];
        }
    }

    // Get unread count for a user
    public function getUnreadCount($userId, $userRole)
    {
        try {
            $sql = "
                SELECT COUNT(*) as unread_count
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id
                    AND nr.user_id = :user_id AND nr.user_role = :user_role
                WHERE n.status = 'Sent'
                AND nr.id IS NULL
                AND (
                    n.recipient_type = 'All'
            ";

            $params = [
                ':user_id' => $userId,
                ':user_role' => $userRole
            ];

            // Add role-specific conditions
            if ($userRole === 'Student') {
                $sql .= " OR n.recipient_type = 'Students'
                         OR (n.recipient_type = 'Individual' AND n.recipient_id = :user_id)
                         OR (n.recipient_type = 'Specific Class' AND n.class_id = (SELECT class_id FROM students WHERE id = :user_id))
                ";
            } elseif ($userRole === 'Teacher') {
                $sql .= " OR n.recipient_type = 'Teachers'
                         OR (n.recipient_type = 'Individual' AND n.recipient_id = :user_id)
                ";
            } elseif ($userRole === 'Parent') {
                $sql .= " OR n.recipient_type = 'Parents'
                ";
            }

            $sql .= ")";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'unread_count' => $result['unread_count']
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching unread count: ' . $e->getMessage()
            ];
        }
    }

    // Update notification
    public function update($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications
                SET title = :title,
                    message = :message,
                    recipient_type = :recipient_type,
                    recipient_id = :recipient_id,
                    class_id = :class_id,
                    priority = :priority,
                    status = :status,
                    scheduled_at = :scheduled_at,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':recipient_type' => $data['recipient_type'],
                ':recipient_id' => $data['recipient_id'] ?? null,
                ':class_id' => $data['class_id'] ?? null,
                ':priority' => $data['priority'],
                ':status' => $data['status'],
                ':scheduled_at' => $data['scheduled_at'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Notification updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating notification: ' . $e->getMessage()
            ];
        }
    }

    // Delete notification
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting notification: ' . $e->getMessage()
            ];
        }
    }

    // Send scheduled notifications (to be called by cron job)
    public function sendScheduledNotifications()
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications
                SET status = 'Sent',
                    sent_at = CURRENT_TIMESTAMP
                WHERE status = 'Scheduled'
                AND scheduled_at <= NOW()
            ");

            $stmt->execute();
            $count = $stmt->rowCount();

            return [
                'success' => true,
                'message' => "Sent $count scheduled notifications",
                'count' => $count
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error sending scheduled notifications: ' . $e->getMessage()
            ];
        }
    }
}
