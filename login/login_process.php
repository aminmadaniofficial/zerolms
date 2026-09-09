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

// Extract submitted login credentials
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

require_once '../log.php';

// Clear legacy cookies if session is empty but stale cookies remain
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("username", "", time() - 3600, "/");
    setcookie("role", "", time() - 3600, "/");
    unset($_COOKIE['user_id'], $_COOKIE['username'], $_COOKIE['role']);
}

// Authenticate submitted credentials
if($username && $password){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        // Set up active user session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['feedback_submitted'] = false;

        // Set persistent 7-day authentication cookies
        setcookie('user_id', $user['id'], time() + (86400 * 7), "/");
        setcookie('username', $user['username'], time() + (86400 * 7), "/");
        setcookie('role', $user['role'], time() + (86400 * 7), "/");

        // Log successful login action
        addLog($pdo, $user['id'], 'login_success');

        header("Location: index.php?success=1");
        exit();
    } else {
        // Log failed login attempt
        $user_id = $user['id'] ?? -1;
        addLog($pdo, $user_id, 'login_failed');

        header("Location: index.php?error=1");
        exit();
    }
} else {
    // Log attempt with missing mandatory fields
    addLog($pdo, -1, 'login_failed_empty');
    header("Location: index.php?error=1");
    exit();
}
?>