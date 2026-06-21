<?php
header('Content-Type: application/json');
require_once '../../db.php';

$class_id = (int)$_GET['class_id'] ?? 0;

if ($class_id <= 0) {
    echo json_encode(['courses' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT cc.id, cc.course_name, 
           GROUP_CONCAT(u.name) AS teacher_names
    FROM ClassCourses cc
    LEFT JOIN ClassCourseTeachers cct ON cc.id = cct.class_course_id
    LEFT JOIN users u ON cct.teacher_id = u.id
    WHERE cc.class_id = ?
    GROUP BY cc.id, cc.course_name
    ORDER BY cc.course_name ASC
");
$stmt->execute([$class_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($courses as &$course) {
    $course['teachers'] = $course['teacher_names'] ? array_map(function($name) {
        return ['name' => $name];
    }, explode(',', $course['teacher_names'])) : [];
    unset($course['teacher_names']);
}

echo json_encode(['courses' => $courses]);
?>