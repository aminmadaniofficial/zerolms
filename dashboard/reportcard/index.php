<?php
session_start();
require_once '../../db.php';
require_once '../../vendor/tcpdf/tcpdf.php';
require_once '../../jdf.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $_GET['action'] ?? '';
$academic_year = (int) ($_GET['year'] ?? date('Y') - 621); 
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$student_id = (int) ($_GET['student_id'] ?? 0);


function to_jalali($datetime) {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    return jdate('Y/m/d H:i', $timestamp);
}


function calculate_average($grades) {
    $valid_grades = array_filter($grades, function($grade) { return $grade !== null; });
    return empty($valid_grades) ? '-' : round(array_sum($valid_grades) / count($valid_grades), 2);
}


$years = [];
$stmt = $pdo->query("SELECT DISTINCT academic_year FROM report_cards ORDER BY academic_year DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $years[] = $row['academic_year'];
}
if (empty($years)) {
    $years = [$academic_year]; 
}


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
        JOIN classcourseteachers cct ON cc.id = cct.class_course_id
        WHERE cct.teacher_id = ?
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute([$user_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.course_name, c.name AS class_name
        FROM classcourses cc
        JOIN classes c ON cc.class_id = c.id
        ORDER BY c.name, cc.course_name
    ");
    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$students = [];
if ($action === 'grade' && in_array($role, ['teacher', 'admin']) && $class_course_id) {
    $query = "SELECT u.id, u.name FROM users u WHERE u.role = 'student' AND u.class_id = (SELECT class_id FROM classcourses WHERE id = ?)";
    $params = [$class_course_id];
    if ($role === 'teacher') {
        $query .= " AND EXISTS (SELECT 1 FROM classcourseteachers cct WHERE cct.class_course_id = ? AND cct.teacher_id = ?)";
        $params[] = $class_course_id;
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


if ($action === 'save_grades' && in_array($role, ['teacher', 'admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_course_id = (int) ($_POST['class_course_id'] ?? 0);
    $academic_year = (int) ($_POST['academic_year'] ?? 0);
    $first_term_continuous = isset($_POST['first_term_continuous']) ? (float) ($_POST['first_term_continuous'] ?: null) : null;
    $first_term_exam = isset($_POST['first_term_exam']) ? (float) ($_POST['first_term_exam'] ?: null) : null;
    $second_term_continuous = isset($_POST['second_term_continuous']) ? (float) ($_POST['second_term_continuous'] ?: null) : null;
    $second_term_exam = isset($_POST['second_term_exam']) ? (float) ($_POST['second_term_exam'] ?: null) : null;

    if ($student_id && $class_course_id && $academic_year) {
        $stmt = $pdo->prepare("
            SELECT id FROM report_cards
            WHERE student_id = ? AND class_course_id = ? AND academic_year = ?
        ");
        $stmt->execute([$student_id, $class_course_id, $academic_year]);
        $report_id = $stmt->fetchColumn();

        if ($report_id) {
            $query = "
                UPDATE report_cards
                SET first_term_continuous = ?, first_term_exam = ?, second_term_continuous = ?, second_term_exam = ?
                WHERE id = ?
            ";
            $params = [$first_term_continuous, $first_term_exam, $second_term_continuous, $second_term_exam, $report_id];
        } else {
            $query = "
                INSERT INTO report_cards (student_id, class_course_id, academic_year, first_term_continuous, first_term_exam, second_term_continuous, second_term_exam)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $params = [$student_id, $class_course_id, $academic_year, $first_term_continuous, $first_term_exam, $second_term_continuous, $second_term_exam];
        }

        if ($role === 'teacher') {
            $query .= " AND EXISTS (SELECT 1 FROM classcourseteachers cct WHERE cct.class_course_id = ? AND cct.teacher_id = ?)";
            $params[] = $class_course_id;
            $params[] = $user_id;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        header("Location: ?action=grade&class_course_id=$class_course_id&year=$academic_year");
        exit;
    }
}


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


if ($action === 'download_pdf' && ($role === 'student' || in_array($role, ['teacher', 'admin']))) {
    $target_id = $role === 'student' ? $user_id : $student_id;
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $student_name = $stmt->fetchColumn();

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Bahonar3 Website');
    $pdf->SetAuthor('Bahonar3');
    $pdf->SetTitle('کارنامه دبیرستان استعدادهای درخشان شهید باهنر 3');
    $pdf->SetSubject('کارنامه');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setFontSubsetting(true);
    $pdf->AddFont('IRANYekanBlack', '', '../../vendor/tcpdf/fonts/dejavu-sans.php');
    $pdf->SetFont('IRANYekanBlack', '', 12);
    $pdf->AddPage();

    
    $pdf->SetFont('IRANYekanBlack', 'B', 16);
    $pdf->Cell(0, 10, 'کارنامه دبیرستان استعدادهای درخشان شهید باهنر 3', 0, 1, 'C');
    $pdf->SetFont('IRANYekanBlack', '', 12);
    $pdf->Cell(0, 10, "دانش‌آموز: $student_name | سال تحصیلی: $academic_year", 0, 1, 'R');
    $pdf->Ln(5);

    
    $pdf->SetFont('IRANYekanBlack', 'B', 10);
    $pdf->Cell(40, 10, 'درس', 1, 0, 'C');
    $pdf->Cell(30, 10, 'مستمر نوبت اول', 1, 0, 'C');
    $pdf->Cell(30, 10, 'نوبت اول', 1, 0, 'C');
    $pdf->Cell(30, 10, 'مستمر نوبت دوم', 1, 0, 'C');
    $pdf->Cell(30, 10, 'نوبت دوم', 1, 0, 'C');
    $pdf->Cell(30, 10, 'معدل', 1, 1, 'C');

    $pdf->SetFont('IRANYekanBlack', '', 10);
    foreach ($report_cards as $rc) {
        $grades = [
            $rc['first_term_continuous'],
            $rc['first_term_exam'],
            $rc['second_term_continuous'],
            $rc['second_term_exam']
        ];
        $average = calculate_average($grades);
        $pdf->Cell(40, 10, $rc['course_name'], 1, 0, 'R');
        $pdf->Cell(30, 10, $rc['first_term_continuous'] ?? '-', 1, 0, 'C');
        $pdf->Cell(30, 10, $rc['first_term_exam'] ?? '-', 1, 0, 'C');
        $pdf->Cell(30, 10, $rc['second_term_continuous'] ?? '-', 1, 0, 'C');
        $pdf->Cell(30, 10, $rc['second_term_exam'] ?? '-', 1, 0, 'C');
        $pdf->Cell(30, 10, $average, 1, 1, 'C');
    }

    
    $pdf->SetY(-15);
    $pdf->SetFont('IRANYekanBlack', '', 8);
    $pdf->Cell(0, 10, 'ساخته شده توسط وبسایت باهنر 3 | برنامه نویسی شده توسط محمدامین مدنی محمدی', 0, 0, 'C');

    $pdf->Output("report_card_{$student_id}_{$academic_year}.pdf", 'D');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کارنامه</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
        <link rel="stylesheet" href="../assets/style.css">

    <style>
        .grade-input { @apply border rounded p-2 w-20 text-center; }
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
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">کارنامه</h2>

        <!-- انتخاب سال تحصیلی -->
        <div class="mb-6">
            <label for="year" class="block text-gray-700 font-medium mb-2">سال تحصیلی</label>
            <select id="year" onchange="window.location.href='?year='+this.value" class="border rounded p-2">
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $academic_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($action === 'grade' && in_array($role, ['teacher', 'admin'])): ?>
            <h3 class="text-xl font-semibold text-gray-700 mb-4">ثبت نمرات</h3>
            <form method="GET" class="mb-6 bg-blue p-6 rounded-lg shadow-md">
                <div class="mb-4">
                    <label for="class_course_id" class="block text-gray-700 font-medium">درس</label>
                    <select name="class_course_id" class="border rounded p-2 w-full" onchange="this.form.submit()">
                        <option value="">انتخاب درس</option>
                        <?php foreach ($lessons as $lesson): ?>
                            <option value="<?php echo $lesson['id']; ?>" <?php echo $lesson['id'] == $class_course_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lesson['class_name'] . ' - ' . $lesson['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="year" value="<?php echo $academic_year; ?>">
                    <input type="hidden" name="action" value="grade">
                </div>
            </form>

            <?php if ($class_course_id): ?>
                <form method="GET" class="mb-6 bg-blue p-6 rounded-lg shadow-md">
                    <div class="mb-4">
                        <label for="student_id" class="block text-gray-700 font-medium">دانش‌آموز</label>
                        <select name="student_id" class="border rounded p-2 w-full" onchange="this.form.submit()">
                            <option value="">انتخاب دانش‌آموز</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo $student['id'] == $student_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="class_course_id" value="<?php echo $class_course_id; ?>">
                        <input type="hidden" name="year" value="<?php echo $academic_year; ?>">
                        <input type="hidden" name="action" value="grade">
                    </div>
                </form>

                <?php if ($student_id): ?>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT * FROM report_cards
                        WHERE student_id = ? AND class_course_id = ? AND academic_year = ?
                    ");
                    $stmt->execute([$student_id, $class_course_id, $academic_year]);
                    $report = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                    ?>
                    <form method="POST" action="?action=save_grades" class="bg-blue p-6 rounded-lg shadow-md">
                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                        <input type="hidden" name="class_course_id" value="<?php echo $class_course_id; ?>">
                        <input type="hidden" name="academic_year" value="<?php echo $academic_year; ?>">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-medium">مستمر نوبت اول</label>
                                <input type="number" name="first_term_continuous" class="grade-input" min="0" max="20" step="0.1" value="<?php echo $report['first_term_continuous'] ?? ''; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium">نوبت اول</label>
                                <input type="number" name="first_term_exam" class="grade-input" min="0" max="20" step="0.1" value="<?php echo $report['first_term_exam'] ?? ''; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium">مستمر نوبت دوم</label>
                                <input type="number" name="second_term_continuous" class="grade-input" min="0" max="20" step="0.1" value="<?php echo $report['second_term_continuous'] ?? ''; ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium">نوبت دوم</label>
                                <input type="number" name="second_term_exam" class="grade-input" min="0" max="20" step="0.1" value="<?php echo $report['second_term_exam'] ?? ''; ?>">
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            <i class="fas fa-save"></i> ذخیره
                        </button>
                        <a href="?action=grade&class_course_id=<?php echo $class_course_id; ?>&year=<?php echo $academic_year; ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                            <i class="fas fa-times"></i> لغو
                        </a>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>
            <!-- نمایش کارنامه -->
            <h3 class="text-xl font-semibold text-gray-700 mb-4">کارنامه سال <?php echo $academic_year; ?></h3>
            <div class="overflow-x-auto">
                <table class="w-full bg-blue shadow-md rounded-lg">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-right">درس</th>
                            <th class="py-3 px-4 text-right">مستمر نوبت اول</th>
                            <th class="py-3 px-4 text-right">نوبت اول</th>
                            <th class="py-3 px-4 text-right">مستمر نوبت دوم</th>
                            <th class="py-3 px-4 text-right">نوبت دوم</th>
                            <th class="py-3 px-4 text-right">معدل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_cards as $rc): ?>
                            <?php
                            $grades = [
                                $rc['first_term_continuous'],
                                $rc['first_term_exam'],
                                $rc['second_term_continuous'],
                                $rc['second_term_exam']
                            ];
                            $average = calculate_average($grades);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo htmlspecialchars($rc['course_name']); ?></td>
                                <td class="py-3 px-4"><?php echo $rc['first_term_continuous'] ?? '-'; ?></td>
                                <td class="py-3 px-4"><?php echo $rc['first_term_exam'] ?? '-'; ?></td>
                                <td class="py-3 px-4"><?php echo $rc['second_term_continuous'] ?? '-'; ?></td>
                                <td class="py-3 px-4"><?php echo $rc['second_term_exam'] ?? '-'; ?></td>
                                <td class="py-3 px-4"><?php echo $average; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($report_cards)): ?>
                            <tr><td colspan="6" class="text-center py-4">هیچ نمره‌ای ثبت نشده است.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($role === 'student' || (in_array($role, ['teacher', 'admin']) && $student_id)): ?>
                <a href="?action=download_pdf&year=<?php echo $academic_year; ?>&student_id=<?php echo $role === 'student' ? $user_id : $student_id; ?>" class="mt-6 inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-download"></i> دانلود PDF
                </a>
            <?php endif; ?>
            <a href="../index.php" class="mt-6 inline-block bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>
            <?php if ($role !== 'student'): ?>
                <a href="?action=grade&year=<?php echo $academic_year; ?>" class="mt-6 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    <i class="fas fa-pen"></i> ثبت نمرات
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
        <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>
</html>