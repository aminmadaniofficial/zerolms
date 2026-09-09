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

$form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ? AND created_by = ?");
$stmt->execute([$form_id, $_SESSION['user_id']]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) die("فرم یافت نشد.");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحلیل پاسخ‌ها | <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../../images/favicon.png">
    <script src="../../js/chart.umd.min.js"></script>

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
        }

        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
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

        .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
        }

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
        <span class="fw-bold fs-5" style="color:#f8fafc;"><i class="fas fa-chart-pie me-2" style="color:#38bdf8;"></i> تحلیل آمار پاسخ‌ها: <?php echo htmlspecialchars($form['title']); ?></span>
        <a href="manage_forms.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت</a>
    </div>

    <div class="container mt-4" style="max-width: 900px;">
        <div class="card-custom mb-4">
            <label class="form-label" style="color:#f8fafc;">نوع نمودار آماری</label>
            <select id="chartType" class="form-select" style="max-width: 200px;">
                <option value="bar">نمودار ستونی</option>
                <option value="pie">نمودار دایره‌ای</option>
                <option value="line">نمودار خطی</option>
            </select>
        </div>

        <div id="chartsContainer"></div>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script>
        // Fetch and render analytics charts asynchronously
        async function loadCharts(formId) {
            try {
                const response = await fetch(`get_responses.php?form_id=${formId}`);
                const data = await response.json();
                const container = document.getElementById('chartsContainer');
                container.innerHTML = '';

                if (!data.questions || Object.keys(data.questions).length === 0) {
                    container.innerHTML = '<div class="card-custom text-center" style="color:#cbd5e1;">هیچ پاسخی ثبت نشده است.</div>';
                    return;
                }

                Object.entries(data.questions).forEach(([qIndex, qData]) => {
                    const chartDiv = document.createElement('div');
                    chartDiv.className = 'card-custom mb-4';
                    chartDiv.innerHTML = `
                        <h6 class="mb-3" style="color:#a855f7;">${qData.label}</h6>
                        <canvas id="chart-${qIndex}"></canvas>`;
                    container.appendChild(chartDiv);

                    const ctx = document.getElementById(`chart-${qIndex}`).getContext('2d');
                    const cType = document.getElementById('chartType').value;
                    
                    new Chart(ctx, {
                        type: cType,
                        data: {
                            labels: qData.options,
                            datasets: [{
                                label: 'تعداد انتخاب',
                                data: qData.counts,
                                backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4']
                            }]
                        },
                        options: {
                            plugins: { legend: { labels: { color: '#f8fafc' } } },
                            scales: cType !== 'pie' ? {
                                y: { ticks: { color: '#f8fafc' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                                x: { ticks: { color: '#f8fafc' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                            } : {}
                        }
                    });
                });
            } catch (err) { console.error(err); }
        }

        document.getElementById('chartType').addEventListener('change', () => loadCharts(<?php echo $form_id; ?>));
        document.addEventListener('DOMContentLoaded', () => loadCharts(<?php echo $form_id; ?>));
    </script>
</body>
</html>