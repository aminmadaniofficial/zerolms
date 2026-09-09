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
require_once '../../jdf.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';
$student_id = (int) ($_GET['student_id'] ?? 0);
$session_date = $_GET['session_date'] ?? date('Y-m-d');

/**
 * Convert standard timestamp to Jalali date format.
 * 
 * @param string $date Date string
 * @return string
 */
function to_jalali($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    return jdate('Y/m/d', $timestamp);
}

// Generate last 30 days array with Gregorian and Jalali dates
$dates = [];
for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = [
        'gregorian' => $date,
        'jalali' => to_jalali($date)
    ];
}

/**
 * Check if specified date falls on Iranian weekend (Thursday/Friday).
 * 
 * @param string $date
 * @return bool
 */
function is_weekend($date) {
    $day = date('w', strtotime($date));
    return $day == 4 || $day == 5;
}

$students = [];
if (in_array($role, ['teacher', 'admin'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Save SMS gateway configuration parameters
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

// Register RFID card UFID badge to student
if ($role === 'admin' && $action === 'register_card' && $_SERVER['REQUEST_METHOD'] === 'POST' && $student_id) {
    $ufid = trim($_POST['ufid'] ?? '');
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

// Save manual attendance status per class period
if ($action === 'save_attendance' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $session_date = $_POST['session_date'] ?? '';
    $statuses = $_POST['status'] ?? [];

    if ($student_id && $session_date && count($statuses) === 4) {
        for ($period = 1; $period <= 4; $period++) {
            $status = $statuses[$period] ?? 'absent';
            if (!in_array($status, ['present', 'absent'])) continue;

            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND session_date = ? AND period = ?");
            $stmt->execute([$student_id, $session_date, $period]);
            $attendance_id = $stmt->fetchColumn();

            if ($attendance_id) {
                $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
                $stmt->execute([$status, $attendance_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO attendance (student_id, session_date, period, status) VALUES (?, ?, ?, ?)");
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
    $stmt = $pdo->prepare("SELECT session_date, period, status FROM attendance WHERE student_id = ? AND session_date = ? ORDER BY period");
    $stmt->execute([$target_id, $session_date]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records) && !is_weekend($session_date)) {
        for ($period = 1; $period <= 4; $period++) {
            $attendance_records[] = ['session_date' => $session_date, 'period' => $period, 'status' => 'absent'];
        }
    } elseif (is_weekend($session_date)) {
        for ($period = 1; $period <= 4; $period++) {
            $attendance_records[] = ['session_date' => $session_date, 'period' => $period, 'status' => 'weekend'];
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
    <title>مدیریت حضور و غیاب | سامانه یادگیری</title>
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
            --: #94a3b8;
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
            padding: 24px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 10px 18px !important;
            font-weight: 600 !important;
        }

        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 10px 14px;
        }

        table {
            background: var(--bg-card);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            color: var(--text-main) !important;
        }

        table thead { background: #0f172a; }
        table th { color: var(--); font-weight: 600; padding: 14px; border-bottom: 1px solid var(--border-color); }
        table td { padding: 12px 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .modal-content {
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            color: var(--text-main);
        }

        .modal-header, .modal-footer { border-color: var(--border-color); }

        footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; padding: 12px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            color: var(--); font-size: 0.8rem;
            border-top: 1px solid var(--border-color);
            z-index: 99;
        }

        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="fw-bold fs-5"><i class="fas fa-calendar-check text-primary me-2"></i> حضور و غیاب</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <!-- Date and Student Filters -->
        <div class="card-custom mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <form method="GET">
                        <label class="form-label ">انتخاب تاریخ</label>
                        <select name="session_date" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($dates as $d): ?>
                                <option value="<?php echo $d['gregorian']; ?>" <?php echo $d['gregorian'] == $session_date ? 'selected' : ''; ?>>
                                    <?php echo $d['jalali']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="action" value="<?php echo $role === 'student' ? 'view' : 'record'; ?>">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    </form>
                </div>

                <?php if (in_array($role, ['teacher', 'admin'])): ?>
                    <div class="col-md-6">
                        <form method="GET">
                            <label class="form-label ">انتخاب دانش‌آموز</label>
                            <select name="student_id" class="form-select" onchange="this.form.submit()">
                                <option value="">انتخاب دانش‌آموز...</option>
                                <?php foreach ($students as $stu): ?>
                                    <option value="<?php echo $stu['id']; ?>" <?php echo $stu['id'] == $student_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($stu['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="action" value="record">
                            <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Control Toolbar -->
        <?php if ($role === 'admin'): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <button class="btn btn-gradient-primary btn-sm" data-bs-toggle="modal" data-bs-target="#smsModal"><i class="fas fa-cog me-1"></i> تنظیمات پیامک</button>
                <button class="btn btn-gradient-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cardModal"><i class="fas fa-id-card me-1"></i> ثبت کارت RFID</button>
                <a href="?action=send_sms_absentees&session_date=<?php echo $session_date; ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-paper-plane me-1"></i> ارسال پیامک به غایبین امروز</a>
            </div>
        <?php endif; ?>

        <!-- Attendance Recording Panel -->
        <?php if ($action === 'record' && in_array($role, ['teacher', 'admin']) && $student_id): ?>
            <div class="card-custom mb-4">
                <h5 class="mb-4 text-primary"><i class="fas fa-edit me-2"></i> ثبت وضعیت حضور و غیاب روز <?php echo to_jalali($session_date); ?></h5>
                <form method="POST" action="?action=save_attendance">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <input type="hidden" name="session_date" value="<?php echo $session_date; ?>">

                    <div class="row g-3 mb-4">
                        <?php for ($period = 1; $period <= 4; $period++): ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND session_date = ? AND period = ?");
                            $stmt->execute([$student_id, $session_date, $period]);
                            $curr_status = $stmt->fetchColumn() ?: (is_weekend($session_date) ? 'weekend' : 'absent');
                            ?>
                            <div class="col-md-3">
                                <div class="p-3 border border-secondary rounded-3 text-center bg-dark bg-opacity-50">
                                    <h6 class="text-light mb-3">زنگ <?php echo $period; ?></h6>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="status[<?php echo $period; ?>]" id="p_<?php echo $period; ?>_present" value="present" <?php echo $curr_status === 'present' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-success btn-sm" for="p_<?php echo $period; ?>_present">حاضر</label>

                                        <input type="radio" class="btn-check" name="status[<?php echo $period; ?>]" id="p_<?php echo $period; ?>_absent" value="absent" <?php echo $curr_status === 'absent' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-danger btn-sm" for="p_<?php echo $period; ?>_absent">غایب</label>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-save me-1"></i> ذخیره تغییرات</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Attendance Records Table -->
        <?php if ($role === 'student' || ($student_id && count($attendance_records) > 0)): ?>
            <div class="card-custom p-0">
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>زنگ آموزشی</th>
                                <th>وضعیت حضور و غیاب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $rec): ?>
                                <tr>
                                    <td><strong>زنگ <?php echo $rec['period']; ?></strong></td>
                                    <td>
                                        <?php if ($rec['status'] === 'present'): ?>
                                            <span class="badge bg-success bg-opacity-20 px-3 py-2">حاضر</span>
                                        <?php elseif ($rec['status'] === 'absent'): ?>
                                            <span class="badge bg-danger bg-opacity-20 px-3 py-2">غایب</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-20 px-3 py-2">تعطیلات</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- SMS Gateway Modal -->
    <div class="modal fade" id="smsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog text-primary me-2"></i> تنظیمات پنل پیامک</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?action=save_sms_settings">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">نام کاربری سامانه</label><input type="text" name="sms_username" class="form-control" value="<?php echo htmlspecialchars($sms_settings['sms_username'] ?? ''); ?>"></div>
                        <div class="mb-3"><label class="form-label">رمز عبور</label><input type="password" name="sms_password" class="form-control" value="<?php echo htmlspecialchars($sms_settings['sms_password'] ?? ''); ?>"></div>
                        <div class="mb-3"><label class="form-label">شماره خط فرستنده</label><input type="text" name="sms_source" class="form-control" value="<?php echo htmlspecialchars($sms_settings['sms_source'] ?? ''); ?>"></div>
                        <div class="mb-3"><label class="form-label">قالب متن پیامک</label><textarea name="sms_template" class="form-control" rows="3"><?php echo htmlspecialchars($sms_settings['sms_template'] ?? ''); ?></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ذخیره تنظیمات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RFID Registration Modal -->
    <div class="modal fade" id="cardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-id-card text-warning me-2"></i> ثبت کارت RFID دانش‌آموز</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?action=register_card">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">انتخاب دانش‌آموز</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">انتخاب کنید...</option>
                                <?php foreach ($students as $stu): ?>
                                    <option value="<?php echo $stu['id']; ?>"><?php echo htmlspecialchars($stu['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">کد UFID کارت RFID</label>
                            <input type="text" name="ufid" class="form-control" placeholder="مثلاً: A1B2C3D4" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ثبت کارت</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>