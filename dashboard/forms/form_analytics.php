<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}

$form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
if ($form_id <= 0) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">شناسه فرم نامعتبر است</h1>');
}

$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ? AND created_by = ?");
$stmt->execute([$form_id, $_SESSION['user_id']]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">فرم یافت نشد.</h1>');
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحلیل پاسخ‌های فرم: <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../../js/chart.umd.min.js"></script>
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <style>
    
        .analytics-container { max-width: 900px; margin: auto; }
        .chart-container { margin-bottom: 30px; }
        .chart-type-select { max-width: 200px; }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="manage_forms.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به مدیریت فرم‌ها</a></span>
        <span>تحلیل پاسخ‌های فرم: <?php echo htmlspecialchars($form['title']); ?></span>
    </div>
    <div class="container mt-4 analytics-container">
        <h3>تحلیل پاسخ‌ها</h3>
        <div class="mb-3">
            <label for="chartType" class="form-label">نوع نمودار</label>
            <select id="chartType" class="form-select chart-type-select">
                <option value="bar">ستونی</option>
                <option value="pie">دایره‌ای</option>
                <option value="line">خطی</option>
            </select>
        </div>
        <div id="chartsContainer"></div>
    </div>
    <script>
        async function loadCharts(formId) {
            try {
                const response = await fetch(`get_responses.php?form_id=${formId}`);
                if (!response.ok) throw new Error('خطا در دریافت داده‌ها');
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                const container = document.getElementById('chartsContainer');
                container.innerHTML = '';

                if (!data.questions || Object.keys(data.questions).length === 0) {
                    container.innerHTML = '<div class="alert alert-warning">پاسخی برای این فرم ثبت نشده است.</div>';
                    return;
                }

                Object.entries(data.questions).forEach(([qIndex, qData]) => {
                    const chartId = `chart-${qIndex}`;
                    const chartDiv = document.createElement('div');
                    chartDiv.className = 'chart-container';
                    chartDiv.innerHTML = `
                        <h5>${qData.label}</h5>
                        <canvas id="${chartId}"></canvas>
                    `;
                    container.appendChild(chartDiv);

                    const ctx = document.getElementById(chartId).getContext('2d');
                    const chartType = document.getElementById('chartType').value;
                    new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: qData.options,
                            datasets: [{
                                label: 'تعداد پاسخ‌ها',
                                data: qData.counts,
                                backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8'],
                                borderColor: ['#0056b3', '#218838', '#c82333', '#e0a800', '#138496'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: chartType !== 'pie' ? {
                                y: { beginAtZero: true, ticks: { stepSize: 1 } }
                            } : {},
                            plugins: {
                                legend: { display: chartType === 'pie' }
                            }
                        }
                    });
                });
            } catch (err) {
                document.getElementById('chartsContainer').innerHTML = `
                    <div class="alert alert-danger">خطا در بارگذاری داده‌ها: ${err.message}</div>`;
            }
        }

        document.getElementById('chartType').addEventListener('change', () => {
            loadCharts(<?php echo $form_id; ?>);
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadCharts(<?php echo $form_id; ?>);
        });
    </script>
</body>
</html>