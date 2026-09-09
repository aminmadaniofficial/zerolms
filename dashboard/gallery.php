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

session_start();
require_once '../db.php';
require_once '../log.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Verify session permissions for gallery operations
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی غیرمجاز']);
    addLog($pdo, $_SESSION['user_id'] ?? null, 'خطای دسترسی غیرمجاز', 'گالری');
    exit;
}

// Validate HTTP POST method and action parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'درخواست نامعتبر']);
    addLog($pdo, $_SESSION['user_id'], 'خطای درخواست نامعتبر', 'گالری');
    exit;
}

// Check CSRF token integrity
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
        require_once '../upload_security.php';
        $upload_dir = '../uploads/gallery/';
        list($success, $filename_or_err, $dest) = store_safe_upload(
            $image,
            $upload_dir,
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'img_',
            5 * 1024 * 1024
        );

        if (!$success) {
            echo json_encode(['success' => false, 'error' => $filename_or_err]);
            addLog($pdo, $_SESSION['user_id'], 'خطای آپلود فایل: ' . $filename_or_err, 'گالری');
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            exit;
        }

        $file_name = $filename_or_err;

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
            require_once '../upload_security.php';
            $upload_dir = '../uploads/gallery/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $image_file,
                $upload_dir,
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'img_',
                5 * 1024 * 1024
            );

            if (!$success) {
                echo json_encode(['success' => false, 'error' => $filename_or_err]);
                addLog($pdo, $_SESSION['user_id'], 'خطای آپلود فایل در ویرایش: ' . $filename_or_err, 'گالری');
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                exit;
            }

            $file_name = $filename_or_err;

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