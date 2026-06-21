<?php
session_start();
require_once '../../db.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$class_course_id = (int) ($_GET['class_course_id'] ?? 0);
$action = $_GET['action'] ?? '';
$homework_id = (int) ($_GET['homework_id'] ?? 0);


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


if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    if ($title && $class_course_id && $deadline && strtotime($deadline)) {
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
            $file_path = '../../Uploads/homework/teachers/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
        }
        $stmt = $pdo->prepare("
            INSERT INTO homeworks (class_course_id, teacher_id, title, description, file_path, deadline)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$class_course_id, $user_id, $title, $description, $file_path, $deadline]);
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


if ($action === 'edit' && $homework_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    if ($title && $deadline && strtotime($deadline)) {
        $file_path = $_POST['existing_file'] ?? null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
            $file_path = '../../Uploads/homework/teachers/' . $file_name;
            move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
            
            if ($_POST['existing_file']) {
                unlink($_POST['existing_file']);
            }
        }
        $query = "UPDATE homeworks SET title = ?, description = ?, file_path = ?, deadline = ? WHERE id = ?";
        $params = [$title, $description, $file_path, $deadline, $homework_id];
        if ($role !== 'admin') {
            $query .= " AND teacher_id = ?";
            $params[] = $user_id;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        header("Location: ?class_course_id=$class_course_id");
        exit;
    }
}


if ($action === 'delete' && $homework_id) {
    $query = "SELECT file_path FROM homeworks WHERE id = ?";
    $params = [$homework_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $file_path = $stmt->fetchColumn();
    if ($file_path) {
        unlink($file_path);
    }
    $query = "DELETE FROM homeworks WHERE id = ?";
    $params = [$homework_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    header("Location: ?class_course_id=$class_course_id");
    exit;
}


if ($action === 'grade' && $homework_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = (int) ($_POST['submission_id'] ?? 0);
    $grade = (int) ($_POST['grade'] ?? 0);
    $feedback = $_POST['feedback'] ?? '';
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


$homeworks = [];
if ($class_course_id) {
    $query = "SELECT id, title, description, file_path, deadline, created_at FROM homeworks WHERE class_course_id = ?";
    $params = [$class_course_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $query .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


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


$edit_homework = null;
if ($action === 'edit' && $homework_id) {
    $query = "SELECT * FROM homeworks WHERE id = ?";
    $params = [$homework_id];
    if ($role !== 'admin') {
        $query .= " AND teacher_id = ?";
        $params[] = $user_id;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $edit_homework = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت تکالیف</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
        <link rel="stylesheet" href="../assets/style.css">

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
        <h2>مدیریت تکالیف</h2>

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

        <?php elseif ($action === 'create' || ($action === 'edit' && $edit_homework)): ?>
            <h3><?php echo $action === 'create' ? 'ایجاد تکلیف جدید' : 'ویرایش تکلیف'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">عنوان تکلیف</label>
                    <input type="text" class="form-control" id="title" name="title"
                        value="<?php echo $edit_homework['title'] ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">توضیحات</label>
                    <textarea class="form-control" id="description"
                        name="description"><?php echo $edit_homework['description'] ?? ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="file" class="form-label">فایل (اختیاری)</label>
                    <input type="file" class="form-control" id="file" name="file">
                    <?php if ($edit_homework && $edit_homework['file_path']): ?>
                        <p>فایل فعلی: <a href="<?php echo $edit_homework['file_path']; ?>"
                                target="_blank"><?php echo basename($edit_homework['file_path']); ?></a></p>
                        <input type="hidden" name="existing_file" value="<?php echo $edit_homework['file_path']; ?>">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="deadline" class="form-label">مهلت ارسال</label>
                    <input type="datetime-local" class="form-control" id="deadline" name="deadline"
                        value="<?php echo $edit_homework['deadline'] ? date('Y-m-d\TH:i', strtotime($edit_homework['deadline'])) : ''; ?>"
                        required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> ذخیره</button>
                <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-secondary"><i
                        class="fas fa-times"></i> لغو</a>
            </form>

        <?php elseif ($action === 'submissions' && $homework_id): ?>
            <h3>پاسخ‌های تکلیف</h3>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>دانش‌آموز</th>
                        <th>فایل</th>
                        <th>متن</th>
                        <th>نمره</th>
                        <th>نظرات</th>
                        <th>تاریخ ارسال</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['name']); ?></td>
                            <td>
                                <?php if ($submission['file_path']): ?>
                                    <a href="<?php echo $submission['file_path']; ?>"
                                        target="_blank"><?php echo basename($submission['file_path']); ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($submission['text_response'] ?? '-'); ?></td>
                            <td><?php echo $submission['grade'] !== null ? $submission['grade'] : '-'; ?></td>
                            <td><?php echo htmlspecialchars($submission['feedback'] ?? '-'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($submission['submitted_at'])); ?></td>
                            <td>
                                <form method="POST"
                                    action="?class_course_id=<?php echo $class_course_id; ?>&action=grade&homework_id=<?php echo $homework_id; ?>">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="grade" class="form-control" min="0" max="20"
                                            value="<?php echo $submission['grade'] ?? ''; ?>" placeholder="نمره">
                                        <input type="text" name="feedback" class="form-control"
                                            value="<?php echo $submission['feedback'] ?? ''; ?>" placeholder="نظرات">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i></button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="7" class="text-center">هیچ پاسخی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="?class_course_id=<?php echo $class_course_id; ?>" class="btn btn-secondary mt-3"><i
                    class="fas fa-arrow-right"></i> بازگشت</a>

        <?php else: ?>
            <h3>تکالیف درس</h3>
            <a href="?class_course_id=<?php echo $class_course_id; ?>&action=create" class="btn btn-success mb-3"><i
                    class="fas fa-plus"></i> ایجاد تکلیف جدید</a>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>توضیحات</th>
                        <th>فایل</th>
                        <th>مهلت ارسال</th>
                        <th>تاریخ ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($homeworks as $homework): ?>
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
                            <td><?php echo date('Y-m-d H:i', strtotime($homework['created_at'])); ?></td>
                            <td>
                                <a href="?class_course_id=<?php echo $class_course_id; ?>&action=edit&homework_id=<?php echo $homework['id']; ?>"
                                    class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="?class_course_id=<?php echo $class_course_id; ?>&action=delete&homework_id=<?php echo $homework['id']; ?>"
                                    class="btn btn-danger btn-sm" onclick="return confirm('آیا مطمئن هستید؟');"><i
                                        class="fas fa-trash"></i></a>
                                <a href="?class_course_id=<?php echo $class_course_id; ?>&action=submissions&homework_id=<?php echo $homework['id']; ?>"
                                    class="btn btn-info btn-sm"><i class="fas fa-list"></i> پاسخ‌ها</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($homeworks)): ?>
                        <tr>
                            <td colspan="6" class="text-center">هیچ تکلیفی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="teacher_homeworks.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-right"></i> بازگشت به
                درس‌ها</a>
        <?php endif; ?>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>

</html>