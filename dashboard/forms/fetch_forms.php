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
require_once '../../db.php';

// Verify admin authentication status
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    exit;
}

try {
    // Fetch all forms created by current admin user ordered by creation date
    $stmt = $pdo->prepare("SELECT id, title, code, created_at FROM forms WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'forms' => $forms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
}
?>