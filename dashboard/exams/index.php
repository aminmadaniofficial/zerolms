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
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$exam_id = (int) ($_GET['exam_id'] ?? 0);

/**
 * Format timestamp to Jalali date time string.
 * 
 * @param string $datetime
 * @return string
 */
function to_jalali($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    return jdate('Y/m/d H:i', $timestamp);
}

$class_id = null;
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT class_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $class_id = $stmt->fetchColumn();
}

$lessons = [];
if ($role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'teacher') {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        JOIN classcourseteachers cct ON cc.id = cct.class_course_id
        WHERE cct.teacher_id = ?
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute([$user_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
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
}

// Create Exam Action
if ($action === 'create' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $question_count = (int) ($_POST['question_count'] ?? 0);
    $duration = (int) ($_POST['duration'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    
    if ($title && $question_count > 0 && $duration > 0 && $class_course_id && strtotime($deadline)) {
        $file_path = null;
        require_once '../../upload_security.php';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/exams/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['file'],
                $dir,
                ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
                [],
                'exam_'
            );
            if ($success) {
                $file_path = 'Uploads/exams/' . $filename_or_err;
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO exams (class_course_id, teacher_id, title, question_count, file_path, duration, deadline)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$class_course_id, $user_id, $title, $question_count, $file_path, $duration, $deadline]);
        $exam_id = $pdo->lastInsertId();
        
        for ($i = 1; $i <= $question_count; $i++) {
            $correct_answer = $_POST["correct_answer_$i"] ?? '1';
            $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_number, correct_answer) VALUES (?, ?, ?)");
            $stmt->execute([$exam_id, $i, $correct_answer]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}

// Edit Exam Action
if ($action === 'edit' && in_array($role, ['teacher', 'admin']) && $exam_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $question_count = (int) ($_POST['question_count'] ?? 0);
    $duration = (int) ($_POST['duration'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    if ($title && $question_count > 0 && $duration > 0 && strtotime($deadline)) {
        $file_path = $_POST['existing_file'] ?? null;
        require_once '../../upload_security.php';
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/exams/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['file'],
                $dir,
                ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
                [],
                'exam_'
            );
            if ($success) {
                $file_path = 'Uploads/exams/' . $filename_or_err;
                if (!empty($_POST['existing_file']) && file_exists('../../' . $_POST['existing_file'])) {
                    @unlink('../../' . $_POST['existing_file']);
                }
            }
        }
        $query = "UPDATE exams SET title = ?, question_count = ?, file_path = ?, duration = ?, deadline = ? WHERE id = ?";
        $params = [$title, $question_count, $file_path, $duration, $deadline, $exam_id];
        if ($role !== 'admin') {
            $query .= " AND teacher_id = ?";
            $params[] = $user_id;
        }
        $pdo->prepare($query)->execute($params);
        $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$exam_id]);
        
        for ($i = 1; $i <= $question_count; $i++) {
            $correct_answer = $_POST["correct_answer_$i"] ?? '1';
            $pdo->prepare("INSERT INTO exam_questions (exam_id, question_number, correct_answer) VALUES (?, ?, ?)")->execute([$exam_id, $i, $correct_answer]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}

// Delete Exam Action (IDOR Protected)
if ($action === 'delete' && in_array($role, ['teacher', 'admin']) && $exam_id) {
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT file_path FROM exams WHERE id = ?");
        $stmt->execute([$exam_id]);
    } else {
        $stmt = $pdo->prepare("SELECT file_path FROM exams WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$exam_id, $user_id]);
    }
    $file_path = $stmt->fetchColumn();
    if ($stmt->rowCount() > 0) {
        if ($file_path && file_exists('../../' . $file_path)) {
            @unlink('../../' . $file_path);
        }
        if ($role === 'admin') {
            $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$exam_id]);
        } else {
            $pdo->prepare("DELETE FROM exams WHERE id = ? AND teacher_id = ?")->execute([$exam_id, $user_id]);
        }
    }
    header("Location: ?class_course_id=$class_course_id");
    exit;
}

// Student Submission & Automatic Grading Logic
if ($action === 'submit' && $role === 'student' && $exam_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT duration, deadline FROM exams WHERE id = ? AND class_course_id = ?");
    $stmt->execute([$exam_id, $class_course_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exam && time() <= strtotime($exam['deadline'])) {
        $answers = [];
        for ($i = 1; $i <= (int) ($_POST['question_count'] ?? 0); $i++) {
            $answers[$i] = $_POST["answer_$i"] ?? null;
        }
        $answers_json = json_encode($answers);
        
        $stmt = $pdo->prepare("SELECT id FROM exam_submissions WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$exam_id, $user_id]);
        $submission_id = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT question_number, correct_answer FROM exam_questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $correct_count = 0;
        foreach ($questions as $q) {
            if (isset($answers[$q['question_number']]) && $answers[$q['question_number']] === $q['correct_answer']) {
                $correct_count++;
            }
        }
        $grade = count($questions) > 0 ? ($correct_count / count($questions)) * 20 : 0;
        
        if ($submission_id) {
            $pdo->prepare("UPDATE exam_submissions SET answers = ?, grade = ?, submitted_at = NOW() WHERE id = ?")->execute([$answers_json, $grade, $submission_id]);
        } else {
            $pdo->prepare("INSERT INTO exam_submissions (exam_id, student_id, answers, grade, submitted_at) VALUES (?, ?, ?, ?, NOW())")->execute([$exam_id, $user_id, $answers_json, $grade]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}

// Query Exams List & Student Submission State
$exams = [];
$current_exam = null;
$current_submission = null;
if ($class_course_id) {
    if ($role === 'student') {
        $query = "SELECT e.id, e.title, e.question_count, e.file_path, e.duration, e.created_at, e.deadline,
                         es.id AS submission_id, es.grade, es.submitted_at
                  FROM exams e
                  LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
                  WHERE e.class_course_id = ?
                  ORDER BY e.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $class_course_id]);
    } else {
        $query = "SELECT id, title, question_count, file_path, duration, created_at, deadline FROM exams WHERE class_course_id = ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$class_course_id]);
    }
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'take' && $role === 'student' && $exam_id) {
        $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND class_course_id = ?");
        $stmt->execute([$exam_id, $class_course_id]);
        $current_exam = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM exam_submissions WHERE exam_id = ? AND student_id = ?");
        $stmt->execute([$exam_id, $user_id]);
        $current_submission = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role === 'student' ? 'آزمون‌های من' : 'مدیریت آزمون‌ها'; ?> | سامانه یادگیری</title>
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
        <span class="fw-bold fs-5"><i class="fas fa-file-signature text-primary me-2"></i> <?php echo $role === 'student' ? 'آزمون‌های من' : 'مدیریت آزمون‌ها'; ?></span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if (!$class_course_id): ?>
            <!-- Course Selection -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-book me-2"></i> درس مورد نظر خود را انتخاب کنید</h5>
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
                                            <i class="fas fa-tasks me-1"></i> مشاهده آزمون‌ها
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

        <?php elseif ($action === 'create' && in_array($role, ['teacher', 'admin'])): ?>
            <!-- Create Exam View -->
            <div class="card-custom">
                <h5 class="mb-4 text-primary"><i class="fas fa-plus-circle me-2"></i> ایجاد آزمون جدید</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">عنوان آزمون</label>
                            <input type="text" class="form-control" name="title" required placeholder="مثلاً: آزمون میان‌ترم زیست">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">تعداد سوالات</label>
                            <input type="number" class="form-control" id="question_count" name="question_count" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">مدت زمان (دقیقه)</label>
                            <input type="number" class="form-control" name="duration" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">مهلت ارسال</label>
                            <input type="datetime-local" class="form-control" name="deadline" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">فایل سوالات (PDF یا تصویر)</label>
                            <input type="file" class="form-control" name="file" accept=".pdf,image/*">
                        </div>
                    </div>
                    <div id="questions-container" class="mb-4"></div>
                    <button type="submit" class="btn btn-gradient-primary"><i class="fas fa-save me-1"></i> ذخیره آزمون</button>
                    <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-secondary">انصراف</a>
                </form>
            </div>
            <script>
                // Dynamic Answer Key Generator Controls
                document.getElementById('question_count').addEventListener('change', function () {
                    const count = parseInt(this.value) || 0;
                    const container = document.getElementById('questions-container');
                    container.innerHTML = '<h6 class="mt-4 mb-3 text-warning">کلید سوالات (پاسخ‌های صحیح):</h6>';
                    for (let i = 1; i <= count; i++) {
                        container.innerHTML += `
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <span style="min-width: 80px;">سوال ${i}:</span>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="correct_answer_${i}" id="btn_${i}_1" value="1" checked>
                                    <label class="btn btn-outline-light btn-sm" for="btn_${i}_1">۱</label>
                                    <input type="radio" class="btn-check" name="correct_answer_${i}" id="btn_${i}_2" value="2">
                                    <label class="btn btn-outline-light btn-sm" for="btn_${i}_2">۲</label>
                                    <input type="radio" class="btn-check" name="correct_answer_${i}" id="btn_${i}_3" value="3">
                                    <label class="btn btn-outline-light btn-sm" for="btn_${i}_3">۳</label>
                                    <input type="radio" class="btn-check" name="correct_answer_${i}" id="btn_${i}_4" value="4">
                                    <label class="btn btn-outline-light btn-sm" for="btn_${i}_4">۴</label>
                                </div>
                            </div>`;
                    }
                });
            </script>

        <?php elseif ($action === 'take' && $role === 'student' && $current_exam): ?>
            <!-- Student Exam Taking View -->
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="m-0 text-primary"><i class="fas fa-pen-alt me-2"></i> <?php echo htmlspecialchars($current_exam['title']); ?></h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-info p-2"><i class="fas fa-clock me-1"></i> مدت زمان: <?php echo $current_exam['duration']; ?> دقیقه</span>
                        <span class="badge bg-warning p-2 text-dark"><i class="fas fa-calendar-alt me-1"></i> مهلت: <?php echo to_jalali($current_exam['deadline']); ?></span>
                    </div>
                </div>

                <?php if ($current_submission): ?>
                    <div class="alert alert-success p-4 text-center">
                        <h5><i class="fas fa-check-circle me-2"></i> شما قبلاً در این آزمون شرکت کرده‌اید.</h5>
                        <p class="m-0 mt-2 fs-5">نمره شما: <strong><?php echo round($current_submission['grade'], 2); ?> از ۲۰</strong></p>
                        <small class="text-muted d-block mt-1">تاریخ ارسال: <?php echo to_jalali($current_submission['submitted_at']); ?></small>
                        <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-light btn-sm mt-3">بازگشت به لیست آزمون‌ها</a>
                    </div>
                <?php elseif (time() > strtotime($current_exam['deadline'])): ?>
                    <div class="alert alert-danger p-4 text-center">
                        <h5><i class="fas fa-times-circle me-2"></i> مهلت ارسال پاسخنامه به پایان رسیده است.</h5>
                        <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-light btn-sm mt-3">بازگشت به لیست آزمون‌ها</a>
                    </div>
                <?php else: ?>
                    <?php if ($current_exam['file_path']): ?>
                        <div class="alert alert-dark border d-flex justify-content-between align-items-center mb-4">
                            <span><i class="fas fa-file-pdf me-2 text-danger"></i> فایل سوالات آزمون ضمیمه شده است:</span>
                            <a href="../../<?php echo htmlspecialchars($current_exam['file_path']); ?>" target="_blank" class="btn btn-gradient-primary btn-sm">
                                <i class="fas fa-download me-1"></i> دانلود دفترچه سوالات
                            </a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="?class_course_id=<?php echo $class_course_id; ?>&action=submit&exam_id=<?php echo $current_exam['id']; ?>">
                        <input type="hidden" name="question_count" value="<?php echo $current_exam['question_count']; ?>">
                        <h6 class="text-warning mb-3"><i class="fas fa-th-list me-1"></i> لطفاً برای هر سوال، گزینه صحیح را انتخاب کنید:</h6>
                        <div class="row g-3 mb-4">
                            <?php for ($i = 1; $i <= (int)$current_exam['question_count']; $i++): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 border rounded bg-dark d-flex align-items-center justify-content-between">
                                        <span class="fw-bold">سوال <?php echo $i; ?>:</span>
                                        <div class="btn-group" role="group">
                                            <?php for ($opt = 1; $opt <= 4; $opt++): ?>
                                                <input type="radio" class="btn-check" name="answer_<?php echo $i; ?>" id="ans_<?php echo $i; ?>_<?php echo $opt; ?>" value="<?php echo $opt; ?>">
                                                <label class="btn btn-outline-light btn-sm" for="ans_<?php echo $i; ?>_<?php echo $opt; ?>"><?php echo $opt; ?></label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gradient-primary" onclick="return confirm('آیا از ثبت نهایی پاسخ‌های خود اطمینان دارید؟');">
                                <i class="fas fa-check me-1"></i> ثبت نهایی پاسخ‌ها و دریافت نمره
                            </button>
                            <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-outline-secondary">انصراف</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Exams List Grid -->
            <div class="card-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="m-0 text-primary"><i class="fas fa-list-alt me-2"></i> لیست آزمون‌های این درس</h5>
                    <?php if (in_array($role, ['teacher', 'admin'])): ?>
                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=create" class="btn btn-gradient-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> ایجاد آزمون جدید
                        </a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>عنوان آزمون</th>
                                <th>تعداد سوال</th>
                                <th>زمان (دقیقه)</th>
                                <th>مهلت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $ex): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ex['title']); ?></strong></td>
                                    <td><?php echo $ex['question_count']; ?></td>
                                    <td><?php echo $ex['duration']; ?></td>
                                    <td><?php echo to_jalali($ex['deadline']); ?></td>
                                    <td>
                                        <?php if (in_array($role, ['teacher', 'admin'])): ?>
                                            <a href="?class_course_id=<?php echo $class_course_id; ?>&action=delete&exam_id=<?php echo $ex['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('آیا از حذف آزمون اطمینان دارید؟');"><i class="fas fa-trash"></i></a>
                                        <?php elseif ($role === 'student'): ?>
                                            <?php if (!empty($ex['submission_id'])): ?>
                                                <span class="badge bg-success py-2 px-3"><i class="fas fa-check-circle me-1"></i> ثبت شده | نمره: <?php echo round($ex['grade'], 2); ?> از ۲۰</span>
                                            <?php elseif (time() > strtotime($ex['deadline'])): ?>
                                                <span class="badge bg-secondary py-2 px-3"><i class="fas fa-clock me-1"></i> مهلت پایان یافته</span>
                                            <?php else: ?>
                                                <a href="?class_course_id=<?php echo $class_course_id; ?>&action=take&exam_id=<?php echo $ex['id']; ?>" class="btn btn-gradient-primary btn-sm">
                                                    <i class="fas fa-pen-alt me-1"></i> شرکت در آزمون
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($exams)): ?>
                                <tr><td colspan="5" class="text-muted py-4">آزمونی برای این درس ثبت نشده است.</td></tr>
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