<?php
/**
 * Update Student and Teacher Controllers to Use Root Admin Resolution
 * This ensures principals see their parent admin's data
 */

echo "=== Updating Controllers for Data Inheritance ===\n\n";

$files = [
    'StudentController.php',
    'TeacherController.php',
    'ClassController.php',
    'SubjectController.php',
    'FeesPaymentController.php',
    'AttendanceController.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/src/Controllers/' . $file;
    
    if (!file_exists($path)) {
        echo "⚠ File not found: $file\n";
        continue;
    }
    
    echo "Processing: $file\n";
    
    $content = file_get_contents($path);
    $original = $content;
    $changes = 0;
    
    // Add ResolvesAdminId trait if LogsActivity is present
    if (strpos($content, 'use LogsActivity;') !== false && strpos($content, 'use ResolvesAdminId;') === false) {
        $content = str_replace(
            "use App\Traits\LogsActivity;",
            "use App\Traits\LogsActivity;\nuse App\Traits\ResolvesAdminId;",
            $content
        );
        
        $content = str_replace(
            "    use LogsActivity;",
            "    use LogsActivity;\n    use ResolvesAdminId;",
            $content
        );
        $changes++;
    }
    
    // Replace common patterns
    $patterns = [
        // In methods after $user = $request->getAttribute('user');
        "/(\\\$user = \\\$request->getAttribute\('user'\);)\n([ ]+)(\\\$admin)/m" => "$1\n$2\$adminId = \$this->getAdminId(\$request, \$user);\n$2$3",
        
        // Replace $user->id with $adminId where appropriate for admin_id usage
        "/findByIdNumber\(\\\$user->id,/" => "findByIdNumber(\$adminId,",
        "/'admin_id'[\\s]*=>[\\s]*\\\$user->id/" => "'admin_id' => \$adminId",
        "/->getCurrentYear\(\\\$user->id\)/" => "->getCurrentYear(\$adminId)",
        "/clearDashboardCache\(\\\$user->id\)/" => "clearDashboardCache(\$adminId)",
        "/getTeachersWithSubjects\(\\\$user->id/" => "getTeachersWithSubjects(\$adminId",
        "/':admin_id' => \\\$user->id/" => "':admin_id' => \$adminId",
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $changes++;
        }
    }
    
    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "  ✓ Applied $changes change(s)\n";
    } else {
        echo "  - No changes needed\n";
    }
}

echo "\n=== Controller Updates Complete ===\n";
echo "Note: Some manual review may be needed for complex methods.\n";
