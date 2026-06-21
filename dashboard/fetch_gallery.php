<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT g.id, g.image_path, g.title, g.created_at, u.username AS uploaded_by
        FROM gallery g
        LEFT JOIN users u ON g.uploaded_by = u.id
        ORDER BY g.created_at DESC
    ");
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'images' => $images]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'خطای سرور: ' . $e->getMessage()]);
}
?>