<?php
require __DIR__ . "/vendor/autoload.php";
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$m = new App\Models\Admin();
try {
  $res = $m->getAdminsBySchool(1);
  var_dump($res);
} catch (Throwable $e) {
  echo 'ERR: ' . $e->getMessage() . "\n";
}
?>
