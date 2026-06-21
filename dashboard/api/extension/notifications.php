<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://bahonarkaraj.ir');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');

require_once '../../../db.php';

$token = $_GET['token'] ?? '';
if (empty($token) || strlen($token) !== 32 || !ctype_xdigit(strtolower($token))) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, class_id FROM users WHERE extension_token = ? AND role = 'student' LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$userId = $user['id'];
$classId = $user['class_id'];
$notifications = [];


$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT status, period FROM attendance WHERE student_id = ? AND session_date = ?");
$stmt->execute([$userId, $today]);
$absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($absences as $absence) {
    if ($absence['status'] === 'absent') {
        $notifications[] = [
            'title' => 'غیبت دوره ' . $absence['period'],
            'message' => 'امروز در دوره ' . $absence['period'] . ' غیبت داشتی. پیگیری کن!',
            'type' => 'absence'
        ];
    }
}


if ($classId) {
    $stmt = $pdo->prepare("
        SELECT h.title, h.deadline, h.description 
        FROM homeworks h
        LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.student_id = ?
        INNER JOIN classcourses cc ON h.class_course_id = cc.id
        WHERE cc.class_id = ? AND hs.id IS NULL AND h.deadline >= NOW()
        ORDER BY h.deadline ASC LIMIT 5
    ");
    $stmt->execute([$userId, $classId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $hw) {
        $due = date('d F', strtotime($hw['deadline']));
        $notifications[] = [
            'title' => 'تکلیف: ' . $hw['title'],
            'message' => 'مهلت: ' . $due . ' - توضیح: ' . substr($hw['description'] ?? '', 0, 50) . '...',
            'type' => 'homework'
        ];
    }
}


if ($classId) {
    $stmt = $pdo->prepare("
        SELECT e.title, e.deadline, e.question_count
        FROM exams e
        INNER JOIN classcourses cc ON e.class_course_id = cc.id
        WHERE cc.class_id = ? AND e.deadline >= NOW() AND e.deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY e.deadline ASC LIMIT 3
    ");
    $stmt->execute([$classId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $exam) {
        $date = date('l d F', strtotime($exam['deadline']));
        $notifications[] = [
            'title' => 'آزمون: ' . $exam['title'],
            'message' => $date . ' - ' . $exam['question_count'] . ' سؤال',
            'type' => 'exam'
        ];
    }
}


$stmt = $pdo->prepare("
    SELECT es.grade, e.title, es.submitted_at
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.student_id = ? AND es.submitted_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    ORDER BY es.submitted_at DESC LIMIT 3
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $grade) {
    $notifications[] = [
        'title' => 'نمره جدید: ' . $grade['title'],
        'message' => 'نمره: ' . $grade['grade'] . '/20 - ' . date('d F H:i', strtotime($grade['submitted_at'])),
        'type' => 'grade'
    ];
}


$stmt = $pdo->prepare("
    SELECT b.name, b.description, ub.awarded_at
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ? AND ub.awarded_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY ub.awarded_at DESC LIMIT 2
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $badge) {
    $notifications[] = [
        'title' => 'نشان جدید: ' . $badge['name'],
        'message' => $badge['description'] ?? 'تبریک! 🎖️',
        'type' => 'badge'
    ];
}


$stmt = $pdo->prepare("
    SELECT mn.id, mn.type, mn.created_at
    FROM m_notifications mn
    WHERE mn.user_id = ? AND mn.is_read = 0
    ORDER BY mn.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $notif) {
    $typeText = $notif['type'] === 'message' ? 'پیام جدید' : ($notif['type'] === 'mention' ? 'منشن' : 'عضویت گروه');
    $notifications[] = [
        'title' => $typeText,
        'message' => 'پیام خوانده‌نشده - ' . date('H:i', strtotime($notif['created_at'])),
        'type' => 'message'
    ];
}

echo json_encode([
    'count' => count($notifications),
    'timestamp' => time(),
    'notifications' => $notifications
], JSON_UNESCAPED_UNICODE);