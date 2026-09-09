<?php
session_start();
header('Content-Type: application/json');
require_once '../../db.php';
require_once '../../log.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'نیاز به ورود با حساب ادمین']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$class_id = (int)($data['class_id'] ?? 0);

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'شناسه کلاس ناقص است']);
    exit;
}

try {
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("SELECT id FROM ClassCourses WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $class_course_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    
    if (!empty($class_course_ids)) {
        $placeholders = implode(',', array_fill(0, count($class_course_ids), '?'));

        // Delete report cards associated with these class courses
        $stmt = $pdo->prepare("DELETE FROM report_cards WHERE class_course_id IN ($placeholders)");
        $stmt->execute($class_course_ids);

        
        $stmt = $pdo->prepare("DELETE FROM ClassCourseTeachers WHERE class_course_id IN ($placeholders)");
        $stmt->execute($class_course_ids);

        
        $stmt = $pdo->prepare("DELETE FROM Notes WHERE class_course_id IN ($placeholders)");
        $stmt->execute($class_course_ids);

        
        $stmt = $pdo->prepare("DELETE FROM Messages WHERE class_course_id IN ($placeholders)");
        $stmt->execute($class_course_ids);
    }

    
    $stmt = $pdo->prepare("DELETE FROM ClassCourses WHERE class_id = ?");
    $stmt->execute([$class_id]);

    
    $stmt = $pdo->prepare("UPDATE users SET class_id = NULL WHERE class_id = ? AND role = 'student'");
    $stmt->execute([$class_id]);

    
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);

    $pdo->commit();
    addLog($pdo, $_SESSION['user_id'], 'حذف موفق کلاس', 'حذف کلاس', $class_id);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    addLog($pdo, $_SESSION['user_id'], 'حذف ناموفق کلاس', 'حذف کلاس', $class_id);
    echo json_encode(['success' => false, 'message' => 'خطا در حذف کلاس: ' . $e->getMessage()]);
}
?>