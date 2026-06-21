<?php
session_start();
require_once '../db.php';

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lang = $_SESSION['lang'] ?? 'fa';
$dir = $lang === 'fa' ? 'rtl' : 'ltr';

$teacher = null;
if ($pdo && $id > 0) {
    // کوئری آپدیت شده: اضافه شدن u.name_en
    $stmt = $pdo->prepare("SELECT u.name, u.name_en, t.* FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$teacher) {
    die("استاد مورد نظر یافت نشد.");
}

// متون دوزبانه (اضافه شدن پیام خالی بودن بیوگرافی)
$t_page = [
    'fa' => [
        'back' => 'برگشت به لیست اساتید', 
        'spec' => 'تخصص و مهارت', 
        'bio' => 'بیوگرافی و سوابق علمی',
        'no_info' => 'اطلاعاتی ثبت نشده است.'
    ],
    'en' => [
        'back' => 'Back to List', 
        'spec' => 'Specialty & Skills', 
        'bio' => 'Biography & Academic Records',
        'no_info' => 'No information has been registered.'
    ]
][$lang];

// تنظیم متغیرها بر اساس زبان انتخاب شده با منطق اصلاح شده
$t_name = ($lang === 'en' && !empty($teacher['name_en'])) ? $teacher['name_en'] : $teacher['name'];
$t_spec = ($lang === 'en' && !empty($teacher['specialty_en'])) ? $teacher['specialty_en'] : ($teacher['specialty'] ?? $t_page['no_info']);

// منطق اصلاح شده بیوگرافی برای جلوگیری از نمایش متن فارسی در نسخه انگلیسی
if ($lang === 'en') {
    $t_bio = !empty($teacher['bio_en']) ? $teacher['bio_en'] : $t_page['no_info'];
} else {
    $t_bio = !empty($teacher['bio']) ? $teacher['bio'] : $t_page['no_info'];
}

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    
    <!-- SEO تگ‌های اختصاصی برای نام استاد -->
    <title><?= $lang === 'fa' ? 'بیوگرافی' : 'Biography' ?> <?= e($t_name) ?> | دبیرستان شهید باهنر ۳</title>
    <meta name="description" content="<?= e(mb_substr(strip_tags($t_bio), 0, 160)) ?>">
    <meta name="keywords" content="<?= e($t_name) ?>, اساتید باهنر ۳, سمپاد کرج, <?= e($t_spec) ?>">
    
    <!-- JSON-LD برای شناسایی بهتر گوگل (Schema.org) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Person",
      "name": "<?= e($t_name) ?>",
      "jobTitle": "<?= e($t_spec) ?>",
      "worksFor": {
        "@type": "EducationalOrganization",
        "name": "دبیرستان شهید باهنر ۳"
      },
      "description": "<?= e(mb_substr(strip_tags($t_bio), 0, 160)) ?>"
    }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: <?= $lang === 'fa' ? "'Vazirmatn'" : "'Inter'" ?>, sans-serif; }
        .profile-header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); }
    </style>
</head>
<body>

    <header class="bg-white shadow-sm py-4 sticky top-0 z-50">
        <div class="container mx-auto px-4 max-w-5xl flex justify-between items-center">
            <a href="index.php" class="text-slate-500 hover:text-blue-600 font-bold transition-colors">
                <i class="fa-solid fa-arrow-<?= $lang === 'fa' ? 'right' : 'left' ?> me-2"></i> <?= $t_page['back'] ?>
            </a>
        </div>
    </header>

    <main class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            
            <!-- کارت اصلی پروفایل -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="profile-header p-8 md:p-12 text-center md:text-<?= $lang === 'fa' ? 'right' : 'left' ?> md:flex items-center gap-8">
                    <div class="w-40 h-40 mx-auto md:mx-0 rounded-2xl overflow-hidden border-4 border-white/20 shadow-2xl shrink-0">
                        <?php $img = !empty($teacher['profile_image']) ? '../'.$teacher['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($t_name)."&background=ffffff&color=1e3a8a&size=256"; ?>
                        <img src="<?= $img ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="mt-6 md:mt-0">
                        <h1 class="text-3xl md:text-5xl font-black text-white mb-2"><?= e($t_name) ?></h1>
                        <p class="text-blue-100 text-lg md:text-xl font-medium opacity-90"><?= e($t_spec) ?></p>
                    </div>
                </div>

                <div class="p-8 md:p-12 grid gap-12">
                    <!-- بخش بیوگرافی -->
                    <section>
                        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-3">
                            <i class="fa-solid fa-graduation-cap text-blue-600"></i>
                            <?= $t_page['bio'] ?>
                        </h2>
                        <div class="text-slate-600 leading-relaxed text-lg text-justify whitespace-pre-line">
                            <?= nl2br(e($t_bio)) ?>
                        </div>
                    </section>

                    <!-- بخش تخصص -->
                    <section>
                        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-3">
                            <i class="fa-solid fa-award text-blue-600"></i>
                            <?= $t_page['spec'] ?>
                        </h2>
                        <div class="inline-block bg-blue-50 text-blue-700 px-6 py-2 rounded-xl font-bold border border-blue-100">
                            <?= e($t_spec) ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- فوتر کوچک اختصاصی -->
            <div class="mt-12 text-center text-slate-400 text-sm">
                &copy; <?= date('Y') ?> <?= $lang === 'fa' ? 'دبیرستان هوشمند شهید باهنر ۳ کرج | مرکز استعدادهای درخشان' : 'Bahonar 3 High School | NODET' ?>
            </div>

        </div>
    </main>

</body>
</html>