<?php
/**
 * Add Activity Logging to All Controllers
 * 
 * This script adds logging calls to key operations in all controllers
 */

$loggingCalls = [
    // AdminController - Login
    'AdminController.php:login' => [
        'search' => "        \$response->getBody()->write(json_encode([\n            'success' => true,\n            'message' => 'Login successful',\n            'role' => \$roleLabel,",
        'insert_before' => "        // Log login activity\n        \$this->logActivity(\n            \$request,\n            'login',\n            \"Admin logged in: {\$accountRecord['email']}\",\n            'admin',\n            \$accountRecord['id'],\n            ['role' => \$roleLabel]\n        );\n\n"
    ],
    
    // AdminController - Register
    'AdminController.php:register' => [
        'search' => "            \$response->getBody()->write(json_encode([\n                'success' => true,\n                'message' => 'Admin registered successfully',",
        'insert_before' => "            // Log registration\n            \$this->logActivity(\n                \$request,\n                'create',\n                \"New admin registered: {\$data['email']}\",\n                'admin',\n                \$adminAccount['id'],\n                ['school_name' => \$adminAccount['school_name']]\n            );\n\n"
    ],
    
    // SubjectController - Create
    'SubjectController.php:create' => [
        'search' => "            \$response->getBody()->write(json_encode([\n                'success' => true,\n                'message' => 'Subject created successfully',",
        'insert_before' => "            // Log subject creation\n            \$this->logActivity(\n                \$request,\n                'create',\n                \"Created subject: {\$data['subject_name']}\",\n                'subject',\n                \$subjectId\n            );\n\n"
    ],
    
    // AttendanceController - Mark
    'AttendanceController.php:mark' => [
        'search' => "        \$response->getBody()->write(json_encode([\n            'success' => true,\n            'message' => 'Attendance marked successfully'",
        'insert_before' => "        // Log attendance marking\n        \$this->logActivity(\n            \$request,\n            'mark',\n            \"Marked attendance for class\",\n            'attendance',\n            null,\n            ['count' => count(\$data['attendance'] ?? [])]\n        );\n\n"
    ],
    
    // ResultController - Publish
    'ResultController.php:publish' => [
        'search' => "            \$response->getBody()->write(json_encode([\n                'success' => true,\n                'message' => 'Results published successfully'",
        'insert_before' => "            // Log result publishing\n            \$this->logActivity(\n                \$request,\n                'publish',\n                \"Published results\",\n                'result',\n                null\n            );\n\n"
    ],
    
    // NoticeController - Create
    'NoticeController.php:create' => [
        'search' => "            \$response->getBody()->write(json_encode([\n                'success' => true,\n                'message' => 'Notice created successfully',",
        'insert_before' => "            // Log notice creation\n            \$this->logActivity(\n                \$request,\n                'create',\n                \"Created notice: {\$data['title']}\",\n                'notice',\n                \$noticeId\n            );\n\n"
    ],
    
    // FeesPaymentController - Record Payment
    'FeesPaymentController.php:create' => [
        'search' => "            \$response->getBody()->write(json_encode([\n                'success' => true,\n                'message' => 'Payment recorded successfully',",
        'insert_before' => "            // Log payment\n            \$this->logActivity(\n                \$request,\n                'payment',\n                \"Payment recorded: {\$data['amount']}\",\n                'payment',\n                \$paymentId,\n                ['amount' => \$data['amount']]\n            );\n\n"
    ],
    
    // SettingsController - Update
    'SettingsController.php:update' => [
        'search' => "        \$response->getBody()->write(json_encode([\n            'success' => true,\n            'message' => 'Settings updated successfully'",
        'insert_before' => "        // Log settings update\n        \$this->logActivity(\n            \$request,\n            'update',\n            \"Updated system settings\",\n            'settings',\n            null,\n            ['category' => \$data['category'] ?? 'general']\n        );\n\n"
    ],
];

echo "Adding activity logging calls to controllers...\n";
echo str_repeat("=", 60) . "\n\n";

$basePath = __DIR__;
$added = 0;
$skipped = 0;

foreach ($loggingCalls as $location => $config) {
    list($file, $method) = explode(':', $location);
    $filePath = "$basePath/src/Controllers/$file";
    
    if (!file_exists($filePath)) {
        echo "[-] $file not found\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Check if already has the logging call
    if (strpos($content, $config['insert_before']) !== false) {
        echo "[=] $file::$method already has logging\n";
        $skipped++;
        continue;
    }
    
    // Find and insert before the search pattern
    $searchPattern = str_replace(["\n", "        "], ["\n", "        "], $config['search']);
    if (strpos($content, $config['search']) !== false) {
        $newContent = str_replace(
            $config['search'],
            $config['insert_before'] . $config['search'],
            $content
        );
        
        file_put_contents($filePath, $newContent);
        echo "[+] Added logging to $file::$method\n";
        $added++;
    } else {
        echo "[?] Could not find insertion point in $file::$method\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✓ Added: $added\n";
echo "= Skipped: $skipped\n";
echo "\nDone!\n";
