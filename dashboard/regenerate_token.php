<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false]));
}

$newToken = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("UPDATE users SET extension_token = ? WHERE id = ?");
$stmt->execute([$newToken, $_SESSION['user_id']]);

echo json_encode(['success' => true, 'new_token' => $newToken]);