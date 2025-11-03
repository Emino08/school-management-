<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SettingsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get school settings
    public function get()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM school_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Return default settings if none exist
                return [
                    'success' => true,
                    'settings' => [
                        'school_name' => '',
                        'school_code' => '',
                        'address' => '',
                        'phone' => '',
                        'email' => '',
                        'website' => '',
                        'logo_url' => null,
                        'principal_name' => '',
                        'academic_year_start_month' => 9,
                        'currency' => 'USD',
                        'timezone' => 'UTC'
                    ]
                ];
            }

            return [
                'success' => true,
                'settings' => $settings
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
            // Check if settings exist
            $stmt = $this->db->query("SELECT id FROM school_settings LIMIT 1");
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                // Update existing settings
                $stmt = $this->db->prepare("
                    UPDATE school_settings
                    SET school_name = :school_name,
                        school_code = :school_code,
                        address = :address,
                        phone = :phone,
                        email = :email,
                        website = :website,
                        logo_url = :logo_url,
                        principal_name = :principal_name,
                        academic_year_start_month = :academic_year_start_month,
                        currency = :currency,
                        timezone = :timezone,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':id' => $existingId,
                    ':school_name' => $data['school_name'],
                    ':school_code' => $data['school_code'] ?? null,
                    ':address' => $data['address'] ?? null,
                    ':phone' => $data['phone'] ?? null,
                    ':email' => $data['email'] ?? null,
                    ':website' => $data['website'] ?? null,
                    ':logo_url' => $data['logo_url'] ?? null,
                    ':principal_name' => $data['principal_name'] ?? null,
                    ':academic_year_start_month' => $data['academic_year_start_month'] ?? 9,
                    ':currency' => $data['currency'] ?? 'USD',
                    ':timezone' => $data['timezone'] ?? 'UTC'
                ]);
            } else {
                // Insert new settings
                $stmt = $this->db->prepare("
                    INSERT INTO school_settings
                    (school_name, school_code, address, phone, email, website, logo_url,
                     principal_name, academic_year_start_month, currency, timezone)
                    VALUES (:school_name, :school_code, :address, :phone, :email, :website,
                            :logo_url, :principal_name, :academic_year_start_month, :currency, :timezone)
                ");

                $stmt->execute([
                    ':school_name' => $data['school_name'],
                    ':school_code' => $data['school_code'] ?? null,
                    ':address' => $data['address'] ?? null,
                    ':phone' => $data['phone'] ?? null,
                    ':email' => $data['email'] ?? null,
                    ':website' => $data['website'] ?? null,
                    ':logo_url' => $data['logo_url'] ?? null,
                    ':principal_name' => $data['principal_name'] ?? null,
                    ':academic_year_start_month' => $data['academic_year_start_month'] ?? 9,
                    ':currency' => $data['currency'] ?? 'USD',
                    ':timezone' => $data['timezone'] ?? 'UTC'
                ]);
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
                $stmt = $this->db->prepare("
                    UPDATE school_settings
                    SET logo_url = :logo_url,
                        updated_at = CURRENT_TIMESTAMP
                    LIMIT 1
                ");
                $stmt->execute([':logo_url' => $logoUrl]);

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
     * Create database backup
     */
    public function createBackup(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');

            // Only admin can create backups
            if ($user->role !== 'Admin') {
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
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'filename' => $filename,
                    'size' => filesize($filepath),
                    'path' => $filepath
                ]));
                return $response->withHeader('Content-Type', 'application/json');
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

            // Only admin can restore backups
            if ($user->role !== 'Admin') {
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
