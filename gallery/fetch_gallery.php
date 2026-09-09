<?php
/**
 *     _____                    __   __  ___ _____
 *    /__  /  ___  _________   / /  /  |/  // ___/
 *      / /  / _ \/ ___/ __ \ / /  / /|_/ / \__ \ 
 *     / /__/  __/ /  / /_/ // /__/ /  / / ___/ / 
 *    /____/\___/_/   \____//____/_/  /_/ /____/  
 * 
 * ------------------------------------------------------------
 *  System      : Zero LMS Core Engine
 *  Author      : Amin Madani
 *  Created     : 2026
 *  Notice      : Unauthorized copying or modification of this file,
 *                via any medium is strictly prohibited.
 * ------------------------------------------------------------
 */

session_start();
require_once '../db.php';

// Configure JSON response headers and disable HTTP caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

try {
    // Sanitize and process pagination inputs
    $page = max(1, isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1);
    $limit = max(1, min(100, isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20));
    $offset = ($page - 1) * $limit;

    // Fetch total count of gallery items to compute pagination bounds
    $stmt = $pdo->query("SELECT COUNT(*) FROM gallery");
    $total_images = $stmt->fetchColumn();
    $total_pages = ceil($total_images / $limit);

    // Query paginated image records with joined uploader details
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

    // Return structured JSON response payload
    echo json_encode([
        'success' => true,
        'images' => $images,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
} catch (Exception $e) {
    // Catch errors and output HTTP 500 status payload
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'خطای سرور: ' . $e->getMessage()]);
}
?>