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

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'لطفاً وارد شوید']);
    exit();
}

$course_id = isset($_POST['class_course_id']) ? (int)$_POST['class_course_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($course_id <= 0 || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'اطلاعات نامعتبر']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'student';

// Verify target class course permissions
if ($user_role === 'teacher' || $user_role === 'admin') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classcourseteachers WHERE teacher_id = :user_id AND class_course_id = :course_id");
    $stmt->execute(['user_id' => $user_id, 'course_id' => $course_id]);
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classcourses cc JOIN users u ON u.class_id = cc.class_id WHERE cc.id = :course_id AND u.id = :user_id");
    $stmt->execute(['course_id' => $course_id, 'user_id' => $user_id]);
}

$count = $stmt->fetchColumn();
if ($count == 0 && $user_role !== 'admin') {
    error_log("عدم دسترسی: user_id={$user_id}, role={$user_role}, course_id={$course_id}");
    echo json_encode(['success' => false, 'error' => 'عدم دسترسی به درس']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (class_course_id, user_id, content, created_at) VALUES (:cid, :uid, :content, NOW())");
    $stmt->execute(['cid' => $course_id, 'uid' => $user_id, 'content' => $content]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("خطا در ذخیره پیام: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در ذخیره پیام']);
}
?>