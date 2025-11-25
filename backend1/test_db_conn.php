<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=4306;dbname=school_management', 'root', '1212');
    echo "Connection successful\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
