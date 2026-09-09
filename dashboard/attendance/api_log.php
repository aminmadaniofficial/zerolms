<?php
/**
 *     _____                    __   __  ___ _____
 *    /__  /  ___  _________   / /  /  |/  // ___/
 *      / /  / _ \/ ___/ __ \ / /  / /|_/ / \__ \ 
 *     / /__/  __/ /  / /_/ // /__/ /  / / ___/ / 
 *    /____/\___/_/   \____//____/_/  /_/ /____/  
 * ------------------------------------------------------------
 *  System      : Zero LMS Core Engine
 *  Author      : Amin Madani
 *  Created     : 2026
 *  Notice      : Unauthorized copying or modification of this file,
 *                via any medium is strictly prohibited.
 * ------------------------------------------------------------
 */

require_once '../../db.php';
header('Content-Type: application/json');

// Log raw POST input body from RFID hardware device into atlogs table
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = file_get_contents('php://input');
    if ($message) {
        $stmt = $pdo->prepare("INSERT INTO atlogs (message) VALUES (?)");
        $stmt->execute([$message]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No log data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
?>