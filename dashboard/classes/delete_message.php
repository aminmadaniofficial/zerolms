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

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'عدم دسترسی']);
    exit();
}

$message_id = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

if ($message_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'شناسه پیام نامعتبر']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    $stmt = $pdo->prepare("
        SELECT m.id 
        FROM messages m 
        JOIN ClassCourseTeachers cct ON m.class_course_id = cct.class_course_id 
        WHERE m.id = :message_id AND cct.teacher_id = :teacher_id
    ");
    $stmt->execute(['message_id' => $message_id, 'teacher_id' => $_SESSION['user_id']]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'عدم دسترسی به پیام']);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = :message_id");
    $stmt->execute(['message_id' => $message_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("خطا در حذف پیام: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در حذف پیام: ' . $e->getMessage()]);
}
?>