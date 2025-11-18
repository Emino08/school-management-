<?php

/**
 * Comprehensive Test Suite for Parents, Medical, and Houses System
 * Run this after migration to verify everything works correctly
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class SystemTester
{
    private $pdo;
    private $baseUrl = 'http://localhost:8080/api';
    private $adminToken;
    private $parentToken;
    private $medicalToken;
    private $teacherToken;
    private $testResults = [];

    public function __construct()
    {
        $this->pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function runAllTests()
    {
        echo "=== COMPREHENSIVE SYSTEM TEST ===\n\n";
        
        // Database Tests
        $this->testDatabaseTables();
        $this->testDatabaseColumns();
        
        // Model Tests
        $this->testModels();
        
        // Controller Tests (if backend is running)
        if ($this->isBackendRunning()) {
            $this->testAPIEndpoints();
        } else {
            echo "⚠️  Backend not running. Skipping API tests.\n";
            echo "   Start backend with: php -S localhost:8080 -t public\n\n";
        }
        
        // Display Results
        $this->displayResults();
    }

    private function test($name, $callback)
    {
        try {
            $callback();
            $this->testResults[$name] = ['status' => 'PASS', 'message' => ''];
            echo "✓ $name\n";
            return true;
        } catch (Exception $e) {
            $this->testResults[$name] = ['status' => 'FAIL', 'message' => $e->getMessage()];
            echo "✗ $name: {$e->getMessage()}\n";
            return false;
        }
    }

    private function testDatabaseTables()
    {
        echo "Testing Database Tables...\n";
        
        $requiredTables = [
            'parents',
            'parent_student_relations',
            'parent_communications',
            'communication_responses',
            'parent_notifications',
            'medical_staff',
            'medical_records',
            'medical_documents',
            'houses',
            'house_blocks',
            'house_masters',
            'student_suspensions',
            'parent_verification_tokens',
            'house_registration_logs'
        ];

        foreach ($requiredTables as $table) {
            $this->test("Table: $table", function() use ($table) {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if (!$stmt->fetch()) {
                    throw new Exception("Table does not exist");
                }
            });
        }
        echo "\n";
    }

    private function testDatabaseColumns()
    {
        echo "Testing New Columns in Existing Tables...\n";
        
        // Test students table columns
        $studentColumns = [
            'house_id', 'house_block_id', 'is_registered', 'registered_at',
            'suspension_status', 'has_medical_condition', 'blood_group'
        ];

        foreach ($studentColumns as $column) {
            $this->test("students.$column", function() use ($column) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM students LIKE '$column'");
                if (!$stmt->fetch()) {
                    throw new Exception("Column does not exist");
                }
            });
        }

        // Test attendance table columns
        $this->test("attendance.parent_notified", function() {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM attendance LIKE 'parent_notified'");
            if (!$stmt->fetch()) {
                throw new Exception("Column does not exist");
            }
        });

        // Test fees_payments table columns
        $this->test("fees_payments.is_tuition_fee", function() {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM fees_payments LIKE 'is_tuition_fee'");
            if (!$stmt->fetch()) {
                throw new Exception("Column does not exist");
            }
        });

        echo "\n";
    }

    private function testModels()
    {
        echo "Testing Model Classes...\n";
        
        $models = [
            'App\\Models\\ParentUser',
            'App\\Models\\MedicalStaff',
            'App\\Models\\MedicalRecord',
            'App\\Models\\House',
            'App\\Models\\ParentNotification'
        ];

        foreach ($models as $model) {
            $className = basename(str_replace('\\', '/', $model));
            $this->test("Model: $className", function() use ($model) {
                if (!class_exists($model)) {
                    throw new Exception("Class does not exist");
                }
            });
        }
        echo "\n";
    }

    private function isBackendRunning()
    {
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }

    private function testAPIEndpoints()
    {
        echo "Testing API Endpoints...\n";
        
        // Test health endpoint
        $this->test("GET /api/health", function() {
            $response = $this->makeRequest('GET', '/health');
            if (!isset($response['success']) || !$response['success']) {
                throw new Exception("Health check failed");
            }
        });

        // Test parent registration endpoint
        $this->test("POST /api/parents/register", function() {
            $response = $this->makeRequest('POST', '/parents/register', [
                'name' => 'Test Parent',
                'email' => 'test_parent_' . time() . '@test.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'admin_id' => 1
            ]);
            if (!isset($response['success'])) {
                throw new Exception("Invalid response format");
            }
        });

        // Test houses endpoint
        $this->test("GET /api/houses (requires auth)", function() {
            // This would require a valid token
            // Just testing that the endpoint exists
            $ch = curl_init($this->baseUrl . '/houses');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Should return 401 (unauthorized) not 404 (not found)
            if ($httpCode === 404) {
                throw new Exception("Endpoint not found");
            }
        });

        echo "\n";
    }

    private function makeRequest($method, $endpoint, $data = null)
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    private function displayResults()
    {
        echo "\n=== TEST SUMMARY ===\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $name => $result) {
            if ($result['status'] === 'PASS') {
                $passed++;
            } else {
                $failed++;
                echo "Failed: $name - {$result['message']}\n";
            }
        }
        
        echo "\nTotal Tests: " . count($this->testResults) . "\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ($failed === 0) {
            echo "\n✓✓✓ ALL TESTS PASSED! ✓✓✓\n";
            echo "System is ready for use.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please review the errors above.\n";
        }
        
        echo "\n===================\n";
    }
}

// Run tests
try {
    $tester = new SystemTester();
    $tester->runAllTests();
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
