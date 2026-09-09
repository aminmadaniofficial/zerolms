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
$form_id = (int)($_GET['form_id'] ?? 0);
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) die("نظرسنجی یافت نشد.");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نظرسنجی زنده | <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <script src="../../js/chart.umd.min.js"></script>

    <style>
        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }
        body { background-color: #090d16; color: #f8fafc; min-height: 100vh; padding: 20px; margin: 0; }
        .card-custom { background: rgba(17, 24, 39, 0.85); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <div class="card-custom text-center mb-4">
            <h4 style="color:#a855f7;"><i class="fas fa-chart-bar me-2"></i> نتایج زنده: <?php echo htmlspecialchars($form['title']); ?></h4>
            <small style="color:#94a3b8;">به‌روزرسانی خودکار هر ۵ ثانیه</small>
        </div>
        <div id="pollCharts"></div>
    </div>

    <!-- Live Poll Refresh Loop Script -->
    <script>
    async function updatePoll() {
        try {
            const res = await fetch(`get_responses.php?form_id=<?php echo $form_id; ?>`);
            const data = await res.json();
            const container = document.getElementById('pollCharts');
            container.innerHTML = '';

            Object.entries(data.questions).forEach(([qIndex, qData]) => {
                const div = document.createElement('div');
                div.className = 'card-custom text-center';
                div.innerHTML = `<h5 style="color:#38bdf8;" class="mb-3">${qData.label}</h5><canvas id="poll-chart-${qIndex}"></canvas>`;
                container.appendChild(div);

                const ctx = document.getElementById(`poll-chart-${qIndex}`).getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: qData.options,
                        datasets: [{
                            data: qData.counts,
                            backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4']
                        }]
                    },
                    options: { plugins: { legend: { labels: { color: '#f8fafc' } } } }
                });
            });
        } catch(e) {}
    }
    updatePoll();
    setInterval(updatePoll, 5000);
    </script>
</body>
</html>