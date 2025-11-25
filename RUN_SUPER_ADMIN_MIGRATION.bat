@echo off
echo Running Super Admin Migration...
echo.

cd /d "%~dp0backend1"

php -r "require 'src/Config/database.php'; $config = require 'src/Config/database.php'; $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=utf8mb4'; try { $pdo = new PDO($dsn, $config['username'], $config['password']); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); echo 'Running super admin migration...' . PHP_EOL; $sql = file_get_contents('database/migrations/add_super_admin_role.sql'); $statements = array_filter(array_map('trim', explode(';', $sql))); foreach ($statements as $statement) { if (!empty($statement)) { try { $pdo->exec($statement); echo chr(10003) . ' Executed: ' . substr($statement, 0, 60) . '...' . PHP_EOL; } catch (Exception $e) { if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) { throw $e; } echo chr(9888) . ' Skipped (already exists): ' . substr($statement, 0, 60) . '...' . PHP_EOL; } } } echo PHP_EOL . chr(9989) . ' Migration completed successfully!' . PHP_EOL; } catch (Exception $e) { echo chr(10060) . ' Error: ' . $e->getMessage() . PHP_EOL; exit(1); }"

echo.
echo Migration complete!
echo.
pause
