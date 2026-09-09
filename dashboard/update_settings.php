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

header('Content-Type: application/json');
ob_clean();

// Check user session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'عدم دسترسی']);
    exit();
}

// Check CSRF token match
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'توکن امنیتی نامعتبر']);
    exit();
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$new_username = $_POST['new_username'] ?? '';
$username_password = $_POST['username_password'] ?? '';
$new_name = $_POST['new_name'] ?? '';
$user_id = $_SESSION['user_id'];

// Query target user profile data
$stmt = $pdo->prepare("SELECT username, password, name FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'کاربر یافت نشد']);
    exit();
}

$updates = [];
$messages = [];

try {
    // Process password update workflow
    if (!empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور فعلی الزامی است']);
            exit();
        }
        if (!password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور فعلی نادرست است']);
            exit();
        }
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور جدید و تکرار آن مطابقت ندارند']);
            exit();
        }
        if (strlen($new_password) < 8) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد']);
            exit();
        }
        $updates['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        $messages[] = 'رمز عبور با موفقیت تغییر کرد';
    }

    // Process username update workflow
    if (!empty($new_username)) {
        if (empty($username_password)) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور فعلی برای تغییر نام کاربری الزامی است']);
            exit();
        }
        if (!password_verify($username_password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'رمز عبور فعلی برای تغییر نام کاربری نادرست است']);
            exit();
        }
        if ($new_username === $user['username']) {
            echo json_encode(['success' => false, 'error' => 'نام کاربری جدید با فعلی یکسان است']);
            exit();
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :user_id");
        $stmt->execute(['username' => $new_username, 'user_id' => $user_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'نام کاربری قبلاً استفاده شده است']);
            exit();
        }
        $updates['username'] = $new_username;
        $_SESSION['username'] = $new_username; 
        $messages[] = 'نام کاربری با موفقیت تغییر کرد';
    }

    // Process display name update workflow
    if (!empty($new_name)) {
        if ($new_name === $user['name']) {
            echo json_encode(['success' => false, 'error' => 'نام جدید با فعلی یکسان است']);
            exit();
        }
        $updates['name'] = $new_name;
        $messages[] = 'نام با موفقیت تغییر کرد';
    }

    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'هیچ تغییری اعمال نشد']);
        exit();
    }

    // Execute dynamic update SQL query
    $query = "UPDATE users SET ";
    $params = [];
    $set = [];
    foreach ($updates as $key => $value) {
        $set[] = "$key = :$key";
        $params[$key] = $value;
    }
    $query .= implode(', ', $set) . " WHERE id = :user_id";
    $params['user_id'] = $user_id;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => implode('، ', $messages)]);
} catch (Exception $e) {
    error_log("خطا در به‌روزرسانی تنظیمات: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'خطا در ذخیره تغییرات: ' . $e->getMessage()]);
}
?>