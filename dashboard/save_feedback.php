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
require_once '../log.php';

header('Content-Type: application/json; charset=utf-8');

// Verify session authenticity
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'عدم دسترسی']);
    exit;
}

// Check CSRF token match
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'توکن امنیتی نامعتبر']);
    exit;
}

$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'لطفاً یک امتیاز از ۱ تا ۵ ستاره انتخاب کنید.']);
    exit;
}

try {
    // Ensure feedback storage table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at DATETIME NOT NULL
    )");

    $stmt = $pdo->prepare("INSERT INTO feedback (user_id, rating, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $rating, $comment]);

    addLog($pdo, $user_id, "ثبت امتیاز $rating ستاره به سایت", "بازخورد");

    // Mark feedback as submitted for this login session
    $_SESSION['feedback_submitted'] = true;

    echo json_encode(['success' => true, 'message' => 'با تشکر! بازخورد شما با موفقیت ثبت شد.']);
} catch (Exception $e) {
    error_log("Feedback save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در ثبت بازخورد: ' . $e->getMessage()]);
}
?>