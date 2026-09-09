<?php
session_start();
require_once '../../db.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header("Location: ../../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$homework_id = (int) ($_GET['homework_id'] ?? 0);

// دریافت دروس
if ($role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        JOIN classcourseteachers cct ON cc.id = cct.class_course_id
        WHERE cct.teacher_id = ?
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute([$user_id]);
}
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ساخت تکلیف جدید
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    
    if ($title && $class_course_id && $deadline && strtotime($deadline)) {
        $file_path = null;
        require_once '../../upload_security.php';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/homework/teachers/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['file'],
                $dir,
                ['pdf', 'docx', 'doc', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'webp', 'txt'],
                [],
                'hw_tch_'
            );
            if ($success) {
                $file_path = 'Uploads/homework/teachers/' . $filename_or_err;
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO homeworks (class_course_id, teacher_id, title, description, file_path, deadline, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$class_course_id, $user_id, $title, $description, $file_path, $deadline]);
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}

// ثبت نمره و بازخورد
if ($action === 'grade' && $homework_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = (int) ($_POST['submission_id'] ?? 0);
    $grade = (int) ($_POST['grade'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($submission_id && $grade >= 0 && $grade <= 20) {
        $stmt = $pdo->prepare("
            UPDATE homework_submissions
            SET grade = ?, feedback = ?
            WHERE id = ? AND homework_id = ?
        ");
        $stmt->execute([$grade, $feedback, $submission_id, $homework_id]);
        header("Location: ?class_course_id=$class_course_id&action=submissions&homework_id=$homework_id");
        exit;
    }
}

// حذف تکلیف با جلوگیری از IDOR
if ($action === 'delete' && $homework_id) {
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT file_path FROM homeworks WHERE id = ?");
        $stmt->execute([$homework_id]);
    } else {
        $stmt = $pdo->prepare("SELECT file_path FROM homeworks WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$homework_id, $user_id]);
    }
    $file_path = $stmt->fetchColumn();
    if ($stmt->rowCount() > 0) {
        if ($file_path && file_exists('../../' . $file_path)) {
            @unlink('../../' . $file_path);
        }
        if ($role === 'admin') {
            $pdo->prepare("DELETE FROM homeworks WHERE id = ?")->execute([$homework_id]);
        } else {
            $pdo->prepare("DELETE FROM homeworks WHERE id = ? AND teacher_id = ?")->execute([$homework_id, $user_id]);
        }
    }
    header("Location: ?class_course_id=$class_course_id");
    exit;
}

// دریافت لیست تکالیف
$homeworks = [];
if ($class_course_id) {
    $stmt = $pdo->prepare("SELECT * FROM homeworks WHERE class_course_id = ? ORDER BY created_at DESC");
    $stmt->execute([$class_course_id]);
    $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// دریافت پاسخ‌های ارسال شده دانش‌آموزان
$submissions = [];
if ($action === 'submissions' && $homework_id) {
    $stmt = $pdo->prepare("
        SELECT hs.id, hs.student_id, hs.file_path, hs.text_response, hs.grade, hs.feedback, hs.submitted_at, u.name
        FROM homework_submissions hs
        JOIN users u ON hs.student_id = u.id
        WHERE hs.homework_id = ?
        ORDER BY hs.submitted_at DESC
    ");
    $stmt->execute([$homework_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت تکالیف | سامانه یادگیری</title>
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
        <span class="fw-bold fs-5"><i class="fas fa-tasks text-primary me-2"></i> مدیریت و تصحیح تکالیف</span>
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
                                            <i class="fas fa-tasks me-1"></i> مدیریت تکالیف
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

        <?php elseif ($action === 'create'): ?>
            <!-- تعریف تکلیف جدید -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-plus-circle me-2"></i> ایجاد تکلیف جدید</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">عنوان تکلیف</label>
                        <input type="text" class="form-control" name="title" required placeholder="عنوان تکلیف را وارد کنید...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">توضیحات تکلیف</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="توضیحات و راهنمایی‌های لازم..."></textarea>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">فایل ضممیه (اختیاری)</label>
                            <input type="file" class="form-control" name="file">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">مهلت تحویل</label>
                            <input type="datetime-local" class="form-control" name="deadline" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-save me-1"></i> انتشار تکلیف</button>
                    <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-secondary">انصراف</a>
                </form>
            </div>

        <?php elseif ($action === 'submissions' && $homework_id): ?>
            <!-- بررسی پاسخ‌های تکالیف -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-user-check me-2"></i> پاسخ‌های ارسال شده دانش‌آموزان</h5>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>نام دانش‌آموز</th>
                                <th>پاسخ متنی</th>
                                <th>فایل ارسال شده</th>
                                <th>تاریخ ارسال</th>
                                <th style="width: 250px;">ثبت نمره و بازخورد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sub['name']); ?></strong></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($sub['text_response'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($sub['file_path']): ?>
                                            <a href="../../<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info border-0"><i class="fas fa-download me-1"></i> دریافت فایل</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars($sub['submitted_at']); ?></td>
                                    <td>
                                        <form method="POST" action="?class_course_id=<?php echo $class_course_id; ?>&action=grade&homework_id=<?php echo $homework_id; ?>" class="d-flex gap-1">
                                            <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                            <input type="number" name="grade" class="form-control form-control-sm" style="width: 70px;" min="0" max="20" value="<?php echo $sub['grade'] ?? ''; ?>" placeholder="نمره" required>
                                            <input type="text" name="feedback" class="form-control form-control-sm" value="<?php echo htmlspecialchars($sub['feedback'] ?? ''); ?>" placeholder="نظر...">
                                            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($submissions)): ?>
                                <tr><td colspan="5" class="text-muted py-4">هیچ پاسخی تاکنون ارسال نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- اصلاح لینک بازگشت از 404 قبلی -->
                <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-light btn-sm mt-4"><i class="fas fa-arrow-right me-1"></i> بازگشت به تکالیف</a>
            </div>

        <?php else: ?>
            <!-- لیست تکالیف -->
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="m-0 text-primary"><i class="fas fa-tasks me-2"></i> لیست تکالیف تعریف‌شده</h5>
                    <a href="?class_course_id=<?php echo $class_course_id; ?>&action=create" class="btn btn-gradient-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> تعریف تکلیف جدید
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>عنوان تکلیف</th>
                                <th>توضیحات</th>
                                <th>مهلت تحویل</th>
                                <th style="width: 180px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($homeworks as $hw): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($hw['title']); ?></strong></td>
                                    <td class="text-muted" style="max-width: 250px;"><?php echo htmlspecialchars($hw['description'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($hw['deadline']); ?></td>
                                    <td>
                                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=submissions&homework_id=<?php echo $hw['id']; ?>" class="btn btn-sm btn-outline-info border-0 me-1" title="پاسخ‌ها">
                                            <i class="fas fa-list"></i> پاسخ‌ها
                                        </a>
                                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=delete&homework_id=<?php echo $hw['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('آیا از حذف این تکلیف اطمینان دارید؟');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($homeworks)): ?>
                                <tr><td colspan="4" class="text-muted py-4">تکلیفی ثبت نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <a href="teacher.php" class="btn btn-outline-light btn-sm mt-4"><i class="fas fa-arrow-right me-1"></i> بازگشت به لیست درس‌ها</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>