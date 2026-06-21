<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']));
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $input['user_id'] ?? 0;
$points = $input['points'] ?? 0;

if ($action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO user_points (user_id, points, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE points = points + ?");
    $success = $stmt->execute([$user_id, $points, $points]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'امتیاز با موفقیت اضافه شد' : 'خطا در اضافه کردن امتیاز'
    ]);
} elseif ($action === 'subtract') {
    $stmt = $pdo->prepare("UPDATE user_points SET points = GREATEST(0, points - ?) WHERE user_id = ?");
    $success = $stmt->execute([$points, $user_id]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'امتیاز با موفقیت کسر شد' : 'خطا در کسر امتیاز'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر']);
}
?>