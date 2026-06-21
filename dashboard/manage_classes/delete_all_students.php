<?php
session_start();
header('Content-Type: application/json');
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'نیاز به ورود با حساب ادمین']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$class_id = (int)($data['class_id'] ?? 0);

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'شناسه کلاس ناقص است']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET class_id = NULL WHERE class_id = ? AND role = 'student'");
    $stmt->execute([$class_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>