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
require_once '../db.php';

/**
 * Sanitize text strings for HTML output escaping.
 * 
 * @param string|null $string String input
 * @return string Escaped output string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Active UI language selection and text orientation
$lang = $_SESSION['lang'] ?? 'fa';
$dir = $lang === 'fa' ? 'rtl' : 'ltr';

$teachers = [];
if ($pdo) {
    // Updated Query: Fetch both Persian and English name and specialty columns
    $stmt = $pdo->query("SELECT u.id, u.name, u.name_en, t.specialty, t.specialty_en, t.profile_image FROM teachers t JOIN users u ON t.user_id = u.id");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Localized strings
$t_text = [
    'fa' => ['title' => 'لیست اساتید و کادر آموزشی', 'home' => 'خانه', 'view' => 'مشاهده پروفایل'],
    'en' => ['title' => 'Faculty & Teachers List', 'home' => 'Home', 'view' => 'View Profile']
][$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $t_text['title'] ?> | دبیرستان باهنر ۳</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: <?= $lang === 'fa' ? "'Vazirmatn'" : "'Inter'" ?>, sans-serif; }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50 py-4">
        <div class="container mx-auto px-4 max-w-7xl flex justify-between items-center">
            <a href="../index.php" class="flex items-center gap-3">
                <img src="../images/logo.png" alt="Logo" class="h-10 w-auto" onerror="this.src='https://placehold.co/100x100/1e3a8a/ffffff?text=B3'">
                <span class="font-bold text-slate-800 uppercase italic">BAHONAR 3</span>
            </a>
            <a href="../index.php" class="text-blue-600 font-bold flex items-center gap-2 hover:text-blue-800 transition-colors">
                <i class="fa-solid fa-house"></i> <?= $t_text['home'] ?>
            </a>
        </div>
    </header>

    <!-- Faculty List Grid -->
    <main class="py-16">
        <div class="container mx-auto px-4 max-w-7xl">
            <h1 class="text-3xl font-black text-slate-800 mb-12 text-center"><?= $t_text['title'] ?></h1>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($teachers as $teacher): ?>
                    <?php 
                        // Verify current language and assign appropriate localized values
                        $t_name = ($lang === 'en' && !empty($teacher['name_en'])) ? $teacher['name_en'] : $teacher['name'];
                        $t_spec = ($lang === 'en' && !empty($teacher['specialty_en'])) ? $teacher['specialty_en'] : ($teacher['specialty'] ?? 'تخصص تعریف نشده');
                    ?>
                    <a href="profile.php?id=<?= $teacher['id'] ?>" class="block group">
                        <div class="bg-white rounded-2xl p-6 text-center border border-slate-200 shadow-sm transition-all group-hover:-translate-y-2 group-hover:shadow-xl">
                            <div class="w-32 h-32 mx-auto rounded-full overflow-hidden mb-4 border-4 border-slate-50 shadow-inner">
                                <?php $img = !empty($teacher['profile_image']) ? '../'.$teacher['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($t_name)."&background=1e3a8a&color=ffffff&size=256"; ?>
                                <img src="<?= $img ?>" class="w-full h-full object-cover">
                            </div>
                            <h3 class="text-xl font-bold text-slate-800"><?= e($t_name) ?></h3>
                            <p class="text-blue-600 text-sm font-medium mb-4"><?= e($t_spec) ?></p>
                            <span class="text-xs font-bold bg-slate-100 text-slate-500 px-4 py-2 rounded-full group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <?= $t_text['view'] ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>