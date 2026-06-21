<?php
session_start();
require_once '../db.php';
require_once '../log.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');


if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    addLog($pdo, $_SESSION['user_id'] ?? null, 'خطای دسترسی غیرمجاز', 'گالری');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'درخواست نامعتبر']);
    addLog($pdo, $_SESSION['user_id'], 'خطای درخواست نامعتبر', 'گالری');
    exit;
}


if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'توکن امنیتی نامعتبر']);
    addLog($pdo, $_SESSION['user_id'], 'خطای توکن امنیتی', 'گالری');
    exit;
}

try {
    if ($_POST['action'] === 'upload') {
        $pdo->beginTransaction();
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'هیچ عکسی آپلود نشد']);
            addLog($pdo, $_SESSION['user_id'], 'خطای آپلود عکس', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $title = trim($_POST['title'] ?? '') ?: null;
        $image = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($image['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'فرمت فایل مجاز نیست (فقط JPEG، PNG، GIF)']);
            addLog($pdo, $_SESSION['user_id'], 'خطای فرمت فایل', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        if ($image['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'حجم فایل بیش از 5 مگابایت است']);
            addLog($pdo, $_SESSION['user_id'], 'خطای حجم فایل', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $upload_dir = '../uploads/gallery/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid('img_') . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
        $destination = $upload_dir . $file_name;

        if (!move_uploaded_file($image['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'error' => 'خطا در آپلود فایل']);
            addLog($pdo, $_SESSION['user_id'], 'خطای آپلود فایل', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO gallery (image_path, title, uploaded_by, created_at) VALUES (:image_path, :title, :uploaded_by, NOW())");
        $stmt->execute([
            'image_path' => 'uploads/gallery/' . $file_name,
            'title' => $title,
            'uploaded_by' => $_SESSION['user_id']
        ]);
        $image_id = $pdo->lastInsertId();
        $pdo->commit();
        addLog($pdo, $_SESSION['user_id'], 'آپلود موفق عکس', 'گالری');
        echo json_encode(['success' => true, 'message' => 'عکس با موفقیت آپلود شد']);
    } elseif ($_POST['action'] === 'edit') {
        $pdo->beginTransaction();
        $id = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '') ?: null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'شناسه عکس نامعتبر']);
            addLog($pdo, $_SESSION['user_id'], 'خطای ویرایش عکس', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            echo json_encode(['success' => false, 'error' => 'عکس یافت نشد']);
            addLog($pdo, $_SESSION['user_id'], 'خطای یافتن عکس', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $new_image_path = $image['image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024;

            if (!in_array($image_file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'error' => 'فرمت فایل مجاز نیست']);
                addLog($pdo, $_SESSION['user_id'], 'خطای فرمت فایل در ویرایش', 'گالری');
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                exit;
            }

            if ($image_file['size'] > $max_size) {
                echo json_encode(['success' => false, 'error' => 'حجم فایل بیش از 5 مگابایت است']);
                addLog($pdo, $_SESSION['user_id'], 'خطای حجم فایل در ویرایش', 'گالری');
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                exit;
            }

            $upload_dir = '../uploads/gallery/';
            $file_name = uniqid('img_') . '.' . pathinfo($image_file['name'], PATHINFO_EXTENSION);
            $destination = $upload_dir . $file_name;

            if (!move_uploaded_file($image_file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'error' => 'خطا در آپلود فایل جدید']);
                addLog($pdo, $_SESSION['user_id'], 'خطای آپلود فایل در ویرایش', 'گالری');
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                exit;
            }

            if (file_exists('../' . $image['image_path'])) {
                unlink('../' . $image['image_path']);
            }
            $new_image_path = 'uploads/gallery/' . $file_name;
        }

        $stmt = $pdo->prepare("UPDATE gallery SET title = :title, image_path = :image_path WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'image_path' => $new_image_path,
            'id' => $id
        ]);
        $pdo->commit();
        addLog($pdo, $_SESSION['user_id'], 'ویرایش موفق عکس', 'گالری');
        echo json_encode(['success' => true, 'message' => 'عکس با موفقیت ویرایش شد']);
    } elseif ($_POST['action'] === 'delete') {
        $pdo->beginTransaction();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'شناسه عکس نامعتبر']);
            addLog($pdo, $_SESSION['user_id'], 'خطای حذف عکس', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$image) {
            echo json_encode(['success' => false, 'error' => 'عکس یافت نشد']);
            addLog($pdo, $_SESSION['user_id'], 'خطای یافتن عکس برای حذف', 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if (file_exists('../' . $image['image_path'])) {
            unlink('../' . $image['image_path']);
        }
        $pdo->commit();
        addLog($pdo, $_SESSION['user_id'], 'حذف موفق عکس', 'گالری');
        echo json_encode(['success' => true, 'message' => 'عکس با موفقیت حذف شد']);
    } else {
        echo json_encode(['success' => false, 'error' => 'عملیات نامعتبر']);
        addLog($pdo, $_SESSION['user_id'], 'خطای عملیات نامعتبر', 'گالری');
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'خطای سرور: ' . $e->getMessage()]);
    addLog($pdo, $_SESSION['user_id'], 'خطای سرور: ' . $e->getMessage(), 'گالری');
    exit;
}
?>