<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = Database::getInstance()->getConnection();
    echo "Fixing incomplete terms for academic year ID 1...\n";

    // Delete existing terms and exams for academic year 1
    echo "  Deleting existing terms and exams...\n";
    $db->exec("DELETE FROM exams WHERE academic_year_id = 1");
    $db->exec("DELETE FROM terms WHERE academic_year_id = 1");
    echo "  ✓ Deleted old terms and exams\n";

    // Get academic year data
    $stmt = $db->prepare("SELECT * FROM academic_years WHERE id = 1");
    $stmt->execute();
    $yearData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$yearData) {
        echo "  ! Academic year 1 not found\n";
        exit(1);
    }

    echo "  Creating terms for: {$yearData['year_name']}\n";

    $totalTerms = $yearData['number_of_terms'];
    $examsPerTerm = $yearData['exams_per_term'];
    $startDate = new DateTime($yearData['start_date']);
    $endDate = new DateTime($yearData['end_date']);

    // Calculate days per term
    $totalDays = $startDate->diff($endDate)->days;
    $daysPerTerm = floor($totalDays / $totalTerms);

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
            ':year_id' => 1,
            ':term_number' => $i,
            ':term_name' => 'Term ' . $i,
            ':start_date' => $termStart->format('Y-m-d'),
            ':end_date' => $termEnd->format('Y-m-d'),
            ':is_current' => ($i === 1) ? 1 : 0,
            ':exams_required' => $examsPerTerm
        ]);

        $termId = $db->lastInsertId();
        echo "    ✓ Created Term {$i} (ID: {$termId})\n";

        // Create exams
        if ($examsPerTerm == 1) {
            createExam($db, 1, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
            echo "      ✓ Created exam: Term {$i} - Final\n";
        } elseif ($examsPerTerm == 2) {
            $daysInTerm = $termStart->diff($termEnd)->days;
            $midTermDate = clone $termStart;
            $midTermDate->add(new DateInterval('P' . floor($daysInTerm / 2) . 'D'));

            createExam($db, 1, $termId, $i, 'test', $midTermDate, $yearData['admin_id']);
            echo "      ✓ Created exam: Term {$i} - Test\n";

            createExam($db, 1, $termId, $i, 'final', $termEnd, $yearData['admin_id']);
            echo "      ✓ Created exam: Term {$i} - Final\n";
        }
    }

    echo "\n✓ Successfully fixed academic year 1\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

function createExam($db, $academicYearId, $termId, $termNumber, $examType, $examDate, $adminId)
{
    $stmt = $db->prepare("
        INSERT INTO exams (admin_id, academic_year_id, term_id, exam_name, exam_type, exam_date, is_published, created_at)
        VALUES (:admin_id, :academic_year_id, :term_id, :exam_name, :exam_type, :exam_date, 0, NOW())
    ");

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
