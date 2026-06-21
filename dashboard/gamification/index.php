<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];


$stmt = $pdo->prepare("SELECT points FROM user_points WHERE user_id = ?");
$stmt->execute([$user_id]);
$points = $stmt->fetchColumn() ?: 0;


$stmt = $pdo->prepare("SELECT b.id, b.name, b.description, b.image_url FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ?");
$stmt->execute([$user_id]);
$user_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user_badge_ids = array_column($user_badges, 'id');


$stmt = $pdo->prepare("SELECT id, name, description, image_url FROM badges");
$stmt->execute();
$all_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
    <title>امتیازات و نشان‌ها</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .badge-card {
            text-align: center;
            margin: 15px;
        }

        .badge-card img {
            max-width: 100px;
            transition: opacity 0.3s;
        }

        .badge-card.inactive img {
            opacity: 0.3;
            filter: grayscale(100%);
        }

        .badge-card.active img {
            opacity: 1;
        }

        .container {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4" style="text-align:center;">
        <?php if ($role === 'student'): ?>
            <h3><i class="fas fa-trophy"></i> امتیازات و نشان‌های شما</h3>
            <p>امتیازات: <strong><?php echo $points; ?></strong></p>
            <hr>
            <div class="row">
                <?php foreach ($all_badges as $badge): ?>
                    <div
                        class="col-md-3 badge-card <?php echo in_array($badge['id'], $user_badge_ids) ? 'active' : 'inactive'; ?>">
                        <img src="<?php echo htmlspecialchars($badge['image_url'] ?: '../../assets/images/default-badge.png'); ?>"
                            alt="<?php echo htmlspecialchars($badge['name']); ?>">
                        <p><?php echo htmlspecialchars($badge['name']); ?></p>
                        <small><?php echo htmlspecialchars($badge['description'] ?: 'بدون توضیح'); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr>
            <h5>پر رنگ بودن نشان به معنای داشتن آن نشان و کم رنگ بودن به معنای نداشتن آن نشان می باشد.</h5>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>

            <h3>مدیریت نشان‌ها</h3>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addBadgeModal">افزودن نشان</button>
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>نام نشان</th>
                        <th>توضیحات</th>
                        <th>تصویر</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_badges as $badge): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($badge['name']); ?></td>
                            <td><?php echo htmlspecialchars($badge['description'] ?: '-'); ?></td>
                            <td><img src="<?php echo htmlspecialchars($badge['image_url'] ?: '../../assets/images/default-badge.png'); ?>"
                                    style="max-width: 50px;"></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBadgeModal"
                                    onclick="editBadge(<?php echo $badge['id']; ?>, '<?php echo htmlspecialchars($badge['name']); ?>', '<?php echo htmlspecialchars($badge['description'] ?: ''); ?>', '<?php echo htmlspecialchars($badge['image_url'] ?: ''); ?>')">ویرایش</button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="deleteBadge(<?php echo $badge['id']; ?>)">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>مدیریت امتیازات و نشان‌های دانش‌آموزان</h3>
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>نام دانش‌آموز</th>
                        <th>امتیاز</th>
                        <th>نشان‌ها</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $student_badge_ids = $student['badge_ids'] ? explode(',', $student['badge_ids']) : [];
                        $student_badges = array_filter($all_badges, fn($b) => in_array($b['id'], $student_badge_ids));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo $student['points'] ?: 0; ?></td>
                            <td>
                                <?php foreach ($student_badges as $badge): ?>
                                    <span class="badge bg-info me-1"><?php echo htmlspecialchars($badge['name']); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="addPoints(<?php echo $student['id']; ?>)">اضافه
                                    کردن امتیاز</button>
                                <button class="btn btn-sm btn-warning"
                                    onclick="subtractPoints(<?php echo $student['id']; ?>)">کاهش امتیاز</button>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#awardBadgeModal"
                                    onclick="setAwardBadgeUser(<?php echo $student['id']; ?>)">اعطای نشان</button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#revokeBadgeModal"
                                    onclick="setRevokeBadgeUser(<?php echo $student['id']; ?>)">گرفتن نشان</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- مودال افزودن نشان -->
            <div class="modal fade" id="addBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">افزودن نشان جدید</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addBadgeForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="badgeName" class="form-label">نام نشان</label>
                                    <input type="text" class="form-control" id="badgeName" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="badgeDescription" class="form-label">توضیحات</label>
                                    <textarea class="form-control" id="badgeDescription" name="description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="badgeImage" class="form-label">تصویر نشان</label>
                                    <input type="file" class="form-control" id="badgeImage" name="image" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary">ذخیره</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مودال ویرایش نشان -->
            <div class="modal fade" id="editBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">ویرایش نشان</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editBadgeForm" enctype="multipart/form-data">
                                <input type="hidden" id="editBadgeId" name="id">
                                <div class="mb-3">
                                    <label for="editBadgeName" class="form-label">نام نشان</label>
                                    <input type="text" class="form-control" id="editBadgeName" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editBadgeDescription" class="form-label">توضیحات</label>
                                    <textarea class="form-control" id="editBadgeDescription" name="description"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="editBadgeImage" class="form-label">تصویر نشان</label>
                                    <input type="file" class="form-control" id="editBadgeImage" name="image"
                                        accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary">ذخیره</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مودال اعطای نشان به دانش‌آموز -->
            <div class="modal fade" id="awardBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">اعطای نشان به دانش‌آموز</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="awardBadgeForm">
                                <input type="hidden" id="awardUserId" name="user_id">
                                <div class="mb-3">
                                    <label for="awardBadgeSelect" class="form-label">انتخاب نشان</label>
                                    <select class="form-select" id="awardBadgeSelect" name="badge_id" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($all_badges as $badge): ?>
                                            <option value="<?php echo $badge['id']; ?>">
                                                <?php echo htmlspecialchars($badge['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">اعطا</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- مودال گرفتن نشان از دانش‌آموز -->
            <div class="modal fade" id="revokeBadgeModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">گرفتن نشان از دانش‌آموز</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="revokeBadgeForm">
                                <input type="hidden" id="revokeUserId" name="user_id">
                                <div class="mb-3">
                                    <label for="revokeBadgeSelect" class="form-label">انتخاب نشان برای گرفتن</label>
                                    <select class="form-select" id="revokeBadgeSelect" name="badge_id" required>
                                        <option value="">انتخاب کنید</option>
                                        <?php foreach ($all_badges as $badge): ?>
                                            <option value="<?php echo $badge['id']; ?>">
                                                <?php echo htmlspecialchars($badge['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-danger">گرفتن</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBadge(id, name, description, image_url) {
            document.getElementById('editBadgeId').value = id;
            document.getElementById('editBadgeName').value = name;
            document.getElementById('editBadgeDescription').value = description;
        }

        async function deleteBadge(id) {
            if (confirm('آیا مطمئن هستید که می‌خواهید این نشان را حذف کنید؟')) {
                try {
                    const response = await fetch('manage_badge.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (result.success) location.reload();
                } catch (err) {
                    alert('خطا: ' + err.message);
                }
            }
        }

        async function addPoints(userId) {
            const points = prompt('امتیاز برای اضافه کردن (عدد مثبت):');
            if (points && !isNaN(points) && points > 0) {
                try {
                    const response = await fetch('manage_points.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'add', user_id: userId, points: parseInt(points) })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (result.success) location.reload();
                } catch (err) {
                    alert('خطا: ' + err.message);
                }
            }
        }

        async function subtractPoints(userId) {
            const points = prompt('امتیاز برای کسر کردن (عدد مثبت):');
            if (points && !isNaN(points) && points > 0) {
                try {
                    const response = await fetch('manage_points.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'subtract', user_id: userId, points: parseInt(points) })
                    });
                    const result = await response.json();
                    alert(result.message);
                    if (result.success) location.reload();
                } catch (err) {
                    alert('خطا: ' + err.message);
                }
            }
        }

        function setAwardBadgeUser(userId) {
            document.getElementById('awardUserId').value = userId;
        }

        function setRevokeBadgeUser(userId) {
            document.getElementById('revokeUserId').value = userId;
        }

        document.getElementById('addBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add');
            try {
                const response = await fetch('manage_badge.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) location.reload();
            } catch (err) {
                alert('خطا: ' + err.message);
            }
        });

        document.getElementById('editBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'edit');
            try {
                const response = await fetch('manage_badge.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) location.reload();
            } catch (err) {
                alert('خطا: ' + err.message);
            }
        });

        document.getElementById('awardBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            if (!data.user_id || !data.badge_id) {
                alert('لطفاً همه فیلدها را پر کنید');
                return;
            }
            try {
                const response = await fetch('manage_badge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'award', ...data })
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) location.reload();
            } catch (err) {
                alert('خطا: ' + err.message);
            }
        });

        document.getElementById('revokeBadgeForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            if (!data.user_id || !data.badge_id) {
                alert('لطفاً همه فیلدها را پر کنید');
                return;
            }
            try {
                const response = await fetch('manage_badge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'revoke', ...data })
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) location.reload();
            } catch (err) {
                alert('خطا: ' + err.message);
            }
        });
    </script>
</body>

</html>
