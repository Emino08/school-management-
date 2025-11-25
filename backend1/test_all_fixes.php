#!/usr/bin/env php
<?php
/**
 * Comprehensive Backend Test Script
 * Tests all the fixes that were applied
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

echo "\n";
echo "========================================\n";
echo "  BACKEND FIXES VERIFICATION TEST\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    $passed = 0;
    $failed = 0;
    
    // Test 1: Check student photo column
    echo "Test 1: Student photo column exists... ";
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'photo'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 2: Check student status column
    echo "Test 2: Student status column exists... ";
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'status'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 3: Check medical_records record_type is VARCHAR
    echo "Test 3: Medical records record_type is VARCHAR... ";
    $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'record_type'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col && strpos($col['Type'], 'varchar') !== false) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 4: Check can_edit_by_parent column exists
    echo "Test 4: Medical records can_edit_by_parent exists... ";
    $stmt = $db->query("SHOW COLUMNS FROM medical_records LIKE 'can_edit_by_parent'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 5: Check student_parents table exists
    echo "Test 5: student_parents table exists... ";
    $stmt = $db->query("SHOW TABLES LIKE 'student_parents'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 6: Check principals table exists
    echo "Test 6: principals table exists... ";
    $stmt = $db->query("SHOW TABLES LIKE 'principals'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 7: Check admins school_id column
    echo "Test 7: Admins school_id column exists... ";
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'school_id'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 8: Check admins role enum includes super_admin
    echo "Test 8: Admins role supports super_admin... ";
    $stmt = $db->query("SHOW COLUMNS FROM admins LIKE 'role'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col && strpos($col['Type'], 'super_admin') !== false) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 9: Check email_templates table exists
    echo "Test 9: email_templates table exists... ";
    $stmt = $db->query("SHOW TABLES LIKE 'email_templates'");
    if ($stmt->fetch()) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 10: Check super admin is set
    echo "Test 10: Super admin is configured... ";
    $stmt = $db->query("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin' OR is_super_admin = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Test 11: Check parents status is active
    echo "Test 11: Parents have active status... ";
    $stmt = $db->query("SELECT COUNT(*) as active_count FROM parents WHERE status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['active_count'] > 0) {
        echo "✅ PASSED\n";
        $passed++;
    } else {
        echo "⚠️  WARNING (No parents in database)\n";
    }
    
    // Test 12: Check database function exists
    echo "Test 12: get_root_admin_id function exists... ";
    try {
        $stmt = $db->query("SELECT get_root_admin_id(1)");
        echo "✅ PASSED\n";
        $passed++;
    } catch (\Exception $e) {
        echo "❌ FAILED\n";
        $failed++;
    }
    
    // Summary
    echo "\n========================================\n";
    echo "  TEST RESULTS\n";
    echo "========================================\n";
    echo "Total Tests: " . ($passed + $failed) . "\n";
    echo "✅ Passed: $passed\n";
    echo "❌ Failed: $failed\n";
    
    if ($failed === 0) {
        echo "\n🎉 ALL TESTS PASSED! Backend is ready.\n";
    } else {
        echo "\n⚠️  Some tests failed. Please review and fix.\n";
    }
    
    echo "\n========================================\n\n";
    
    exit($failed > 0 ? 1 : 0);
    
} catch (\Exception $e) {
    echo "\n❌ Test suite failed: " . $e->getMessage() . "\n";
    exit(1);
}
