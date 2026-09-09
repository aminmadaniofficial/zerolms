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
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form = null;
if ($form_id > 0) {
    $stmt = $pdo->prepare("SELECT title, content, logo_url, start_date, end_date FROM forms WHERE id = ? AND created_by = ?");
    $stmt->execute([$form_id, $_SESSION['user_id']]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_form') {
    $form_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die(json_encode(['success' => false, 'error' => 'خطای امنیت CSRF']));
    }
    $title = trim($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $raw_questions = $_POST['questions'] ?? [];
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'عنوان فرم الزامی است']);
        exit;
    }

    $questions = [];
    foreach ($raw_questions as $index => $q) {
        $questions[$index] = [
            'label' => $q['label'] ?? '',
            'type' => $q['type'] ?? '',
            'required' => isset($q['required']) ? '1' : '0',
            'placeholder' => $q['placeholder'] ?? '',
            'maxlength' => $q['maxlength'] ?? '',
            'options' => $q['options'] ?? [],
            'accept' => $q['accept'] ?? '',
            'maxsize' => $q['maxsize'] ?? '',
            'min' => $q['min'] ?? '',
            'max' => $q['max'] ?? '',
            'step' => $q['step'] ?? ''
        ];
    }

    $content = json_encode([
        'title' => $title,
        'description' => $description,
        'questions' => $questions
    ], JSON_UNESCAPED_UNICODE);

    $logo_url = $form['logo_url'] ?? null;
    require_once '../../upload_security.php';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $dir = "../../Uploads/logos/";
        list($success, $filename_or_err, $dest) = store_safe_upload(
            $_FILES['logo'],
            $dir,
            ['jpg', 'jpeg', 'png', 'webp', 'svg'],
            ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            'logo_'
        );
        if ($success) {
            $logo_url = 'Uploads/logos/' . $filename_or_err;
        }
    }

    try {
        $pdo->beginTransaction();
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if ($form_id > 0) {
            $stmt = $pdo->prepare("UPDATE forms SET title = ?, content = ?, logo_url = ?, start_date = ?, end_date = ? WHERE id = ? AND created_by = ?");
            $stmt->execute([$title, $content, $logo_url, $start_date, $end_date, $form_id, $_SESSION['user_id']]);
            $code = $pdo->query("SELECT code FROM forms WHERE id = $form_id")->fetchColumn();
            addLog($pdo, $_SESSION['user_id'], 'ویرایش فرم', 'مدیریت فرم‌ها', $form_id);
        } else {
            $code = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO forms (title, code, content, logo_url, start_date, end_date, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $code, $content, $logo_url, $start_date, $end_date, $_SESSION['user_id']]);
            $form_id = $pdo->lastInsertId();
            addLog($pdo, $_SESSION['user_id'], 'ساخت فرم', 'مدیریت فرم‌ها', $form_id);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'فرم با موفقیت ذخیره شد', 'code' => $code, 'form_id' => $form_id]);
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
    <title>فرم‌ساز | سامانه یادگیری</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/favicon.png">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.85);
            --border-color: rgba(255, 255, 255, 0.1);
            --primary-accent: #6366f1;
        }

        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%);
            color: #f8fafc;
            min-height: 100vh;
            padding-bottom: 80px;
            margin: 0;
        }

        .topbar {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-custom {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            padding: 24px;
            margin-bottom: 20px;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            color: #ffffff !important;
            border-radius: 10px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
        }

        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 10px 14px;
        }

        .question-item {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .modal-content { background-color: #0f172a; border: 1px solid var(--border-color); color: #f8fafc; }

        footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; padding: 12px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            color: #94a3b8; font-size: 0.8rem;
            border-top: 1px solid var(--border-color);
            z-index: 99;
        }

        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="fw-bold fs-5" style="color:#f8fafc;"><i class="fas fa-tools me-2" style="color:#6366f1;"></i> <?php echo $form_id > 0 ? 'ویرایش فرم' : 'ساخت فرم جدید'; ?></span>
        <a href="manage_forms.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به لیست فرم‌ها</a>
    </div>

    <div class="container mt-4" style="max-width: 900px;">
        <div id="formMessage"></div>
        <form id="formBuilderForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_form">
            <input type="hidden" name="id" value="<?php echo $form_id; ?>">

            <div class="card-custom">
                <h5 class="mb-3" style="color:#a855f7;">تنظیمات اصلی فرم</h5>
                <div class="mb-3">
                    <label class="form-label" style="color:#f8fafc;">عنوان فرم</label>
                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($form['title'] ?? ''); ?>" required placeholder="عنوان فرم را وارد کنید...">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color:#f8fafc;">توضیحات فرم</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="توضیحات کلی برای پاسخ‌دهندگان..."><?php 
                        $content = json_decode($form['content'] ?? '', true);
                        echo htmlspecialchars($content['description'] ?? ''); 
                    ?></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="color:#f8fafc;">تاریخ شروع (اختیاری)</label>
                        <input type="datetime-local" class="form-control" name="start_date" value="<?php echo !empty($form['start_date']) ? date('Y-m-d\TH:i', strtotime($form['start_date'])) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="color:#f8fafc;">تاریخ پایان (اختیاری)</label>
                        <input type="datetime-local" class="form-control" name="end_date" value="<?php echo !empty($form['end_date']) ? date('Y-m-d\TH:i', strtotime($form['end_date'])) : ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Question Controls -->
            <div class="card-custom">
                <h5 class="mb-3" style="color:#38bdf8;">افزودن سوال جدید</h5>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="addQuestion('text')">+ متن کوتاه</button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="addQuestion('textarea')">+ متن بلند</button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="addQuestion('multiple')">+ چند گزینه‌ای</button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="addQuestion('checkbox')">+ چک‌باکس</button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="addQuestion('dropdown')">+ منوی کشویی</button>
                </div>

                <div id="questionsContainer"></div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-save me-1"></i> ذخیره فرم</button>
            </div>
        </form>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let questionCounter = 0;

        function addQuestion(type, data = null) {
            questionCounter++;
            const container = document.getElementById('questionsContainer');
            const div = document.createElement('div');
            div.className = 'question-item';

            let html = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong style="color:#a855f7;">سوال ${questionCounter} (${type})</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-trash"></i></button>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color:#f8fafc;">متن سوال</label>
                    <input type="text" class="form-control" name="questions[${questionCounter}][label]" value="${data?.label || ''}" required placeholder="سوال را بنویسید...">
                </div>
                <input type="hidden" name="questions[${questionCounter}][type]" value="${type}">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="questions[${questionCounter}][required]" value="1" ${data?.required === '1' ? 'checked' : ''}>
                    <label class="form-check-label" style="color:#cbd5e1;">پاسخ به این سوال اجباری باشد</label>
                </div>`;

            if (['multiple', 'checkbox', 'dropdown'].includes(type)) {
                html += `<div class="options-group mb-3"><label class="form-label" style="color:#38bdf8;">گزینه‌ها</label>`;
                const opts = data?.options && data.options.length ? data.options : ['گزینه ۱'];
                opts.forEach(opt => {
                    html += `
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="questions[${questionCounter}][options][]" value="${opt}" required>
                            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">حذف</button>
                        </div>`;
                });
                html += `<button type="button" class="btn btn-sm btn-outline-info mt-1" onclick="addOption(this, ${questionCounter})">+ افزودن گزینه</button></div>`;
            }

            div.innerHTML = html;
            container.appendChild(div);
        }

        function addOption(btn, qId) {
            const group = btn.parentElement;
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="text" class="form-control" name="questions[${qId}][options][]" placeholder="گزینه جدید" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">حذف</button>`;
            group.insertBefore(div, btn);
        }

        document.getElementById('formBuilderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('form_builder.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) window.location.href = 'manage_forms.php';
                else alert('خطا: ' + result.error);
            } catch (err) { alert('خطا در ارتباط با سرور'); }
        });

        // Load questions if editing existing form
        <?php if ($form && !empty($form['content'])): ?>
            const savedData = <?php echo json_encode(json_decode($form['content'], true), JSON_UNESCAPED_UNICODE); ?>;
            if (savedData && savedData.questions) {
                Object.values(savedData.questions).forEach(q => addQuestion(q.type, q));
            }
        <?php endif; ?>
    </script>
</body>
</html>