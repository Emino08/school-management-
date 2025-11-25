<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Students Table Structure ===\n\n";
    
    $stmt = $db->query("DESCRIBE students");
    
    printf("%-30s %-20s\n", "Column", "Type");
    echo str_repeat('=', 60) . "\n";
    
    $hasPhoto = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("%-30s %-20s\n", $row['Field'], $row['Type']);
        if ($row['Field'] === 'photo') {
            $hasPhoto = true;
        }
    }
    
    echo "\n";
    if ($hasPhoto) {
        echo "✅ 'photo' column EXISTS\n";
    } else {
        echo "❌ 'photo' column DOES NOT EXIST\n";
        echo "   Need to add it!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
