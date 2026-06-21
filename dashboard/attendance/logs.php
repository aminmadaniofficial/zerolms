<?php
session_start();
require_once '../../db.php';
require_once '../../jdf.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
function to_jalali($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    return jdate('Y/m/d', $timestamp);
}


$stmt = $pdo->prepare("SELECT message, created_at FROM atlogs ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$atlogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لاگ‌های دستگاه</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        @font-face {
            font-family: 'font-iran-normal';
            src: url('../../css/font-iran-normal.woff2') format('woff2'),
                 url('../../css/font-iran-normal.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'font-iran-normal', sans-serif;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 98%;
            right: 1%;
            margin: auto;
            text-align: center;
            padding: 12px 0;
            background-color: #1e1e1e;
            color: #ffffff;
            font-weight: bold;
            box-shadow: 0 -2px 10px rgba(1, 238, 255, 0.5);
            border-radius: 12px 12px 0 0;
        }
        footer a {
            color: #01eeff;
            text-decoration: none;
            transition: color 0.3s;
        }
        footer a:hover {
            color: #00ccdd;
        }
        .table-container {
            @apply overflow-x-auto shadow-lg rounded-lg bg-white;
        }
        th, td {
            @apply py-4 px-6 text-right border-b border-gray-200;
        }
        th {
            @apply bg-gradient-to-r from-blue-300 to-blue-600 text-white font-bold;
        }
        tr:hover {
            @apply bg-gray-50;
        }
        .btn {
            @apply px-4 py-2 rounded-lg text-white font-semibold transition duration-300 flex items-center justify-center gap-2;
        }
        .btn-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-800 hover:from-gray-700 hover:to-gray-900;
        }
    </style>
</head>
<body>
    <div class="container mx-auto p-4 sm:p-6 md:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 bg-gradient-to-r from-blue-300 to-blue-800 text-white p-4 rounded-lg shadow-md">لاگ‌های دستگاه</h2>
        <div class="table-container">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>پیام</th>
                        <th>زمان</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atlogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['message']); ?></td>
                            <td><?php echo to_jalali($log['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($atlogs)): ?>
                        <tr><td colspan="2" class="text-center py-4 text-gray-600">هیچ لاگی ثبت نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>
        </div>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>