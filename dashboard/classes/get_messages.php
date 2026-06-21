<?php
require_once '../../db.php';

header('Content-Type: application/json');
ob_clean();
$class_course_id = isset($_GET['class_course_id']) ? (int)$_GET['class_course_id'] : 0;

if ($class_course_id <= 0) {
    error_log("Invalid class_course_id: $class_course_id");
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.content, m.created_at, u.name
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.class_course_id = :class_course_id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['class_course_id' => $class_course_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $encodedMessages = array_map(function($message) {
        return [
            'id' => (int)$message['id'], 
            'content' => htmlspecialchars($message['content']),
            'created_at' => htmlspecialchars($message['created_at']),
            'name' => htmlspecialchars($message['name'])
        ];
    }, $messages);

    echo json_encode($encodedMessages);
} catch (Exception $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    echo json_encode(['error' => 'خطا در دریافت پیام‌ها: ' . $e->getMessage()]);
}
?>