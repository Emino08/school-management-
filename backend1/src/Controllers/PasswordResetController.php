<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Config\Database;
use App\Utils\Mailer;
use App\Utils\Validator;
use PDO;

class PasswordResetController
{
    private $db;
    private $mailer;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->mailer = new Mailer();
        $this->ensurePasswordResetTable();
    }

    /**
     * Request password reset - send email with token
     */
    public function requestReset(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? null;
        $role = strtolower($data['role'] ?? 'admin'); // admin, student, teacher, parent

        if (!$email) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Email is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Check if email settings are configured
            $emailSettings = $this->getEmailSettings();
            
            if (!$emailSettings || !$this->isEmailConfigured($emailSettings)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Password reset is currently disabled. Please contact the system administrator.',
                    'error_code' => 'EMAIL_NOT_CONFIGURED'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(503);
            }

            // Find user by email and role
            $user = $this->findUserByEmailAndRole($email, $role);

            if (!$user) {
                // Don't reveal if email exists or not (security)
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'If your email exists in our system, you will receive a password reset link shortly.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Generate reset token
            $token = $this->generateResetToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to database
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (email, role, token, expires_at, created_at)
                VALUES (:email, :role, :token, :expires, NOW())
                ON DUPLICATE KEY UPDATE 
                    token = :token2, 
                    expires_at = :expires2, 
                    created_at = NOW(),
                    used = 0
            ");

            $stmt->execute([
                ':email' => $email,
                ':role' => $role,
                ':token' => $token,
                ':expires' => $expires,
                ':token2' => $token,
                ':expires2' => $expires,
            ]);

            // Send email
            $name = $user['name'] ?? $user['contact_name'] ?? $user['school_name'] ?? 'User';
            
            try {
                $emailSent = $this->mailer->sendPasswordResetEmail($email, $name, $token);
                
                if ($emailSent) {
                    $response->getBody()->write(json_encode([
                        'success' => true,
                        'message' => 'Password reset link has been sent to your email.'
                    ]));
                } else {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'message' => 'Failed to send email. Please check email configuration or try again later.',
                        'error_code' => 'EMAIL_SEND_FAILED'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                }
            } catch (\Exception $emailError) {
                error_log('Email sending error: ' . $emailError->getMessage());
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email service error. Please verify email settings or contact the administrator.',
                    'error_code' => 'EMAIL_SERVICE_ERROR'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('Password reset request error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again later.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Verify reset token
     */
    public function verifyToken(Request $request, Response $response)
    {
        $params = $request->getQueryParams();
        $token = $params['token'] ?? null;

        if (!$token) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token is required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $stmt = $this->db->prepare("
                SELECT email, role, expires_at, used 
                FROM password_resets 
                WHERE token = :token 
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
            $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRequest) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid token'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($resetRequest['used']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Token has already been used'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (strtotime($resetRequest['expires_at']) < time()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Token has expired'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Token is valid',
                'email' => $resetRequest['email'],
                'role' => $resetRequest['role']
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('Token verification error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'An error occurred'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;
        $confirmPassword = $data['confirm_password'] ?? null;

        // Validate inputs
        if (!$token || !$newPassword || !$confirmPassword) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($newPassword !== $confirmPassword) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Passwords do not match'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($newPassword) < 6) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Password must be at least 6 characters'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Verify token
            $stmt = $this->db->prepare("
                SELECT email, role, expires_at, used 
                FROM password_resets 
                WHERE token = :token 
                LIMIT 1
            ");
            $stmt->execute([':token' => $token]);
            $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRequest || $resetRequest['used'] || strtotime($resetRequest['expires_at']) < time()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Update password based on role
            $updated = $this->updateUserPassword(
                $resetRequest['email'],
                $resetRequest['role'],
                $newPassword
            );

            if (!$updated) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Mark token as used
            $stmt = $this->db->prepare("
                UPDATE password_resets 
                SET used = 1 
                WHERE token = :token
            ");
            $stmt->execute([':token' => $token]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login with your new password.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Find user by email and role
     */
    private function findUserByEmailAndRole($email, $role)
    {
        $table = $this->getTableForRole($role);
        if (!$table) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user password
     */
    private function updateUserPassword($email, $role, $newPassword)
    {
        $table = $this->getTableForRole($role);
        if (!$table) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("
            UPDATE {$table} 
            SET password = :password, 
                updated_at = NOW() 
            WHERE email = :email
        ");

        return $stmt->execute([
            ':password' => $hashedPassword,
            ':email' => $email
        ]);
    }

    /**
     * Get table name for role
     */
    private function getTableForRole($role)
    {
        $roleMap = [
            'admin' => 'admins',
            'principal' => 'admins',
            'student' => 'students',
            'teacher' => 'teachers',
            'parent' => 'parents'
        ];

        return $roleMap[$role] ?? null;
    }

    /**
     * Generate secure random token
     */
    private function generateResetToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Ensure password_resets table exists
     */
    private function ensurePasswordResetTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                role ENUM('admin', 'principal', 'student', 'teacher', 'parent') NOT NULL,
                token VARCHAR(100) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token),
                INDEX idx_expires (expires_at),
                UNIQUE KEY unique_email_role (email, role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        try {
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            error_log('Failed to create password_resets table: ' . $e->getMessage());
        }
    }

    /**
     * Clean up expired tokens (can be called via cron)
     */
    public function cleanupExpiredTokens(Request $request, Response $response)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM password_resets 
                WHERE expires_at < NOW() OR used = 1
            ");
            $stmt->execute();
            $deleted = $stmt->rowCount();

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => "Cleaned up {$deleted} expired/used tokens"
            ]));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get email settings from database
     */
    private function getEmailSettings()
    {
        try {
            $stmt = $this->db->query("SELECT email_settings FROM school_settings LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['email_settings']) {
                return json_decode($row['email_settings'], true);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('Error fetching email settings: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if email is properly configured
     */
    private function isEmailConfigured($settings)
    {
        if (!$settings) {
            return false;
        }

        // Check if required fields are set and not empty
        $requiredFields = ['smtp_host', 'smtp_username', 'smtp_password', 'from_email'];
        
        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                return false;
            }
        }

        return true;
    }
}
