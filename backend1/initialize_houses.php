<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Initialize Houses for School Management System\n";
    echo "==============================================\n\n";

    // Get admin ID
    echo "Enter Admin ID: ";
    $adminId = trim(fgets(STDIN));

    if (!is_numeric($adminId)) {
        die("Invalid admin ID\n");
    }

    // Verify admin exists
    $stmt = $pdo->prepare("SELECT school_name FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        die("Admin not found\n");
    }

    echo "School: {$admin['school_name']}\n\n";

    // Default houses
    $defaultHouses = [
        ['name' => 'Red House', 'color' => '#DC143C', 'motto' => 'Strength and Courage'],
        ['name' => 'Blue House', 'color' => '#1E90FF', 'motto' => 'Wisdom and Truth'],
        ['name' => 'Green House', 'color' => '#228B22', 'motto' => 'Growth and Unity'],
        ['name' => 'Yellow House', 'color' => '#FFD700', 'motto' => 'Excellence and Pride'],
        ['name' => 'Purple House', 'color' => '#8B008B', 'motto' => 'Honor and Dignity'],
        ['name' => 'Orange House', 'color' => '#FF8C00', 'motto' => 'Energy and Spirit']
    ];

    echo "Creating 6 default houses...\n\n";

    $pdo->beginTransaction();

    try {
        foreach ($defaultHouses as $house) {
            // Create house
            $sql = "INSERT INTO houses (admin_id, house_name, house_color, house_motto, points)
                    VALUES (?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adminId, $house['name'], $house['color'], $house['motto']]);
            $houseId = $pdo->lastInsertId();

            echo "✓ Created {$house['name']}\n";

            // Create blocks A-F for this house
            $blocks = ['A', 'B', 'C', 'D', 'E', 'F'];
            foreach ($blocks as $block) {
                $blockSql = "INSERT INTO house_blocks (house_id, admin_id, block_name, capacity, current_occupancy)
                            VALUES (?, ?, ?, 50, 0)";
                $blockStmt = $pdo->prepare($blockSql);
                $blockStmt->execute([$houseId, $adminId, $block]);
            }
            echo "  Created blocks: A, B, C, D, E, F\n\n";
        }

        $pdo->commit();
        
        echo "\n==============================================\n";
        echo "✓ Successfully created 6 houses with blocks!\n";
        echo "==============================================\n\n";

        // Show summary
        $stmt = $pdo->prepare("
            SELECT h.house_name, h.house_color, h.house_motto,
                   COUNT(hb.id) as block_count
            FROM houses h
            LEFT JOIN house_blocks hb ON h.id = hb.house_id
            WHERE h.admin_id = ?
            GROUP BY h.id
            ORDER BY h.house_name
        ");
        $stmt->execute([$adminId]);
        $houses = $stmt->fetchAll();

        echo "House Summary:\n";
        echo "--------------\n";
        foreach ($houses as $h) {
            echo "• {$h['house_name']} - {$h['block_count']} blocks\n";
            echo "  Motto: {$h['house_motto']}\n";
            echo "  Color: {$h['house_color']}\n\n";
        }

        echo "\nNext Steps:\n";
        echo "1. Assign teachers as house masters via admin panel\n";
        echo "2. Ensure students have paid tuition fees\n";
        echo "3. House masters can then register students\n\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
