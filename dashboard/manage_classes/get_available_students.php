<?php
header('Content-Type: application/json');
require_once '../../db.php';

$class_id = (int)$_GET['class_id'] ?? 0;

if ($class_id <= 0) {
    echo json_encode(['students' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name 
    FROM users 
    WHERE role = 'student' 
    AND class_id IS NULL
    ORDER BY name ASC
");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['students' => $students]);
?>