<?php
session_start();
require_once '../../db.php';

header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'عدم دسترسی']);
    exit();
}

$class_course_id = isset($_POST['class_course_id']) ? (int)$_POST['class_course_id'] : 0;

if ($class_course_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'شناسه درس نامعتبر']);
    exit();
}


if ($_SESSION['role'] !== 'admin') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ClassCourseTeachers WHERE teacher_id = :teacher_id AND class_course_id = :class_course_id");
    $stmt->execute(['teacher_id' => $_SESSION['user_id'], 'class_course_id' => $class_course_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'عدم دسترسی به درس']);
        exit();
    }
}


try {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE class_course_id = :class_course_id");
    $stmt->execute(['class_course_id' => $class_course_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("خطا در حذف پیام‌ها: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در حذف پیام‌ها: ' . $e->getMessage()]);
}
?>