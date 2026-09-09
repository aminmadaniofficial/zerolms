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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login/");
    exit;
}

// Delete form action handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_form') {
    $form_id = (int)$_POST['form_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM form_responses WHERE form_id = ?")->execute([$form_id]);
        $pdo->prepare("DELETE FROM forms WHERE id = ? AND created_by = ?")->execute([$form_id, $_SESSION['user_id']]);
        addLog($pdo, $_SESSION['user_id'], 'حذف فرم', 'مدیریت فرم‌ها', $form_id);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'فرم با موفقیت حذف شد.']);
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
    <title>مدیریت فرم‌ها | سامانه یادگیری</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/favicon.png">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.8);
            --border-color: rgba(255, 255, 255, 0.08);
            --primary-accent: #6366f1;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%);
            color: var(--text-main);
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
            padding: 20px;
            margin-bottom: 16px;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 10px 18px !important;
            font-weight: 600 !important;
        }

        footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; padding: 12px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            color: var(--text-muted); font-size: 0.8rem;
            border-top: 1px solid var(--border-color);
            z-index: 99;
        }

        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="fw-bold fs-5"><i class="fas fa-clipboard-list text-primary me-2"></i> مدیریت فرم‌ها و نظرسنجی‌ها</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="form_builder.php" class="btn btn-gradient-primary"><i class="fas fa-plus me-1"></i> ساخت فرم جدید</a>
        </div>

        <div id="formMessage"></div>
        <div id="formsContainer"></div>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load existing forms list via fetch_forms.php endpoint
        async function loadForms() {
            try {
                const response = await fetch('fetch_forms.php', { cache: 'no-cache' });
                const data = await response.json();
                const container = document.getElementById('formsContainer');
                container.innerHTML = '';

                if (!data.forms || data.forms.length === 0) {
                    container.innerHTML = '<div class="card-custom text-center text-muted">هیچ فرمی تاکنون ساخته نشده است.</div>';
                    return;
                }

                data.forms.forEach(form => {
                    const div = document.createElement('div');
                    div.className = 'card-custom d-flex flex-wrap align-items-center justify-content-between gap-3';
                    div.innerHTML = `
                        <div>
                            <h5 class="m-0 font-weight-bold text-primary">${form.title}</h5>
                            <small class="text-muted">کد اختصاصی: <code>${form.code}</code> | تاریخ ساخت: ${form.created_at}</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="view_form.php?code=${form.code}" target="_blank" class="btn btn-sm btn-outline-info"><i class="fas fa-eye me-1"></i> مشاهده</a>
                            <a href="form_builder.php?id=${form.id}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit me-1"></i> ویرایش</a>
                            <a href="form_analytics.php?form_id=${form.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-chart-pie me-1"></i> تحلیل</a>
                            <a href="view_responses.php?form_id=${form.id}" class="btn btn-sm btn-outline-success"><i class="fas fa-list me-1"></i> پاسخ‌ها</a>
                            <button class="btn btn-sm btn-outline-danger btn-delete-form" data-id="${form.id}"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    container.appendChild(div);
                });

                document.querySelectorAll('.btn-delete-form').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (confirm('آیا از حذف این فرم و تمامی پاسخ‌های آن اطمینان دارید؟')) {
                            const res = await fetch('manage_forms.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `action=delete_form&form_id=${btn.dataset.id}`
                            });
                            const result = await res.json();
                            if (result.success) loadForms();
                        }
                    });
                });
            } catch (err) {
                console.error(err);
            }
        }

        document.addEventListener('DOMContentLoaded', loadForms);
    </script>
</body>
</html>