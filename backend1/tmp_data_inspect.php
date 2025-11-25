<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$db = App\Config\Database::getInstance()->getConnection();
$aid = 1;
$yearId = 2;
// enrollment counts
$stmt = $db->prepare('SELECT class_id, COUNT(*) c FROM student_enrollments se INNER JOIN classes c ON se.class_id = c.id WHERE c.admin_id = :admin AND se.academic_year_id = :year GROUP BY class_id');
$stmt->execute([':admin'=>$aid, ':year'=>$yearId]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
// teacher assignments
$stmt = $db->prepare('SELECT teacher_id, subject_id, class_id FROM teacher_assignments WHERE academic_year_id = :year');
$stmt->execute([':year'=>$yearId]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
// subjects count and assignments distinct
$stmt = $db->prepare('SELECT COUNT(DISTINCT subject_id) c FROM teacher_assignments WHERE academic_year_id = :year');
$stmt->execute([':year'=>$yearId]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
