<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit();
}

ob_clean();

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'شناسه کلاس نامعتبر']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM users 
        WHERE role = 'student' AND class_id = :class_id 
        ORDER BY name ASC
    ");
    $stmt->execute(['class_id' => $class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $encodedStudents = array_map(function($student) {
        return [
            'id' => (int)$student['id'],
            'name' => htmlspecialchars($student['name'])
        ];
    }, $students);

    echo json_encode(['success' => true, 'students' => $encodedStudents]);
} catch (Exception $e) {
    error_log("Error fetching class members: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در دریافت دانش‌آموزان: ' . $e->getMessage()]);
}
?>