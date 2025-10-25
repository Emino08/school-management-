<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Creating exams for existing academic years...\n";

    // Get all academic years with their terms
    $stmt = $db->query("
        SELECT ay.id as academic_year_id, ay.admin_id, ay.exams_per_term, ay.number_of_terms
        FROM academic_years ay
        WHERE ay.exams_per_term IS NOT NULL
        AND ay.number_of_terms IS NOT NULL
    ");
    $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($academicYears)) {
        echo "  - No academic years found\n";
        echo "Done.\n";
        exit(0);
    }

    echo "  Found " . count($academicYears) . " academic year(s)\n";

    foreach ($academicYears as $academicYear) {
        $academicYearId = $academicYear['academic_year_id'];
        $adminId = $academicYear['admin_id'];
        $examsPerTerm = $academicYear['exams_per_term'];
        $totalTerms = $academicYear['number_of_terms'];

        echo "\n  Processing Academic Year ID: {$academicYearId}\n";
        echo "    - Terms: {$totalTerms}, Exams per term: {$examsPerTerm}\n";

        // Get all terms for this academic year
        $stmt = $db->prepare("
            SELECT id, term_number, start_date, end_date
            FROM terms
            WHERE academic_year_id = :academic_year_id
            ORDER BY term_number
        ");
        $stmt->execute([':academic_year_id' => $academicYearId]);
        $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($terms)) {
            echo "    ! No terms found for this academic year, skipping...\n";
            continue;
        }

        echo "    - Found " . count($terms) . " term(s)\n";

        foreach ($terms as $term) {
            $termId = $term['id'];
            $termNumber = $term['term_number'];
            $termStart = new DateTime($term['start_date']);
            $termEnd = new DateTime($term['end_date']);

            // Check if exams already exist for this term
            $stmt = $db->prepare("SELECT COUNT(*) as exam_count FROM exams WHERE term_id = :term_id");
            $stmt->execute([':term_id' => $termId]);
            $existingCount = $stmt->fetch(PDO::FETCH_ASSOC)['exam_count'];

            if ($existingCount >= $examsPerTerm) {
                echo "      Term {$termNumber}: Already has {$existingCount} exam(s), skipping...\n";
                continue;
            }

            if ($existingCount > 0) {
                echo "      Term {$termNumber}: Has {$existingCount} exam(s), creating " . ($examsPerTerm - $existingCount) . " more...\n";
            }

            // Calculate dates for exams
            $daysInTerm = $termStart->diff($termEnd)->days;

            if ($examsPerTerm == 1) {
                // Single exam at end of term
                if ($existingCount == 0) {
                    createExam($db, $academicYearId, $termId, $adminId, $termNumber, 'Exam', $termEnd);
                    echo "      ✓ Created: Term {$termNumber} - Exam\n";
                }
            } elseif ($examsPerTerm == 2) {
                // Two exams: Test (mid-term) and Exam (end-term)
                $midTermDate = clone $termStart;
                $midTermDate->add(new DateInterval('P' . floor($daysInTerm / 2) . 'D'));

                // Check which exams to create
                $stmt = $db->prepare("SELECT exam_type FROM exams WHERE term_id = :term_id");
                $stmt->execute([':term_id' => $termId]);
                $existingTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!in_array('Test', $existingTypes)) {
                    createExam($db, $academicYearId, $termId, $adminId, $termNumber, 'Test', $midTermDate);
                    echo "      ✓ Created: Term {$termNumber} - Test\n";
                }

                if (!in_array('Exam', $existingTypes)) {
                    createExam($db, $academicYearId, $termId, $adminId, $termNumber, 'Exam', $termEnd);
                    echo "      ✓ Created: Term {$termNumber} - Exam\n";
                }
            }
        }
    }

    echo "\n✓ Successfully created exams for all academic years\n";
    echo "Done.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

function createExam($db, $academicYearId, $termId, $adminId, $termNumber, $examType, $examDate)
{
    $stmt = $db->prepare("
        INSERT INTO exams (admin_id, academic_year_id, term_id, exam_name, exam_type, exam_date, is_published, created_at)
        VALUES (:admin_id, :academic_year_id, :term_id, :exam_name, :exam_type, :exam_date, 0, NOW())
    ");

    $examName = "Term {$termNumber} - {$examType}";

    $stmt->execute([
        ':admin_id' => $adminId,
        ':academic_year_id' => $academicYearId,
        ':term_id' => $termId,
        ':exam_name' => $examName,
        ':exam_type' => $examType,
        ':exam_date' => $examDate->format('Y-m-d')
    ]);

    return $db->lastInsertId();
}
