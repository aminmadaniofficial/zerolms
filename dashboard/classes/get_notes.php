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
        SELECT n.id, n.title, n.file_path, n.created_at 
        FROM notes n 
        WHERE n.class_course_id = :class_course_id 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute(['class_course_id' => $class_course_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $encodedNotes = array_map(function($note) {
        return [
            'id' => (int)$note['id'], 
            'title' => htmlspecialchars($note['title']),
            'file_path' => htmlspecialchars($note['file_path']),
            'created_at' => htmlspecialchars($note['created_at'])
        ];
    }, $notes);

    echo json_encode($encodedNotes);
} catch (Exception $e) {
    error_log("Error fetching notes: " . $e->getMessage());
    echo json_encode(['error' => 'خطا در دریافت جزوه‌ها: ' . $e->getMessage()]);
}
?>