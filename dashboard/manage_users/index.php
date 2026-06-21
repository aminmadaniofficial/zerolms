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


$stmt = $pdo->prepare("SELECT id, name FROM classes ORDER BY name ASC");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$class_map = array_column($classes, 'name', 'id');


$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.username, u.national_id, u.class_id, u.role, t.bio, t.specialty, t.profile_image 
    FROM users u 
    LEFT JOIN teachers t ON u.id = t.user_id 
    ORDER BY u.name ASC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر";
    } else {
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $national_id = trim($_POST['nationalid'] ?? '') ?: null;
        $role = $_POST['role'] ?? '';
        $specialty = trim($_POST['specialty'] ?? '') ?: null;
        $specialty_en = trim($_POST['specialty_en'] ?? '') ?: null;
        $bio = trim($_POST['bio'] ?? '') ?: null;
        $bio_en = trim($_POST['bio_en'] ?? '') ?: null;
        

        if ($role === 'student') {
            $username = $username ?: $national_id;
            $password = $password ?: $national_id;
        }

        $profile_image = null;
        if (in_array($role, ['teacher', 'admin']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/' . ($role === 'admin' ? 'admins' : 'teachers') . '/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = ($role === 'admin' ? 'admin_' : 'teacher_') . uniqid() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dir . $filename)) {
                $profile_image = 'Uploads/' . ($role === 'admin' ? 'admins' : 'teachers') . '/' . $filename;
            } else { $error = "خطا در آپلود فایل"; }
        }

        if (!isset($error)) {
            if (empty($name) || empty($username) || empty($password)) {
                $error = "پر کردن فیلدها الزامی است";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = "نام کاربری تکراری است";
                } else {
                    try {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("INSERT INTO users (name, name_en, username, password, role, national_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$name, $name_en, $username, password_hash($password, PASSWORD_BCRYPT), $role, $national_id]);
                        $user_id = $pdo->lastInsertId();
                        if (in_array($role, ['teacher', 'admin'])) {
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, bio, bio_en, specialty, specialty_en, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $bio, $bio_en, $specialty, $specialty_en, $profile_image]);
                        }
                        $pdo->commit();
                        addLog($pdo, $_SESSION['user_id'], "ساخت $role", "مدیریت");
                        header("Location: index.php?success=1"); exit;
                    } catch (Exception $e) { $pdo->rollBack(); $error = "خطا: " . $e->getMessage(); }
                }
            }
        }
    


        
        error_log("[index.php] Input: name=$name, username=$username, password=$password, role=$role, national_id=" . ($national_id ?? 'NULL') . ", bio=" . ($bio ?? 'NULL') . ", specialty=" . ($specialty ?? 'NULL') . ", profile_image=" . ($profile_image ?? 'NULL'));

        
        if (empty($name)) {
            $error = "نام الزامی است";
            error_log("[index.php] Validation Error: $error");
        } elseif (empty($username)) {
            $error = "نام کاربری الزامی است";
            error_log("[index.php] Validation Error: $error");
        } elseif (empty($password)) {
            $error = "رمز عبور الزامی است";
            error_log("[index.php] Validation Error: $error");
        } elseif (!in_array($role, ['student', 'teacher', 'admin'])) {
            $error = "نقش نامعتبر است: role=$role";
            error_log("[index.php] Validation Error: $error");
        } elseif (in_array($role, ['teacher', 'admin']) && empty($specialty)) {
            $error = "تخصص برای $role الزامی است";
            error_log("[index.php] Validation Error: $error");
        } else {
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "نام کاربری قبلاً استفاده شده است: username=$username";
                error_log("[index.php] Duplicate Username: $error");
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, username, password, role, national_id, class_id, created_at)
                        VALUES (?, ?, ?, ?, ?, NULL, NOW())
                    ");
                    $params = [
                        $name,
                        $username,
                        password_hash($password, PASSWORD_BCRYPT),
                        $role,
                        $national_id
                    ];
                    error_log("[index.php] Executing INSERT users with params: " . print_r($params, true));
                    $stmt->execute($params);
                    $user_id = $pdo->lastInsertId();

                    
                    if (in_array($role, ['teacher', 'admin'])) {
                        $stmt = $pdo->prepare("INSERT INTO teachers (user_id, bio, specialty, profile_image) VALUES (?, ?, ?, ?)");
                        $teacher_params = [$user_id, $bio, $specialty, $profile_image];
                        error_log("[index.php] Executing INSERT teachers with params: " . print_r($teacher_params, true));
                        $stmt->execute($teacher_params);
                    }

                    $pdo->commit();
                    if ($role === "teacher") {
                        addLog($pdo, $_SESSION['user_id'], 'ساخت موفق استاد', 'ساخت استاد', $user_id);
                    } elseif ($role === 'admin') {
                        addLog($pdo, $_SESSION['user_id'], 'ساخت موفق ادمین', 'ساخت ادمین', $user_id);
                    } elseif ($role === 'student') {
                        addLog($pdo, $_SESSION['user_id'], 'ساخت موفق دانش آموز', 'ساخت دانش آموز', $user_id);
                    }
                    header("Location: index.php?success=1");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "خطا در ثبت کاربر: " . $e->getMessage();
                    addLog($pdo, $_SESSION['user_id'], 'ساخت ناموفق کاربر', 'ساخت کاربر', $user_id ?? null);
                    error_log("[index.php] Database Error: $error");
                }
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن نامعتبر";
    } else {
        $user_id = (int)$_POST['user_id'];
        $name = trim($_POST['name'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '') ?: null;
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $national_id = trim($_POST['nationalid'] ?? '');
        $role = $_POST['role'] ?? '';
        $bio = trim($_POST['bio'] ?? '') ?: null;
        $bio_en = trim($_POST['bio_en'] ?? '') ?: null;
        $specialty = trim($_POST['specialty'] ?? '') ?: null;
        $specialty_en = trim($_POST['specialty_en'] ?? '') ?: null;

        $profile_image = null;
        if (in_array($role, ['teacher', 'admin']) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $dir = '../../Uploads/' . ($role === 'admin' ? 'admins' : 'teachers') . '/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = ($role === 'admin' ? 'admin_' : 'teacher_') . uniqid() . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dir . $filename)) {
                $profile_image = 'Uploads/' . ($role === 'admin' ? 'admins' : 'teachers') . '/' . $filename;
            }
        }

        try {
            $pdo->beginTransaction();
            $updates = ["name = ?", "name_en = ?", "username = ?", "national_id = ?", "role = ?"];
            $params = [$name, $name_en, $username, $national_id, $role];

            if (!empty($password)) { $updates[] = "password = ?"; $params[] = password_hash($password, PASSWORD_BCRYPT); }
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
            }
            $pdo->commit();
            header("Location: index.php?success=2"); exit;
        } catch (Exception $e) { $pdo->rollBack(); $error = "خطا: " . $e->getMessage(); }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر";
    } else {
        $user_id = (int) $_POST['user_id'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $pdo->commit();
            addLog($pdo, $_SESSION['user_id'], 'حذف موفق کاربر', 'حذف کاربر', $user_id);
            header("Location: index.php?success=3");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            addLog($pdo, $_SESSION['user_id'], 'حذف ناموفق کاربر', 'حذف کاربر', $user_id);
            $error = "خطا در حذف کاربر: " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_class_users') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر";
    } else {
        $class_id = (int) $_POST['class_id'];
        if ($class_id <= 0) {
            $error = "کلاس انتخاب نشده است";
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("DELETE FROM teachers WHERE user_id IN (SELECT id FROM users WHERE class_id = ? AND role = 'student')");
                $stmt->execute([$class_id]);
                $stmt = $pdo->prepare("DELETE FROM users WHERE class_id = ? AND role = 'student'");
                $stmt->execute([$class_id]);
                $pdo->commit();
                addLog($pdo, $_SESSION['user_id'], 'حذف موفق دانش آموزان کلاس', 'حذف دانش آموزان کلاس');
                header("Location: index.php?success=4");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                addLog($pdo, $_SESSION['user_id'], 'حذف ناموفق دانش آموزان کلاس', 'حذف دانش آموزان کلاس');
                $error = "خطا در حذف کاربران کلاس: " . $e->getMessage();
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
    <title>مدیریت کاربران</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <style>
        :root {
            --primary-color: linear-gradient(135deg, #00b7eb, #0077b6);
            --secondary-color: linear-gradient(135deg, #ff8c00, #ff4500);
            --bg-color: #0f172a;
            --card-bg: rgba(255, 255, 255, 0.05);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --text-color: #e2e8f0;
        }

        body {
            font-family: 'font-iran-normal', 'Vazir', 'Shabnam', sans-serif;
            background: linear-gradient(270deg, #0f172a, #1a2238, #0f172a);
            background-size: 600% 600%;
            animation: moveBg 15s ease infinite;
            color: var(--text-color);
            min-height: 100vh;
            padding-bottom: 80px;
            overflow-x: hidden;
        }

        @keyframes moveBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        h1, h2, h3 {
            color: var(--text-color);
            text-shadow: 0 0 10px #00b7eb, 0 0 20px #0077b6, 0 0 30px #00b7eb;
            animation: glowText 2s ease-in-out infinite alternate;
        }

        @keyframes glowText {
            from { text-shadow: 0 0 5px #00b7eb, 0 0 10px #0077b6; }
            to { text-shadow: 0 0 20px #ff8c00, 0 0 30px #ff4500, 0 0 40px #ff8c00; }
        }

        p, h4, h5 { color: var(--text-color); }

        .sidebar {
            height: 100vh;
            background: var(--primary-color);
            color: #fff;
            padding: 20px 0;
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            transition: right 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
        }

        .sidebar.show { right: 0; }

        .sidebar .nav-link {
            color: #fff;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transform: translateX(20px);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: var(--glass-bg);
            transform: translateX(10px);
            border-radius: 8px;
        }

        .sidebar .nav-link i {
            margin-left: 12px;
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link:hover i { transform: scale(1.2); }

        .main-content {
            margin-right: 0;
            padding: 30px;
            transition: margin-right 0.4s ease;
        }

        @media (min-width: 992px) {
            .sidebar { right: 0; }
            .main-content { margin-right: 300px; }
        }

        .topbar {
            background: var(--primary-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-direction: row-reverse;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar .hamburger {
            font-size: 1.8rem;
            cursor: pointer;
            display: none;
            transition: transform 0.3s ease;
        }

        .topbar .hamburger:hover { transform: rotate(90deg); }

        @media (max-width: 991px) {
            .topbar .hamburger { display: block; }
        }

        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: #fff;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.6);
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px #00b7eb, 0 0 25px #0077b6;
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
        }

        .btn-primary:active::after {
            width: 200px;
            height: 200px;
        }

        .class-list .list-group-item,
        .gallery-list .list-group-item {
            background: var(--card-bg);
            border: none;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .class-list .list-group-item:hover,
        .gallery-list .list-group-item:hover {
            background: var(--secondary-color);
            transform: translateX(5px);
            color: #fff;
        }

        .dashboard-section {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .gallery-img {
            max-width: 120px;
            height: auto;
            border-radius: 8px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .gallery-img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px #00b7eb;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: calc(100% - 20px);
            right: 10px;
            text-align: center;
            padding: 15px 0;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            color: var(--text-color);
            font-weight: bold;
            box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.4);
            border-radius: 12px 12px 0 0;
            z-index: 998;
        }

        footer a {
            color: #00b7eb;
            text-decoration: none;
            position: relative;
            transition: color 0.3s ease;
        }

        footer a:hover { color: #ff8c00; }

        footer a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: var(--secondary-color);
            transition: width 0.3s ease;
        }

        footer a:hover::after { width: 100%; }

        .logo { animation: logoSpin 2s ease-in-out; }

        @keyframes logoSpin {
            0% { transform: rotate(0deg); }
            50% { transform: rotate(10deg); }
            100% { transform: rotate(0deg); }
        }

        select, option {
            background-color: var(--secondary-color);
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
        }

        a, button, select, option {
            cursor: url(https:
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

        .table-responsive { max-width: 900px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.6s ease-in-out;
        }

        table thead {
            background: var(--primary-color);
            color: #fff;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
        }

        table th, table td {
            padding: 14px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: background 0.3s ease, color 0.3s ease, transform 0.3s ease;
        }

        table tbody tr:hover {
            background: var(--secondary-color);
            color: #fff;
            transform: scale(1.01);
        }

        table td:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.05);
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
                width: 100%;
            }
            thead { display: none; }
            tr {
                margin-bottom: 15px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                border-radius: 10px;
                overflow: hidden;
            }
            td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: bold;
                color: #00b7eb;
            }
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
            animation: fadeIn 0.4s ease-in-out;
        }

        .modal-content {
            background: var(--card-bg);
            border: none;
            border-radius: 15px;
            padding: 25px;
            color: var(--text-color);
            backdrop-filter: blur(12px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.6), 0 0 25px #00b7eb;
            animation: modalIn 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modal-content:hover {
            transform: scale(1.02);
            box-shadow: 0 0 25px #ff8c00, 0 0 35px #ff4500;
        }

        .modal-header {
            background: var(--primary-color);
            color: #fff;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
            padding: 15px 20px;
            text-shadow: 0 0 8px rgba(0, 0, 0, 0.4);
        }

        .modal-body {
            padding: 20px;
            font-size: 1rem;
            line-height: 1.6;
            animation: fadeIn 0.6s ease-in-out;
        }

        .modal-footer {
            border-top: none;
            padding: 15px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: var(--glass-bg);
            border-radius: 0 0 15px 15px;
        }

        .modal-footer .btn {
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modal-footer .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 15px #00b7eb, 0 0 25px #0077b6;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.8) translateY(-50px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <span><a href="../index.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a></span>
        <span>مدیریت کاربران</span>
        <span class="hamburger" id="hamburger"><i class="fas fa-bars fa-spin-hover"></i></span>
    </div>
    <div class="main-content">
        <div class="container mt-4">
            <h2>مدیریت کاربران</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    switch ($_GET['success']) {
                        case 1: echo "کاربر با موفقیت ثبت شد"; break;
                        case 2: echo "کاربر با موفقیت ویرایش شد"; break;
                        case 3: echo "کاربر با موفقیت حذف شد"; break;
                        case 4: echo "کاربران کلاس با موفقیت حذف شدند"; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createStudentModal">ساخت دانش‌آموز</button>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createTeacherModal">ساخت استاد</button>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createAdminModal">ساخت ادمین</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteClassUsersModal">حذف کاربران کلاس</button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>نام</th>
                            <th>نام کاربری</th>
                            <th>نقش</th>
                            <th>کد ملی</th>
                            <th>کلاس</th>
                            <th>تخصص</th>
                            <th>تصویر پروفایل</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="9">هیچ کاربری یافت نشد</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td data-label="#"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td data-label="نام"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td data-label="نام کاربری"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td data-label="نقش">
                                        <?php
                                        $roles = ['student' => 'دانش‌آموز', 'teacher' => 'استاد', 'admin' => 'ادمین'];
                                        echo htmlspecialchars($roles[$user['role']] ?? $user['role']);
                                        ?>
                                    </td>
                                    <td data-label="کد ملی"><?php echo htmlspecialchars($user['national_id'] ?: '-'); ?></td>
                                    <td data-label="کلاس">
                                        <?php
                                        echo $user['class_id'] && isset($class_map[$user['class_id']])
                                            ? htmlspecialchars($class_map[$user['class_id']] . " ({$user['class_id']})")
                                            : '-';
                                        ?>
                                    </td>
                                    <td data-label="تخصص"><?php echo htmlspecialchars($user['specialty'] ?: '-'); ?></td>
                                    <td data-label="تصویر پروفایل">
                                        <?php if (in_array($user['role'], ['teacher', 'admin']) && $user['profile_image']): ?>
                                            <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile"
                                                style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="عملیات">
                                        <button class="btn btn-sm btn-primary edit-user" data-bs-toggle="modal"
                                            data-bs-target="#editUserModal" data-user-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-national-id="<?php echo htmlspecialchars($user['national_id'] ?: ''); ?>"
                                            data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                            data-bio="<?php echo htmlspecialchars($user['bio'] ?: ''); ?>"
                                            data-specialty="<?php echo htmlspecialchars($user['specialty'] ?: ''); ?>"
                                            data-profile-image="<?php echo htmlspecialchars($user['profile_image'] ?: ''); ?>">
                                            ویرایش
                                        </button>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createStudentModal" tabindex="-1" aria-labelledby="createStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createStudentModalLabel">ساخت دانش‌آموز</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="student">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="studentName" class="form-label">نام</label>
                            <input type="text" class="form-control" id="studentName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentUsername" class="form-label">نام کاربری (اختیاری - در صورت خالی بودن، کد ملی استفاده می‌شود)</label>
                            <input type="text" class="form-control" id="studentUsername" name="username">
                        </div>
                        <div class="mb-3">
                            <label for="studentPassword" class="form-label">رمز عبور (اختیاری - در صورت خالی بودن، کد ملی استفاده می‌شود)</label>
                            <input type="password" class="form-control" id="studentPassword" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="studentNationalId" class="form-label">کد ملی</label>
                            <input type="text" class="form-control" id="studentNationalId" name="nationalid">
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

    <div class="modal fade" id="createTeacherModal" tabindex="-1" aria-labelledby="createTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTeacherModalLabel">ساخت استاد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="teacher">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="teacherName" class="form-label">نام</label>
                            <input type="text" class="form-control" id="teacherName" name="name" required>
                        </div>
                        <div class="mb-3">
    <label class="form-label">نام (انگلیسی)</label>
    <input type="text" class="form-control" name="name_en" dir="ltr">
</div>
                        <div class="mb-3">
                            <label for="teacherUsername" class="form-label">نام کاربری</label>
                            <input type="text" class="form-control" id="teacherUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="teacherPassword" class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" id="teacherPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="teacherBio" class="form-label">بیوگرافی</label>
                            <textarea class="form-control" id="teacherBio" name="bio" rows="4"></textarea>
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
    <label class="form-label">بیوگرافی (انگلیسی)</label>
    <textarea class="form-control" name="bio_en" rows="4" dir="ltr"></textarea>
</div>
                        <div class="mb-3">
                            <label for="teacherSpecialty" class="form-label">تخصص</label>
                            <input type="text" class="form-control" id="teacherSpecialty" name="specialty" required>
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
    <label class="form-label">تخصص (انگلیسی)</label>
    <input type="text" class="form-control" name="specialty_en" dir="ltr">
</div>
                        <div class="mb-3">
                            <label for="teacherProfileImage" class="form-label">تصویر پروفایل</label>
                            <input type="file" class="form-control" id="teacherProfileImage" name="profile_image"
                                accept="image/jpeg,image/png,image/gif">
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

    <div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAdminModalLabel">ساخت ادمین</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="role" value="admin">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="adminName" class="form-label">نام</label>
                            <input type="text" class="form-control" id="adminName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminUsername" class="form-label">نام کاربری</label>
                            <input type="text" class="form-control" id="adminUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminBio" class="form-label">بیوگرافی</label>
                            <textarea class="form-control" id="adminBio" name="bio" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="adminSpecialty" class="form-label">تخصص</label>
                            <input type="text" class="form-control" id="adminSpecialty" name="specialty" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminProfileImage" class="form-label">تصویر پروفایل</label>
                            <input type="file" class="form-control" id="adminProfileImage" name="profile_image"
                                accept="image/jpeg,image/png,image/gif">
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

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">ویرایش کاربر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editUserForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="editUserId">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editName" class="form-label">نام</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نام (انگلیسی)</label>
                            <input type="text" class="form-control" id="editNameEn" name="name_en" dir="ltr">
                        </div>
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">نام کاربری</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">رمز عبور جدید (اختیاری)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="editNationalId" class="form-label">کد ملی</label>
                            <input type="text" class="form-control" id="editNationalId" name="nationalid">
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">نقش</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="student">دانش‌آموز</option>
                                <option value="teacher">استاد</option>
                                <option value="admin">ادمین</option>
                            </select>
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
                            <label for="editSpecialty" class="form-label">تخصص</label>
                            <input type="text" class="form-control" id="editSpecialty" name="specialty">
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
                            <label class="form-label">تخصص (انگلیسی)</label>
                            <input type="text" class="form-control" id="editSpecialtyEn" name="specialty_en" dir="ltr">
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
                            <label for="editBio" class="form-label">بیوگرافی</label>
                            <textarea class="form-control" id="editBio" name="bio" rows="4"></textarea>
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
                            <label class="form-label">بیوگرافی (انگلیسی)</label>
                            <textarea class="form-control" id="editBioEn" name="bio_en" rows="4" dir="ltr"></textarea>
                        </div>
                        <div class="mb-3 teacher-admin-fields" style="display: none;">
                            <label for="editProfileImage" class="form-label">تصویر پروفایل (اختیاری)</label>
                            <input type="file" class="form-control" id="editProfileImage" name="profile_image"
                                accept="image/jpeg,image/png,image/gif">
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

    <div class="modal fade" id="deleteClassUsersModal" tabindex="-1" aria-labelledby="deleteClassUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassUsersModalLabel">حذف کاربران کلاس</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_class_users">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deleteClassId" class="form-label">انتخاب کلاس</label>
                            <select class="form-select" id="deleteClassId" name="class_id" required>
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('آیا مطمئن هستید که می‌خواهید همه دانش‌آموزان این کلاس را حذف کنید؟');">حذف</button>
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
        document.addEventListener('DOMContentLoaded', () => {
            const hamburger = document.getElementById('hamburger');
            const sidebar = document.getElementById('sidebar');
            hamburger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Hamburger clicked!');
                sidebar.classList.toggle('show');
                console.log('Sidebar class:', sidebar.className);
            });

            document.getElementById('editRole').addEventListener('change', function () {
                const teacherAdminFields = document.querySelectorAll('.teacher-admin-fields');
                const isTeacherOrAdmin = ['teacher', 'admin'].includes(this.value);
                teacherAdminFields.forEach(field => {
                    field.style.display = isTeacherOrAdmin ? 'block' : 'none';
                    if (isTeacherOrAdmin) {
                        document.getElementById('editSpecialty').required = true;
                        document.getElementById('editProfileImage').required = false;
                    } else {
                        document.getElementById('editSpecialty').required = false;
                        document.getElementById('editProfileImage').required = false;
                    }
                });
            });

            document.querySelectorAll('.edit-user').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('editUserId').value = btn.dataset.userId;
                    document.getElementById('editName').value = btn.dataset.name;
                    document.getElementById('editNameEn').value = btn.dataset.nameEn || '';
                    document.getElementById('editUsername').value = btn.dataset.username;
                    document.getElementById('editNationalId').value = btn.dataset.nationalId;
                    document.getElementById('editRole').value = btn.dataset.role;
                    document.getElementById('editBio').value = btn.dataset.bio || '';
                    document.getElementById('editBioEn').value = btn.dataset.bioEn || '';
                    document.getElementById('editSpecialty').value = btn.dataset.specialty || '';
                    document.getElementById('editSpecialtyEn').value = btn.dataset.specialtyEn || '';
                    
                    const teacherAdminFields = document.querySelectorAll('.teacher-admin-fields');
                    const isTeacherOrAdmin = ['teacher', 'admin'].includes(btn.dataset.role);
                    teacherAdminFields.forEach(field => {
                        field.style.display = isTeacherOrAdmin ? 'block' : 'none';
                        if (isTeacherOrAdmin) {
                            document.getElementById('editSpecialty').required = true;
                            document.getElementById('editProfileImage').required = false;
                        } else {
                            document.getElementById('editSpecialty').required = false;
                            document.getElementById('editProfileImage').required = false;
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>