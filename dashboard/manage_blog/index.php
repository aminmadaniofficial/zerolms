<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'student') {
    header("Location: ../../login/");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ۱. حذف پست
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post && !empty($post['image_path']) && file_exists('../../' . $post['image_path'])) {
            @unlink('../../' . $post['image_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        addLog($pdo, $_SESSION['user_id'], 'حذف موفق پست وبلاگ', 'مدیریت وبلاگ', $id);
        header("Location: index.php?success=deleted");
        exit;
    }
}

// ۲. ساخت و ویرایش پست
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "توکن امنیتی نامعتبر است.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $author_name = trim($_POST['author_name'] ?? '');
        $id = (int)($_POST['id'] ?? 0);

        if (empty($title) || empty($content) || empty($author_name)) {
            $error = "پر کردن عنوان، محتوا و نام نویسنده الزامی است.";
        } else {
            $image_path = '';
            require_once '../../upload_security.php';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../Uploads/blog/';
                list($success, $filename_or_err, $dest) = store_safe_upload(
                    $_FILES['image'],
                    $upload_dir,
                    ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                    ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    'blog_'
                );
                
                if ($success) {
                    $image_path = 'Uploads/blog/' . $filename_or_err;
                    if ($id > 0) {
                        $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_img = $stmt->fetchColumn();
                        if ($old_img && file_exists('../../' . $old_img)) @unlink('../../' . $old_img);
                    }
                } else {
                    $error = "خطا در آپلود تصویر: " . $filename_or_err;
                }
            } elseif ($id > 0) {
                $stmt = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
                $stmt->execute([$id]);
                $image_path = $stmt->fetchColumn();
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, author_name = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$title, $content, $author_name, $image_path, $id]);
                addLog($pdo, $_SESSION['user_id'], 'ویرایش موفق پست وبلاگ', 'مدیریت وبلاگ', $id);
                header("Location: index.php?success=updated");
            } else {
                $stmt = $pdo->prepare("INSERT INTO posts (title, content, author_name, image_path, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $content, $author_name, $image_path]);
                $new_id = $pdo->lastInsertId();
                addLog($pdo, $_SESSION['user_id'], 'ساخت موفق پست وبلاگ', 'مدیریت وبلاگ', $new_id);
                header("Location: index.php?success=created");
            }
            exit;
        }
    }
}

// ۳. جستجو و صفحه‌بندی
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE title LIKE ? OR content LIKE ? OR author_name LIKE ?");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $total_posts = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM posts WHERE title LIKE ? OR content LIKE ? OR author_name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(4, $limit, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total_posts / $limit);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت وبلاگ | سامانه یادگیری</title>
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
        }

        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important;
            padding: 10px 14px;
        }

        /* ادیتور اختصاصی */
        .editor-toolbar {
            background: #1e293b;
            border: 1px solid var(--border-color);
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            padding: 8px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .editor-toolbar button {
            background: #0f172a;
            border: 1px solid var(--border-color);
            color: #f8fafc;
            border-radius: 6px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .editor-toolbar button:hover {
            background: var(--primary-accent);
        }

        .editor-content {
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: 0 0 10px 10px;
            color: #f8fafc;
            padding: 14px;
            min-height: 200px;
            outline: none;
            overflow-y: auto;
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
        <span class="fw-bold fs-5"><i class="fas fa-newspaper text-primary me-2"></i> مدیریت اخبار و پست‌های وبلاگ</span>
        <a href="../index.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد</a>
    </div>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger bg-danger bg-opacity-20 text-danger border-0 mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success bg-success bg-opacity-20 text-success border-0 mb-4">عملیات با موفقیت انجام شد.</div>
        <?php endif; ?>

        <!-- سرچ و دکمه ساخت -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <button class="btn btn-gradient-primary" data-bs-toggle="modal" data-bs-target="#postModal" id="createNewPostBtn">
                <i class="fas fa-plus me-1"></i> ایجاد پست جدید
            </button>

            <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 400px;">
                <input type="text" name="search" class="form-control" placeholder="جستجو در عنوان یا متن پست..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-light"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- جدول پست‌ها -->
        <div class="card-custom p-0 mb-4">
            <div class="table-responsive">
                <table class="table text-center align-middle m-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th style="width: 90px;">تصویر</th>
                            <th>عنوان پست</th>
                            <th>نویسنده</th>
                            <th>تاریخ انتشار</th>
                            <th style="width: 140px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr><td colspan="6" class="py-4 text-muted">هیچ پستی یافت نشد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($posts as $index => $post): ?>
                                <tr>
                                    <td><?php echo $offset + $index + 1; ?></td>
                                    <td>
                                        <?php if (!empty($post['image_path'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post" class="rounded" style="width: 50px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.8rem;">بدون عکس</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                                    <td><span class="badge bg-secondary bg-opacity-20 text-light px-2 py-1"><?php echo htmlspecialchars($post['author_name']); ?></span></td>
                                    <td style="font-size: 0.85rem;" class="text-muted"><?php echo htmlspecialchars($post['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning edit-post border-0 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#postModal" 
                                                data-id="<?php echo $post['id']; ?>" 
                                                data-title="<?php echo htmlspecialchars($post['title']); ?>" 
                                                data-author="<?php echo htmlspecialchars($post['author_name']); ?>" 
                                                data-image="<?php echo htmlspecialchars($post['image_path']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div class="d-none post-raw-content"><?php echo htmlspecialchars($post['content']); ?></div>
                                        
                                        <a href="?action=delete&id=<?php echo $post['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" 
                                           class="btn btn-sm btn-outline-danger border-0" 
                                           onclick="return confirm('آیا از حذف این پست اطمینان دارید؟');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                            <a class="page-link bg-dark text-light border-secondary" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- مودال ساخت/ویرایش پست -->
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalLabel"><i class="fas fa-pen-nib text-primary me-2"></i> ایجاد پست جدید</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="postForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="id" id="postId">
                    <input type="hidden" name="content" id="hiddenContentInput">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان پست</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="عنوان پست را وارد کنید...">
                        </div>
                        <div class="mb-3">
                            <label for="author_name" class="form-label">نام نویسنده</label>
                            <input type="text" class="form-control" id="author_name" name="author_name" required placeholder="مثلاً: روابط عمومی مدرسه">
                        </div>
                        
                        <!-- ادیتور متنی پیشرفته اختصاصی -->
                        <div class="mb-3">
                            <label class="form-label">متن کامل پست</label>
                            <div class="editor-toolbar">
                                <button type="button" onclick="execCmd('bold')"><i class="fas fa-bold"></i></button>
                                <button type="button" onclick="execCmd('italic')"><i class="fas fa-italic"></i></button>
                                <button type="button" onclick="execCmd('underline')"><i class="fas fa-underline"></i></button>
                                <button type="button" onclick="execCmd('formatBlock', '<h3>')">H3</button>
                                <button type="button" onclick="execCmd('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                                <button type="button" onclick="execCmd('justifyRight')"><i class="fas fa-align-right"></i></button>
                                <button type="button" onclick="execCmd('justifyCenter')"><i class="fas fa-align-center"></i></button>
                                <button type="button" onclick="createLink()"><i class="fas fa-link"></i></button>
                            </div>
                            <div class="editor-content" id="customEditor" contenteditable="true"></div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">تصویر کاور پست (اختیاری)</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="mt-3">
                                <img id="previewImage" src="" alt="پیش‌نمایش تصویر" class="rounded border border-secondary" style="max-width: 200px; max-height: 140px; object-fit: cover; display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-gradient-primary btn-sm">انتشار پست</button>
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
        function execCmd(command, value = null) {
            document.execCommand(command, false, value);
        }

        function createLink() {
            const url = prompt('آدرس لینک را وارد کنید:');
            if (url) execCmd('createLink', url);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const postForm = document.getElementById('postForm');
            const customEditor = document.getElementById('customEditor');
            const hiddenContentInput = document.getElementById('hiddenContentInput');

            postForm.addEventListener('submit', () => {
                hiddenContentInput.value = customEditor.innerHTML;
            });

            document.getElementById('createNewPostBtn')?.addEventListener('click', () => {
                document.getElementById('postModalLabel').innerHTML = '<i class="fas fa-pen-nib text-primary me-2"></i> ایجاد پست جدید';
                document.getElementById('postId').value = '';
                document.getElementById('title').value = '';
                document.getElementById('author_name').value = '';
                customEditor.innerHTML = '';
                document.getElementById('previewImage').style.display = 'none';
            });

            document.querySelectorAll('.edit-post').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('postModalLabel').innerHTML = '<i class="fas fa-edit text-warning me-2"></i> ویرایش پست وبلاگ';
                    document.getElementById('postId').value = btn.dataset.id;
                    document.getElementById('title').value = btn.dataset.title;
                    document.getElementById('author_name').value = btn.dataset.author;
                    
                    const rawContent = btn.parentElement.querySelector('.post-raw-content').innerHTML;
                    customEditor.innerHTML = rawContent;
                    
                    const preview = document.getElementById('previewImage');
                    if (btn.dataset.image) {
                        preview.src = '../../' + btn.dataset.image;
                        preview.style.display = 'block';
                    } else {
                        preview.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>