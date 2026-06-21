<?php
function addLog($pdo, $user_id=-1, $action, $target_type, $target_id=-1){
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, target_type, target_id, created_at) 
                           VALUES (:user_id, :action, :target_type, :target_id, NOW())");
    $stmt->execute([
        'user_id' => $user_id,
        'action' => $action,
        'target_type' => $target_type,
        'target_id' => $target_id
    ]);
}
?>
