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

$note_id = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;

if ($note_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'شناسه جزوه نامعتبر']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    $stmt = $pdo->prepare("
        SELECT n.id 
        FROM notes n 
        JOIN ClassCourseTeachers cct ON n.class_course_id = cct.class_course_id 
        WHERE n.id = :note_id AND cct.teacher_id = :teacher_id
    ");
    $stmt->execute(['note_id' => $note_id, 'teacher_id' => $_SESSION['user_id']]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'عدم دسترسی به جزوه']);
        exit();
    }
}

$stmt = $pdo->prepare("SELECT file_path FROM notes WHERE id = :note_id");
$stmt->execute(['note_id' => $note_id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) {
    echo json_encode(['success' => false, 'error' => 'جزوه یافت نشد']);
    exit();
}

// Unlink physical file from server filesystem
$file_path = '../../' . $note['file_path'];
if (file_exists($file_path)) {
    unlink($file_path);
}

try {
    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = :note_id");
    $stmt->execute(['note_id' => $note_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("خطا در حذف جزوه: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در حذف جزوه: ' . $e->getMessage()]);
}
?>