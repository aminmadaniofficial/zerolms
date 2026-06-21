<?php
header('Content-Type: application/json');
require_once '../../db.php';

$course_id = (int)$_GET['course_id'] ?? 0;

if ($course_id <= 0) {
    echo json_encode(['teachers' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.name 
    FROM users u 
    JOIN teachers t ON u.id = t.user_id 
    WHERE u.role = 'teacher' 
    AND u.id NOT IN (SELECT teacher_id FROM ClassCourseTeachers WHERE class_course_id = ?)
    ORDER BY u.name ASC
");
$stmt->execute([$course_id]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['teachers' => $teachers]);
?>