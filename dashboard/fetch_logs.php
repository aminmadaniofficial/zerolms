<?php
session_start();
require_once '../db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    echo json_encode(['logs'=>"Access Denied | Devloped By Amin Madani", 'page'=>1000, 'totalPages'=>1000]);
    exit();
}


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page-1)*$perPage;


$totalStmt = $pdo->query("SELECT COUNT(*) FROM logs");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);


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