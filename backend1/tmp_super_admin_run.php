<?php
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
echo "Connected\n";
$sql = file_get_contents(__DIR__ . '/database/migrations/add_super_admin_role.sql');
$stmts = array_filter(array_map('trim', explode(';', $sql)));
foreach ($stmts as $stmt) {
  if ($stmt === '') continue;
  try {
    $db->exec($stmt);
    echo "✓ " . substr($stmt, 0, 80) . "...\n";
  } catch (Throwable $e) {
    if (stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), 'exists') !== false) {
      echo "• skipped " . substr($stmt, 0, 80) . "...\n";
    } else {
      throw $e;
    }
  }
}
echo "Done\n";
?>
