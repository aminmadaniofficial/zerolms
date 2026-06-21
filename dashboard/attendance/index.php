<?php
session_start();
require_once '../../db.php';
require_once '../../jdf.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';
$student_id = (int) ($_GET['student_id'] ?? 0);
$session_date = $_GET['session_date'] ?? date('Y-m-d');

function get_current_date() {
    $ch = curl_init('http://api.time.ir/api/v1/time');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['data']['gregorian']['date'])) {
            return $data['data']['gregorian']['date']; 
        }
    }
    return date('Y-m-d'); 
}

function to_jalali($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    return jdate('Y/m/d', $timestamp);
}

$dates = [];
for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = [
        'gregorian' => $date,
        'jalali' => to_jalali($date)
    ];
}

function is_weekend($date) {
    $day = date('w', strtotime($date));
    return $day == 4 || $day == 5;
}

$students = [];
if (in_array($role, ['teacher', 'admin'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student'");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($role === 'admin' && $action === 'save_sms_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['sms_username'] ?? '';
    $password = $_POST['sms_password'] ?? '';
    $source = $_POST['sms_source'] ?? '';
    $template = $_POST['sms_template'] ?? '';

    $stmt = $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('sms_username', ?), ('sms_password', ?), ('sms_source', ?), ('sms_template', ?)");
    $stmt->execute([$username, $password, $source, $template]);
    header("Location: ?action=record&student_id=$student_id&session_date=$session_date");
    exit;
}

if ($role === 'admin' && $action === 'register_card' && $_SERVER['REQUEST_METHOD'] === 'POST' && $student_id) {
    $ufid = $_POST['ufid'] ?? '';
    if ($ufid) {
        $stmt = $pdo->prepare("REPLACE INTO cards (student_id, ufid) VALUES (?, ?)");
        $stmt->execute([$student_id, $ufid]);
        header("Location: ?action=record&student_id=$student_id&session_date=$session_date");
        exit;
    }
}

$sms_settings = [];
if ($role === 'admin') {
    $keys = ['sms_username', 'sms_password', 'sms_source', 'sms_template'];
    foreach ($keys as $key) {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $sms_settings[$key] = $stmt->fetchColumn() ?: '';
    }
}


if ($role === 'admin' && $action === 'send_sms_absentees' && $session_date) {
    $absentees = [];
    $stmt_students = $pdo->prepare("SELECT id, name, phone FROM users WHERE role = 'student' AND phone IS NOT NULL");
    $stmt_students->execute();
    $all_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_students as $stu) {
        $absent_periods = [];
        for ($period = 1; $period <= 4; $period++) {
            $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND session_date = ? AND period = ?");
            $stmt->execute([$stu['id'], $session_date, $period]);
            $status = $stmt->fetchColumn() ?: (is_weekend($session_date) ? 'weekend' : 'absent');
            if ($status === 'absent') {
                $absent_periods[] = $period;
            }
        }
        if (!empty($absent_periods)) {
            $absentees[] = [
                'id' => $stu['id'],
                'name' => $stu['name'],
                'phone' => $stu['phone'],
                'zangha' => implode('، ', $absent_periods)
            ];
        }
    }

    $url = 'https://sms.asanak.ir/webservice/v2rest/sendsms';
    foreach ($absentees as $absentee) {
        $message = $sms_settings['sms_template'];
        $message = str_replace(['{name}', '{date}', '{zangha}'], [$absentee['name'], to_jalali($session_date), $absentee['zangha']], $message);

        $postFields = json_encode([
            'username' => $sms_settings['sms_username'],
            'password' => $sms_settings['sms_password'],
            'source' => $sms_settings['sms_source'],
            'message' => $message,
            'destination' => $absentee['phone']
        ]);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
    }
    header("Location: ?action=record&session_date=$session_date");
    exit;
}

if ($action === 'save_attendance' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? '';
    $statuses = $_POST['status'] ?? [];

    if ($student_id && $session_date && count($statuses) === 4) {
        for ($period = 1; $period <= 4; $period++) {
            $status = $statuses[$period] ?? 'absent';
            if (!in_array($status, ['present', 'absent'])) continue;

            $stmt = $pdo->prepare("
                SELECT id FROM attendance
                WHERE student_id = ? AND session_date = ? AND period = ?
            ");
            $stmt->execute([$student_id, $session_date, $period]);
            $attendance_id = $stmt->fetchColumn();

            if ($attendance_id) {
                $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
                $stmt->execute([$status, $attendance_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, session_date, period, status)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$student_id, $session_date, $period, $status]);
            }
        }
        header("Location: ?action=record&student_id=$student_id&session_date=$session_date");
        exit;
    }
}

$attendance_records = [];
if ($role === 'student' || (in_array($role, ['teacher', 'admin']) && $student_id)) {
    $target_id = $role === 'student' ? $user_id : $student_id;
    $stmt = $pdo->prepare("
        SELECT session_date, period, status
        FROM attendance
        WHERE student_id = ? AND session_date = ?
        ORDER BY session_date DESC, period
    ");
    $stmt->execute([$target_id, $session_date]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records) && !is_weekend($session_date)) {
        for ($period = 1; $period <= 4; $period++) {
            $attendance_records[] = [
                'session_date' => $session_date,
                'period' => $period,
                'status' => 'absent'
            ];
        }
    } elseif (is_weekend($session_date)) {
        for ($period = 1; $period <= 4; $period++) {
            $attendance_records[] = [
                'session_date' => $session_date,
                'period' => $period,
                'status' => 'weekend'
            ];
        }
    } else {
        $attendance_records = $records;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حضور و غیاب</title>
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
        .form-container {
            @apply bg-white p-6 rounded-lg shadow-lg mb-6 border border-gray-200;
        }
        .btn {
            @apply px-4 py-2 rounded-lg text-white font-semibold transition duration-300 flex items-center justify-center gap-2;
        }
        .btn-primary {
            @apply bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-700 hover:to-blue-900;
        }
        .btn-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-800 hover:from-gray-700 hover:to-gray-900;
        }
        .btn-success {
            @apply bg-gradient-to-r from-green-600 to-green-800 hover:from-green-700 hover:to-green-900;
        }
        .status-present {
            @apply text-green-600 font-semibold;
        }
        .status-absent {
            @apply text-red-600 font-semibold;
        }
        .status-weekend {
            @apply text-gray-600 font-semibold;
        }
        .modal {
            @apply fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden;
        }
        .modal-content {
            @apply bg-white p-6 rounded-lg shadow-lg w-full max-w-md;
        }
        @media (max-width: 640px) {
            .form-container {
                @apply p-4;
            }
            th, td {
                @apply py-2 px-3 text-sm;
            }
            .btn {
                @apply text-sm px-3 py-1;
            }
        }
    </style>
</head>
<body>
    <div class="container mx-auto p-4 sm:p-6 md:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 bg-gradient-to-r from-blue-300 to-blue-800 text-white p-4 rounded-lg shadow-md">حضور و غیاب</h2>

        <div class="form-container mb-6">
            <form method="GET">
                <label for="session_date" class="block text-gray-700 font-medium mb-2">انتخاب تاریخ</label>
                <select name="session_date" class="border rounded p-2 w-full sm:w-64 focus:ring-2 focus:ring-blue-600" onchange="this.form.submit()">
                    <option value="">انتخاب تاریخ</option>
                    <?php foreach ($dates as $date): ?>
                        <option value="<?php echo $date['gregorian']; ?>" <?php echo $date['gregorian'] == $session_date ? 'selected' : ''; ?>>
                            <?php echo $date['jalali']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="action" value="<?php echo $role === 'student' ? 'view' : 'record'; ?>">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            </form>
        </div>

        <?php if (in_array($role, ['teacher', 'admin'])): ?>
            <div class="form-container">
                <form method="GET">
                    <label for="student_id" class="block text-gray-700 font-medium mb-2">دانش‌آموز</label>
                    <select name="student_id" class="border rounded p-2 w-full sm:w-64 focus:ring-2 focus:ring-blue-600" onchange="this.form.submit()">
                        <option value="">انتخاب دانش‌آموز</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo $student['id'] == $student_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="action" value="record">
                    <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">
                </form>
            </div>
        <?php endif; ?>

        <?php if ($action === 'record' && in_array($role, ['teacher', 'admin']) && $student_id): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">ثبت حضور و غیاب</h3>
            <div class="form-container">
                <form method="POST" action="?action=save_attendance">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <div class="mb-4">
                        <label for="session_date" class="block text-gray-700 font-medium">تاریخ</label>
                        <input type="date" name="session_date" class="border rounded p-2 w-full sm:w-64 focus:ring-2 focus:ring-blue-600" value="<?php echo $session_date; ?>" required readonly>
                    </div>
                    <?php for ($period = 1; $period <= 4; $period++): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND session_date = ? AND period = ?");
                        $stmt->execute([$student_id, $session_date, $period]);
                        $current_status = $stmt->fetchColumn() ?: (is_weekend($session_date) ? 'weekend' : 'absent');
                        ?>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium">زنگ <?php echo $period; ?></label>
                            <div class="flex space-x-4 space-x-reverse">
                                <label class="flex items-center">
                                    <input type="radio" name="status[<?php echo $period; ?>]" value="present" <?php echo $current_status === 'present' ? 'checked' : ''; ?> class="mr-2" <?php echo $current_status === 'weekend' ? 'disabled' : ''; ?>>
                                    <span class="status-present">حاضر</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="status[<?php echo $period; ?>]" value="absent" <?php echo $current_status === 'absent' ? 'checked' : ''; ?> class="mr-2" <?php echo $current_status === 'weekend' ? 'disabled' : ''; ?>>
                                    <span class="status-absent">غایب</span>
                                </label>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class="flex space-x-4 space-x-reverse">
                        <button type="submit" class="btn btn-primary" <?php echo is_weekend($session_date) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save"></i> ذخیره
                        </button>
                        <a href="?action=record&student_id=<?php echo $student_id; ?>&session_date=<?php echo $session_date; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> لغو
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <div class="flex space-x-4 space-x-reverse">
                <button onclick="openSmsModal()" class="btn btn-primary mt-4"><i class="fas fa-cog"></i> تنظیمات پیامک</button>
                <button onclick="openCardModal()" class="btn btn-primary mt-4"><i class="fas fa-id-card"></i> ثبت کارت RFID</button>
                <a href="?action=send_sms_absentees&session_date=<?php echo $session_date; ?>" class="btn btn-success mt-4"><i class="fas fa-paper-plane"></i> ارسال پیام به غایبین</a>
                <a href="logs.php" class="btn btn-secondary mt-4"><i class="fas fa-list"></i> لاگ‌های دستگاه</a>
            </div>

            <div id="smsModal" class="modal hidden">
                <div class="modal-content">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">تنظیمات پیامک</h3>
                    <form method="POST" action="?action=save_sms_settings">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">
                        <div class="mb-4">
                            <label for="sms_username" class="block text-gray-700 font-medium">نام کاربری</label>
                            <input type="text" name="sms_username" class="border rounded p-2 w-full" value="<?php echo htmlspecialchars($sms_settings['sms_username']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="sms_password" class="block text-gray-700 font-medium">رمز عبور</label>
                            <input type="password" name="sms_password" class="border rounded p-2 w-full" value="<?php echo htmlspecialchars($sms_settings['sms_password']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="sms_source" class="block text-gray-700 font-medium">شماره فرستنده</label>
                            <input type="text" name="sms_source" class="border rounded p-2 w-full" value="<?php echo htmlspecialchars($sms_settings['sms_source']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="sms_template" class="block text-gray-700 font-medium">قالب پیام</label>
                            <textarea name="sms_template" class="border rounded p-2 w-full" rows="4"><?php echo htmlspecialchars($sms_settings['sms_template']); ?></textarea>
                            <p class="text-sm text-gray-600 mt-2">راهنما: از {name} برای نام، {date} برای تاریخ، {zangha} برای زنگ‌های غایب استفاده کنید. مثال: "دانش‌آموز {name} در تاریخ {date} زنگ‌های {zangha} غایب بود."</p>
                        </div>
                        <div class="flex space-x-4 space-x-reverse">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ذخیره</button>
                            <button type="button" onclick="closeSmsModal()" class="btn btn-secondary"><i class="fas fa-times"></i> بستن</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="cardModal" class="modal hidden">
    <div class="modal-content">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">ثبت کارت RFID</h3>
        <form method="POST" action="?action=register_card">
            <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">
            <div class="mb-4">
                <label for="student_id_card" class="block text-gray-700 font-medium">انتخاب دانش‌آموز</label>
                <select name="student_id" id="student_id_card" class="border rounded p-2 w-full focus:ring-2 focus:ring-blue-600" required>
                    <option value="">انتخاب دانش‌آموز</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo $student['id'] == $student_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="ufid" class="block text-gray-700 font-medium">UFID کارت</label>
                <input type="text" name="ufid" class="border rounded p-2 w-full" placeholder="مثال: A1B2C3D4" required>
                <p class="text-sm text-gray-600 mt-2">کارت را نزدیک PN532 ببرید، UID را از لاگ‌های دستگاه (صفحه لاگ‌ها) کپی کنید.</p>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> ثبت کارت</button>
                <button type="button" onclick="closeCardModal()" class="btn btn-secondary"><i class="fas fa-times"></i> بستن</button>
            </div>
        </form>
    </div>
</div>

            <script>
                function openSmsModal() {
                    document.getElementById('smsModal').classList.remove('hidden');
                }
                function closeSmsModal() {
                    document.getElementById('smsModal').classList.add('hidden');
                }
                function openCardModal() {
                    document.getElementById('cardModal').classList.remove('hidden');
                }
                function closeCardModal() {
                    document.getElementById('cardModal').classList.add('hidden');
                }
            </script>
        <?php endif; ?>

        <?php if ($role === 'student' || ($action === 'view' && $student_id) || ($action === 'record' && $student_id)): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 bg-gradient-to-r from-blue-300 to-blue-800 text-white p-3 rounded-lg shadow-md">
                حضور و غیاب روز <?php echo to_jalali($session_date); ?>
            </h3>
            <div class="table-container">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>زنگ</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td>زنگ <?php echo $record['period']; ?></td>
                                <td class="<?php echo $record['status'] === 'present' ? 'status-present' : ($record['status'] === 'absent' ? 'status-absent' : 'status-weekend'); ?>">
                                    <?php echo $record['status'] === 'present' ? 'حاضر' : ($record['status'] === 'absent' ? 'غایب' : 'تعطیلات آخر هفته'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($attendance_records)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-gray-600">هیچ رکوردی ثبت نشده است.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex space-x-4 space-x-reverse">
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> بازگشت
                </a>
            </div>
        <?php else: ?>
            <p class="text-gray-700 p-4 rounded-lg shadow-md">لطفاً یک دانش‌آموز انتخاب کنید.</p>
            <div class="mt-6">
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> بازگشت
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>
