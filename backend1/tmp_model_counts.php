<?php
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();
$student = new App\Models\Student();
$teacher = new App\Models\Teacher();
$class = new App\Models\ClassModel();
$subject = new App\Models\Subject();
$admin = new App\Models\Admin();
$aid = 1;
var_dump($student->count(['admin_id'=>$aid]));
var_dump($teacher->count(['admin_id'=>$aid]));
var_dump($class->count(['admin_id'=>$aid]));
var_dump($subject->count(['admin_id'=>$aid]));
var_dump(count($admin->getAdminsBySchool($aid)));
