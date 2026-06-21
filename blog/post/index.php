<?php
session_start();
require_once '../../db.php';

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}


if (file_exists('../../jdf.php')) {
    require_once '../../jdf.php';
}

function toJalali($date_str)
{
    if (empty($date_str))
        return '';
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

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lang = $_SESSION['lang'] ?? 'fa';
$dir = $lang === 'fa' ? 'rtl' : 'ltr';

$stmt = $pdo->prepare("SELECT title, content, author_name, image_path, created_at FROM posts WHERE id = :id");
$stmt->execute(['id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("مقاله مورد نظر یافت نشد.");
}

$t_page = [
    'fa' => ['back' => 'بازگشت به وبلاگ', 'author' => 'نویسنده:', 'published' => 'تاریخ انتشار:'],
    'en' => ['back' => 'Back to Blog', 'author' => 'Author:', 'published' => 'Published:']
][$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($post['title']) ?> | دبیرستان باهنر ۳</title>
    <meta name="description" content="<?= e(mb_substr(strip_tags($post['content']), 0, 150)) ?>">

    <!-- JSON-LD برای سئو -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "NewsArticle",
      "headline": "<?= e($post['title']) ?>",
      "image": [ "https://bahonarkaraj.ir/<?= e($post['image_path']) ?>" ],
      "datePublished": "<?= $post['created_at'] ?>",
      "author": [{ "@type": "Person", "name": "<?= e($post['author_name']) ?>" }]
    }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Vazirmatn:wght@400;700;900&display=swap"
        rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family:
                <?= $lang === 'fa' ? 'Vazirmatn' : 'Inter' ?>
                , sans-serif;
        }

        .post-content p {
            margin-bottom: 1.5rem;
            line-height: 1.8;
            text-align: justify;
        }

        .post-content a {
            color: #2563eb;
            text-decoration: underline;
        }
    </style>
</head>

<body class="text-slate-700">

    <header class="bg-white shadow-sm sticky top-0 z-50 py-4">
        <div class="container mx-auto px-4 max-w-4xl flex justify-between items-center">
            <a href="../index.php" class="text-slate-500 hover:text-blue-600 font-bold transition-colors">
                <i class="fa-solid fa-arrow-<?= $lang === 'fa' ? 'right' : 'left' ?> me-2"></i> <?= $t_page['back'] ?>
            </a>
        </div>
    </header>

    <main class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <article class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <!-- عکس مقاله -->
                <?php if (!empty($post['image_path'])): ?>
                    <div class="w-full h-[40vh] md:h-[60vh] bg-slate-200">
                        <img src="../../<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>"
                            class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>

                <div class="p-8 md:p-12">
                    <h1 class="text-3xl md:text-5xl font-black text-slate-800 mb-6 leading-tight">
                        <?= e($post['title']) ?></h1>

                    <div
                        class="flex flex-wrap items-center gap-6 mb-10 pb-6 border-b border-slate-100 text-sm font-bold text-slate-500">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-user-pen text-blue-600"></i>
                            <?= $t_page['author'] ?> <?= e($post['author_name']) ?></span>
                        <span class="flex items-center gap-2"><i class="fa-regular fa-calendar text-blue-600"></i>
                            <?= $t_page['published'] ?>
                            <?= $lang === 'fa' ? toJalali($post['created_at']) : date('M d, Y', strtotime($post['created_at'])) ?></span>
                    </div>

                    <!-- محتوای مقاله -->
                    <div class="post-content text-slate-600 text-lg">
                        <?= nl2br(e(str_replace('\n', "\n", $post['content']))) ?>
                    </div>
                </div>
            </article>

            <!-- فوتر اختصاصی صفحه مقاله -->
            <div class="mt-12 text-center text-slate-400 text-sm">
                &copy; <?= date('Y') ?> دبیرستان هوشمند شهید باهنر ۳ کرج
            </div>
        </div>
    </main>
</body>

</html>