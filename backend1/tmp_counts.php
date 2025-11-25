<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
$adminId = 1;
$tables = [
    'students', 'teachers', 'classes', 'subjects', 'finance_users', 'exam_officers', 'medical_staff', 'parents'
];
foreach ($tables as $table) {
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM {$table} WHERE admin_id = :admin");
    $stmt->execute([':admin' => $adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $table . ': ' . ($row['c'] ?? '0') . "\n";
}
// principals
$pri = $db->prepare("SELECT COUNT(*) AS c FROM admins WHERE parent_admin_id = :admin AND role = 'principal'");
$pri->execute([':admin' => $adminId]);
echo 'principals: ' . ($pri->fetch(PDO::FETCH_ASSOC)['c'] ?? 0) . "\n";
