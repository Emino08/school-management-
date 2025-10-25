<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creating exams based on academic year configuration...\n\n";

    // Get all academic years with their exam configuration
    $stmt = $pdo->query("
        SELECT id, year_name, number_of_terms, exams_per_term, admin_id
        FROM academic_years
        WHERE exams_per_term IS NOT NULL AND exams_per_term > 0
        ORDER BY id
    ");
    $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($academicYears)) {
        echo "No academic years found with exam configuration.\n";
        exit(0);
    }

    foreach ($academicYears as $year) {
        echo "Processing Academic Year: {$year['year_name']}\n";
        echo "  - Terms: {$year['number_of_terms']}, Exams per term: {$year['exams_per_term']}\n";

        // Get all terms for this academic year
        $stmt = $pdo->prepare("
            SELECT id, term_name, term_number, start_date, end_date
            FROM terms
            WHERE academic_year_id = ?
            ORDER BY term_number
        ");
        $stmt->execute([$year['id']]);
        $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($terms)) {
            echo "  ⚠ No terms found for this academic year. Skipping.\n\n";
            continue;
        }

        $examsPerTerm = (int)$year['exams_per_term'];
        $adminId = $year['admin_id'] ?? 1;

        // Define exam types and names based on exams_per_term
        $examConfigs = [];

        if ($examsPerTerm == 1) {
            $examConfigs = [
                ['name' => 'Final', 'type' => 'final', 'position' => 1.0]
            ];
        } elseif ($examsPerTerm == 2) {
            $examConfigs = [
                ['name' => 'Midterm', 'type' => 'midterm', 'position' => 0.5],
                ['name' => 'Final', 'type' => 'final', 'position' => 1.0]
            ];
        } elseif ($examsPerTerm == 3) {
            $examConfigs = [
                ['name' => 'Test', 'type' => 'test', 'position' => 0.33],
                ['name' => 'Midterm', 'type' => 'midterm', 'position' => 0.66],
                ['name' => 'Final', 'type' => 'final', 'position' => 1.0]
            ];
        } elseif ($examsPerTerm >= 4) {
            $examConfigs = [
                ['name' => 'Test 1', 'type' => 'test', 'position' => 0.25],
                ['name' => 'Test 2', 'type' => 'test', 'position' => 0.50],
                ['name' => 'Midterm', 'type' => 'midterm', 'position' => 0.75],
                ['name' => 'Final', 'type' => 'final', 'position' => 1.0]
            ];
        }

        // Create exams for each term
        foreach ($terms as $term) {
            echo "  Processing {$term['term_name']}...\n";

            // Calculate term duration
            $termStart = new DateTime($term['start_date']);
            $termEnd = new DateTime($term['end_date']);
            $termDuration = $termStart->diff($termEnd)->days;

            $examNumber = 1;
            foreach ($examConfigs as $config) {
                $examName = "{$term['term_name']} - {$config['name']}";

                // Calculate exam date based on position in term
                $daysIntoTerm = (int)($termDuration * $config['position']);
                $examDate = clone $termStart;
                $examDate->modify("+{$daysIntoTerm} days");

                // Check if this exam already exists
                $checkStmt = $pdo->prepare("
                    SELECT id FROM exams
                    WHERE term_id = ?
                    AND exam_type = ?
                    AND exam_number = ?
                ");
                $checkStmt->execute([$term['id'], $config['type'], $examNumber]);
                $exists = $checkStmt->fetch();

                if ($exists) {
                    echo "    - {$examName} (already exists, skipping)\n";
                } else {
                    // Insert the exam
                    $insertStmt = $pdo->prepare("
                        INSERT INTO exams (
                            admin_id, academic_year_id, term_id, exam_name, exam_type,
                            exam_date, exam_number, total_marks, passing_marks,
                            is_published, can_be_published,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 100, 40, 0, 0, NOW(), NOW())
                    ");

                    $insertStmt->execute([
                        $adminId,
                        $year['id'],
                        $term['id'],
                        $examName,
                        $config['type'],
                        $examDate->format('Y-m-d'),
                        $examNumber
                    ]);

                    echo "    ✓ Created: {$examName} (Date: {$examDate->format('Y-m-d')})\n";
                }

                $examNumber++;
            }
        }

        echo "  ✓ Completed {$year['year_name']}\n\n";
    }

    // Display summary
    echo "\n=== SUMMARY ===\n";
    $stmt = $pdo->query("
        SELECT ay.year_name, COUNT(e.id) as exam_count, ay.number_of_terms, ay.exams_per_term,
               (ay.number_of_terms * ay.exams_per_term) as expected_exams
        FROM academic_years ay
        LEFT JOIN exams e ON ay.id = e.academic_year_id
        GROUP BY ay.id, ay.year_name, ay.number_of_terms, ay.exams_per_term
        ORDER BY ay.id
    ");
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($summary as $row) {
        $expected = $row['expected_exams'] ?? 0;
        $actual = $row['exam_count'] ?? 0;
        $status = ($actual >= $expected) ? '✓' : '⚠';

        echo sprintf(
            "%s %s: %d/%d exams (%d terms × %d exams/term)\n",
            $status,
            $row['year_name'],
            $actual,
            $expected,
            $row['number_of_terms'] ?? 0,
            $row['exams_per_term'] ?? 0
        );
    }

    echo "\n✅ Exam generation completed!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
