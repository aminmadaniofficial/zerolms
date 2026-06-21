<?php
session_start();
require_once '../../db.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}

if (!isset($_GET['form_id'])) {
    die('شناسه فرم نامعتبر است');
}

$form_id = (int)$_GET['form_id'];
$stmt = $pdo->prepare("SELECT title, content FROM forms WHERE id = ? AND created_by = ?");
$stmt->execute([$form_id, $_SESSION['user_id']]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die('فرم یافت نشد');
}

$content = json_decode($form['content'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('خطا در پردازش JSON: ' . json_last_error_msg());
}


$stmt = $pdo->prepare("SELECT id, response, submitted_by, submitted_at FROM form_responses WHERE form_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$form_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    
    $headers = ['شناسه پاسخ', 'کاربر', 'زمان ارسال'];
    foreach ($content['questions'] as $index => $question) {
        $headers[] = $question['label'] ?? 'سوال ' . ($index + 1);
    }
    $sheet->fromArray($headers, null, 'A1');

    
    $row = 2;
    foreach ($responses as $response) {
        $response_data = json_decode($response['response'], true);
        $row_data = [
            $response['id'],
            $response['submitted_by'] ?? 'مهمان',
            $response['submitted_at']
        ];
        foreach ($content['questions'] as $index => $question) {
            $answer = $response_data[$index] ?? '-';
            if (is_array($answer)) {
                $answer = implode(', ', $answer);
            }
            $row_data[] = $answer;
        }
        $sheet->fromArray($row_data, null, 'A' . $row);
        $row++;
    }

    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="form_responses_' . $form_id . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پاسخ‌های فرم: <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .responses-container { max-width: 900px; margin: auto; }
        .response-item { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="manage_forms.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به مدیریت فرم‌ها</a></span>
        <span>پاسخ‌های فرم: <?php echo htmlspecialchars($form['title']); ?></span>
    </div>
    <div class="container mt-4 responses-container">
        <h3>پاسخ‌های فرم: <?php echo htmlspecialchars($form['title']); ?></h3>
        <a href="view_responses.php?form_id=<?php echo $form_id; ?>&export=excel" class="btn btn-success mb-3">دانلود اکسل</a>
        <?php if (empty($responses)): ?>
            <div class="alert alert-info">هیچ پاسخی برای این فرم ثبت نشده است.</div>
        <?php else: ?>
            <?php foreach ($responses as $response): ?>
                <div class="response-item">
                    <p><strong>شناسه پاسخ:</strong> <?php echo $response['id']; ?></p>
                    <p><strong>کاربر:</strong> <?php echo $response['submitted_by'] ?? 'مهمان'; ?></p>
                    <p><strong>زمان ارسال:</strong> <?php echo $response['submitted_at']; ?></p>
                    <h5>پاسخ‌ها:</h5>
                    <?php $response_data = json_decode($response['response'], true); ?>
                    <?php foreach ($content['questions'] as $index => $question): ?>
                        <p><strong><?php echo htmlspecialchars($question['label'] ?? 'سوال ' . ($index + 1)); ?>:</strong>
                        <?php
                            $answer = $response_data[$index] ?? '-';
                            if (is_array($answer)) {
                                echo htmlspecialchars(implode(', ', $answer));
                            } else {
                                echo htmlspecialchars($answer);
                            }
                        ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>