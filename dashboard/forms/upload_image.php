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
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'دسترسی غیرمجاز']);
    exit;
}

require_once '../../upload_security.php';

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'هیچ فایلی آپلود نشد']);
    exit;
}

$file = $_FILES['file'];
$upload_dir = '../uploads/images/';
list($success, $filename_or_err, $dest) = store_safe_upload(
    $file,
    $upload_dir,
    ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'form_img_',
    5 * 1024 * 1024
);

if ($success) {
    echo json_encode(['location' => $dest]);
} else {
    echo json_encode(['error' => $filename_or_err]);
}
?>