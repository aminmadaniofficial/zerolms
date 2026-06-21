<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

try {
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery");
    $total_images = $stmt->fetchColumn();
    $total_pages = ceil($total_images / $limit);

    
    $stmt = $pdo->prepare("
        SELECT g.id, g.image_path, g.title, g.created_at, u.username AS uploaded_by
        FROM gallery g
        LEFT JOIN users u ON g.uploaded_by = u.id
        ORDER BY g.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'images' => $images,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'خطای سرور: ' . $e->getMessage()]);
}
?>