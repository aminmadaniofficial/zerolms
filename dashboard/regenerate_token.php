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

// Validate user session and CSRF token hash equality
if (!isset($_SESSION['user_id']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false]));
}

// Generate new 32-character random hex token
$newToken = bin2hex(random_bytes(16));
$stmt = $pdo->prepare("UPDATE users SET extension_token = ? WHERE id = ?");
$stmt->execute([$newToken, $_SESSION['user_id']]);

echo json_encode(['success' => true, 'new_token' => $newToken]);
?>