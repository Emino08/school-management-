<?php
/**
 * Test script for Position Ranking System
 * Verifies that tables and columns exist
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "========================================\n";
    echo "Position Ranking System Verification\n";
    echo "========================================\n\n";

    // Check subject_results table
    echo "[1/5] Checking subject_results table...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM subject_results LIKE 'subject_position'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ subject_position column exists\n";
    } else {
        echo "  ✗ subject_position column MISSING\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM subject_results LIKE 'average_score'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ average_score column exists\n";
    } else {
        echo "  ✗ average_score column MISSING\n";
    }

    // Check term_results table
    echo "\n[2/5] Checking term_results table...\n";
    $requiredColumns = ['average_score', 'subject_count', 'class_position', 'class_total_students'];
    foreach ($requiredColumns as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM term_results LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ $col column exists\n";
        } else {
            echo "  ✗ $col column MISSING\n";
        }
    }

    // Check result_summary table
    echo "\n[3/5] Checking result_summary table...\n";
    foreach ($requiredColumns as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM result_summary LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ $col column exists\n";
        } else {
            echo "  ✗ $col column MISSING\n";
        }
    }

    // Check subject_rankings table
    echo "\n[4/5] Checking subject_rankings table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'subject_rankings'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ subject_rankings table exists\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subject_rankings");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ℹ Current records: " . $count['count'] . "\n";
    } else {
        echo "  ✗ subject_rankings table MISSING\n";
    }

    // Check class_rankings table
    echo "\n[5/5] Checking class_rankings table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'class_rankings'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ class_rankings table exists\n";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM class_rankings");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ℹ Current records: " . $count['count'] . "\n";
    } else {
        echo "  ✗ class_rankings table MISSING\n";
    }

    // Check indexes
    echo "\n[Bonus] Checking performance indexes...\n";
    $stmt = $pdo->query("SHOW INDEX FROM subject_results WHERE Key_name = 'idx_subject_position'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ subject_results index exists\n";
    } else {
        echo "  ℹ subject_results index not found (may not be needed)\n";
    }

    $stmt = $pdo->query("SHOW INDEX FROM term_results WHERE Key_name = 'idx_term_average_score'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ term_results index exists\n";
    } else {
        echo "  ℹ term_results index not found (may not be needed)\n";
    }

    // Test average_score calculation
    echo "\n[Test] Testing average_score calculation...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subject_results WHERE average_score IS NOT NULL");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  ℹ Records with calculated average_score: " . $count['count'] . "\n";

    // Check if models exist
    echo "\n[Code] Checking model files...\n";
    if (file_exists(__DIR__ . '/src/Models/SubjectRanking.php')) {
        echo "  ✓ SubjectRanking.php exists\n";
    } else {
        echo "  ✗ SubjectRanking.php MISSING\n";
    }

    if (file_exists(__DIR__ . '/src/Models/ClassRanking.php')) {
        echo "  ✓ ClassRanking.php exists\n";
    } else {
        echo "  ✗ ClassRanking.php MISSING\n";
    }

    if (file_exists(__DIR__ . '/src/Controllers/RankingController.php')) {
        echo "  ✓ RankingController.php exists\n";
    } else {
        echo "  ✗ RankingController.php MISSING\n";
    }

    echo "\n========================================\n";
    echo "✅ Verification complete!\n";
    echo "========================================\n";
    echo "\nNext Steps:\n";
    echo "1. Enter student results for an exam/term\n";
    echo "2. Call: POST /api/rankings/exam/{examId}/calculate\n";
    echo "3. Verify rankings: GET /api/rankings/exam/{examId}/class/{classId}\n";
    echo "4. Publish: POST /api/rankings/exam/{examId}/class/publish\n";
    echo "\nFor detailed guide, see: POSITION_RANKING_AND_AVERAGE_GRADING_GUIDE.md\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
