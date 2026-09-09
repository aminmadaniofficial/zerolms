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

require_once '../../db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$uid = $_GET['uid'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// Register attendance via RFID Card UFID hardware trigger
if ($action === 'register' && $uid && $date) {
    
    // Query student associated with RFID card UFID
    $stmt = $pdo->prepare("SELECT student_id FROM cards WHERE ufid = ?");
    $stmt->execute([$uid]);
    $student_id = $stmt->fetchColumn();
    
    if ($student_id) {
        
        // Prevent duplicate registration for same session date
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND session_date = ? LIMIT 1");
        $stmt->execute([$student_id, $date]);
        if ($stmt->fetchColumn()) {
            echo json_encode(['status' => 'already_present']);
            exit;
        }
        
        // Mark present for all 4 daily periods
        for ($period = 1; $period <= 4; $period++) {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, session_date, period, status) VALUES (?, ?, ?, 'present')");
            $stmt->execute([$student_id, $date, $period]);
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'unknown_card']);
    }
} elseif ($action === 'check' && $uid && $date) {
    // Check if card UFID is present today
    $stmt = $pdo->prepare("SELECT student_id FROM cards WHERE ufid = ?");
    $stmt->execute([$uid]);
    $student_id = $stmt->fetchColumn();
    
    if ($student_id) {
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND session_date = ? LIMIT 1");
        $stmt->execute([$student_id, $date]);
        if ($stmt->fetchColumn()) {
            echo 'present';
        } else {
            echo 'not_present';
        }
    } else {
        echo 'unknown_card';
    }
} else {
    echo json_encode(['status' => 'error']);
}
?>