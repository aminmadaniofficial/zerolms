<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز'], JSON_UNESCAPED_UNICODE));
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

if ($action === 'add') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../uploads/badges/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $image_url = $target_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_url)) {
            echo json_encode(['success' => false, 'message' => 'خطا در آپلود تصویر']);
            exit;
        }
    }
    $stmt = $pdo->prepare("INSERT INTO badges (name, description, image_url) VALUES (?, ?, ?)");
    $success = $stmt->execute([$name, $description, $image_url]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'نشان با موفقیت اضافه شد' : 'خطا در افزودن نشان'
    ], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'edit') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../uploads/badges/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $image_url = $target_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_url)) {
            echo json_encode(['success' => false, 'message' => 'خطا در آپلود تصویر']);
            exit;
        }
    }
    $stmt = $pdo->prepare("UPDATE badges SET name = ?, description = ?, image_url = ? WHERE id = ?");
    $success = $stmt->execute([$name, $description, $image_url, $id]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'نشان با موفقیت ویرایش شد' : 'خطا در ویرایش نشان'
    ], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'delete') {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه نشان نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE badge_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM badges WHERE id = ?");
    $success = $stmt->execute([$id]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'نشان با موفقیت حذف شد' : 'خطا در حذف نشان'
    ], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'award') {
    $user_id = $input['user_id'] ?? 0;
    $badge_id = $input['badge_id'] ?? 0;
    if ($user_id <= 0 || $badge_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه کاربر یا نشان نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() !== 'student') {
        echo json_encode(['success' => false, 'message' => 'کاربر باید دانش‌آموز باشد'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id, awarded_at) VALUES (?, ?, NOW())");
    $success = $stmt->execute([$user_id, $badge_id]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'نشان با موفقیت اعطا شد' : 'خطا در اعطای نشان یا نشان قبلاً اعطا شده است'
    ], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'revoke') {
    $user_id = $input['user_id'] ?? 0;
    $badge_id = $input['badge_id'] ?? 0;
    if ($user_id <= 0 || $badge_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه کاربر یا نشان نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM user_badges WHERE user_id = ? AND badge_id = ?");
    $success = $stmt->execute([$user_id, $badge_id]);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'نشان با موفقیت گرفته شد' : 'خطا در گرفتن نشان یا نشان یافت نشد'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر: ' . htmlspecialchars($action)], JSON_UNESCAPED_UNICODE);
}
?>