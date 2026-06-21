<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}


$stmt = $pdo->prepare("SELECT id, name FROM classes ORDER BY name ASC");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = "نام کلاس الزامی است";
    } else {
        $stmt = $pdo->prepare("INSERT INTO classes (name, created_by, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $_SESSION['user_id']]);
        addLog($pdo, $_SESSION['user_id'], 'ساخت موفق کلاس', 'ساخت کلاس');
        header("Location: index.php?success=1");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_class') {
    $class_id = (int)$_POST['class_id'];
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        $error = "نام کلاس الزامی است";
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE classes SET name = ? WHERE id = ?");
            $stmt->execute([$name, $class_id]);

            
            if (!empty($_POST['new_students'])) {
                foreach ($_POST['new_students'] as $student_id) {
                    $stmt = $pdo->prepare("UPDATE users SET class_id = ? WHERE id = ? AND role = 'student'");
                    $stmt->execute([$class_id, (int)$student_id]);
                }
            }

            
            if (!empty($_POST['new_courses'])) {
                foreach ($_POST['new_courses'] as $course_name) {
                    $course_name = trim($course_name);
                    if (!empty($course_name)) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO ClassCourses (class_id, course_name) VALUES (?, ?)");
                        $stmt->execute([$class_id, $course_name]);
                    }
                }
            }

            
            if (!empty($_POST['new_teachers']) && !empty($_POST['course_id']) && (int)$_POST['course_id'] > 0) {
                $course_id = (int)$_POST['course_id'];
                foreach ($_POST['new_teachers'] as $teacher_id) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO ClassCourseTeachers (class_course_id, teacher_id) VALUES (?, ?)");
                    $stmt->execute([$course_id, (int)$teacher_id]);
                }
            }

            $pdo->commit();
            addLog($pdo, $_SESSION['user_id'], 'ویرایش موفق کلاس', 'ویرایش کلاس');
            header("Location: index.php?success=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            addLog($pdo, $_SESSION['user_id'], 'ویرایش ناموفق کلاس', 'ویرایش کلاس');
            $error = "خطا در ویرایش کلاس: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کلاس‌ها</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">

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
        .topbar {
            background: var(--primary-color);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-direction: row-reverse;
        }
        .main-content {
            padding: 20px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        .table-responsive {
            max-width: 900px;
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="../index.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a></span>
        <span>مدیریت کلاس‌ها</span>
    </div>
    <div class="main-content">
        <div class="container mt-4">
            <h2>مدیریت کلاس‌ها</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">عملیات با موفقیت انجام شد</div>
            <?php endif; ?>

            <!-- دکمه‌های ساخت و افزودن گروهی -->
            <div class="mb-3">
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createClassModal">ساخت کلاس جدید</button>
            </div>

            <!-- جدول کلاس‌ها -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>نام کلاس</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr><td colspan="3">هیچ کلاسی یافت نشد</td></tr>
                        <?php else: ?>
                            <?php foreach ($classes as $index => $class): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($class['name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-class" data-bs-toggle="modal" data-bs-target="#editClassModal" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['name']); ?>">ویرایش</button>
                                        <button class="btn btn-sm btn-danger delete-class" data-class-id="<?php echo $class['id']; ?>">حذف</button>
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
    <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createClassModalLabel">ساخت کلاس جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_class">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="className" class="form-label">نام کلاس</label>
                            <input type="text" class="form-control" id="className" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        <button type="submit" class="btn btn-primary">ثبت</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال افزودن گروهی -->
    <div class="modal fade" id="addBulkModal" tabindex="-1" aria-labelledby="addBulkModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBulkModalLabel">افزودن گروهی دانش‌آموزان به کلاس</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_bulk">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">انتخاب کلاس</label>
                            <select class="form-select" name="class_id" required>
                                <?php
                                $stmt = $pdo->prepare("SELECT id, name FROM classes ORDER BY name ASC");
                                $stmt->execute();
                                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($classes as $class) {
                                    echo "<option value='{$class['id']}'>" . htmlspecialchars($class['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">انتخاب دانش‌آموزان</label>
                            <select class="form-select" name="student_ids[]" multiple required>
                                <?php
                                $stmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student' AND class_id IS NULL ORDER BY name ASC");
                                $stmt->execute();
                                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($students as $student) {
                                    echo "<option value='{$student['id']}'>" . htmlspecialchars($student['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        <button type="submit" class="btn btn-primary">ثبت</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال ویرایش کلاس -->
    <div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClassModalLabel">ویرایش کلاس</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editClassForm">
                    <input type="hidden" name="action" value="edit_class">
                    <input type="hidden" name="class_id" id="editClassId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editClassName" class="form-label">نام کلاس</label>
                            <input type="text" class="form-control" id="editClassName" name="name" required>
                        </div>

                        <!-- لیست اعضای کلاس -->
                        <h5>دانش‌آموزان کلاس</h5>
                        <div class="mb-3">
                            <button type="button" class="btn btn-danger" id="deleteAllStudents">حذف همه دانش‌آموزان</button>
                        </div>
                        <div id="classMembersTable" class="table-responsive mb-3">
                            <!-- جدول دانش‌آموزان با AJAX لود می‌شه -->
                        </div>

                        <!-- اضافه کردن دانش‌آموز -->
                        <div class="mb-3">
                            <label class="form-label">اضافه کردن دانش‌آموز</label>
                            <select class="form-select" name="new_students[]" multiple id="newStudentsSelect">
                                <!-- با AJAX پر می‌شه -->
                            </select>
                        </div>

                        <!-- لیست درس‌ها -->
                        <h5>درس‌های کلاس</h5>
                        <div id="classCoursesTable" class="table-responsive mb-3">
                            <!-- جدول درس‌ها با AJAX لود می‌شه -->
                        </div>

                        <!-- اضافه کردن درس جدید -->
                        <div class="mb-3">
                            <label class="form-label">اضافه کردن درس</label>
                            <input type="text" class="form-control" name="new_courses[]" placeholder="نام درس (مثل ریاضی)">
                        </div>

                        <!-- اضافه کردن معلم به درس -->
                        <div class="mb-3">
                            <label class="form-label">اضافه کردن معلم به درس</label>
                            <select class="form-select" name="course_id" id="courseSelect">
                                <!-- با AJAX پر می‌شه -->
                            </select>
                            <select class="form-select mt-2" name="new_teachers[]" multiple id="newTeachersSelect">
                                <!-- با AJAX پر می‌شه -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer>
        برنامه نویسی شده توسط 
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        function loadClassData(classId, className) {
            document.getElementById('editClassId').value = classId;
            document.getElementById('editClassName').value = className;

            
            fetch(`get_class_members.php?class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    let html = `
                        <table class="table table-striped table-bordered text-center align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>نام</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>`;
                    if (data.students.length === 0) {
                        html += `<tr><td colspan="2">هیچ دانش‌آموزی یافت نشد</td></tr>`;
                    } else {
                        data.students.forEach(student => {
                            html += `
                                <tr>
                                    <td>${student.name}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger delete-student" data-user-id="${student.id}">حذف</button>
                                    </td>
                                </tr>`;
                        });
                    }
                    html += `</tbody></table>`;
                    document.getElementById('classMembersTable').innerHTML = html;

                    
                    fetch(`get_available_students.php?class_id=${classId}`)
                        .then(res => res.json())
                        .then(data => {
                            let select = document.getElementById('newStudentsSelect');
                            select.innerHTML = '';
                            data.students.forEach(student => {
                                let option = document.createElement('option');
                                option.value = student.id;
                                option.textContent = student.name;
                                select.appendChild(option);
                            });
                        });

                    
                    fetch(`get_class_courses.php?class_id=${classId}`)
                        .then(res => res.json())
                        .then(data => {
                            let html = `
                                <table class="table table-striped table-bordered text-center align-middle">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>نام درس</th>
                                            <th>اساتید</th>
                                            <th>عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                            if (data.courses.length === 0) {
                                html += `<tr><td colspan="3">هیچ درسی یافت نشد</td></tr>`;
                            } else {
                                data.courses.forEach(course => {
                                    html += `
                                        <tr>
                                            <td>${course.course_name}</td>
                                            <td>${course.teachers.length > 0 ? course.teachers.map(t => t.name).join(', ') : 'بدون استاد'}</td>
                                            <td>
                                                <button class="btn btn-sm btn-danger delete-course" data-course-id="${course.id}">حذف</button>
                                            </td>
                                        </tr>`;
                                });
                            }
                            html += `</tbody></table>`;
                            document.getElementById('classCoursesTable').innerHTML = html;

                            
                            let courseSelect = document.getElementById('courseSelect');
                            courseSelect.innerHTML = '<option value="">انتخاب درس</option>';
                            data.courses.forEach(course => {
                                let option = document.createElement('option');
                                option.value = course.id;
                                option.textContent = course.course_name;
                                courseSelect.appendChild(option);
                            });

                            
                            courseSelect.addEventListener('change', () => {
                                const courseId = courseSelect.value;
                                if (courseId) {
                                    fetch(`get_available_teachers.php?course_id=${courseId}`)
                                        .then(res => res.json())
                                        .then(data => {
                                            let select = document.getElementById('newTeachersSelect');
                                            select.innerHTML = '';
                                            data.teachers.forEach(teacher => {
                                                let option = document.createElement('option');
                                                option.value = teacher.id;
                                                option.textContent = teacher.name;
                                                select.appendChild(option);
                                            });
                                        });
                                }
                            });

                            
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
                                            .then(data => {
                                                if (data.success) {
                                                    loadClassData(classId, className);
                                                } else {
                                                    alert('خطا در حذف: ' + data.message);
                                                }
                                            });
                                    }
                                });
                            });
                        });

                    
                    document.querySelectorAll('.delete-student').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const userId = parseInt(btn.dataset.userId);
                            if (!userId || !classId) {
                                alert('خطا: اطلاعات ناقص است');
                                return;
                            }
                            fetch('delete_class_member.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ class_id: parseInt(classId), user_id: userId })
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        loadClassData(classId, className);
                                    } else {
                                        alert('خطا در حذف: ' + data.message);
                                    }
                                });
                        });
                    });
                });

            
            document.getElementById('deleteAllStudents').addEventListener('click', () => {
                if (confirm('آیا مطمئن هستید که می‌خواهید همه دانش‌آموزان را از این کلاس حذف کنید؟')) {
                    fetch('delete_all_students.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ class_id: classId })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                loadClassData(classId, className);
                            } else {
                                alert('خطا در حذف: ' + data.message);
                            }
                        });
                }
            });

            
            
        }
        document.querySelectorAll('.delete-class').forEach(btn => {
                btn.addEventListener('click', () => {
                    const classId = parseInt(btn.dataset.classId);
                    if (confirm('آیا مطمئن هستید که می‌خواهید این کلاس را حذف کنید؟ این کار تمام درس‌ها و داده‌های مرتبط را حذف می‌کند.')) {
                        fetch('delete_class.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ class_id: classId })
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.reload(); 
                                } else {
                                    alert('خطا در حذف کلاس: ' + data.message);
                                }
                            });
                    }
                });
            });
        document.querySelectorAll('.edit-class').forEach(btn => {
            btn.addEventListener('click', () => {
                const classId = btn.dataset.classId;
                const className = btn.dataset.className;
                loadClassData(classId, className);
            });
        });
    </script>
</body>
</html>