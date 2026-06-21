<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'دسترسی غیرمجاز']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'هیچ فایلی آپلود نشد']);
    exit;
}

$file = $_FILES['file'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; 

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['error' => 'فرمت فایل مجاز نیست']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['error' => 'حجم فایل بیش از حد مجاز است']);
    exit;
}

$upload_dir = '../uploads/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$file_name = uniqid() . '_' . $file['name'];
$destination = $upload_dir . $file_name;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['location' => $destination]);
} else {
    echo json_encode(['error' => 'خطا در آپلود فایل']);
}
?>