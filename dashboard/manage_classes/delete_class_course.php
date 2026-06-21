<?php
session_start();
header('Content-Type: application/json');
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'نیاز به ورود با حساب ادمین']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$course_id = (int)($data['course_id'] ?? 0);

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'شناسه درس ناقص است']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM ClassCourseTeachers WHERE class_course_id = ?");
    $stmt->execute([$course_id]);
    
    $stmt = $pdo->prepare("DELETE FROM Notes WHERE class_course_id = ?");
    $stmt->execute([$course_id]);
    $stmt = $pdo->prepare("DELETE FROM Messages WHERE class_course_id = ?");
    $stmt->execute([$course_id]);
    
    $stmt = $pdo->prepare("DELETE FROM ClassCourses WHERE id = ?");
    $stmt->execute([$course_id]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'خطا در حذف: ' . $e->getMessage()]);
}
?>