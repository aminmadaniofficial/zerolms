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
date_default_timezone_set('Asia/Tehran');

$code = $_GET['code'] ?? '';
if (empty($code)) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">کد فرم نامعتبر می باشد</h1>');
}

$stmt = $pdo->prepare("SELECT id, title, content,logo_url,start_date ,end_date FROM forms WHERE code = ?");
$stmt->execute([$code]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">فرم یافت نشد</h1>');
}

$content = json_decode($form['content'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('خطا در پردازش JSON: ' . json_last_error_msg());
}

// Generate device tracking cookie
$device_id = $_COOKIE['device_id'] ?? null;
if (!$device_id) {
    $device_id = bin2hex(random_bytes(16));
    setcookie('device_id', $device_id, time() + (365 * 24 * 60 * 60), '/'); 
}

$stmt = $pdo->prepare("SELECT id, submitted_at FROM form_responses WHERE form_id = ? AND device_id = ?");
$stmt->execute([$form['id'], $device_id]);
$existing_response = $stmt->fetch(PDO::FETCH_ASSOC);
$now = new DateTime();

if ($form['start_date'] && $now < new DateTime($form['start_date'])) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">زمان پاسخگویی به فرم هنوز شروع نشده است.</h1>');
}
if ($form['end_date'] && $now > new DateTime($form['end_date'])) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">زمان پاسخ گویی به فرم به اتمام رسیده است.</h1>');
}

// Handle dynamic form response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing_response) {
    $response_data = $_POST['response'] ?? [];
    $response_json = json_encode($response_data, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die(json_encode(['success' => false, 'error' => 'خطا در پردازش پاسخ‌ها']));
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO form_responses (form_id, response, device_id, submitted_by, submitted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$form['id'], $response_json, $device_id, $_SESSION['user_id'] ?? null]);
        $response_id = $pdo->lastInsertId();
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'پاسخ با موفقیت ثبت شد', 'tracking_code' => $response_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .form-container { max-width: 900px; margin: auto; }
        .form-description img { max-width: 100%; }
    </style>
</head>
<body>
    <div class="topbar">
        <span><?php echo htmlspecialchars($form['title']); ?></span>
        <?php if ($form['logo_url']): ?>
            <?php $logo_src = (strpos($form['logo_url'], 'http') === 0 || strpos($form['logo_url'], '/') === 0) ? $form['logo_url'] : '../../' . $form['logo_url']; ?>
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="لوگوی فرم" class="img-fluid mb-3" style="max-height: 100px;">
        <?php endif; ?>
    </div>
    <div class="container mt-4 form-container">
        <?php if ($existing_response): ?>
            <div class="alert alert-info">
                شما قبلاً این فرم را در تاریخ <?php echo htmlspecialchars($existing_response['submitted_at']); ?> پر کرده‌اید.
                کد پیگیری: <strong><?php echo (int)$existing_response['id']; ?></strong>
            </div>
        <?php else: ?>
            <div class="form-description"><?php echo nl2br(htmlspecialchars($content['description'] ?? '')); ?></div>
            <div id="formMessage"></div>
            <form id="responseForm">
                <?php foreach ($content['questions'] as $index => $question): ?>
                    <div class="mb-3">
                        <label class="form-label"><?php echo htmlspecialchars($question['label'] ?? 'سوال ' . ($index + 1)); ?>
                            <?php if ($question['required'] === '1'): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <?php if ($question['type'] === 'text'): ?>
                            <input type="text" class="form-control" name="response[<?php echo $index; ?>]"
                                   placeholder="<?php echo htmlspecialchars($question['placeholder'] ?? ''); ?>"
                                   <?php echo $question['required'] === '1' ? 'required' : ''; ?>
                                   maxlength="<?php echo $question['maxlength'] ?: '255'; ?>">
                        <?php elseif ($question['type'] === 'textarea'): ?>
                            <textarea class="form-control" name="response[<?php echo $index; ?>]"
                                      placeholder="<?php echo htmlspecialchars($question['placeholder'] ?? ''); ?>"
                                      <?php echo $question['required'] === '1' ? 'required' : ''; ?>
                                      maxlength="<?php echo $question['maxlength'] ?: '1000'; ?>"></textarea>
                        <?php elseif ($question['type'] === 'multiple'): ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="response[<?php echo $index; ?>]"
                                           value="<?php echo htmlspecialchars($option); ?>"
                                           <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($option); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($question['type'] === 'checkbox'): ?>
                            <?php foreach ($question['options'] as $option): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="response[<?php echo $index; ?>][]"
                                           value="<?php echo htmlspecialchars($option); ?>"
                                           <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                                    <label class="form-check-label"><?php echo htmlspecialchars($option); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif ($question['type'] === 'dropdown'): ?>
                            <select class="form-select" name="response[<?php echo $index; ?>]"
                                    <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($question['options'] as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>">
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($question['type'] === 'file'): ?>
                            <input type="file" class="form-control" name="response[<?php echo $index; ?>]"
                                   accept="<?php echo htmlspecialchars($question['accept'] ?? '*/*'); ?>"
                                   <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                        <?php elseif ($question['type'] === 'date'): ?>
                            <input type="date" class="form-control" name="response[<?php echo $index; ?>]"
                                   <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                        <?php elseif ($question['type'] === 'range'): ?>
                            <input type="range" class="form-range" name="response[<?php echo $index; ?>]"
                                   min="<?php echo $question['min'] ?? '0'; ?>" max="<?php echo $question['max'] ?? '100'; ?>"
                                   step="<?php echo $question['step'] ?? '1'; ?>"
                                   <?php echo $question['required'] === '1' ? 'required' : ''; ?>>
                            <div>مقدار: <span class="range-value">50</span></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">ارسال پاسخ</button>
            </form>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('responseForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('view_form.php?code=<?php echo $code; ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                const formMessage = document.getElementById('formMessage');
                formMessage.innerHTML = `
                    <div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                        ${result.success ? result.message : 'خطا: ' + result.error}
                        ${result.success && result.tracking_code ? '<br>کد پیگیری: <strong>' + result.tracking_code + '</strong>' : ''}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                if (result.success) {
                    e.target.remove(); 
                }
            } catch (err) {
                document.getElementById('formMessage').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
            }
        });

        document.querySelectorAll('input[type="range"]').forEach(input => {
            const valueSpan = input.nextElementSibling?.querySelector('.range-value');
            if (valueSpan) {
                valueSpan.textContent = input.value;
                input.addEventListener('input', () => {
                    valueSpan.textContent = input.value;
                });
            }
        });
    </script>
</body>
</html>