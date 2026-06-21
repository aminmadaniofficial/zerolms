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
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$file = isset($_FILES['note_file']) ? $_FILES['note_file'] : null;

if ($class_course_id <= 0 || empty($title) || !$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'اطلاعات ناقص یا خطا در فایل']);
    exit();
}


$allowed_extensions = ['pdf', 'docx', 'jpg', 'png'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'error' => 'فرمت فایل پشتیبانی نمی‌شود']);
    exit();
}


if ($_SESSION['role'] !== 'admin') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ClassCourseTeachers WHERE teacher_id = :teacher_id AND class_course_id = :class_course_id");
    $stmt->execute(['teacher_id' => $_SESSION['user_id'], 'class_course_id' => $class_course_id]);
    if ($stmt->fetchColumn() == 0) {
        error_log("عدم دسترسی آپلود: user_id={$_SESSION['user_id']}, role={$_SESSION['role']}, class_course_id=$class_course_id");
        echo json_encode(['success' => false, 'error' => 'عدم دسترسی به درس']);
        exit();
    }
}


$upload_dir = 'uploads/notes/';
if (!is_dir('../../' . $upload_dir)) {
    mkdir('../../' . $upload_dir, 0755, true);
}
$unique_name = uniqid('note_') . '.' . $file_ext;
$upload_path = $upload_dir . $unique_name;

if (!move_uploaded_file($file['tmp_name'], '../../' . $upload_path)) {
    error_log("خطا در آپلود فایل: name={$file['name']}, path=$upload_path");
    echo json_encode(['success' => false, 'error' => 'خطا در آپلود فایل']);
    exit();
}


$stmt = $pdo->prepare("
    INSERT INTO notes (class_course_id, teacher_id, title, file_path, created_at) 
    VALUES (:class_course_id, :teacher_id, :title, :file_path, NOW())
");
try {
    $stmt->execute([
        'class_course_id' => $class_course_id,
        'teacher_id' => $_SESSION['user_id'],
        'title' => $title,
        'file_path' => $upload_path
    ]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("خطا در ذخیره جزوه: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در ذخیره جزوه: ' . $e->getMessage()]);
}
?>