<?php
require_once '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = file_get_contents('php://input');
    if ($message) {
        $stmt = $pdo->prepare("INSERT INTO atlogs (message) VALUES (?)");
        $stmt->execute([$message]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No log data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
?>