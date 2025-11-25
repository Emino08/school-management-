<?php

namespace App\Controllers;

use App\Traits\LogsActivity;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Stream;

class SettingsController
{
    use LogsActivity;

    private $db;
    private $defaultGeneral = [
        'school_name' => '',
        'school_code' => '',
        'school_address' => '',
        'school_phone' => '',
        'school_email' => '',
        'school_website' => '',
        'school_logo' => '',
        'academic_year_start_month' => 9,
        'academic_year_end_month' => 6,
        'timezone' => 'UTC',
    ];

    private $defaultNotifications = [
        'email_enabled' => true,
        'sms_enabled' => false,
        'push_enabled' => true,
        'notify_attendance' => true,
        'notify_results' => true,
        'notify_fees' => true,
        'notify_complaints' => true,
    ];

    private $defaultEmail = [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_email' => '',
        'from_name' => '',
    ];

    private $defaultSecurity = [
        'force_password_change' => false,
        'password_min_length' => 6,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => false,
        'session_timeout' => 30,
        'max_login_attempts' => 5,
        'two_factor_enabled' => false,
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureExtendedColumns();
    }

    // Get school settings
    public function get()
    {
        try {
            $settings = $this->getSettingsRow();

            if (!$settings) {
                return [
                    'success' => true,
                    'settings' => [
                        'general' => $this->defaultGeneral,
                        'notifications' => $this->defaultNotifications,
                        'email' => $this->defaultEmail,
                        'security' => $this->defaultSecurity,
                        'maintenance_mode' => false,
                    ]
                ];
            }

            $general = [
                'school_name' => $settings['school_name'] ?? '',
                'school_code' => $settings['school_code'] ?? '',
                'school_address' => $settings['address'] ?? '',
                'school_phone' => $settings['phone'] ?? '',
                'school_email' => $settings['email'] ?? '',
                'school_website' => $settings['website'] ?? '',
                'school_logo' => $settings['logo_url'] ?? '',
                'academic_year_start_month' => (int)($settings['academic_year_start_month'] ?? 9),
                'academic_year_end_month' => (int)($settings['academic_year_end_month'] ?? 6),
                'timezone' => $settings['timezone'] ?? 'UTC',
            ];

            return [
                'success' => true,
                'settings' => [
                    'general' => array_merge($this->defaultGeneral, $general),
                    'notifications' => $this->decodeSettings($settings['notification_settings'] ?? null, $this->defaultNotifications),
                    'email' => $this->decodeSettings($settings['email_settings'] ?? null, $this->defaultEmail),
                    'security' => $this->decodeSettings($settings['security_settings'] ?? null, $this->defaultSecurity),
                    'maintenance_mode' => (bool)($settings['maintenance_mode'] ?? 0),
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching school settings: ' . $e->getMessage()
            ];
        }
    }

    // Update school settings
    public function update($data)
    {
        try {
            $type = $data['type'] ?? 'general';
            $payload = $data['settings'] ?? [];

            switch ($type) {
                case 'general':
                    $this->saveGeneralSettings($payload);
                    break;
                case 'notifications':
                    $this->saveJsonSettings('notification_settings', $payload, $this->defaultNotifications);
                    break;
                case 'email':
                    $this->saveJsonSettings('email_settings', $payload, $this->defaultEmail);
                    break;
                case 'security':
                    $this->saveJsonSettings('security_settings', $payload, $this->defaultSecurity);
                    break;
                case 'system':
                    $this->saveSystemSettings($payload);
                    break;
                default:
                    return [
                        'success' => false,
                        'message' => 'Invalid settings type'
                    ];
            }

            return [
                'success' => true,
                'message' => 'School settings updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating school settings: ' . $e->getMessage()
            ];
        }
    }

    private function decodeSettings(?string $payload, array $defaults): array
    {
        if (!$payload) {
            return $defaults;
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    private function ensureSettingsRow(): int
    {
        $stmt = $this->db->query("SELECT id FROM school_settings LIMIT 1");
        $existingId = (int)$stmt->fetchColumn();

        if ($existingId) {
            return $existingId;
        }

        $stmt = $this->db->prepare("
            INSERT INTO school_settings (school_name, school_code, address, phone, email, website, logo_url, principal_name, academic_year_start_month, academic_year_end_month, currency, timezone)
            VALUES ('', '', '', '', '', '', '', '', 9, 6, 'USD', 'UTC')
        ");
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    private function saveGeneralSettings(array $data): void
    {
        $settings = array_merge($this->defaultGeneral, $data);
        $id = $this->ensureSettingsRow();

        $stmt = $this->db->prepare("
            UPDATE school_settings
               SET school_name = :school_name,
                   school_code = :school_code,
                   address = :address,
                   phone = :phone,
                   email = :email,
                   website = :website,
                   logo_url = :logo_url,
                   academic_year_start_month = :academic_year_start_month,
                   academic_year_end_month = :academic_year_end_month,
                   timezone = :timezone,
                   updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
            ':school_name' => $settings['school_name'],
            ':school_code' => $settings['school_code'] ?? null,
            ':address' => $settings['school_address'] ?? null,
            ':phone' => $settings['school_phone'] ?? null,
            ':email' => $settings['school_email'] ?? null,
            ':website' => $settings['school_website'] ?? null,
            ':logo_url' => $settings['school_logo'] ?? null,
            ':academic_year_start_month' => $settings['academic_year_start_month'] ?? 9,
            ':academic_year_end_month' => $settings['academic_year_end_month'] ?? 6,
            ':timezone' => $settings['timezone'] ?? 'UTC',
        ]);
    }

    private function saveJsonSettings(string $column, array $data, array $defaults): void
    {
        $payload = json_encode(array_merge($defaults, $data));
        $id = $this->ensureSettingsRow();

        // Validate column name to prevent SQL injection
        $allowedColumns = ['notification_settings', 'email_settings', 'security_settings'];
        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }

        // Use string interpolation but safe because we validated column name above
        $sql = "UPDATE school_settings SET {$column} = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payload, $id]);
    }

    private function saveSystemSettings(array $data): void
    {
        $id = $this->ensureSettingsRow();
        $stmt = $this->db->prepare("
            UPDATE school_settings
               SET maintenance_mode = :mode,
                   updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
        ");
        $stmt->execute([
            ':mode' => !empty($data['maintenance_mode']) ? 1 : 0,
            ':id' => $id,
        ]);
    }

    private function getSettingsRow(): ?array
    {
        $stmt = $this->db->query("SELECT * FROM school_settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        return $settings ?: null;
    }

    private function columnExists(string $column): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'school_settings' 
                  AND COLUMN_NAME = ?
            ");
            $stmt->execute([$column]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['cnt'] ?? 0) > 0;
        } catch (\PDOException $e) {
            error_log("Column check error: " . $e->getMessage());
            return false;
        }
    }

    private function ensureExtendedColumns(): void
    {
        $columns = [
            'notification_settings' => "ALTER TABLE school_settings ADD COLUMN notification_settings TEXT NULL AFTER timezone",
            'email_settings' => "ALTER TABLE school_settings ADD COLUMN email_settings TEXT NULL AFTER notification_settings",
            'security_settings' => "ALTER TABLE school_settings ADD COLUMN security_settings TEXT NULL AFTER email_settings",
            'maintenance_mode' => "ALTER TABLE school_settings ADD COLUMN maintenance_mode TINYINT(1) NOT NULL DEFAULT 0 AFTER security_settings",
            'academic_year_end_month' => "ALTER TABLE school_settings ADD COLUMN academic_year_end_month INT NULL DEFAULT 6 AFTER academic_year_start_month",
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists($column)) {
                $this->db->exec($sql);
            }
        }
    }

    // Upload school logo
    public function uploadLogo($file)
    {
        try {
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowedTypes)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. Only JPEG and PNG are allowed.'
                ];
            }

            if ($file['size'] > $maxSize) {
                return [
                    'success' => false,
                    'message' => 'File size exceeds 2MB limit.'
                ];
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'school_logo_' . time() . '.' . $extension;
            $uploadDir = __DIR__ . '/../../public/uploads/';
            $uploadPath = $uploadDir . $filename;

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $logoUrl = '/uploads/' . $filename;

                // Update settings with new logo URL
                $id = $this->ensureSettingsRow();
                $stmt = $this->db->prepare("
                    UPDATE school_settings
                    SET logo_url = :logo_url,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->execute([':logo_url' => $logoUrl, ':id' => $id]);

                return [
                    'success' => true,
                    'message' => 'Logo uploaded successfully',
                    'logo_url' => $logoUrl
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to upload logo'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error uploading logo: ' . $e->getMessage()
            ];
        }
    }

    // ======= PSR-7 ROUTE HANDLERS =======

    /**
     * Get settings (PSR-7)
     */
    public function getSettings(Request $request, Response $response)
    {
        $result = $this->get();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update settings (PSR-7)
     */
    public function updateSettings(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->update($data);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $testEmail = $data['to'] ?? $data['email'] ?? null;
            $subject = $data['subject'] ?? 'Test Email from School Management System';
            $message = $data['message'] ?? null;

            // Log the request
            error_log('Test email request received for: ' . $testEmail);

            if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                error_log('Invalid email address provided: ' . ($testEmail ?? 'null'));
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Valid email address is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $mailer = new \App\Utils\Mailer();

            // Test SMTP connection first
            error_log('Testing SMTP connection...');
            $connectionTest = $mailer->testConnection();
            if (!$connectionTest['success']) {
                error_log('SMTP Connection Failed: ' . $connectionTest['message']);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'SMTP Connection Failed: ' . $connectionTest['message']
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            error_log('SMTP connection successful');

            // Build email body
            if ($message) {
                // Use custom message with HTML formatting
                $body = '
                    <div style="font-family: Arial, sans-serif; padding: 20px; max-width: 600px;">
                        <h2 style="color: #333;">' . htmlspecialchars($subject) . '</h2>
                        <div style="margin: 20px 0; line-height: 1.6;">
                            ' . nl2br(htmlspecialchars($message)) . '
                        </div>
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                        <p style="color: #666; font-size: 12px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
                    </div>
                ';
            } else {
                // Default test email body
                $body = '
                    <h2>Email Configuration Test</h2>
                    <p>This is a test email to verify your email configuration is working correctly.</p>
                    <p>If you received this email, your SMTP settings are configured properly!</p>
                    <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
                ';
            }

            error_log('Attempting to send test email...');
            $sent = $mailer->send($testEmail, $subject, $body);

            if ($sent) {
                error_log('Test email sent successfully to: ' . $testEmail);
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $testEmail
                ]));
            } else {
                error_log('Failed to send test email to: ' . $testEmail);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Failed to send test email. Please check your email logs for details.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            error_log('Test email exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');

            // Only admin or principal can create backups
            $role = strtolower($user->role ?? '');
            if (!in_array($role, ['admin', 'principal'], true)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $backupDir = __DIR__ . '/../../backups/';

            // Create backup directory if it doesn't exist
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = 'backup_' . date('Y-m-d_His') . '.sql';
            $filepath = $backupDir . $filename;

            // Get database credentials from config
            $dbConfig = require __DIR__ . '/../../config/database.php';

            $host = $dbConfig['host'];
            $dbname = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Execute mysqldump
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($dbname),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($filepath)) {
                $stream = new Stream(fopen($filepath, 'rb'));
                return $response
                    ->withBody($stream)
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            } else {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Backup creation failed. Please ensure mysqldump is available on your system.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error creating backup: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');

            // Only admin or principal can restore backups
            $role = strtolower($user->role ?? '');
            if (!in_array($role, ['admin', 'principal'], true)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $data = json_decode($request->getBody()->getContents(), true);
            $filename = $data['filename'] ?? null;

            if (!$filename) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Backup filename is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $backupDir = __DIR__ . '/../../backups/';
            $filepath = $backupDir . $filename;

            if (!file_exists($filepath)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Backup file not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get database credentials
            $dbConfig = require __DIR__ . '/../../config/database.php';

            $host = $dbConfig['host'];
            $dbname = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];

            // Execute mysql restore
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($dbname),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Backup restored successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Backup restoration failed'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error restoring backup: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

