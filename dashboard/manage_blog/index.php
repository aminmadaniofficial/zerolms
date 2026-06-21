<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'student') {
    header("Location: ../../login/");
    exit;
}


if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($post && file_exists('../../' . $post['image_path'])) {
        unlink('../../' . $post['image_path']);
    }
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    addLog($pdo, $_SESSION['user_id'], 'حذف موفق پست', 'حذف پست');
    header("Location: index.php?success=deleted");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author_name = trim($_POST['author_name'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if (empty($title) || empty($content) || empty($author_name)) {
        $error = "همه فیلدها الزامی هستند.";
    } else {
        $image_path = '';
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = '../../Uploads/blog/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $image_path = 'Uploads/blog/' . time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], '../../' . $image_path);
        } elseif ($id > 0) {
            $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $image_path = $stmt->fetchColumn();
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, author_name = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$title, $content, $author_name, $image_path, $id]);
            addLog($pdo, $_SESSION['user_id'], 'ویرایش موفق پست', 'ویرایش پست');

            header("Location: index.php?success=updated");
        } else {
            $stmt = $pdo->prepare("INSERT INTO posts (title, content, author_name, image_path, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $content, $author_name, $image_path]);
            addLog($pdo, $_SESSION['user_id'], 'ساخت موفق پست', 'ساخت پست');

            header("Location: index.php?success=created");
        }
        exit;
    }
}


$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت وبلاگ</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
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
        .table-responsive {
            max-width: 900px;
            margin: auto;
        }
        .post-content {
            max-width: 300px;
            white-space: pre-wrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="../index.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a></span>
        <span>مدیریت وبلاگ</span>
    </div>
    <div class="main-content">
        <div class="container mt-4">
            <h2>مدیریت پست‌های وبلاگ</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">عملیات با موفقیت انجام شد</div>
            <?php endif; ?>

            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postModal" data-mode="create">ایجاد پست جدید</button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>عنوان</th>
                            <th>محتوا</th>
                            <th>نویسنده</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr><td colspan="6">هیچ پستی یافت نشد</td></tr>
                        <?php else: ?>
                            <?php foreach ($posts as $index => $post): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></td>
                                    <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                    <td><?php echo htmlspecialchars($post['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-post" data-bs-toggle="modal" data-bs-target="#postModal" data-mode="edit" data-id="<?php echo $post['id']; ?>" data-title="<?php echo htmlspecialchars($post['title']); ?>" data-content="<?php echo htmlspecialchars($post['content']); ?>" data-author="<?php echo htmlspecialchars($post['author_name']); ?>" data-image="<?php echo htmlspecialchars($post['image_path']); ?>">ویرایش</button>
                                        <a href="?action=delete&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟');">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- مودال ایجاد/ویرایش پست -->
    <div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalLabel">پست جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="postId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">محتوا</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="author_name" class="form-label">نام نویسنده</label>
                            <input type="text" class="form-control" id="author_name" name="author_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">تصویر (اختیاری)</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <img id="previewImage" src="" alt="پیش‌نمایش تصویر" style="max-width: 200px; margin-top: 10px; display: none;">
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
        document.querySelectorAll('.edit-post').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('postModalLabel').textContent = 'ویرایش پست';
                document.getElementById('postId').value = btn.dataset.id;
                document.getElementById('title').value = btn.dataset.title;
                document.getElementById('content').value = btn.dataset.content; 
                document.getElementById('author_name').value = btn.dataset.author;
                const preview = document.getElementById('previewImage');
                if (btn.dataset.image) {
                    preview.src = '../../' + btn.dataset.image;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            });
        });

        document.querySelector('[data-mode="create"]').addEventListener('click', () => {
            document.getElementById('postModalLabel').textContent = 'پست جدید';
            document.getElementById('postId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('content').value = '';
            document.getElementById('author_name').value = '';
            document.getElementById('previewImage').style.display = 'none';
        });
    </script>
</body>
</html>