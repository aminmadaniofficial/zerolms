<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_form') {
    $form_id = (int)$_POST['form_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM form_responses WHERE form_id = ?");
        $stmt->execute([$form_id]);
        $stmt = $pdo->prepare("DELETE FROM forms WHERE id = ? AND created_by = ?");
        $stmt->execute([$form_id, $_SESSION['user_id']]);
        addLog($pdo, $_SESSION['user_id'], 'حذف فرم', 'فرم', $form_id);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'فرم با موفقیت حذف شد']);
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
    <title>مدیریت فرم‌ها</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <script src="../../js/qrcode.min.js"></script>
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .forms-container { max-width: 900px; margin: auto; }
        .form-item { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="../../dashboard" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a></span>
        <span>مدیریت فرم‌ها</span>
    </div>
    <div class="container mt-4 forms-container">
        <h3>مدیریت فرم‌ها</h3>
        <a href="form_builder.php" class="btn btn-primary mb-3">ساخت فرم جدید</a>
        <div id="formMessage"></div>
        <div id="formsContainer"></div>
    </div>
    <script>
        async function loadForms() {
            try {
                const response = await fetch('fetch_forms.php', { cache: 'no-cache' });
                if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'خطای ناشناخته');
                const formsContainer = document.getElementById('formsContainer');
                formsContainer.innerHTML = '';
                data.forms.forEach(form => {
                    const formDiv = document.createElement('div');
                    formDiv.className = 'form-item';
                    formDiv.innerHTML = `
                        <h5>${form.title}</h5>
                        <p>کد: ${form.code}</p>
                        <p>تاریخ ساخت: ${form.created_at}</p>
                        <a href="view_form.php?code=${form.code}" class="btn btn-info btn-sm">مشاهده</a>
                        <a href="form_builder.php?id=${form.id}" class="btn btn-warning btn-sm">ویرایش</a>
                        <a href="form_analytics.php?form_id=${form['id']}" class="btn btn-sm btn-info">تحلیل پاسخ‌ها</a>
                        <a href="view_responses.php?form_id=${form.id}" class="btn btn-success btn-sm">مشاهده پاسخ‌ها</a>
                        <a href="live_poll.php?form_id=${form['id']}" class="btn btn-sm btn-success">نظرسنجی زنده</a>
                        <button class="btn btn-danger btn-sm btn-delete-form" data-id="${form.id}">حذف</button>
                    `;
                    formsContainer.appendChild(formDiv);
                });
                document.querySelectorAll('.btn-delete-form').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (confirm('آیا مطمئن هستید که می‌خواهید این فرم را حذف کنید؟')) {
                            try {
                                const response = await fetch('manage_forms.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `action=delete_form&form_id=${btn.dataset.id}`
                                });
                                const result = await response.json();
                                const formMessage = document.getElementById('formMessage');
                                formMessage.innerHTML = `
                                    <div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                                        ${result.success ? result.message : 'خطا: ' + result.error}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>`;
                                if (result.success) loadForms();
                            } catch (err) {
                                document.getElementById('formMessage').innerHTML = `
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        خطا: ${err.message}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>`;
                            }
                        }
                    });
                });
            } catch (err) {
                document.getElementById('formMessage').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا در بارگذاری فرم‌ها: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
            }
        }

        document.addEventListener('DOMContentLoaded', loadForms);
    </script>
</body>
</html>