<?php
session_start();
require_once '../db.php';

function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }

if (file_exists('../jdf.php')) {
    require_once '../jdf.php';
}

function toJalali($date_str) {
    if (empty($date_str)) return '';
    try {
        
        $timestamp = strtotime($date_str);
        
        
        if (function_exists('jdate')) {
            return jdate('j F Y', $timestamp);
        }
        
        
        return $date_str; 
    } catch (Exception $e) {
        return $date_str;
    }
}

$lang = $_SESSION['lang'] ?? 'fa';
$dir = $lang === 'fa' ? 'rtl' : 'ltr';

$posts_per_page = 6; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $posts_per_page;

$stmt = $pdo->query("SELECT COUNT(*) FROM posts");
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

$stmt = $pdo->prepare("SELECT id, title, content, author_name, image_path, created_at FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$t_page = [
    'fa' => ['title' => 'اخبار و مقالات', 'home' => 'خانه', 'read' => 'ادامه مطلب', 'author' => 'نویسنده:', 'empty' => 'هیچ پستی یافت نشد.'],
    'en' => ['title' => 'News & Blog', 'home' => 'Home', 'read' => 'Read More', 'author' => 'Author:', 'empty' => 'No posts found.']
][$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $t_page['title'] ?> | دبیرستان باهنر ۳</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: <?= $lang === 'fa' ? 'Vazirmatn' : 'Inter' ?>, sans-serif; }
    </style>
</head>
<body class="text-slate-700">

    <header class="bg-white shadow-sm sticky top-0 z-50 py-4">
        <div class="container mx-auto px-4 max-w-7xl flex justify-between items-center">
            <a href="../index.php" class="flex items-center gap-3">
                <img src="../images/logo.png" alt="Logo" class="h-10 w-auto" onerror="this.src='https://placehold.co/100x100/1e3a8a/ffffff?text=B3'">
                <span class="font-bold text-slate-800 uppercase italic">BAHONAR 3</span>
            </a>
            <a href="../index.php" class="text-blue-600 font-bold flex items-center gap-2">
                <i class="fa-solid fa-house"></i> <?= $t_page['home'] ?>
            </a>
        </div>
    </header>

    <main class="py-16">
        <div class="container mx-auto px-4 max-w-7xl">
            <h1 class="text-4xl font-black text-slate-800 mb-12 text-center"><?= $t_page['title'] ?></h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (empty($posts)): ?>
                    <div class="col-span-full text-center py-10"><p class="text-slate-500 font-medium"><?= $t_page['empty'] ?></p></div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:-translate-y-2 hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group">
                            <a href="post/index.php?id=<?= $post['id'] ?>" class="block w-full aspect-video bg-slate-200 relative overflow-hidden">
                                <?php $img = !empty($post['image_path']) ? "../" . $post['image_path'] : "https://placehold.co/800x450/1e3a8a/ffffff?text=News"; ?>
                                <img src="<?= $img ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <time class="absolute top-4 <?= $lang === 'fa' ? 'right-4' : 'left-4' ?> bg-white px-3 py-1 rounded shadow text-xs font-bold text-blue-600">
                                    <i class="fa-regular fa-calendar me-1"></i> <?= $lang === 'fa' ? toJalali($post['created_at']) : date('M d, Y', strtotime($post['created_at'])) ?>
                                </time>
                            </a>
                            <div class="p-6 flex flex-col flex-grow">
                                <div class="text-xs text-slate-500 mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-user-pen"></i> <?= e($post['author_name']) ?>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-4 line-clamp-2 hover:text-blue-600 transition-colors">
                                    <a href="post/index.php?id=<?= $post['id'] ?>"><?= e($post['title']) ?></a>
                                </h3>
                                <p class="text-sm text-slate-600 mb-6 line-clamp-3 leading-relaxed">
                                    <?= e(mb_substr(strip_tags($post['content']), 0, 150)) ?>...
                                </p>
                                <div class="mt-auto pt-4 border-t border-slate-100">
                                    <a href="post/index.php?id=<?= $post['id'] ?>" class="text-blue-600 font-bold text-sm inline-flex items-center gap-2 hover:text-blue-800 transition-colors">
                                        <?= $t_page['read'] ?> <i class="fa-solid fa-arrow-<?= $lang === 'fa' ? 'left' : 'right' ?>"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-16">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition-colors"><i class="fa-solid fa-chevron-<?= $lang === 'fa' ? 'right' : 'left' ?>"></i></a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-lg border <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-slate-200 text-slate-600 hover:bg-blue-50' ?> font-bold transition-colors">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-blue-600 transition-colors"><i class="fa-solid fa-chevron-<?= $lang === 'fa' ? 'left' : 'right' ?>"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>