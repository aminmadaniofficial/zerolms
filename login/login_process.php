<?php
session_start();
require_once '../db.php';


$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';


function addLog($pdo, $user_id, $action, $target_type='login', $target_id=-1){
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, target_type, target_id, created_at) 
                           VALUES (:user_id, :action, :target_type, :target_id, NOW())");
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'target_type' => $target_type,
        'target_id' => $target_id
    ]);
}


if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("username", "", time() - 3600, "/");
    setcookie("role", "", time() - 3600, "/");
    unset($_COOKIE['user_id'], $_COOKIE['username'], $_COOKIE['role']);
}



if($username && $password){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        
        setcookie('user_id', $user['id'], time() + (86400 * 7), "/");
        setcookie('username', $user['username'], time() + (86400 * 7), "/");
        setcookie('role', $user['role'], time() + (86400 * 7), "/");

        
        addLog($pdo, $user['id'], 'login_success');

        header("Location: index.php?success=1");
        exit();
    } else {
        
        $user_id = $user['id'] ?? -1;
        addLog($pdo, $user_id, 'login_failed');

        header("Location: index.php?error=1");
        exit();
    }
} else {
    
    addLog($pdo, -1, 'login_failed_empty');
    header("Location: login.php?error=1");
    exit();
}
?>
