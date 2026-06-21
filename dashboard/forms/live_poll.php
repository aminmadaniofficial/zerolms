<?php
session_start();
require_once '../../db.php';
$form_id = (int)($_GET['form_id'] ?? 0);
$stmt = $pdo->prepare("SELECT title, content FROM forms WHERE id = ?");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>نظرسنجی زنده: <?php echo htmlspecialchars($form['title']); ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <script src="../../js/chart.umd.min.js"></script>
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
    body {
        font-family: 'Vazir', sans-serif;
    }

    .poll-container {
        max-width: 900px;
        margin: auto;
    }

    .container,
    .poll-container,
    .analytics-container {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>

<body>
    <div class="container mt-4 poll-container">
        <h3>نظرسنجی زنده: <?php echo htmlspecialchars($form['title']); ?></h3>
        <div id="pollCharts"></div>
    </div>
    <script>
    async function updatePoll() {
        const response = await fetch(`get_responses.php?form_id=<?php echo $form_id; ?>`);
        const data = await response.json();
        const container = document.getElementById('pollCharts');
        container.innerHTML = '';
        Object.entries(data.questions).forEach(([qIndex, qData]) => {
            const chartId = `poll-chart-${qIndex}`;
            container.innerHTML += `<h5>${qData.label}</h5><canvas id="${chartId}"></canvas>`;
            const ctx = document.getElementById(chartId).getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: qData.options,
                    datasets: [{
                        label: 'تعداد پاسخ‌ها',
                        data: qData.counts,
                        backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107']
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });
        });
    }
    updatePoll();
    setInterval(updatePoll, 5000); 
    </script>
</body>

</html>