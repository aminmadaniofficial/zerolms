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

// Access control check restricting log viewing to admin role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    echo json_encode(['logs'=>"Access Denied | Devloped By Amin Madani", 'page'=>1000, 'totalPages'=>1000]);
    exit();
}

// Pagination parameter processing
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Calculate total audit logs count
$totalStmt = $pdo->query("SELECT COUNT(*) FROM logs");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Fetch paginated logs joined with username
$stmt = $pdo->prepare("SELECT logs.*, users.username 
                       FROM logs 
                       LEFT JOIN users ON logs.user_id = users.id
                       ORDER BY logs.id DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'logs' => $logs,
    'page' => $page,
    'totalPages' => $totalPages
]);
?>