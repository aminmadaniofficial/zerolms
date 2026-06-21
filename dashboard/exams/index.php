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
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$exam_id = (int) ($_GET['exam_id'] ?? 0);


function to_jalali($datetime)
{
    if (!$datetime)
        return '-';
    $timestamp = strtotime($datetime);
    $jalali = jdate('Y/m/d H:i', $timestamp);
    return $jalali;
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


if ($action === 'create' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $question_count = (int) ($_POST['question_count'] ?? 0);
    $duration = (int) ($_POST['duration'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    if ($title && $question_count > 0 && $duration > 0 && $class_course_id && strtotime($deadline)) {
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
            $file_path = '../../Uploads/exams/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        }
        $stmt = $pdo->prepare("
            INSERT INTO exams (class_course_id, teacher_id, title, question_count, file_path, duration, deadline)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$class_course_id, $user_id, $title, $question_count, $file_path, $duration, $deadline]);
        $exam_id = $pdo->lastInsertId();
        
        for ($i = 1; $i <= $question_count; $i++) {
            $correct_answer = $_POST["correct_answer_$i"] ?? '1';
            $stmt = $pdo->prepare("
                INSERT INTO exam_questions (exam_id, question_number, correct_answer)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$exam_id, $i, $correct_answer]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


if ($action === 'edit' && in_array($role, ['teacher', 'admin']) && $exam_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $question_count = (int) ($_POST['question_count'] ?? 0);
    $duration = (int) ($_POST['duration'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    if ($title && $question_count > 0 && $duration > 0 && strtotime($deadline)) {
        $file_path = $_POST['existing_file'] ?? null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
            $file_path = '../../Uploads/exams/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
            if ($_POST['existing_file']) {
                unlink($_POST['existing_file']);
            }
        }
        $query = "UPDATE exams SET title = ?, question_count = ?, file_path = ?, duration = ?, deadline = ? WHERE id = ?";
        $params = [$title, $question_count, $file_path, $duration, $deadline, $exam_id];
        if ($role !== 'admin') {
            $query .= " AND teacher_id = ?";
            $params[] = $user_id;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $stmt = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        for ($i = 1; $i <= $question_count; $i++) {
            $correct_answer = $_POST["correct_answer_$i"] ?? '1';
            $stmt = $pdo->prepare("
                INSERT INTO exam_questions (exam_id, question_number, correct_answer)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$exam_id, $i, $correct_answer]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


if ($action === 'delete' && in_array($role, ['teacher', 'admin']) && $exam_id) {
    $query = "SELECT file_path FROM exams WHERE id = ?";
    $params = [$exam_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $file_path = $stmt->fetchColumn();
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }
    $query = "DELETE FROM exams WHERE id = ?";
    $params = [$exam_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    header("Location: ?class_course_id=$class_course_id");
    exit;
}


if ($action === 'grade' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = (int) ($_POST['submission_id'] ?? 0);
    $grade = (int) ($_POST['grade'] ?? 0);
    if ($submission_id && $grade >= 0 && $grade <= 20) {
        $stmt = $pdo->prepare("
            UPDATE exam_submissions
            SET grade = ?
            WHERE id = ? AND exam_id = ?
        ");
        $stmt->execute([$grade, $submission_id, $exam_id]);
        header("Location: ?class_course_id=$class_course_id&action=submissions&exam_id=$exam_id");
        exit;
    }
}


if ($action === 'submit' && $role === 'student' && $exam_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        SELECT duration, deadline FROM exams WHERE id = ? AND class_course_id = ?
    ");
    $stmt->execute([$exam_id, $class_course_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exam && time() <= strtotime($exam['deadline'])) {
        $answers = [];
        for ($i = 1; $i <= (int) ($_POST['question_count'] ?? 0); $i++) {
            $answers[$i] = $_POST["answer_$i"] ?? null;
        }
        $answers_json = json_encode($answers);
        $stmt = $pdo->prepare("
            SELECT id FROM exam_submissions WHERE exam_id = ? AND student_id = ?
        ");
        $stmt->execute([$exam_id, $user_id]);
        $submission_id = $stmt->fetchColumn();
        $stmt = $pdo->prepare("
            SELECT question_number, correct_answer FROM exam_questions WHERE exam_id = ?
        ");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $correct_count = 0;
        foreach ($questions as $q) {
            if (isset($answers[$q['question_number']]) && $answers[$q['question_number']] === $q['correct_answer']) {
                $correct_count++;
            }
        }
        $grade = ($correct_count / count($questions)) * 20;
        if ($submission_id) {
            $stmt = $pdo->prepare("
                UPDATE exam_submissions
                SET answers = ?, grade = ?, submitted_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$answers_json, $grade, $submission_id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO exam_submissions (exam_id, student_id, answers, grade, submitted_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$exam_id, $user_id, $answers_json, $grade]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


$exams = [];
if ($class_course_id) {
    $query = "SELECT id, title, question_count, file_path, duration, created_at, deadline FROM exams WHERE class_course_id = ?";
    $params = [$class_course_id];
    if ($role === 'teacher') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$submissions = [];
if ($action === 'submissions' && $exam_id && in_array($role, ['teacher', 'admin'])) {
    $stmt = $pdo->prepare("
        SELECT es.id, es.student_id, es.answers, es.grade, es.submitted_at, u.name
        FROM exam_submissions es
        JOIN users u ON es.student_id = u.id
        WHERE es.exam_id = ?
        ORDER BY es.submitted_at DESC
    ");
    $stmt->execute([$exam_id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$edit_exam = null;
$edit_questions = [];
if ($action === 'edit' && $exam_id && in_array($role, ['teacher', 'admin'])) {
    $query = "SELECT * FROM exams WHERE id = ?";
    $params = [$exam_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $edit_exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_exam) {
        $stmt = $pdo->prepare("SELECT question_number, correct_answer FROM exam_questions WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $edit_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


$exam = null;
$submission = [];
$time_left = 0;
if ($action === 'take' && $role === 'student' && $exam_id) {
    $stmt = $pdo->prepare("
        SELECT e.*, es.answers, es.submitted_at
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id AND es.student_id = ?
        WHERE e.id = ? AND e.class_course_id = ?
    ");
    $stmt->execute([$user_id, $exam_id, $class_course_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exam) {
        $submission = $exam['answers'] ? json_decode($exam['answers'], true) : [];
        if (!$exam['submitted_at'] && time() <= strtotime($exam['deadline'])) {
            $time_left = $exam['duration'] * 60; 
        }
    }
}



$rankings = [];
$user_ranking = null;
if ($action === 'rankings' && $role === 'student' && $exam_id) {
    $stmt = $pdo->prepare("SELECT deadline FROM exams WHERE id = ? AND class_course_id = ?");
    $stmt->execute([$exam_id, $class_course_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($exam && time() > strtotime($exam['deadline'])) {
        
        $stmt = $pdo->prepare("
            SELECT u.name, es.grade, es.submitted_at
            FROM exam_submissions es
            JOIN users u ON es.student_id = u.id
            WHERE es.exam_id = ?
            ORDER BY es.grade DESC, es.submitted_at ASC
            LIMIT 10
        ");
        $stmt->execute([$exam_id]);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        
        $current_rank = 1;
        $last_grade = null;
        $last_submitted_at = null;
        foreach ($rankings as &$r) {
            if ($last_grade !== null && ($r['grade'] < $last_grade || ($r['grade'] == $last_grade && $r['submitted_at'] > $last_submitted_at))) {
                $current_rank++;
            }
            $r['rank'] = $current_rank;
            $last_grade = $r['grade'];
            $last_submitted_at = $r['submitted_at'];
        }
        unset($r); 

        
        $stmt = $pdo->prepare("
            SELECT u.name, es.grade, es.submitted_at
            FROM exam_submissions es
            JOIN users u ON es.student_id = u.id
            WHERE es.exam_id = ? AND es.student_id = ?
        ");
        $stmt->execute([$exam_id, $user_id]);
        $user_ranking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_ranking) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as rank_number
                FROM exam_submissions es
                WHERE es.exam_id = ? AND 
                      (es.grade > ? OR (es.grade = ? AND es.submitted_at < ?))
            ");
            $stmt->execute([$exam_id, $user_ranking['grade'], $user_ranking['grade'], $user_ranking['submitted_at']]);
            $user_ranking['rank'] = $stmt->fetchColumn();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role === 'student' ? 'آزمون‌های من' : 'مدیریت آزمون‌ها'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <link rel="stylesheet" href="../assets/style.css">

    <style>

        .answer-circle {
            @apply inline-block w-10 h-10 leading-10 text-center border-2 border-gray-800 -full mx-2 cursor-pointer transition-all duration-200;
        }

        .answer-circle input:checked+span {
            @apply bg-blue-600 text-white;
        }

        .pdf-error {
            @apply text-red-500 text-center mt-4;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 98%;
            right: 1%;
            margin: auto;
            text-align: center;
            padding: 12px 0;
            background-color: #1e1e1eff;
            color: #ffffff;
            font-weight: bold;
            box-shadow: 0 -2px 10px rgba(1, 238, 255, 0.5);
            border-radius: 12px 12px 0 0;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-4 sm:p-6 md:p-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">
            <?php echo $role === 'student' ? 'آزمون‌های من' : 'مدیریت آزمون‌ها'; ?></h2>

        <?php if (!$class_course_id): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">درس‌های شما</h3>
            <?php if ($role === 'student' && !$class_id): ?>
                <p class="text-red-500 mb-4">شما به هیچ کلاسی اختصاص داده نشده‌اید.</p>
            <?php endif; ?>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md -lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">نام درس</th>
                            <th class="py-3 px-4 text-right">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $lesson): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <?php echo htmlspecialchars($lesson['class_name'] . ' - ' . $lesson['course_name']); ?></td>
                                <td class="py-3 px-4">
                                    <a href="?class_course_id=<?php echo $lesson['id']; ?>"
                                        class="inline-block bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition">
                                        <i class="fas fa-tasks"></i> آزمون‌ها
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lessons)): ?>
                            <tr>
                                <td colspan="2" class="text-center py-4">هیچ درسی یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="../index.php"
                class="mt-6 inline-block bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>

        <?php elseif ($action === 'create' && in_array($role, ['teacher', 'admin'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">ایجاد آزمون جدید</h3>
            <form method="POST" enctype="multipart/form-data" class="bg-blue p-6 -lg shadow-md">
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 font-medium">عنوان آزمون</label>
                    <input type="text" class="w-full border  p-2 mt-1" id="title" name="title" required>
                </div>
                <div class="mb-4">
                    <label for="question_count" class="block text-gray-700 font-medium">تعداد سوالات</label>
                    <input type="number" class="w-full border  p-2 mt-1" id="question_count" name="question_count"
                        min="1" required>
                </div>
                <div class="mb-4">
                    <label for="duration" class="block text-gray-700 font-medium">مدت زمان (دقیقه)</label>
                    <input type="number" class="w-full border  p-2 mt-1" id="duration" name="duration" min="1"
                        required>
                </div>
                <div class="mb-4">
                    <label for="deadline" class="block text-gray-700 font-medium">مهلت ارسال (میلادی)</label>
                    <input type="datetime-local" class="w-full border  p-2 mt-1" id="deadline" name="deadline"
                        required>
                </div>
                <div class="mb-4">
                    <label for="file" class="block text-gray-700 font-medium">فایل سوالات (PDF/تصویر)</label>
                    <input type="file" class="w-full border  p-2 mt-1" id="file" name="file" accept=".pdf,image/*">
                </div>
                <div id="questions-container" class="mb-4"></div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i> ذخیره
                </button>
                <a href="?class_course_id=<?php echo $class_course_id; ?>"
                    class="bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                    <i class="fas fa-times"></i> لغو
                </a>
            </form>
            <script>
                document.getElementById('question_count').addEventListener('change', function () {
                    const count = parseInt(this.value) || 0;
                    const container = document.getElementById('questions-container');
                    container.innerHTML = '';
                    for (let i = 1; i <= count; i++) {
                        container.innerHTML += `
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium">پاسخ صحیح سوال ${i}</label>
                                <div class="flex gap-2">
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="1" checked><span class="block w-full h-full">1</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="2"><span class="block w-full h-full">2</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="3"><span class="block w-full h-full">3</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="4"><span class="block w-full h-full">4</span></label>
                                </div>
                            </div>`;
                    }
                });
            </script>

        <?php elseif ($action === 'edit' && $edit_exam && in_array($role, ['teacher', 'admin'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">ویرایش آزمون</h3>
            <form method="POST" enctype="multipart/form-data" class="bg-blue p-6 -lg shadow-md">
                <div class="mb-4">
                    <label for="title" class="block text-gray-700 font-medium">عنوان آزمون</label>
                    <input type="text" class="w-full border  p-2 mt-1" id="title" name="title"
                        value="<?php echo htmlspecialchars($edit_exam['title']); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="question_count" class="block text-gray-700 font-medium">تعداد سوالات</label>
                    <input type="number" class="w-full border  p-2 mt-1" id="question_count" name="question_count"
                        value="<?php echo $edit_exam['question_count']; ?>" min="1" required>
                </div>
                <div class="mb-4">
                    <label for="duration" class="block text-gray-700 font-medium">مدت زمان (دقیقه)</label>
                    <input type="number" class="w-full border  p-2 mt-1" id="duration" name="duration"
                        value="<?php echo $edit_exam['duration']; ?>" min="1" required>
                </div>
                <div class="mb-4">
                    <label for="deadline" class="block text-gray-700 font-medium">مهلت ارسال (میلادی)</label>
                    <input type="datetime-local" class="w-full border  p-2 mt-1" id="deadline" name="deadline"
                        value="<?php echo date('Y-m-d\TH:i', strtotime($edit_exam['deadline'])); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="file" class="block text-gray-700 font-medium">فایل سوالات (PDF/تصویر)</label>
                    <input type="file" class="w-full border  p-2 mt-1" id="file" name="file" accept=".pdf,image/*">
                    <?php if ($edit_exam['file_path']): ?>
                        <p class="mt-2">فایل فعلی: <a href="<?php echo $edit_exam['file_path']; ?>" target="_blank"
                                class="text-blue-600 hover:underline"><?php echo basename($edit_exam['file_path']); ?></a></p>
                        <input type="hidden" name="existing_file" value="<?php echo $edit_exam['file_path']; ?>">
                    <?php endif; ?>
                </div>
                <div id="questions-container" class="mb-4">
                    <?php foreach ($edit_questions as $q): ?>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium">پاسخ صحیح سوال
                                <?php echo $q['question_number']; ?></label>
                            <div class="flex gap-2">
                                <label class="answer-circle"><input type="radio"
                                        name="correct_answer_<?php echo $q['question_number']; ?>" value="1" <?php echo $q['correct_answer'] === '1' ? 'checked' : ''; ?>><span
                                        class="block w-full h-full">1</span></label>
                                <label class="answer-circle"><input type="radio"
                                        name="correct_answer_<?php echo $q['question_number']; ?>" value="2" <?php echo $q['correct_answer'] === '2' ? 'checked' : ''; ?>><span
                                        class="block w-full h-full">2</span></label>
                                <label class="answer-circle"><input type="radio"
                                        name="correct_answer_<?php echo $q['question_number']; ?>" value="3" <?php echo $q['correct_answer'] === '3' ? 'checked' : ''; ?>><span
                                        class="block w-full h-full">3</span></label>
                                <label class="answer-circle"><input type="radio"
                                        name="correct_answer_<?php echo $q['question_number']; ?>" value="4" <?php echo $q['correct_answer'] === '4' ? 'checked' : ''; ?>><span
                                        class="block w-full h-full">4</span></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i> ذخیره
                </button>
                <a href="?class_course_id=<?php echo $class_course_id; ?>"
                    class="bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                    <i class="fas fa-times"></i> لغو
                </a>
            </form>
            <script>
                document.getElementById('question_count').addEventListener('change', function () {
                    const count = parseInt(this.value) || 0;
                    const container = document.getElementById('questions-container');
                    container.innerHTML = '';
                    for (let i = 1; i <= count; i++) {
                        container.innerHTML += `
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium">پاسخ صحیح سوال ${i}</label>
                                <div class="flex gap-2">
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="1" checked><span class="block w-full h-full">1</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="2"><span class="block w-full h-full">2</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="3"><span class="block w-full h-full">3</span></label>
                                    <label class="answer-circle"><input type="radio" name="correct_answer_${i}" value="4"><span class="block w-full h-full">4</span></label>
                                </div>
                            </div>`;
                    }
                });
            </script>

        <?php elseif ($action === 'submissions' && $exam_id && in_array($role, ['teacher', 'admin'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">پاسخ‌های آزمون</h3>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md -lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">دانش‌آموز</th>
                            <th class="py-3 px-4 text-right">پاسخ‌ها</th>
                            <th class="py-3 px-4 text-right">نمره</th>
                            <th class="py-3 px-4 text-right">تاریخ ارسال</th>
                            <th class="py-3 px-4 text-right">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $s): ?>
                            <?php $answers = json_decode($s['answers'] ?? '[]', true); ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($s['name']); ?></td>
                                <td class="py-3 px-4">
                                    <?php
                                    foreach ($answers as $q => $a) {
                                        echo "سوال $q: $a<br>";
                                    }
                                    ?>
                                </td>
                                <td class="py-3 px-4"><?php echo $s['grade'] !== null ? $s['grade'] : '-'; ?></td>
                                <td class="py-3 px-4"><?php echo to_jalali($s['submitted_at']); ?></td>
                                <td class="py-3 px-4">
                                    <form method="POST"
                                        action="?class_course_id=<?php echo $class_course_id; ?>&action=grade&exam_id=<?php echo $exam_id; ?>"
                                        class="flex gap-2">
                                        <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                                        <input type="number" name="grade" class="border  p-2 w-20" min="0" max="20"
                                            value="<?php echo $s['grade'] ?? ''; ?>" placeholder="نمره">
                                        <button type="submit"
                                            class="bg-blue-600 text-white px-3 py-1  hover:bg-blue-700 transition">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">هیچ پاسخی یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="?class_course_id=<?php echo $class_course_id; ?>"
                class="mt-6 inline-block bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>

        <?php elseif ($action === 'take' && $role === 'student' && $exam && !$exam['submitted_at'] && $time_left > 0): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4"><?php echo htmlspecialchars($exam['title']); ?></h3>
            <div class="timer text-lg font-bold text-red-600 mb-4" id="timer"></div>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2">
                    <?php if ($exam['file_path'] && file_exists($exam['file_path'])): ?>
                        <?php if (pathinfo($exam['file_path'], PATHINFO_EXTENSION) === 'pdf'): ?>
                            <canvas id="pdf-canvas" class="w-full h-[600px] border "></canvas>
                            <div class="mt-2 flex gap-2 justify-center">
                                <button class="bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition"
                                    onclick="prevPage()">صفحه قبلی</button>
                                <span id="page-num"></span> / <span id="page-count"></span>
                                <button class="bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition"
                                    onclick="nextPage()">صفحه بعدی</button>
                            </div>
                            <script>
                                pdfjsLib.getDocument('<?php echo $exam['file_path']; ?>').promise.then(pdf => {
                                    window.pdfDoc = pdf;
                                    document.getElementById('page-count').textContent = pdf.numPages;
                                    renderPage(1);
                                }).catch(error => {
                                    console.error('PDF Load Error:', error);
                                    document.querySelector('.pdf-container').innerHTML += '<p class="pdf-error">خطا در بارگذاری PDF: فایل ممکن است خراب یا غیرقابل دسترس باشد.</p>';
                                });
                                let currentPage = 1;
                                function renderPage(num) {
                                    window.pdfDoc.getPage(num).then(page => {
                                        const canvas = document.getElementById('pdf-canvas');
                                        const ctx = canvas.getContext('2d');
                                        const viewport = page.getViewport({ scale: 1 });
                                        canvas.height = viewport.height;
                                        canvas.width = viewport.width;
                                        page.render({ canvasContext: ctx, viewport: viewport });
                                        document.getElementById('page-num').textContent = num;
                                    }).catch(error => {
                                        console.error('PDF Render Error:', error);
                                    });
                                    currentPage = num;
                                }
                                function prevPage() {
                                    if (currentPage > 1) renderPage(currentPage - 1);
                                }
                                function nextPage() {
                                    if (currentPage < window.pdfDoc.numPages) renderPage(currentPage + 1);
                                }
                            </script>
                        <?php else: ?>
                            <img src="<?php echo $exam['file_path']; ?>" class="w-full " alt="Exam Image">
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="pdf-error">فایل سوالات یافت نشد یا غیرقابل دسترس است.</p>
                    <?php endif; ?>
                </div>
                <div class="w-full md:w-1/2 p-4 bg-blue -lg shadow-md">
                    <form method="POST"
                        action="?class_course_id=<?php echo $class_course_id; ?>&action=submit&exam_id=<?php echo $exam_id; ?>">
                        <input type="hidden" name="question_count" value="<?php echo $exam['question_count']; ?>">
                        <?php for ($i = 1; $i <= $exam['question_count']; $i++): ?>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium">سوال <?php echo $i; ?></label>
                                <div class="flex gap-2">
                                    <label class="answer-circle"><input type="radio" name="answer_<?php echo $i; ?>" value="1"
                                            <?php echo isset($submission[$i]) && $submission[$i] === '1' ? 'checked' : ''; ?>
                                            onchange="saveAnswer(<?php echo $i; ?>, this.value)"><span
                                            class="block w-full h-full">1</span></label>
                                    <label class="answer-circle"><input type="radio" name="answer_<?php echo $i; ?>" value="2"
                                            <?php echo isset($submission[$i]) && $submission[$i] === '2' ? 'checked' : ''; ?>
                                            onchange="saveAnswer(<?php echo $i; ?>, this.value)"><span
                                            class="block w-full h-full">2</span></label>
                                    <label class="answer-circle"><input type="radio" name="answer_<?php echo $i; ?>" value="3"
                                            <?php echo isset($submission[$i]) && $submission[$i] === '3' ? 'checked' : ''; ?>
                                            onchange="saveAnswer(<?php echo $i; ?>, this.value)"><span
                                            class="block w-full h-full">3</span></label>
                                    <label class="answer-circle"><input type="radio" name="answer_<?php echo $i; ?>" value="4"
                                            <?php echo isset($submission[$i]) && $submission[$i] === '4' ? 'checked' : ''; ?>
                                            onchange="saveAnswer(<?php echo $i; ?>, this.value)"><span
                                            class="block w-full h-full">4</span></label>
                                </div>
                            </div>
                        <?php endfor; ?>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition">
                            <i class="fas fa-save"></i> ثبت نهایی
                        </button>
                    </form>
                </div>
            </div>
            <script>
                let timeLeft = <?php echo $time_left; ?>;
                const timer = document.getElementById('timer');
                const interval = setInterval(() => {
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        document.querySelector('form').submit();
                    } else {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        timer.textContent = `زمان باقی‌مانده: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                        timeLeft--;
                    }
                }, 1000);
                function saveAnswer(question, answer) {
                    const form = document.querySelector('form');
                    const data = new FormData(form);
                    fetch(form.action, { method: 'POST', body: data });
                }
            </script>

        <?php elseif ($action === 'rankings' && $role === 'student' && $exam_id && $exam && time() > strtotime($exam['deadline'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">رتبه‌بندی آزمون:
                <?php echo htmlspecialchars($exam['title'] ?? ''); ?></h3>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md -lg mb-6">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">رتبه</th>
                            <th class="py-3 px-4 text-right">نام دانش‌آموز</th>
                            <th class="py-3 px-4 text-right">نمره</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rankings): ?>
                            <?php foreach ($rankings as $r): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4"><?php echo $r['rank']; ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td class="py-3 px-4"><?php echo $r['grade'] !== null ? $r['grade'] : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">هیچ رتبه‌ای یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($user_ranking): ?>
                <div class="bg-blue p-4 -lg shadow-md">
                    <p class="text-lg font-semibold text-gray-700">
                        رتبه شما: <?php echo $user_ranking['rank']; ?> |
                        نام: <?php echo htmlspecialchars($user_ranking['name']); ?> |
                        نمره: <?php echo $user_ranking['grade'] !== null ? $user_ranking['grade'] : '-'; ?>
                    </p>
                </div>
            <?php else: ?>
                <p class="text-red-500">شما در این آزمون شرکت نکرده‌اید.</p>
            <?php endif; ?>
            <a href="?class_course_id=<?php echo $class_course_id; ?>"
                class="mt-6 inline-block bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت به آزمون‌ها
            </a>

        <?php elseif ($role === 'student' && $class_course_id): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">آزمون‌های درس</h3>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md -lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">عنوان</th>
                            <th class="py-3 px-4 text-right">تعداد سوالات</th>
                            <th class="py-3 px-4 text-right">مدت زمان (دقیقه)</th>
                            <th class="py-3 px-4 text-right">مهلت ارسال</th>
                            <th class="py-3 px-4 text-right">تاریخ ایجاد</th>
                            <th class="py-3 px-4 text-right">نمره</th>
                            <th class="py-3 px-4 text-right">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT grade, submitted_at
                                FROM exam_submissions
                                WHERE exam_id = ? AND student_id = ?
                            ");
                            $stmt->execute([$exam['id'], $user_id]);
                            $submission = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['grade' => null, 'submitted_at' => null];
                            $is_active = time() <= strtotime($exam['deadline']) && !$submission['submitted_at'];
                            $show_rankings = time() > strtotime($exam['deadline']);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td class="py-3 px-4"><?php echo $exam['question_count']; ?></td>
                                <td class="py-3 px-4"><?php echo $exam['duration']; ?></td>
                                <td class="py-3 px-4"><?php echo to_jalali($exam['deadline']); ?></td>
                                <td class="py-3 px-4"><?php echo to_jalali($exam['created_at']); ?></td>
                                <td class="py-3 px-4"><?php echo $submission['grade'] !== null ? $submission['grade'] : '-'; ?>
                                </td>
                                <td class="py-3 px-4 flex gap-2">
                                    <?php if ($is_active): ?>
                                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=take&exam_id=<?php echo $exam['id']; ?>"
                                            class="inline-block bg-blue-600 text-white px-4 py-2  hover:bg-blue-700 transition">
                                            <i class="fas fa-pen"></i> شرکت در آزمون
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($show_rankings): ?>
                                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=rankings&exam_id=<?php echo $exam['id']; ?>"
                                            class="inline-block bg-teal-600 text-white px-4 py-2  hover:bg-teal-700 transition">
                                            <i class="fas fa-trophy"></i> مشاهده رتبه‌ها
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">هیچ آزمونی یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="index.php"
                class="mt-6 inline-block bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت به درس‌ها
            </a>

        <?php elseif (in_array($role, ['teacher', 'admin']) && $class_course_id): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">آزمون‌های درس</h3>
            <a href="?class_course_id=<?php echo $class_course_id; ?>&action=create"
                class="mb-6 inline-block bg-green-600 text-white px-4 py-2  hover:bg-green-700 transition">
                <i class="fas fa-plus"></i> ایجاد آزمون جدید
            </a>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md -lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">عنوان</th>
                            <th class="py-3 px-4 text-right">تعداد سوالات</th>
                            <th class="py-3 px-4 text-right">مدت زمان (دقیقه)</th>
                            <th class="py-3 px-4 text-right">مهلت ارسال</th>
                            <th class="py-3 px-4 text-right">فایل</th>
                            <th class="py-3 px-4 text-right">تاریخ ایجاد</th>
                            <th class="py-3 px-4 text-right">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($exam['title']); ?></td>
                                <td class="py-3 px-4"><?php echo $exam['question_count']; ?></td>
                                <td class="py-3 px-4"><?php echo $exam['duration']; ?></td>
                                <td class="py-3 px-4"><?php echo to_jalali($exam['deadline']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($exam['file_path'] && file_exists($exam['file_path'])): ?>
                                        <a href="<?php echo $exam['file_path']; ?>" target="_blank"
                                            class="text-blue-600 hover:underline"><?php echo basename($exam['file_path']); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4"><?php echo to_jalali($exam['created_at']); ?></td>
                                <td class="py-3 px-4 flex gap-2">
                                    <a href="?class_course_id=<?php echo $class_course_id; ?>&action=edit&exam_id=<?php echo $exam['id']; ?>"
                                        class="bg-blue-600 text-white px-3 py-1  hover:bg-blue-700 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?class_course_id=<?php echo $class_course_id; ?>&action=delete&exam_id=<?php echo $exam['id']; ?>"
                                        class="bg-red-600 text-white px-3 py-1  hover:bg-red-700 transition"
                                        onclick="return confirm('آیا مطمئن هستید؟');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="?class_course_id=<?php echo $class_course_id; ?>&action=submissions&exam_id=<?php echo $exam['id']; ?>"
                                        class="bg-teal-600 text-white px-3 py-1  hover:bg-teal-700 transition">
                                        <i class="fas fa-list"></i> پاسخ‌ها
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">هیچ آزمونی یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="index.php"
                class="mt-6 inline-block bg-gray-600 text-white px-4 py-2  hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت به درس‌ها
            </a>
        <?php endif; ?>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>

</html>