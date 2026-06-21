<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://bahonarkaraj.ir');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');
header('Cache-Control: no-cache');

require_once '../../../db.php'; 

$token = $_GET['token'] ?? '';

if (empty($token) || strlen($token) !== 32 || !ctype_xdigit(strtolower($token))) {
    echo json_encode(['success' => false, 'error' => 'invalid_token']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE extension_token = ? AND role = 'student' LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => $user['name'],
            'user_id' => (int)$user['id']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'invalid_token']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}