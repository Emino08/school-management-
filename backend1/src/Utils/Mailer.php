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
                $this->settings = $this->normalizeSettings(json_decode($row['email_settings'], true));
            } else {
                $this->settings = $this->normalizeSettings($this->getDefaultSettings());
            }
        } catch (\Exception $e) {
            $this->settings = $this->normalizeSettings($this->getDefaultSettings());
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

    /**
     * Normalize and correct known-bad settings so email sending doesn't fail
     */
    private function normalizeSettings(array $settings): array
    {
        $host = strtolower($settings['smtp_host'] ?? '');
        if ($host === 'smtp.titan.email' || strpos($host, 'titan.email') !== false) {
            $settings['smtp_host'] = 'smtp.hostinger.com';
            error_log('Mailer: Replacing deprecated smtp.titan.email with smtp.hostinger.com');
        }

        return $settings;
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
            
            // Handle encryption type - convert to PHPMailer constants
            $encryption = strtolower($this->settings['smtp_encryption'] ?? 'tls');
            if ($encryption === 'ssl' || $this->settings['smtp_port'] == 465) {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL for port 465
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS for port 587
            } else {
                $this->mailer->SMTPSecure = $encryption;
            }
            
            $this->mailer->Port = $this->settings['smtp_port'];
            
            // Timeouts and debugging
            $this->mailer->Timeout = 30;
            $this->mailer->SMTPDebug = 0; // Set to 2 for debugging
            $this->mailer->SMTPKeepAlive = false; // Don't keep connection open
            $this->mailer->SMTPAutoTLS = true; // Auto enable TLS if available
            
            // SSL options for compatibility
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
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
     * Enable debug mode
     */
    public function enableDebug($level = 2)
    {
        $this->mailer->SMTPDebug = $level;
        $this->mailer->Debugoutput = 'echo';
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
            'email' => $email,
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
        $schoolName = $this->settings['from_name'] ?? 'BoSchool';
        $logoUrl = isset($_ENV['APP_URL']) ? rtrim($_ENV['APP_URL'], '/') . '/Bo-School-logo.png' : 'https://via.placeholder.com/180x80/667eea/ffffff?text=BoSchool';
        $currentYear = date('Y');
        
        $baseStyle = "
            <style>
                body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; }
                .logo { max-width: 180px; height: auto; margin-bottom: 20px; background-color: white; padding: 15px; border-radius: 10px; }
                .button { display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 50px; font-size: 16px; font-weight: 600; }
            </style>
        ";
        
        switch ($template) {
            case 'welcome':
                return "<!DOCTYPE html><html><head>{$baseStyle}</head><body style='background-color: #f5f5f5; padding: 20px;'>
                    <div class='container' style='border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                        <div class='header'>
                            <img src='{$logoUrl}' alt='{$schoolName}' class='logo'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>Welcome to {$schoolName}!</h1>
                        </div>
                        <div style='padding: 40px 30px;'>
                            <p style='font-size: 16px; color: #333;'>Dear <strong>{$data['name']}</strong>,</p>
                            <p style='font-size: 16px; color: #555; line-height: 1.6;'>Your account has been created successfully. Welcome to our school management system!</p>
                            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p style='margin: 5px 0; color: #333;'><strong>Role:</strong> {$data['role']}</p>
                                <p style='margin: 5px 0; color: #333;'><strong>Email:</strong> {$data['email']}</p>
                                " . ($data['temp_password'] ? "<p style='margin: 5px 0; color: #333;'><strong>Temporary Password:</strong> {$data['temp_password']}</p>" : "") . "
                            </div>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$data['login_url']}' class='button'>Login to Your Account</a>
                            </div>
                            <p style='font-size: 14px; color: #777;'>Best regards,<br><strong style='color: #667eea;'>{$schoolName} Team</strong></p>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                            <p style='color: #6c757d; font-size: 12px; margin: 0;'>&copy; {$currentYear} {$schoolName}. All rights reserved.</p>
                        </div>
                    </div>
                </body></html>";
                
            case 'password-reset':
                return "<!DOCTYPE html><html><head>{$baseStyle}</head><body style='background-color: #f5f5f5; padding: 20px;'>
                    <div class='container' style='border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                        <div class='header'>
                            <img src='{$logoUrl}' alt='{$schoolName}' class='logo'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>Password Reset Request</h1>
                        </div>
                        <div style='padding: 40px 30px;'>
                            <p style='font-size: 16px; color: #333;'>Hello <strong>{$data['name']}</strong>,</p>
                            <p style='font-size: 16px; color: #555; line-height: 1.6;'>We received a request to reset your password. Click the button below to create a new password:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$data['reset_url']}' class='button'>Reset Your Password</a>
                            </div>
                            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;'>
                                <p style='font-size: 13px; color: #555; margin: 0;'>Or copy this link: <br><span style='color: #667eea;'>{$data['reset_url']}</span></p>
                            </div>
                            <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                                <p style='font-size: 14px; color: #856404; margin: 0;'>⏱️ This link will expire in {$data['expires_in']}.</p>
                            </div>
                            <p style='font-size: 15px; color: #555;'>If you didn't request this, please ignore this email.</p>
                            <p style='font-size: 14px; color: #777;'>Best regards,<br><strong style='color: #667eea;'>{$schoolName} Team</strong></p>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                            <p style='color: #6c757d; font-size: 12px; margin: 0;'>&copy; {$currentYear} {$schoolName}. All rights reserved.</p>
                        </div>
                    </div>
                </body></html>";
                
            case 'verification':
                return "<!DOCTYPE html><html><head>{$baseStyle}</head><body style='background-color: #f5f5f5; padding: 20px;'>
                    <div class='container' style='border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                        <div class='header'>
                            <img src='{$logoUrl}' alt='{$schoolName}' class='logo'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>Verify Your Email</h1>
                        </div>
                        <div style='padding: 40px 30px;'>
                            <p style='font-size: 16px; color: #333;'>Dear <strong>{$data['name']}</strong>,</p>
                            <p style='font-size: 16px; color: #555; line-height: 1.6;'>Please verify your email address by clicking the button below:</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$data['verify_url']}' class='button' style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%);'>Verify Email</a>
                            </div>
                            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>
                                <p style='font-size: 13px; color: #555; margin: 0;'>Or copy this link: <br><span style='color: #28a745;'>{$data['verify_url']}</span></p>
                            </div>
                            <p style='font-size: 14px; color: #777;'>Best regards,<br><strong style='color: #667eea;'>{$schoolName} Team</strong></p>
                        </div>
                        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                            <p style='color: #6c757d; font-size: 12px; margin: 0;'>&copy; {$currentYear} {$schoolName}. All rights reserved.</p>
                        </div>
                    </div>
                </body></html>";
                
            default:
                return "<html><body><p>Message from {$schoolName}</p></body></html>";
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
