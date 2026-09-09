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

// Initialize PHP session scope
session_start();
require_once './db.php'; 
require_once './log.php'; 

// Extract user identity from session or fall back to cookie/default
$user_id = $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? -1;

// Log user logout activity into audit logs
addLog($pdo, $user_id, 'logout');

// Unset all active in-memory session variables
$_SESSION = [];

// Expire and clear session cookie if session cookies are enabled
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Expire persistent user authentication cookies
setcookie('user_id', '', time() - 3600, "/");
setcookie('username', '', time() - 3600, "/");
setcookie('role', '', time() - 3600, "/");

// Destroy server-side session data store
session_destroy();

// Redirect user back to home landing page
header("Location: ./index.php");
exit();
?>