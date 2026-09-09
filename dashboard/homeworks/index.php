<?php
session_start();
require_once '../../db.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$homework_id = (int) ($_GET['homework_id'] ?? 0);

// دروس دانش‌آموز
$stmt = $pdo->prepare("
    SELECT cc.id, cc.course_name, c.name AS class_name
    FROM classcourses cc
    JOIN classes c ON cc.class_id = c.id
    JOIN users u ON u.class_id = c.id
    WHERE u.id = ?
    ORDER BY c.name, cc.course_name
");
$stmt->execute([$user_id]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ارسال یا ویرایش پاسخ تکلیف
if ($action === 'submit' && $homework_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $text_response = trim($_POST['text_response'] ?? '');
    $stmt = $pdo->prepare("SELECT deadline FROM homeworks WHERE id = ? AND class_course_id = ?");
    $stmt->execute([$homework_id, $class_course_id]);
    $deadline = $stmt->fetchColumn();
    
    if ($deadline && strtotime($deadline) > time()) {
        $file_path = null;
        require_once '../../upload_security.php';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/homework/students/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['file'],
                $dir,
                ['pdf', 'docx', 'doc', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'webp', 'txt'],
                [],
                'hw_stu_'
            );
            if ($success) {
                $file_path = 'Uploads/homework/students/' . $filename_or_err;
            }
        }
        
        $stmt = $pdo->prepare("SELECT id, file_path FROM homework_submissions WHERE homework_id = ? AND student_id = ?");
        $stmt->execute([$homework_id, $user_id]);
        $existing_submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_submission) {
            if ($existing_submission['file_path'] && $file_path && file_exists('../../' . $existing_submission['file_path'])) {
                @unlink('../../' . $existing_submission['file_path']);
            }
            $final_file = $file_path ?: $existing_submission['file_path'];
            $stmt = $pdo->prepare("UPDATE homework_submissions SET file_path = ?, text_response = ?, submitted_at = NOW() WHERE id = ?");
            $stmt->execute([$final_file, $text_response, $existing_submission['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO homework_submissions (homework_id, student_id, file_path, text_response, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$homework_id, $user_id, $file_path, $text_response]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}

// لیست تکالیف
$homeworks = [];
if ($class_course_id) {
    $stmt = $pdo->prepare("
        SELECT h.id, h.title, h.description, h.file_path, h.deadline, h.created_at,
               hs.file_path AS student_file, hs.text_response, hs.grade, hs.feedback
        FROM homeworks h
        LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.student_id = ?
        WHERE h.class_course_id = ?
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([$user_id, $class_course_id]);
    $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تکالیف من | سامانه یادگیری</title>
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
            padding: 24px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 10px 20px !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3) !important;
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
        table th { color: var(--text-muted); font-weight: 600; padding: 14px; border-bottom: 1px solid var(--border-color); }
        table td { padding: 12px 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

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
        <span class="fw-bold fs-5"><i class="fas fa-pencil-alt text-primary me-2"></i> تکالیف من</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if (!$class_course_id): ?>
            <!-- انتخاب درس -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-book me-2"></i> درس مورد نظر را انتخاب کنید</h5>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>نام کلاس و درس</th>
                                <th style="width: 150px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($lesson['class_name'] . ' - ' . $lesson['course_name']); ?></strong></td>
                                    <td>
                                        <a href="?class_course_id=<?php echo $lesson['id']; ?>" class="btn btn-gradient-primary btn-sm">
                                            <i class="fas fa-tasks me-1"></i> تکالیف
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($lessons)): ?>
                                <tr><td colspan="2" class="text-muted py-4">هیچ درسی یافت نشد.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'submit' && $homework_id): ?>
            <!-- ارسال پاسخ -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-upload me-2"></i> ارسال پاسخ تکلیف</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">توضیحات و پاسخ متنی (اختیاری)</label>
                        <textarea class="form-control" name="text_response" rows="5" placeholder="توضیحات خود را بنویسید..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">فایل پاسخ (اختیاری)</label>
                        <input type="file" class="form-control" name="file">
                    </div>
                    <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-paper-plane me-1"></i> ثبت و ارسال</button>
                    <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-secondary">انصراف</a>
                </form>
            </div>

        <?php else: ?>
            <!-- لیست تکالیف درس -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-tasks me-2"></i> لیست تکالیف درس</h5>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>عنوان تکلیف</th>
                                <th>توضیحات</th>
                                <th>مهلت</th>
                                <th>نمره</th>
                                <th>بازخورد استاد</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($homeworks as $hw): ?>
                                <?php $is_active = strtotime($hw['deadline']) > time(); ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($hw['title']); ?></strong></td>
                                    <td class="text-muted" style="max-width: 200px;"><?php echo htmlspecialchars($hw['description'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($hw['deadline']); ?></td>
                                    <td>
                                        <?php if ($hw['grade'] !== null): ?>
                                            <span class="badge bg-success bg-opacity-20 text-success px-2 py-1"><?php echo $hw['grade']; ?> / ۲۰</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($hw['feedback'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($is_active): ?>
                                            <a href="?class_course_id=<?php echo $class_course_id; ?>&action=submit&homework_id=<?php echo $hw['id']; ?>" class="btn btn-sm btn-outline-warning border-0">
                                                <i class="fas fa-upload me-1"></i> ارسال/ویرایش
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-20 text-danger">پایان مهلت</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($homeworks)): ?>
                                <tr><td colspan="6" class="text-muted py-4">تکلیفی برای این درس یافت نشد.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="index.php" class="btn btn-outline-light btn-sm mt-4"><i class="fas fa-arrow-right me-1"></i> بازگشت به لیست درس‌ها</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>