<?php
require_once '../../db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$uid = $_GET['uid'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

if ($action === 'register' && $uid && $date) {
    
    $stmt = $pdo->prepare("SELECT student_id FROM cards WHERE ufid = ?");
    $stmt->execute([$uid]);
    $student_id = $stmt->fetchColumn();
    
    if ($student_id) {
        
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND session_date = ? LIMIT 1");
        $stmt->execute([$student_id, $date]);
        if ($stmt->fetchColumn()) {
            echo json_encode(['status' => 'already_present']);
            exit;
        }
        
        
        for ($period = 1; $period <= 4; $period++) {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, session_date, period, status) VALUES (?, ?, ?, 'present')");
            $stmt->execute([$student_id, $date, $period]);
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'unknown_card']);
    }
} elseif ($action === 'check' && $uid && $date) {
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