<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, title, code, created_at FROM forms WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'forms' => $forms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
}
?>