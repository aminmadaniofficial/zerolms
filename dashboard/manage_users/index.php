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
$class_map = array_column($classes, 'name', 'id');

// ۱. ساخت کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '') ?: null;
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $national_id = trim($_POST['nationalid'] ?? '') ?: null;
        $role = $_POST['role'] ?? '';
        $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
        
        $specialty = trim($_POST['specialty'] ?? '') ?: null;
        $specialty_en = trim($_POST['specialty_en'] ?? '') ?: null;
        $bio = trim($_POST['bio'] ?? '') ?: null;
        $bio_en = trim($_POST['bio_en'] ?? '') ?: null;

        if ($role === 'student') {
            $username = $username ?: $national_id;
            $password = $password ?: $national_id;
        }

        require_once '../../upload_security.php';
        $profile_image = null;
        if (in_array($role, ['teacher', 'admin']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $subfolder = ($role === 'admin' ? 'admins' : 'teachers');
            $dir = '../../Uploads/' . $subfolder . '/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['profile_image'],
                $dir,
                ['jpg', 'jpeg', 'png', 'webp'],
                ['image/jpeg', 'image/png', 'image/webp'],
                ($role === 'admin' ? 'admin_' : 'teacher_')
            );
            if ($success) {
                $profile_image = 'Uploads/' . $subfolder . '/' . $filename_or_err;
            }
        }

        if (empty($name) || empty($username) || empty($password)) {
            $error = "وارد کردن نام، نام کاربری و رمز عبور الزامی است.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "این نام کاربری قبلاً ثبت شده است.";
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO users (name, name_en, username, password, role, national_id, class_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $name_en, $username, password_hash($password, PASSWORD_BCRYPT), $role, $national_id, $class_id]);
                    $user_id = $pdo->lastInsertId();

                    if (in_array($role, ['teacher', 'admin'])) {
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, bio, bio_en, specialty, specialty_en, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $bio, $bio_en, $specialty, $specialty_en, $profile_image]);
                    }

                    $pdo->commit();
                    addLog($pdo, $_SESSION['user_id'], "ساخت $role با نام $name", "مدیریت کاربران", $user_id);
                    header("Location: index.php?success=1"); 
                    exit;
                } catch (Exception $e) { $pdo->rollBack(); $error = "خطا در ثبت کاربر: " . $e->getMessage(); }
            }
        }
    }
}

// ۲. ویرایش کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $user_id = (int)$_POST['user_id'];
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '') ?: null;
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $national_id = trim($_POST['nationalid'] ?? '') ?: null;
        $role = $_POST['role'] ?? '';
        $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;

        $bio = trim($_POST['bio'] ?? '') ?: null;
        $bio_en = trim($_POST['bio_en'] ?? '') ?: null;
        $specialty = trim($_POST['specialty'] ?? '') ?: null;
        $specialty_en = trim($_POST['specialty_en'] ?? '') ?: null;

        $profile_image = null;
        if (in_array($role, ['teacher', 'admin']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $subfolder = ($role === 'admin' ? 'admins' : 'teachers');
            $dir = '../../Uploads/' . $subfolder . '/';
            list($success, $filename_or_err, $dest) = store_safe_upload(
                $_FILES['profile_image'],
                $dir,
                ['jpg', 'jpeg', 'png', 'webp'],
                ['image/jpeg', 'image/png', 'image/webp'],
                ($role === 'admin' ? 'admin_' : 'teacher_')
            );
            if ($success) {
                $profile_image = 'Uploads/' . $subfolder . '/' . $filename_or_err;
            }
        }

        try {
            $pdo->beginTransaction();
            $updates = ["name = ?", "name_en = ?", "username = ?", "national_id = ?", "role = ?", "class_id = ?"];
            $params = [$name, $name_en, $username, $national_id, $role, $class_id];

            if (!empty($password)) { 
                $updates[] = "password = ?"; 
                $params[] = password_hash($password, PASSWORD_BCRYPT); 
            }
            $params[] = $user_id;
            
            $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);

            if (in_array($role, ['teacher', 'admin'])) {
                $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE user_id = ?");
                $stmt->execute([$user_id]);
                if ($stmt->fetch()) {
                    $t_updates = ["bio = ?", "bio_en = ?", "specialty = ?", "specialty_en = ?"];
                    $t_params = [$bio, $bio_en, $specialty, $specialty_en];
                    if ($profile_image) { $t_updates[] = "profile_image = ?"; $t_params[] = $profile_image; }
                    $t_params[] = $user_id;
                    $pdo->prepare("UPDATE teachers SET " . implode(', ', $t_updates) . " WHERE user_id = ?")->execute($t_params);
                } else {
                    $pdo->prepare("INSERT INTO teachers (user_id, bio, bio_en, specialty, specialty_en, profile_image) VALUES (?, ?, ?, ?, ?, ?)")->execute([$user_id, $bio, $bio_en, $specialty, $specialty_en, $profile_image]);
                }
            } else {
                $pdo->prepare("DELETE FROM teachers WHERE user_id = ?")->execute([$user_id]);
            }
            $pdo->commit();
            addLog($pdo, $_SESSION['user_id'], "ویرایش موفق کاربر ID: $user_id", "مدیریت کاربران", $user_id);
            header("Location: index.php?success=2"); 
            exit;
        } catch (Exception $e) { $pdo->rollBack(); $error = "خطا در ویرایش: " . $e->getMessage(); }
    }
}

// ۳. حذف کاربر
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $user_id = (int) $_POST['user_id'];
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM teachers WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM user_badges WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM user_points WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM report_cards WHERE student_id = ?")->execute([$user_id]);
            $pdo->prepare("UPDATE form_responses SET submitted_by = NULL WHERE submitted_by = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $pdo->commit();
            addLog($pdo, $_SESSION['user_id'], 'حذف موفق کاربر', 'مدیریت کاربران', $user_id);
            header("Location: index.php?success=3");
            exit;
        } catch (Exception $e) { $pdo->rollBack(); $error = "خطا در حذف کاربر: " . $e->getMessage(); }
    }
}

// ۴. سرچ، فیلتر نقش و صفحه‌بندی
$search = trim($_GET['search'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.username LIKE ? OR u.national_id LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

if (!empty($role_filter) && in_array($role_filter, ['student', 'teacher', 'admin'])) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}

$where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";

$count_sql = "SELECT COUNT(*) FROM users u $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

$sql = "
    SELECT u.id, u.name, u.name_en, u.username, u.national_id, u.class_id, u.role, 
           t.bio, t.bio_en, t.specialty, t.specialty_en, t.profile_image 
    FROM users u 
    LEFT JOIN teachers t ON u.id = t.user_id 
    $where_sql 
    ORDER BY u.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران | سامانه یادگیری</title>
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
            padding: 10px 18px !important;
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

        .modal-content {
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            color: var(--text-main);
        }

        .modal-header, .modal-footer { border-color: var(--border-color); }

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
        <span class="fw-bold fs-5"><i class="fas fa-users-cog text-primary me-2"></i> مدیریت کاربران</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger bg-danger bg-opacity-20 text-danger border-0 mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success bg-success bg-opacity-20 text-success border-0 mb-4">عملیات با موفقیت انجام شد.</div>
        <?php endif; ?>

        <!-- دکمه‌های ساخت -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal"><i class="fas fa-user-plus me-1"></i> ساخت دانش‌آموز</button>
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#createTeacherModal"><i class="fas fa-chalkboard-teacher me-1"></i> ساخت استاد</button>
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal"><i class="fas fa-user-shield me-1"></i> ساخت ادمین</button>
        </div>

        <!-- سرچ و فیلتر -->
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="جستجو در نام، نام کاربری یا کد ملی..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">همه نقش‌ها</option>
                    <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>دانش‌آموز</option>
                    <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>استاد</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>ادمین</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-light w-100"><i class="fas fa-filter"></i></button>
            </div>
        </form>

        <!-- جدول کاربران -->
        <div class="card-custom p-0 mb-4">
            <div class="table-responsive">
                <table class="table text-center align-middle m-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام کاربر</th>
                            <th>نام انگلیسی</th>
                            <th>نام کاربری</th>
                            <th>نقش</th>
                            <th>کد ملی</th>
                            <th>کلاس</th>
                            <th>تخصص</th>
                            <th>تصویر</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="10" class="py-4 text-muted">هیچ کاربری یافت نشد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td dir="ltr" class="text-start"><?php echo htmlspecialchars($user['name_en'] ?: '-'); ?></td>
                                    <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'student' => '<span class="badge bg-info bg-opacity-20  px-2 py-1">دانش‌آموز</span>',
                                            'teacher' => '<span class="badge bg-warning bg-opacity-20  px-2 py-1">استاد</span>',
                                            'admin' => '<span class="badge bg-danger bg-opacity-20  px-2 py-1">ادمین</span>'
                                        ];
                                        echo $badges[$user['role']] ?? $user['role'];
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['national_id'] ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        echo $user['class_id'] && isset($class_map[$user['class_id']])
                                            ? htmlspecialchars($class_map[$user['class_id']])
                                            : '-';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['specialty'] ?: '-'); ?></td>
                                    <td>
                                        <?php if (in_array($user['role'], ['teacher', 'admin']) && $user['profile_image']): ?>
                                            <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning edit-user border-0 me-1" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-name-en="<?php echo htmlspecialchars($user['name_en'] ?: ''); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-national-id="<?php echo htmlspecialchars($user['national_id'] ?: ''); ?>"
                                            data-class-id="<?php echo htmlspecialchars($user['class_id'] ?: ''); ?>"
                                            data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                            data-bio="<?php echo htmlspecialchars($user['bio'] ?: ''); ?>"
                                            data-bio-en="<?php echo htmlspecialchars($user['bio_en'] ?: ''); ?>"
                                            data-specialty="<?php echo htmlspecialchars($user['specialty'] ?: ''); ?>"
                                            data-specialty-en="<?php echo htmlspecialchars($user['specialty_en'] ?: ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form method="POST" class="d-inline" onsubmit="return confirm('آیا از حذف این کاربر اطمینان دارید؟');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <?php if ($total_pages > 1): ?>
            <nav class="d-flex justify-content-center">
                <ul class="pagination gap-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link bg-dark text-light border-secondary" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- مودال ساخت دانش‌آموز -->
    <div class="modal fade" id="createStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus text-primary me-2"></i> ساخت دانش‌آموز</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="student">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">نام و نام خانوادگی</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نام کاربری (اختیاری)</label>
                            <input type="text" class="form-control" name="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور (اختیاری)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">کد ملی</label>
                            <input type="text" class="form-control" name="nationalid">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">کلاس</label>
                            <select class="form-select" name="class_id">
                                <option value="">انتخاب کلاس...</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ثبت کاربر</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال ساخت استاد -->
    <div class="modal fade" id="createTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chalkboard-teacher text-warning me-2"></i> ساخت استاد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="teacher">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">نام و نام خانوادگی</label><input type="text" class="form-control" name="name" required></div>
                        <div class="mb-3"><label class="form-label">نام (انگلیسی)</label><input type="text" class="form-control" name="name_en" dir="ltr"></div>
                        <div class="mb-3"><label class="form-label">نام کاربری</label><input type="text" class="form-control" name="username" required></div>
                        <div class="mb-3"><label class="form-label">رمز عبور</label><input type="password" class="form-control" name="password" required></div>
                        <div class="mb-3"><label class="form-label">تخصص</label><input type="text" class="form-control" name="specialty"></div>
                        <div class="mb-3"><label class="form-label">تخصص (انگلیسی)</label><input type="text" class="form-control" name="specialty_en" dir="ltr"></div>
                        <div class="mb-3"><label class="form-label">بیوگرافی</label><textarea class="form-control" name="bio" rows="3"></textarea></div>
                        <div class="mb-3"><label class="form-label">بیوگرافی (انگلیسی)</label><textarea class="form-control" name="bio_en" rows="3" dir="ltr"></textarea></div>
                        <div class="mb-3"><label class="form-label">تصویر پروفایل</label><input type="file" class="form-control" name="profile_image" accept="image/*"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ثبت استاد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال ساخت ادمین -->
    <div class="modal fade" id="createAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-shield text-danger me-2"></i> ساخت ادمین</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="admin">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label">نام و نام خانوادگی</label><input type="text" class="form-control" name="name" required></div>
                        <div class="mb-3"><label class="form-label">نام (انگلیسی)</label><input type="text" class="form-control" name="name_en" dir="ltr"></div>
                        <div class="mb-3"><label class="form-label">نام کاربری</label><input type="text" class="form-control" name="username" required></div>
                        <div class="mb-3"><label class="form-label">رمز عبور</label><input type="password" class="form-control" name="password" required></div>
                        <div class="mb-3"><label class="form-label">تصویر پروفایل</label><input type="file" class="form-control" name="profile_image" accept="image/*"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">ثبت ادمین</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال ویرایش کاربر -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit text-warning me-2"></i> ویرایش کاربر</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="editUserId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label">نام فارسی</label><input type="text" class="form-control" id="editName" name="name" required></div>
                            <div class="col-md-6"><label class="form-label">نام انگلیسی</label><input type="text" class="form-control" id="editNameEn" name="name_en" dir="ltr"></div>
                            <div class="col-md-6"><label class="form-label">نام کاربری</label><input type="text" class="form-control" id="editUsername" name="username" required></div>
                            <div class="col-md-6"><label class="form-label">رمز عبور جدید (اختیاری)</label><input type="password" class="form-control" id="editPassword" name="password"></div>
                            <div class="col-md-6"><label class="form-label">کد ملی</label><input type="text" class="form-control" id="editNationalId" name="nationalid"></div>
                            <div class="col-md-6">
                                <label class="form-label">نقش کاربر</label>
                                <select class="form-select" id="editRole" name="role" required>
                                    <option value="student">دانش‌آموز</option>
                                    <option value="teacher">استاد</option>
                                    <option value="admin">ادمین</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">کلاس</label>
                                <select class="form-select" id="editClassId" name="class_id">
                                    <option value="">بدون کلاس</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="teacher-admin-fields row g-3" style="display: none;">
                            <div class="col-md-6"><label class="form-label">تخصص (فارسی)</label><input type="text" class="form-control" id="editSpecialty" name="specialty"></div>
                            <div class="col-md-6"><label class="form-label">تخصص (انگلیسی)</label><input type="text" class="form-control" id="editSpecialtyEn" name="specialty_en" dir="ltr"></div>
                            <div class="col-md-6"><label class="form-label">بیوگرافی (فارسی)</label><textarea class="form-control" id="editBio" name="bio" rows="3"></textarea></div>
                            <div class="col-md-6"><label class="form-label">بیوگرافی (انگلیسی)</label><textarea class="form-control" id="editBioEn" name="bio_en" rows="3" dir="ltr"></textarea></div>
                            <div class="col-md-12"><label class="form-label">تصویر پروفایل جدید (اختیاری)</label><input type="file" class="form-control" id="editProfileImage" name="profile_image" accept="image/*"></div>
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
        document.addEventListener('DOMContentLoaded', () => {
            const editRoleSelect = document.getElementById('editRole');
            function toggleTeacherFields(role) {
                const isTeacherOrAdmin = ['teacher', 'admin'].includes(role);
                document.querySelectorAll('.teacher-admin-fields').forEach(field => {
                    field.style.display = isTeacherOrAdmin ? 'flex' : 'none';
                });
            }

            editRoleSelect.addEventListener('change', function () {
                toggleTeacherFields(this.value);
            });

            document.querySelectorAll('.edit-user').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('editUserId').value = btn.dataset.userId;
                    document.getElementById('editName').value = btn.dataset.name;
                    document.getElementById('editNameEn').value = btn.dataset.nameEn || '';
                    document.getElementById('editUsername').value = btn.dataset.username;
                    document.getElementById('editNationalId').value = btn.dataset.nationalId || '';
                    document.getElementById('editClassId').value = btn.dataset.classId || '';
                    document.getElementById('editRole').value = btn.dataset.role;
                    document.getElementById('editBio').value = btn.dataset.bio || '';
                    document.getElementById('editBioEn').value = btn.dataset.bioEn || '';
                    document.getElementById('editSpecialty').value = btn.dataset.specialty || '';
                    document.getElementById('editSpecialtyEn').value = btn.dataset.specialtyEn || '';
                    
                    toggleTeacherFields(btn.dataset.role);
                });
            });
        });
    </script>
</body>
</html>