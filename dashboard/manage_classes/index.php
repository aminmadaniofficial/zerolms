<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// دریافت لیست کلاس‌ها
$stmt = $pdo->prepare("SELECT id, name FROM classes ORDER BY name ASC");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ۱. ساخت کلاس جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "نام کلاس الزامی است.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO classes (name, created_by, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $_SESSION['user_id']]);
            addLog($pdo, $_SESSION['user_id'], 'ساخت موفق کلاس', 'مدیریت کلاس‌ها');
            header("Location: index.php?success=1");
            exit;
        }
    }
}

// ۲. ویرایش کلاس (افزودن دانش‌آموز، درس و معلم)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_class') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $class_id = (int)$_POST['class_id'];
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "نام کلاس الزامی است.";
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE classes SET name = ? WHERE id = ?");
                $stmt->execute([$name, $class_id]);

                // اضافه کردن دانش‌آموزان به کلاس
                if (!empty($_POST['new_students'])) {
                    foreach ($_POST['new_students'] as $student_id) {
                        $stmt = $pdo->prepare("UPDATE users SET class_id = ? WHERE id = ? AND role = 'student'");
                        $stmt->execute([$class_id, (int)$student_id]);
                    }
                }

                // اضافه کردن درس جدید به کلاس
                if (!empty($_POST['new_courses'])) {
                    foreach ($_POST['new_courses'] as $course_name) {
                        $course_name = trim($course_name);
                        if (!empty($course_name)) {
                            $stmt = $pdo->prepare("INSERT IGNORE INTO classcourses (class_id, course_name) VALUES (?, ?)");
                            $stmt->execute([$class_id, $course_name]);
                        }
                    }
                }

                // اضافه کردن معلم به درس
                if (!empty($_POST['new_teachers']) && !empty($_POST['course_id']) && (int)$_POST['course_id'] > 0) {
                    $course_id = (int)$_POST['course_id'];
                    foreach ($_POST['new_teachers'] as $teacher_id) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO classcourseteachers (class_course_id, teacher_id) VALUES (?, ?)");
                        $stmt->execute([$course_id, (int)$teacher_id]);
                    }
                }

                $pdo->commit();
                addLog($pdo, $_SESSION['user_id'], 'ویرایش موفق کلاس', 'مدیریت کلاس‌ها', $class_id);
                header("Location: index.php?success=1");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                addLog($pdo, $_SESSION['user_id'], 'ویرایش ناموفق کلاس', 'مدیریت کلاس‌ها', $class_id);
                $error = "خطا در ویرایش کلاس: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کلاس‌ها | سامانه یادگیری</title>
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
            transition: all 0.25s ease !important;
        }

        .btn-gradient-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5) !important;
        }

        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 10px 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-accent) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
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
        table tbody tr:hover td { background: rgba(99, 102, 241, 0.05); }

        .modal-content {
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            color: var(--text-main);
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        }

        .modal-header, .modal-footer {
            border-color: var(--border-color);
        }

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
        <span class="fw-bold fs-5"><i class="fas fa-chalkboard-teacher text-primary me-2"></i> مدیریت کلاس‌ها و دروس</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger bg-danger bg-opacity-20 text-danger border-0 rounded-3 mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success bg-success bg-opacity-20 border-0 rounded-3 mb-4">عملیات با موفقیت انجام شد.</div>
        <?php endif; ?>

        <!-- دکمه ساخت کلاس -->
        <div class="mb-4">
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                <i class="fas fa-plus me-1"></i> ساخت کلاس جدید
            </button>
        </div>

        <!-- جدول کلاس‌ها -->
        <div class="card-custom p-0">
            <div class="table-responsive">
                <table class="table text-center align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">#</th>
                            <th>نام کلاس</th>
                            <th style="width: 200px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr><td colspan="3" class="py-4 text-muted">هیچ کلاسی یافت نشد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($classes as $index => $class): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($class['name']); ?></strong></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning edit-class border-0 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editClassModal" 
                                                data-class-id="<?php echo $class['id']; ?>" 
                                                data-class-name="<?php echo htmlspecialchars($class['name']); ?>">
                                            <i class="fas fa-edit me-1"></i> ویرایش
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-class border-0" 
                                                data-class-id="<?php echo $class['id']; ?>">
                                            <i class="fas fa-trash me-1"></i> حذف
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- مودال ساخت کلاس -->
    <div class="modal fade" id="createClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle text-primary me-2"></i> ساخت کلاس جدید</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_class">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="className" class="form-label">نام کلاس (مثلاً: ۷/۱)</label>
                            <input type="text" class="form-control" id="className" name="name" required placeholder="نام کلاس را وارد کنید...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ثبت کلاس</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال ویرایش کلاس -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit text-warning me-2"></i> مدیریت و ویرایش کلاس</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editClassForm">
                    <input type="hidden" name="action" value="edit_class">
                    <input type="hidden" name="class_id" id="editClassId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="editClassName" class="form-label font-weight-bold">نام کلاس</label>
                            <input type="text" class="form-control" id="editClassName" name="name" required>
                        </div>

                        <!-- لیست دانش‌آموزان -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="m-0 text-primary"><i class="fas fa-user-graduate me-1"></i> دانش‌آموزان این کلاس</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteAllStudents">حذف همه دانش‌آموزان</button>
                        </div>
                        <div id="classMembersTable" class="table-responsive mb-3"></div>

                        <!-- اضافه کردن دانش‌آموز -->
                        <div class="mb-4">
                            <label class="form-label">افزودن دانش‌آموزان جدید به این کلاس</label>
                            <select class="form-select" name="new_students[]" multiple id="newStudentsSelect" style="min-height: 100px;">
                            </select>
                        </div>

                        <hr class="border-secondary my-4">

                        <!-- لیست درس‌ها -->
                        <h6 class="mb-2 text-primary"><i class="fas fa-book me-1"></i> دروس این کلاس</h6>
                        <div id="classCoursesTable" class="table-responsive mb-3"></div>

                        <!-- اضافه کردن درس جدید -->
                        <div class="mb-4">
                            <label class="form-label">افزودن درس جدید به این کلاس</label>
                            <input type="text" class="form-control" name="new_courses[]" placeholder="نام درس (مثلاً: ریاضی ۷/۱)">
                        </div>

                        <!-- تخصیص معلم به درس -->
                        <div class="mb-3">
                            <label class="form-label">تخصیص معلم به درس‌های این کلاس</label>
                            <select class="form-select mb-2" name="course_id" id="courseSelect">
                                <option value="">انتخاب درس...</option>
                            </select>
                            <select class="form-select" name="new_teachers[]" multiple id="newTeachersSelect" style="min-height: 90px;">
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentEditingClassId = null;
        let currentEditingClassName = null;

        function loadClassData(classId, className) {
            currentEditingClassId = classId;
            currentEditingClassName = className;

            document.getElementById('editClassId').value = classId;
            document.getElementById('editClassName').value = className;

            // دریافت اعضای کلاس
            fetch(`get_class_members.php?class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    let html = `
                        <table class="table text-center align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>نام دانش‌آموز</th>
                                    <th style="width: 100px;">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    if (!data.students || data.students.length === 0) {
                        html += `<tr><td colspan="2" class="text-muted">دانش‌آموزی در این کلاس ثبت نشده است.</td></tr>`;
                    } else {
                        data.students.forEach(student => {
                            html += `
                                <tr>
                                    <td>${student.name}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 delete-student" data-user-id="${student.id}">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        });
                    }
                    html += `</tbody></table>`;
                    document.getElementById('classMembersTable').innerHTML = html;

                    // بایند ایونت حذف دانش‌آموز
                    document.querySelectorAll('.delete-student').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const userId = parseInt(btn.dataset.userId);
                            if (confirm('آیا از حذف این دانش‌آموز از کلاس اطمینان دارید؟')) {
                                fetch('delete_class_member.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ class_id: parseInt(classId), user_id: userId })
                                })
                                .then(res => res.json())
                                .then(resData => {
                                    if (resData.success) loadClassData(classId, className);
                                    else alert('خطا در حذف: ' + resData.message);
                                });
                            }
                        });
                    });
                });

            // دریافت دانش‌آموزان بدون کلاس
            fetch(`get_available_students.php?class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    let select = document.getElementById('newStudentsSelect');
                    select.innerHTML = '';
                    if (data.students) {
                        data.students.forEach(student => {
                            let option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = student.name;
                            select.appendChild(option);
                        });
                    }
                });

            // دریافت دروس کلاس
            fetch(`get_class_courses.php?class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    let html = `
                        <table class="table text-center align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>نام درس</th>
                                    <th>اساتید</th>
                                    <th style="width: 100px;">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    if (!data.courses || data.courses.length === 0) {
                        html += `<tr><td colspan="3" class="text-muted">درسی برای این کلاس تعریف نشده است.</td></tr>`;
                    } else {
                        data.courses.forEach(course => {
                            const teacherNames = course.teachers && course.teachers.length > 0 
                                ? course.teachers.map(t => t.name).join(', ') 
                                : 'بدون استاد';
                            html += `
                                <tr>
                                    <td><strong>${course.course_name}</strong></td>
                                    <td>${teacherNames}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 delete-course" data-course-id="${course.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>`;
                        });
                    }
                    html += `</tbody></table>`;
                    document.getElementById('classCoursesTable').innerHTML = html;

                    let courseSelect = document.getElementById('courseSelect');
                    courseSelect.innerHTML = '<option value="">انتخاب درس...</option>';
                    if (data.courses) {
                        data.courses.forEach(course => {
                            let option = document.createElement('option');
                            option.value = course.id;
                            option.textContent = course.course_name;
                            courseSelect.appendChild(option);
                        });
                    }

                    // بایند ایونت حذف درس
                    document.querySelectorAll('.delete-course').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const courseId = btn.dataset.courseId;
                            if (confirm('آیا مطمئن هستید که می‌خواهید این درس را حذف کنید؟')) {
                                fetch('delete_class_course.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ course_id: parseInt(courseId) })
                                })
                                .then(res => res.json())
                                .then(resData => {
                                    if (resData.success) loadClassData(classId, className);
                                    else alert('خطا در حذف: ' + resData.message);
                                });
                            }
                        });
                    });
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // تغییر سلکتور درس جهت لود اساتید
            document.getElementById('courseSelect').addEventListener('change', function () {
                const courseId = this.value;
                let select = document.getElementById('newTeachersSelect');
                select.innerHTML = '';
                if (courseId) {
                    fetch(`get_available_teachers.php?course_id=${courseId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.teachers) {
                                data.teachers.forEach(teacher => {
                                    let option = document.createElement('option');
                                    option.value = teacher.id;
                                    option.textContent = teacher.name;
                                    select.appendChild(option);
                                });
                            }
                        });
                }
            });

            // حذف همه دانش‌آموزان
            document.getElementById('deleteAllStudents').addEventListener('click', () => {
                if (currentEditingClassId && confirm('آیا از حذف تمام دانش‌آموزان این کلاس اطمینان دارید؟')) {
                    fetch('delete_all_students.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ class_id: currentEditingClassId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) loadClassData(currentEditingClassId, currentEditingClassName);
                        else alert('خطا در حذف: ' + data.message);
                    });
                }
            });

            // حذف کامل کلاس
            document.querySelectorAll('.delete-class').forEach(btn => {
                btn.addEventListener('click', () => {
                    const classId = parseInt(btn.dataset.classId);
                    if (confirm('آیا از حذف این کلاس اطمینان دارید؟ تمام داده‌های مرتبط حذف خواهند شد.')) {
                        fetch('delete_class.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ class_id: classId })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) window.location.reload();
                            else alert('خطا در حذف کلاس: ' + data.message);
                        });
                    }
                });
            });

            // باز کردن مودال ویرایش
            document.querySelectorAll('.edit-class').forEach(btn => {
                btn.addEventListener('click', () => {
                    loadClassData(btn.dataset.classId, btn.dataset.className);
                });
            });
        });
    </script>
</body>
</html>