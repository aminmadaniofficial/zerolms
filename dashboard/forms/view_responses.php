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
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/");
    exit;
}

$form_id = (int)($_GET['form_id'] ?? 0);
$stmt = $pdo->prepare("SELECT title, content FROM forms WHERE id = ? AND created_by = ?");
$stmt->execute([$form_id, $_SESSION['user_id']]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) die("فرم یافت نشد.");

$content = json_decode($form['content'], true);
$stmt = $pdo->prepare("SELECT id, response, submitted_by, submitted_at FROM form_responses WHERE form_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$form_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export form response data to Excel spreadsheet
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $headers = ['شناسه', 'کاربر', 'زمان ارسال'];
    foreach ($content['questions'] as $index => $q) {
        $headers[] = $q['label'] ?? 'سوال ' . ($index + 1);
    }
    $sheet->fromArray($headers, null, 'A1');

    $row = 2;
    foreach ($responses as $resp) {
        $data = json_decode($resp['response'], true);
        $rowData = [$resp['id'], $resp['submitted_by'] ?? 'مهمان', $resp['submitted_at']];
        foreach ($content['questions'] as $index => $q) {
             $ans = $data[$index] ?? '-';
             $rowData[] = is_array($ans) ? implode(', ', $ans) : $ans;
        }
        $sheet->fromArray($rowData, null, 'A' . $row);
        $row++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="responses_' . $form_id . '.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پاسخ‌های ثبت شده | <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/favicon.png">

    <style>
        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }
        body { background-color: #090d16; color: #f8fafc; min-height: 100vh; padding-bottom: 80px; margin: 0; }
        .topbar { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.08); padding: 16px 30px; display: flex; justify-content: space-between; align-items: center; }
        .card-custom { background: rgba(17, 24, 39, 0.85); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; margin-bottom: 16px; }
        footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; padding: 12px; background: rgba(15, 23, 42, 0.9); color: #94a3b8; font-size: 0.8rem; border-top: 1px solid rgba(255,255,255,0.08); }
        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="fw-bold fs-5" style="color:#f8fafc;"><i class="fas fa-list-alt me-2" style="color:#10b981;"></i> پاسخ‌های ثبت‌شده: <?php echo htmlspecialchars($form['title']); ?></span>
        <a href="manage_forms.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت</a>
    </div>

    <div class="container mt-4" style="max-width: 900px;">
        <div class="mb-4">
            <a href="view_responses.php?form_id=<?php echo $form_id; ?>&export=excel" class="btn btn-success"><i class="fas fa-file-excel me-1"></i> دانلود فایل اکسل</a>
        </div>

        <?php if (empty($responses)): ?>
            <div class="card-custom text-center" style="color:#cbd5e1;">هیچ پاسخی تاکنون ثبت نشده است.</div>
        <?php else: ?>
            <?php foreach ($responses as $resp): ?>
                <?php $data = json_decode($resp['response'], true); ?>
                <div class="card-custom">
                    <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-3">
                        <span style="color:#38bdf8;">کد پیگیری: #<?php echo $resp['id']; ?></span>
                        <span style="color:#94a3b8; font-size:0.85rem;"><?php echo $resp['submitted_at']; ?></span>
                    </div>
                    <?php foreach ($content['questions'] as $index => $q): ?>
                        <div class="mb-2">
                            <strong style="color:#a855f7;"><?php echo htmlspecialchars($q['label'] ?? 'سوال ' . ($index + 1)); ?>:</strong>
                            <span style="color:#f8fafc;" class="ms-2">
                                <?php 
                                    $ans = $data[$index] ?? '-';
                                    echo htmlspecialchars(is_array($ans) ? implode(', ', $ans) : $ans);
                                ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>