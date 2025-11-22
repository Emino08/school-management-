<?php

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Config\Database;
use PDO;

class Mailer
{
    private $mailer;
    private $db;
    private $settings;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->loadSettings();
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function loadSettings()
    {
        try {
            $stmt = $this->db->query("SELECT email_settings FROM school_settings LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['email_settings']) {
                $this->settings = json_decode($row['email_settings'], true);
            } else {
                $this->settings = $this->getDefaultSettings();
            }
        } catch (\Exception $e) {
            $this->settings = $this->getDefaultSettings();
        }
    }

    private function getDefaultSettings()
    {
        return [
            'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
            'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
            'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
            'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
            'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? '',
            'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'School Management System',
        ];
    }

    private function configure()
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->settings['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->settings['smtp_username'];
            $this->mailer->Password = $this->settings['smtp_password'];
            $this->mailer->SMTPSecure = $this->settings['smtp_encryption'];
            $this->mailer->Port = $this->settings['smtp_port'];
            
            // Sender info
            $this->mailer->setFrom(
                $this->settings['from_email'], 
                $this->settings['from_name']
            );
            
            // UTF-8 encoding
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            error_log('Mailer configuration error: ' . $e->getMessage());
        }
    }

    /**
     * Send email
     */
    public function send($to, $subject, $body, $altBody = '')
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags($body);

            $result = $this->mailer->send();
            
            // Log email sent
            $this->logEmail($to, $subject, $result ? 'sent' : 'failed');
            
            return $result;
        } catch (Exception $e) {
            error_log('Email send error: ' . $this->mailer->ErrorInfo);
            $this->logEmail($to, $subject, 'failed', $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Test email configuration
     */
    public function testConnection()
    {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send welcome email for new account
     */
    public function sendWelcomeEmail($email, $name, $role, $tempPassword = null)
    {
        $subject = "Welcome to " . ($this->settings['from_name'] ?? 'School Management System');
        
        $body = $this->getTemplate('welcome', [
            'name' => $name,
            'role' => $role,
            'email' => $email,
            'temp_password' => $tempPassword,
            'login_url' => $_ENV['APP_URL'] ?? 'http://localhost:5173',
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $name, $resetToken)
    {
        $subject = "Password Reset Request";
        
        $resetUrl = ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173') . '/reset-password?token=' . $resetToken;
        
        $body = $this->getTemplate('password-reset', [
            'name' => $name,
            'reset_url' => $resetUrl,
            'reset_token' => $resetToken,
            'expires_in' => '1 hour',
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send account verification email
     */
    public function sendVerificationEmail($email, $name, $verificationToken)
    {
        $subject = "Verify Your Email Address";
        
        $verifyUrl = ($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173') . '/verify-email?token=' . $verificationToken;
        
        $body = $this->getTemplate('verification', [
            'name' => $name,
            'verify_url' => $verifyUrl,
            'verification_token' => $verificationToken,
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Get email template
     */
    private function getTemplate($template, $data)
    {
        $templatePath = __DIR__ . '/../Templates/emails/' . $template . '.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            extract($data);
            include $templatePath;
            return ob_get_clean();
        }
        
        // Fallback to default template
        return $this->getDefaultTemplate($template, $data);
    }

    /**
     * Default email templates
     */
    private function getDefaultTemplate($template, $data)
    {
        $schoolName = $this->settings['from_name'] ?? 'School Management System';
        
        switch ($template) {
            case 'welcome':
                return "
                    <h2>Welcome to {$schoolName}!</h2>
                    <p>Dear {$data['name']},</p>
                    <p>Your account has been created successfully.</p>
                    <p><strong>Role:</strong> {$data['role']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    " . ($data['temp_password'] ? "<p><strong>Temporary Password:</strong> {$data['temp_password']}</p>" : "") . "
                    <p>Please login at: <a href='{$data['login_url']}'>{$data['login_url']}</a></p>
                    <p>Best regards,<br>{$schoolName}</p>
                ";
                
            case 'password-reset':
                return "
                    <h2>Password Reset Request</h2>
                    <p>Dear {$data['name']},</p>
                    <p>We received a request to reset your password. Click the link below to reset it:</p>
                    <p><a href='{$data['reset_url']}' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>Or copy this link: {$data['reset_url']}</p>
                    <p>This link will expire in {$data['expires_in']}.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <p>Best regards,<br>{$schoolName}</p>
                ";
                
            case 'verification':
                return "
                    <h2>Verify Your Email</h2>
                    <p>Dear {$data['name']},</p>
                    <p>Please verify your email address by clicking the link below:</p>
                    <p><a href='{$data['verify_url']}' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
                    <p>Or copy this link: {$data['verify_url']}</p>
                    <p>Best regards,<br>{$schoolName}</p>
                ";
                
            default:
                return "<p>Message from {$schoolName}</p>";
        }
    }

    /**
     * Log email activity
     */
    private function logEmail($to, $subject, $status, $error = null)
    {
        try {
            // Check if email_logs table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'email_logs'");
            if (!$stmt->fetch()) {
                $this->createEmailLogsTable();
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (recipient, subject, status, error_message, created_at)
                VALUES (:recipient, :subject, :status, :error, NOW())
            ");
            
            $stmt->execute([
                ':recipient' => $to,
                ':subject' => $subject,
                ':status' => $status,
                ':error' => $error,
            ]);
        } catch (\Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }

    /**
     * Create email logs table if not exists
     */
    private function createEmailLogsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(500) NOT NULL,
                status ENUM('sent', 'failed') NOT NULL,
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_recipient (recipient),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->db->exec($sql);
    }
}
