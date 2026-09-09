<?php
session_start();
require_once '../../db.php';
require_once '../../vendor/tcpdf/tcpdf.php';
require_once '../../jdf.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';
$academic_year = (int) ($_GET['year'] ?? (date('Y') - 621)); 
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$student_id = (int) ($_GET['student_id'] ?? 0);

function calculate_average($grades) {
    $valid_grades = array_filter($grades, function($grade) { return $grade !== null; });
    return empty($valid_grades) ? '-' : round(array_sum($valid_grades) / count($valid_grades), 2);
}

// لیست سال‌های تحصیلی
$years = [];
$stmt = $pdo->query("SELECT DISTINCT academic_year FROM report_cards ORDER BY academic_year DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $years[] = $row['academic_year'];
}
if (empty($years)) $years = [$academic_year];

// لیست دروس
$lessons = [];
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $class_id = $stmt->fetchColumn();
    if ($class_id) {
        $stmt = $pdo->prepare("
            SELECT cc.id, cc.course_name, c.name AS class_name
            FROM classcourses cc
            JOIN classes c ON cc.class_id = c.id
            WHERE cc.class_id = ?
            ORDER BY c.name, cc.course_name
        ");
        $stmt->execute([$class_id]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} elseif ($role === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        JOIN ClassCourseTeachers cct ON cct.class_course_id = cc.id
        WHERE cct.teacher_id = ?
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute([$user_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// لیست دانش‌آموزان برای نمره‌دهی
$students = [];
if ($action === 'grade' && in_array($role, ['teacher', 'admin']) && $class_course_id) {
    if ($role === 'teacher') {
        $check = $pdo->prepare("SELECT 1 FROM ClassCourseTeachers WHERE class_course_id = ? AND teacher_id = ?");
        $check->execute([$class_course_id, $user_id]);
        if (!$check->fetch()) {
            header("Location: index.php?error=unauthorized");
            exit;
        }
    }
    $stmt = $pdo->prepare("SELECT u.id, u.name FROM users u WHERE u.role = 'student' AND u.class_id = (SELECT class_id FROM classcourses WHERE id = ?)");
    $stmt->execute([$class_course_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ذخیره نمرات
if ($action === 'save_grades' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_course_id = (int) ($_POST['class_course_id'] ?? 0);
    $academic_year = (int) ($_POST['academic_year'] ?? 0);

    if ($role === 'teacher') {
        $check = $pdo->prepare("SELECT 1 FROM ClassCourseTeachers WHERE class_course_id = ? AND teacher_id = ?");
        $check->execute([$class_course_id, $user_id]);
        if (!$check->fetch()) {
            header("Location: index.php?error=unauthorized");
            exit;
        }
    }
    
    $c1 = $_POST['first_term_continuous'] !== '' ? (float)$_POST['first_term_continuous'] : null;
    $e1 = $_POST['first_term_exam'] !== '' ? (float)$_POST['first_term_exam'] : null;
    $c2 = $_POST['second_term_continuous'] !== '' ? (float)$_POST['second_term_continuous'] : null;
    $e2 = $_POST['second_term_exam'] !== '' ? (float)$_POST['second_term_exam'] : null;

    if ($student_id && $class_course_id && $academic_year) {
        $stmt = $pdo->prepare("SELECT id FROM report_cards WHERE student_id = ? AND class_course_id = ? AND academic_year = ?");
        $stmt->execute([$student_id, $class_course_id, $academic_year]);
        $report_id = $stmt->fetchColumn();

        if ($report_id) {
            $stmt = $pdo->prepare("UPDATE report_cards SET first_term_continuous = ?, first_term_exam = ?, second_term_continuous = ?, second_term_exam = ? WHERE id = ?");
            $stmt->execute([$c1, $e1, $c2, $e2, $report_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO report_cards (student_id, class_course_id, academic_year, first_term_continuous, first_term_exam, second_term_continuous, second_term_exam) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $class_course_id, $academic_year, $c1, $e1, $c2, $e2]);
        }
        header("Location: ?action=grade&class_course_id=$class_course_id&year=$academic_year");
        exit;
    }
}

// کارنامه دانش‌آموز
$report_cards = [];
if ($role === 'student' || ($action === 'view' && in_array($role, ['teacher', 'admin']) && $student_id)) {
    $target_id = $role === 'student' ? $user_id : $student_id;
    $stmt = $pdo->prepare("
        SELECT rc.*, cc.course_name, c.name AS class_name
        FROM report_cards rc
        JOIN classcourses cc ON rc.class_course_id = cc.id
        JOIN classes c ON cc.class_id = c.id
        WHERE rc.student_id = ? AND rc.academic_year = ?
        ORDER BY cc.course_name
    ");
    $stmt->execute([$target_id, $academic_year]);
    $report_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارنامه تحصیلی | سامانه یادگیری</title>
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
        <span class="fw-bold fs-5"><i class="fas fa-graduation-cap text-primary me-2"></i> کارنامه تحصیلی و نمرات</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <!-- انتخاب سال تحصیلی -->
        <div class="card-custom mb-4">
            <div class="d-flex align-items-center gap-3">
                <label class="form-label m-0 text-muted">سال تحصیلی:</label>
                <select id="year" onchange="window.location.href='?year='+this.value" class="form-select" style="max-width: 180px;">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $academic_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <?php if ($role !== 'student'): ?>
                    <a href="?action=grade&year=<?php echo $academic_year; ?>" class="btn btn-gradient-primary btn-sm ms-auto"><i class="fas fa-edit me-1"></i> ثبت نمرات دانش‌آموزان</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($action === 'grade' && in_array($role, ['teacher', 'admin'])): ?>
            <!-- فرم ثبت نمره توسط معلم/ادمین -->
            <div class="card-custom mb-4">
                <h5 class="mb-4 text-primary"><i class="fas fa-edit me-2"></i> انتخاب درس و دانش‌آموز جهت نمره‌دهی</h5>
                <form method="GET" class="row g-3 mb-3">
                    <input type="hidden" name="year" value="<?php echo $academic_year; ?>">
                    <input type="hidden" name="action" value="grade">
                    <div class="col-md-6">
                        <label class="form-label">درس</label>
                        <select name="class_course_id" class="form-select" onchange="this.form.submit()">
                            <option value="">انتخاب درس...</option>
                            <?php foreach ($lessons as $l): ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo $l['id'] == $class_course_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($l['class_name'] . ' - ' . $l['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($class_course_id): ?>
                        <div class="col-md-6">
                            <label class="form-label">دانش‌آموز</label>
                            <select name="student_id" class="form-select" onchange="this.form.submit()">
                                <option value="">انتخاب دانش‌آموز...</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $s['id'] == $student_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </form>

                <?php if ($student_id && $class_course_id): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM report_cards WHERE student_id = ? AND class_course_id = ? AND academic_year = ?");
                    $stmt->execute([$student_id, $class_course_id, $academic_year]);
                    $report = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    ?>
                    <form method="POST" action="?action=save_grades" class="border-top border-secondary pt-4 mt-4">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="class_course_id" value="<?php echo $class_course_id; ?>">
                        <input type="hidden" name="academic_year" value="<?php echo $academic_year; ?>">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">مستمر نوبت اول</label>
                                <input type="number" step="0.25" min="0" max="20" name="first_term_continuous" class="form-control" value="<?php echo $report['first_term_continuous'] ?? ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">آزمون نوبت اول</label>
                                <input type="number" step="0.25" min="0" max="20" name="first_term_exam" class="form-control" value="<?php echo $report['first_term_exam'] ?? ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">مستمر نوبت دوم</label>
                                <input type="number" step="0.25" min="0" max="20" name="second_term_continuous" class="form-control" value="<?php echo $report['second_term_continuous'] ?? ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">آزمون نوبت دوم</label>
                                <input type="number" step="0.25" min="0" max="20" name="second_term_exam" class="form-control" value="<?php echo $report['second_term_exam'] ?? ''; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-save me-1"></i> ثبت نمرات</button>
                    </form>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- نمایش جدول کارنامه -->
            <div class="card-custom p-0 mb-4">
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>عنوان درس</th>
                                <th>مستمر نوبت اول</th>
                                <th>نوبت اول</th>
                                <th>مستمر نوبت دوم</th>
                                <th>نوبت دوم</th>
                                <th>معدل درس</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_cards as $rc): ?>
                                <?php
                                $grades = [$rc['first_term_continuous'], $rc['first_term_exam'], $rc['second_term_continuous'], $rc['second_term_exam']];
                                $avg = calculate_average($grades);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rc['course_name']); ?></strong></td>
                                    <td><?php echo $rc['first_term_continuous'] ?? '-'; ?></td>
                                    <td><?php echo $rc['first_term_exam'] ?? '-'; ?></td>
                                    <td><?php echo $rc['second_term_continuous'] ?? '-'; ?></td>
                                    <td><?php echo $rc['second_term_exam'] ?? '-'; ?></td>
                                    <td><span class="badge bg-primary bg-opacity-20 text-info px-2 py-1"><?php echo $avg; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($report_cards)): ?>
                                <tr><td colspan="6" class="text-muted py-4">کارنامه‌ای برای این سال تحصیلی ثبت نشده است.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>