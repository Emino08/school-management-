<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Creating terms for existing academic years...\n";

    // Get all academic years without terms
    $stmt = $db->query("
        SELECT ay.id, ay.admin_id, ay.year_name, ay.start_date, ay.end_date,
               ay.number_of_terms, ay.exams_per_term
        FROM academic_years ay
        WHERE ay.number_of_terms IS NOT NULL
    ");
    $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($academicYears)) {
        echo "  - No academic years found\n";
        echo "Done.\n";
        exit(0);
    }

    echo "  Found " . count($academicYears) . " academic year(s)\n\n";

    foreach ($academicYears as $yearData) {
        $academicYearId = $yearData['id'];

        echo "Processing Academic Year: {$yearData['year_name']} (ID: {$academicYearId})\n";
        echo "  Start: {$yearData['start_date']}, End: {$yearData['end_date']}\n";
        echo "  Terms: {$yearData['number_of_terms']}, Exams per term: {$yearData['exams_per_term']}\n";

        // Check if terms already exist
        $stmt = $db->prepare("SELECT COUNT(*) as term_count FROM terms WHERE academic_year_id = :id");
        $stmt->execute([':id' => $academicYearId]);
        $termCount = $stmt->fetch(PDO::FETCH_ASSOC)['term_count'];

        if ($termCount > 0) {
            echo "  ✓ Already has {$termCount} term(s), skipping...\n\n";
            continue;
        }

        // Create terms and exams
        $totalTerms = $yearData['number_of_terms'];
        $examsPerTerm = $yearData['exams_per_term'];
        $startDate = new DateTime($yearData['start_date']);
        $endDate = new DateTime($yearData['end_date']);

        // Calculate days per term
        $totalDays = $startDate->diff($endDate)->days;
        $daysPerTerm = floor($totalDays / $totalTerms);

        echo "  Creating {$totalTerms} term(s)...\n";

        for ($i = 1; $i <= $totalTerms; $i++) {
            $termStart = clone $startDate;
            if ($i > 1) {
                $termStart->add(new DateInterval('P' . ($daysPerTerm * ($i - 1)) . 'D'));
            }

            $termEnd = clone $termStart;
            if ($i < $totalTerms) {
                $termEnd->add(new DateInterval('P' . ($daysPerTerm - 1) . 'D'));
            } else {
                $termEnd = clone $endDate;
            }

            // Insert term
            $stmt = $db->prepare("
                INSERT INTO terms (academic_year_id, term_number, term_name, start_date, end_date,
                                  is_current, exams_required, exams_published)
                VALUES (:year_id, :term_number, :term_name, :start_date, :end_date, :is_current, :exams_required, 0)
            ");

            $stmt->execute([
                ':year_id' => $academicYearId,
                ':term_number' => $i,
                ':term_name' => 'Term ' . $i,
                ':start_date' => $termStart->format('Y-m-d'),
                ':end_date' => $termEnd->format('Y-m-d'),
                ':is_current' => ($i === 1) ? 1 : 0,
                ':exams_required' => $examsPerTerm
            ]);

            $termId = $db->lastInsertId();
            echo "    ✓ Created Term {$i} (ID: {$termId}): {$termStart->format('Y-m-d')} to {$termEnd->format('Y-m-d')}\n";

            // Auto-create exams for this term
            $daysInTerm = $termStart->diff($termEnd)->days;

            if ($examsPerTerm == 1) {
                // Single exam
                createTermExam($db, $academicYearId, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
                echo "      ✓ Created exam: Term {$i} - Final\n";
            } elseif ($examsPerTerm == 2) {
                // Two exams: Test (mid-term) and Final (end-term)
                $midTermDate = clone $termStart;
                $midTermDate->add(new DateInterval('P' . floor($daysInTerm / 2) . 'D'));

                createTermExam($db, $academicYearId, $termId, $i, 'test', $midTermDate, $yearData['admin_id']);
                echo "      ✓ Created exam: Term {$i} - Test\n";

                createTermExam($db, $academicYearId, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
                echo "      ✓ Created exam: Term {$i} - Final\n";
            }
        }

        // Update academic year with term info
        $stmt = $db->prepare("
            UPDATE academic_years
            SET current_term = 1, total_terms = :total_terms
            WHERE id = :id
        ");
        $stmt->execute([
            ':total_terms' => $totalTerms,
            ':id' => $academicYearId
        ]);

        echo "  ✓ Completed academic year setup\n\n";
    }

    echo "✓ Successfully created terms and exams for all academic years\n";
    echo "Done.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

function createTermExam($db, $academicYearId, $termId, $termNumber, $examType, $examDate, $adminId)
{
    $stmt = $db->prepare("
        INSERT INTO exams (admin_id, academic_year_id, term_id, exam_name, exam_type, exam_date, is_published, created_at)
        VALUES (:admin_id, :academic_year_id, :term_id, :exam_name, :exam_type, :exam_date, 0, NOW())
    ");

    // Format exam name nicely
    $examTypeDisplay = ucfirst($examType);
    $examName = "Term {$termNumber} - {$examTypeDisplay}";

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
