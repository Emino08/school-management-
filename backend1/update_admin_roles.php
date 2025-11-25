<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Updating role enum in admins table...\n";

    // Update role column to support super_admin, admin, and principal
    $db->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'admin', 'principal') NOT NULL DEFAULT 'admin'");
    echo "✓ Role enum updated successfully\n";

    // Ensure first admin is marked as super_admin
    $stmt = $db->query("SELECT MIN(id) as first_admin_id FROM admins");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['first_admin_id']) {
        $db->exec("UPDATE admins SET role = 'super_admin', is_super_admin = 1 WHERE id = {$result['first_admin_id']}");
        echo "✓ First admin marked as super admin\n";
    }

    // Update role column to include 'super_admin' in enum if needed
    echo "\n✅ All role updates completed successfully!\n";

} catch (\Exception $e) {
    echo "\n❌ Update failed: " . $e->getMessage() . "\n";
    exit(1);
}
