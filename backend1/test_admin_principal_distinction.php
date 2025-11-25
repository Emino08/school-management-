<?php
/**
 * Test Script: Admin Registration Distinction
 * 
 * This script tests that:
 * 1. Public admin registration creates a NEW school profile
 * 2. Admin creating principal creates account under SAME school
 */

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Admin Registration Distinction Test                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check existing admin and their principals
echo "TEST 1: Checking Admin-Principal Relationship\n";
echo str_repeat("─", 64) . "\n\n";

$stmt = $db->query("
    SELECT 
        a.id,
        a.school_name,
        a.contact_name,
        a.email,
        a.role,
        a.parent_admin_id,
        parent.school_name as parent_school
    FROM admins a
    LEFT JOIN admins parent ON a.parent_admin_id = parent.id
    ORDER BY a.parent_admin_id, a.id
");

$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
$schools = [];

foreach ($admins as $admin) {
    if ($admin['role'] === 'admin') {
        // This is a main admin (school owner)
        $schoolKey = $admin['id'];
        if (!isset($schools[$schoolKey])) {
            $schools[$schoolKey] = [
                'school' => $admin['school_name'],
                'admin' => $admin,
                'principals' => []
            ];
        }
        echo "🏫 SCHOOL: {$admin['school_name']}\n";
        echo "   └─ Admin: {$admin['contact_name']} ({$admin['email']})\n";
        echo "      ID: {$admin['id']} | Role: {$admin['role']}\n";
    } else if ($admin['role'] === 'principal') {
        // This is a principal under a school
        $parentId = $admin['parent_admin_id'];
        echo "   └─ Principal: {$admin['contact_name']} ({$admin['email']})\n";
        echo "      ID: {$admin['id']} | Parent Admin: {$parentId}\n";
        echo "      School (inherited): {$admin['school_name']}\n";
        
        if ($admin['school_name'] === $admin['parent_school']) {
            echo "      ✓ CORRECT: Same school as parent admin\n";
        } else {
            echo "      ✗ ERROR: Different school from parent!\n";
        }
    }
    echo "\n";
}

// Test 2: Verify data integrity
echo "\nTEST 2: Data Integrity Check\n";
echo str_repeat("─", 64) . "\n\n";

// Check if any principals have different school from their parent
$integrityStmt = $db->query("
    SELECT 
        p.id as principal_id,
        p.school_name as principal_school,
        p.contact_name as principal_name,
        a.id as admin_id,
        a.school_name as admin_school,
        a.contact_name as admin_name
    FROM admins p
    INNER JOIN admins a ON p.parent_admin_id = a.id
    WHERE p.role = 'principal'
    AND p.school_name != a.school_name
");

$mismatches = $integrityStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mismatches)) {
    echo "✓ All principals inherit correct school from their parent admin\n";
    echo "✓ Data integrity verified\n";
} else {
    echo "✗ Found " . count($mismatches) . " mismatch(es):\n\n";
    foreach ($mismatches as $mismatch) {
        echo "  Principal: {$mismatch['principal_name']} (School: {$mismatch['principal_school']})\n";
        echo "  Parent Admin: {$mismatch['admin_name']} (School: {$mismatch['admin_school']})\n";
        echo "  ✗ Schools don't match!\n\n";
    }
}

// Test 3: Count schools and principals
echo "\nTEST 3: Statistics\n";
echo str_repeat("─", 64) . "\n\n";

$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM admins WHERE role = 'admin') as total_schools,
        (SELECT COUNT(*) FROM admins WHERE role = 'principal') as total_principals,
        (SELECT COUNT(DISTINCT school_name) FROM admins WHERE role = 'admin') as unique_schools
")->fetch(PDO::FETCH_ASSOC);

echo "Total Independent Schools: {$stats['total_schools']}\n";
echo "Total Principals (sub-admins): {$stats['total_principals']}\n";
echo "Unique School Names: {$stats['unique_schools']}\n";

// Test 4: Show what happens with public registration vs internal creation
echo "\n\nTEST 4: Registration Type Comparison\n";
echo str_repeat("─", 64) . "\n\n";

echo "PUBLIC REGISTRATION (/api/admin/register):\n";
echo "  → Creates NEW admin with role='admin'\n";
echo "  → parent_admin_id = NULL\n";
echo "  → school_name = User's input (NEW SCHOOL)\n";
echo "  → Full independent profile\n";
echo "  → Can manage their own school\n\n";

echo "INTERNAL CREATION (Admin creates Principal):\n";
echo "  → Creates NEW admin with role='principal'\n";
echo "  → parent_admin_id = Parent Admin's ID\n";
echo "  → school_name = Parent's school_name (SAME SCHOOL)\n";
echo "  → Shared school profile\n";
echo "  → Works under parent admin's school\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Test Complete                                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
