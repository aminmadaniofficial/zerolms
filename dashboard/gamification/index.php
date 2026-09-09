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
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch user gamification points
$stmt = $pdo->prepare("SELECT points FROM user_points WHERE user_id = ?");
$stmt->execute([$user_id]);
$points = $stmt->fetchColumn() ?: 0;

// Fetch earned user badges
$stmt = $pdo->prepare("SELECT b.id, b.name, b.description, b.image_url FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ?");
$stmt->execute([$user_id]);
$user_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user_badge_ids = array_column($user_badges, 'id');

// Fetch complete system badge list
$stmt = $pdo->prepare("SELECT id, name, description, image_url FROM badges");
$stmt->execute();
$all_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query student roster for admin management
$students = [];
if ($role === 'admin') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, COALESCE(up.points, 0) as points, GROUP_CONCAT(b.id) as badge_ids
        FROM users u 
        LEFT JOIN user_points up ON u.id = up.user_id 
        LEFT JOIN user_badges ub ON u.id = ub.user_id 
        LEFT JOIN badges b ON ub.badge_id = b.id 
        WHERE u.role = 'student' 
        GROUP BY u.id, u.name, up.points
        ORDER BY u.name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sort badges to highlight unlocked badges first
usort($all_badges, function($a, $b) use ($user_badge_ids) {
    $a_has = in_array($a['id'], $user_badge_ids);
    $b_has = in_array($b['id'], $user_badge_ids);
    return $a_has === $b_has ? 0 : ($a_has ? -1 : 1);
});
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>امتیازات و نشان‌ها | سامانه یادگیری</title>
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
            --classes: #94a3b8;
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

        .badge-box {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .badge-box.active {
            border-color: #f59e0b;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.2);
        }

        .badge-box.inactive {
            opacity: 0.4;
            filter: grayscale(100%);
        }

        .badge-box img {
            width: 75px;
            height: 75px;
            object-fit: contain;
            margin-bottom: 12px;
        }

        .btn-gradient-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
            border: none !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 10px 18px !important;
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
        table th { color: var(--classes); font-weight: 600; padding: 14px; border-bottom: 1px solid var(--border-color); }
        table td { padding: 12px 14px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .modal-content { background-color: #0f172a; border: 1px solid var(--border-color); border-radius: 18px; color: var(--text-main); }
        .modal-header, .modal-footer { border-color: var(--border-color); }

        footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; padding: 12px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            color: var(--classes); font-size: 0.8rem;
            border-top: 1px solid var(--border-color);
            z-index: 99;
        }

        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>

<body>
    <div class="topbar">
        <span class="fw-bold fs-5"><i class="fas fa-award text-warning me-2"></i> امتیازات و نشان‌های افتخار</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if ($role === 'student'): ?>
            <!-- Student Score Overview Widget -->
            <div class="card-custom text-center mb-4">
                <h4 class="text-warning mb-2"><i class="fas fa-star me-2"></i> مجموع امتیازات شما: <?php echo $points; ?></h4>
                <p class="classes m-0 style-sm">با فعالیت در کلاس‌ها و تحویل به‌موقع تکالیف نشان‌های افتخار کسب کنید.</p>
            </div>

            <h5 class="mb-3 text-primary"><i class="fas fa-id-badge me-2"></i> مدال‌های افتخار</h5>
            <div class="row g-3 mb-4">
                <?php foreach ($all_badges as $badge): ?>
                    <?php $has_badge = in_array($badge['id'], $user_badge_ids); ?>
                    <div class="col-md-3 col-6">
                        <div class="badge-box <?php echo $has_badge ? 'active' : 'inactive'; ?>">
                            <img src="<?php echo htmlspecialchars($badge['image_url'] ?: '../../assets/images/default-badge.png'); ?>" alt="Badge">
                            <h6 class="m-0 font-weight-bold"><?php echo htmlspecialchars($badge['name']); ?></h6>
                            <small class=" d-block mt-1"><?php echo htmlspecialchars($badge['description'] ?: 'بدون توضیح'); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <!-- Badge Definition Management Grid -->
            <div class="card-custom mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0 text-primary"><i class="fas fa-cog me-2"></i> مدیریت نشان‌های سیستم</h5>
                    <button class="btn btn-gradient-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBadgeModal"><i class="fas fa-plus me-1"></i> تعریف نشان جدید</button>
                </div>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>تصویر</th>
                                <th>نام نشان</th>
                                <th>توضیحات</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_badges as $b): ?>
                                <tr>
                                    <td><img src="<?php echo htmlspecialchars($b['image_url'] ?: '../../assets/images/default-badge.png'); ?>" style="width: 40px; height: 40px; object-fit: contain;"></td>
                                    <td><strong><?php echo htmlspecialchars($b['name']); ?></strong></td>
                                    <td class="classes"><?php echo htmlspecialchars($b['description'] ?: '-'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger border-0" onclick="deleteBadge(<?php echo $b['id']; ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Student Gamification Points Roster -->
            <div class="card-custom">
                <h5 class="mb-3 text-primary"><i class="fas fa-users me-2"></i> مدیریت امتیازات و اعطای نشان دانش‌آموزان</h5>
                <div class="table-responsive">
                    <table class="table text-center align-middle m-0">
                        <thead>
                            <tr>
                                <th>نام دانش‌آموز</th>
                                <th>امتیاز فعلی</th>
                                <th>نشان‌های فعال</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $stu): ?>
                                <?php
                                $stu_badge_ids = $stu['badge_ids'] ? explode(',', $stu['badge_ids']) : [];
                                $stu_badges = array_filter($all_badges, fn($b) => in_array($b['id'], $stu_badge_ids));
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stu['name']); ?></strong></td>
                                    <td><span class="badge bg-warning bg-opacity-20 px-3 py-1"><?php echo $stu['points']; ?></span></td>
                                    <td>
                                        <?php foreach ($stu_badges as $badge): ?>
                                            <span class="badge bg-info bg-opacity-20 me-1"><?php echo htmlspecialchars($badge['name']); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (empty($stu_badges)): ?><span class="classes">-</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success border-0 me-1" onclick="addPoints(<?php echo $stu['id']; ?>)" title="افزایش امتیاز"><i class="fas fa-plus-circle"></i></button>
                                        <button class="btn btn-sm btn-outline-warning border-0 me-1" onclick="subtractPoints(<?php echo $stu['id']; ?>)" title="کاهش امتیاز"><i class="fas fa-minus-circle"></i></button>
                                        <button class="btn btn-sm btn-outline-info border-0 me-1" data-bs-toggle="modal" data-bs-target="#awardBadgeModal" onclick="setAwardBadgeUser(<?php echo $stu['id']; ?>)"><i class="fas fa-award"></i> اعطای نشان</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Badge Modal -->
            <div class="modal fade" id="addBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">افزودن نشان جدید</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                        <form id="addBadgeForm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label">نام نشان</label><input type="text" class="form-control" name="name" required></div>
                                <div class="mb-3"><label class="form-label">توضیحات</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                                <div class="mb-3"><label class="form-label">تصویر نشان</label><input type="file" class="form-control" name="image" accept="image/*"></div>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-gradient-primary btn-sm">ثبت نشان</button></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Award Badge Modal -->
            <div class="modal fade" id="awardBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">اعطای نشان به دانش‌آموز</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                        <form id="awardBadgeForm">
                            <input type="hidden" id="awardUserId" name="user_id">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">انتخاب نشان</label>
                                    <select class="form-select" name="badge_id" required>
                                        <option value="">انتخاب کنید...</option>
                                        <?php foreach ($all_badges as $b): ?>
                                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-gradient-primary btn-sm">اعطای نشان</button></div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        سامانه مدیریت یادگیری | طراحی شده توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setAwardBadgeUser(userId) { document.getElementById('awardUserId').value = userId; }

        async function deleteBadge(id) {
            if (confirm('آیا مطمئن هستید؟')) {
                const res = await fetch('manage_badge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                const data = await res.json();
                if (data.success) location.reload();
            }
        }

        async function addPoints(userId) {
            const points = prompt('مقدار امتیاز برای اضافه کردن:');
            if (points && !isNaN(points)) {
                const res = await fetch('manage_points.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', user_id: userId, points: parseInt(points) })
                });
                const data = await res.json();
                if (data.success) location.reload();
            }
        }

        async function subtractPoints(userId) {
            const points = prompt('مقدار امتیاز برای کسر کردن:');
            if (points && !isNaN(points)) {
                const res = await fetch('manage_points.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'subtract', user_id: userId, points: parseInt(points) })
                });
                const data = await res.json();
                if (data.success) location.reload();
            }
        }

        document.getElementById('addBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add');
            const res = await fetch('manage_badge.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) location.reload();
        });

        document.getElementById('awardBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const body = Object.fromEntries(formData);
            const res = await fetch('manage_badge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'award', ...body })
            });
            const data = await res.json();
            if (data.success) location.reload();
        });
    </script>
</body>
</html>