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

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');

require_once '../../../db.php'; 

$token = $_GET['token'] ?? '';

// Check token format validity
if (empty($token) || strlen($token) !== 32) {
    echo json_encode(['success' => false, 'error' => 'invalid_token']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE extension_token = ? LIMIT 1");
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
?>