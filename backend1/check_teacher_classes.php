<?php

require __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = Database::getInstance()->getConnection();

echo "=== Checking Tables ===" . PHP_EOL . PHP_EOL;

// Check notifications table
echo "1. NOTIFICATIONS TABLE:" . PHP_EOL;
$stmt = $db->query("SHOW TABLES LIKE 'notifications'");
if ($stmt->fetch()) {
    echo "   ✓ Table exists" . PHP_EOL;
    $stmt = $db->query("DESCRIBE notifications");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "     - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
    }
} else {
    echo "   ✗ Table does not exist - needs to be created" . PHP_EOL;
}

echo PHP_EOL . "2. TEACHERS TABLE:" . PHP_EOL;
$stmt = $db->query("DESCRIBE teachers");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "   - " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
}

echo PHP_EOL . "3. TEACHER_CLASSES TABLE:" . PHP_EOL;
$stmt = $db->query("SHOW TABLES LIKE 'teacher_classes'");
if ($stmt->fetch()) {
    echo "   ✓ Table exists" . PHP_EOL;
    $stmt = $db->query("DESCRIBE teacher_classes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "     - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
    }
} else {
    echo "   ✗ Table does not exist - checking subjects_teachers..." . PHP_EOL;
    $stmt = $db->query("SHOW TABLES LIKE 'subjects_teachers'");
    if ($stmt->fetch()) {
        echo "   ✓ Found subjects_teachers table" . PHP_EOL;
        $stmt = $db->query("DESCRIBE subjects_teachers");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "     - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "4. Sample Query - Teachers with Classes:" . PHP_EOL;
try {
    $stmt = $db->query("
        SELECT t.id, t.name, t.is_class_master, t.class_master_of, c.class_name
        FROM teachers t
        LEFT JOIN classes c ON t.class_master_of = c.id
        LIMIT 5
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Teacher: " . $row['name'] . " | Class Master: " . ($row['is_class_master'] ? 'Yes' : 'No');
        if ($row['class_name']) {
            echo " | Class: " . $row['class_name'];
        }
        echo PHP_EOL;
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "5. Check for subject assignments:" . PHP_EOL;
try {
    $stmt = $db->query("
        SELECT DISTINCT st.teacher_id, t.name, COUNT(DISTINCT st.subject_id) as subject_count, 
               COUNT(DISTINCT st.class_id) as class_count
        FROM subjects_teachers st
        JOIN teachers t ON st.teacher_id = t.id
        GROUP BY st.teacher_id
        LIMIT 5
    ");
    $found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found = true;
        echo "   Teacher: " . $row['name'] . " | Subjects: " . $row['subject_count'] . " | Classes: " . $row['class_count'] . PHP_EOL;
    }
    if (!$found) {
        echo "   No subject assignments found" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . PHP_EOL;
}
