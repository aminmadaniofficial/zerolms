<?php
session_start();
require_once './db.php'; 


$user_id = $_SESSION['user_id'] ?? $_COOKIE['user_id'] ?? -1;


function addLog($pdo, $user_id, $action, $target_type='logout', $target_id=-1){
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, target_type, target_id, created_at) 
                           VALUES (:user_id, :action, :target_type, :target_id, NOW())");
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'target_type' => $target_type,
        'target_id' => $target_id
    ]);
}


addLog($pdo, $user_id, 'logout');


$_SESSION = [];


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


setcookie('user_id', '', time() - 3600, "/");
setcookie('username', '', time() - 3600, "/");
setcookie('role', '', time() - 3600, "/");


session_destroy();


header("Location: ./index.php");
exit();
?>
