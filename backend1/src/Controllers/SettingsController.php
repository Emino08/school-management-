<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

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
                return [
                    'success' => false,
                    'message' => 'School settings not found'
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
}
