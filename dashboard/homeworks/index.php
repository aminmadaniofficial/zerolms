<?php
session_start();
require_once '../../db.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$homework_id = (int) ($_GET['homework_id'] ?? 0);


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


if ($action === 'submit' && $homework_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $text_response = $_POST['text_response'] ?? '';
    $stmt = $pdo->prepare("SELECT deadline FROM homeworks WHERE id = ? AND class_course_id = ?");
    $stmt->execute([$homework_id, $class_course_id]);
    $deadline = $stmt->fetchColumn();
    if ($deadline && strtotime($deadline) > time()) {
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
            $file_path = '../../Uploads/homework/students/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        }
        
        $stmt = $pdo->prepare("SELECT id, file_path FROM homework_submissions WHERE homework_id = ? AND student_id = ?");
        $stmt->execute([$homework_id, $user_id]);
        $existing_submission = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing_submission) {
            if ($existing_submission['file_path'] && $file_path) {
                unlink($existing_submission['file_path']);
            }
            $stmt = $pdo->prepare("
                UPDATE homework_submissions
                SET file_path = ?, text_response = ?, submitted_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$file_path, $text_response, $existing_submission['id']]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO homework_submissions (homework_id, student_id, file_path, text_response)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$homework_id, $user_id, $file_path, $text_response]);
        }
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


if ($action === 'delete_submission' && $homework_id) {
    $stmt = $pdo->prepare("SELECT deadline FROM homeworks WHERE id = ? AND class_course_id = ?");
    $stmt->execute([$homework_id, $class_course_id]);
    $deadline = $stmt->fetchColumn();
    if ($deadline && strtotime($deadline) > time()) {
        $stmt = $pdo->prepare("SELECT file_path FROM homework_submissions WHERE homework_id = ? AND student_id = ?");
        $stmt->execute([$homework_id, $user_id]);
        $file_path = $stmt->fetchColumn();
        if ($file_path) {
            unlink($file_path);
        }
        $stmt = $pdo->prepare("DELETE FROM homework_submissions WHERE homework_id = ? AND student_id = ?");
        $stmt->execute([$homework_id, $user_id]);
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


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
    <title>تکالیف من</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
        <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
</head>
<style>
    :root {
        --primary-color: hsl(187, 91%, 50%);
        --secondary-color: hsl(25, 85%, 60%);
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

<body>
    <div class="container">
        <h2>تکالیف من</h2>

        <?php if (!$class_course_id): ?>
            <h3>درس‌های شما</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>نام درس</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $lesson): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lesson['class_name'] . ' - ' . $lesson['course_name']); ?></td>
                            <td>
                                <a href="?class_course_id=<?php echo $lesson['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-tasks"></i> تکالیف
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lessons)): ?>
                        <tr>
                            <td colspan="2" class="text-center">هیچ درسی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="../index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-right"></i> بازگشت</a>

        <?php elseif ($action === 'submit' && $homework_id): ?>
            <h3>ارسال پاسخ</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="text_response" class="form-label">پاسخ متنی (اختیاری)</label>
                    <textarea class="form-control" id="text_response" name="text_response"></textarea>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">فایل (اختیاری)</label>
                    <input type="file" class="form-control" id="file" name="file">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> ارسال</button>
                <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-secondary"><i
                        class="fas fa-times"></i> لغو</a>
            </form>

        <?php else: ?>
            <h3>تکالیف درس</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>توضیحات</th>
                        <th>فایل تکلیف</th>
                        <th>مهلت ارسال</th>
                        <th>پاسخ شما</th>
                        <th>نمره</th>
                        <th>نظرات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($homeworks as $homework): ?>
                        <?php $is_active = strtotime($homework['deadline']) > time(); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($homework['title']); ?></td>
                            <td><?php echo htmlspecialchars($homework['description'] ?? '-'); ?></td>
                            <td>
                                <?php if ($homework['file_path']): ?>
                                    <a href="<?php echo $homework['file_path']; ?>"
                                        target="_blank"><?php echo basename($homework['file_path']); ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($homework['deadline'])); ?></td>
                            <td>
                                <?php if ($homework['student_file'] || $homework['text_response']): ?>
                                    <?php if ($homework['student_file']): ?>
                                        <a href="<?php echo $homework['student_file']; ?>"
                                            target="_blank"><?php echo basename($homework['student_file']); ?></a><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($homework['text_response'] ?? ''); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $homework['grade'] !== null ? $homework['grade'] : '-'; ?></td>
                            <td><?php echo htmlspecialchars($homework['feedback'] ?? '-'); ?></td>
                            <td>
                                <?php if ($is_active): ?>
                                    <a href="?class_course_id=<?php echo $class_course_id; ?>&action=submit&homework_id=<?php echo $homework['id']; ?>"
                                        class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> ارسال/ویرایش</a>
                                    <?php if ($homework['student_file'] || $homework['text_response']): ?>
                                        <a href="?class_course_id=<?php echo $class_course_id; ?>&action=delete_submission&homework_id=<?php echo $homework['id']; ?>"
                                            class="btn btn-danger btn-sm" onclick="return confirm('آیا مطمئن هستید؟');"><i
                                                class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($homeworks)): ?>
                        <tr>
                            <td colspan="8" class="text-center">هیچ تکلیفی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-right"></i> بازگشت به درس‌ها</a>
        <?php endif; ?>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>

</html>