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

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');

require_once '../../../db.php';

// Validate extension token length and format
$token = $_GET['token'] ?? '';
if (empty($token) || strlen($token) !== 32) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

// Fetch user account associated with extension token
$stmt = $pdo->prepare("SELECT id, name, class_id FROM users WHERE extension_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['count' => 0, 'notifications' => []]);
    exit;
}

$userId = $user['id'];
$classId = $user['class_id'];
$notifications = [];

// 1. Check today's absences
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT status, period FROM attendance WHERE student_id = ? AND session_date = ?");
$stmt->execute([$userId, $today]);
$absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($absences as $absence) {
    if ($absence['status'] === 'absent') {
        $notifications[] = [
            'title' => 'ثبت غیبت در زنگ ' . $absence['period'],
            'message' => 'امروز در زنگ ' . $absence['period'] . ' غیبت شما ثبت شد.',
            'type' => 'absence'
        ];
    }
}

// 2. Pending unsubmitted homework assignments
if ($classId) {
    $stmt = $pdo->prepare("
        SELECT h.title, h.deadline 
        FROM homeworks h
        LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.student_id = ?
        INNER JOIN classcourses cc ON h.class_course_id = cc.id
        WHERE cc.class_id = ? AND hs.id IS NULL AND h.deadline >= NOW()
        ORDER BY h.deadline ASC LIMIT 3
    ");
    $stmt->execute([$userId, $classId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $hw) {
        $notifications[] = [
            'title' => 'تکلیف در انتظار تحویل',
            'message' => $hw['title'] . ' - مهلت: ' . $hw['deadline'],
            'type' => 'homework'
        ];
    }
}

// 3. Upcoming online exams
if ($classId) {
    $stmt = $pdo->prepare("
        SELECT e.title, e.deadline
        FROM exams e
        INNER JOIN classcourses cc ON e.class_course_id = cc.id
        WHERE cc.class_id = ? AND e.deadline >= NOW()
        ORDER BY e.deadline ASC LIMIT 2
    ");
    $stmt->execute([$classId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $exam) {
        $notifications[] = [
            'title' => 'آزمون فعال: ' . $exam['title'],
            'message' => 'مهلت شرکت: ' . $exam['deadline'],
            'type' => 'exam'
        ];
    }
}

// 4. Recently posted exam grades
$stmt = $pdo->prepare("
    SELECT es.grade, e.title
    FROM exam_submissions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.student_id = ? AND es.submitted_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
    ORDER BY es.submitted_at DESC LIMIT 2
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $grade) {
    $notifications[] = [
        'title' => 'ثبت نمره آزمون: ' . $grade['title'],
        'message' => 'نمره شما: ' . $grade['grade'] . ' از ۲۰',
        'type' => 'grade'
    ];
}

// 5. Newly awarded gamification badges
$stmt = $pdo->prepare("
    SELECT b.name
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ? AND ub.awarded_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY ub.awarded_at DESC LIMIT 2
");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $badge) {
    $notifications[] = [
        'title' => 'کسب نشان جدید!',
        'message' => 'نشان «' . $badge['name'] . '» به شما اعطا شد.',
        'type' => 'badge'
    ];
}

echo json_encode([
    'count' => count($notifications),
    'timestamp' => time(),
    'notifications' => $notifications
], JSON_UNESCAPED_UNICODE);
?>