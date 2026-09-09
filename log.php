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

if (!function_exists('addLog')) {
    /**
     * Record user and system activities into database logs table.
     * 
     * @param PDO $pdo Active PDO instance
     * @param int|null $user_id Performing user ID (-1 for anonymous/system)
     * @param string $action Description of action performed
     * @param string $target_type Categorization key of logged event target
     * @param int $target_id ID of impacted entity (-1 if unassigned)
     * @return void
     */
    function addLog($pdo, $user_id = -1, $action = '', $target_type = 'general', $target_id = -1) {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, target_type, target_id, created_at) 
                               VALUES (:user_id, :action, :target_type, :target_id, NOW())");
        $stmt->execute([
            'user_id' => ($user_id !== null && $user_id !== '') ? $user_id : -1,
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id
        ]);
    }
}
?>