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

/**
 * Escape HTML special characters to prevent XSS attacks.
 * 
 * @param string|null $string Input text to escape
 * @return string Safe HTML escaped string
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Initialize database variables
$pdo = null;
$db_error = false;

// Attempt to connect to database if db.php exists
try {
    if (file_exists('db.php')) {
        require_once 'db.php';
        
        // Configure PDO to throw exceptions on database errors
        if ($pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
} catch (Exception $e) {
    $db_error = true;
    error_log("Database Connection Failed: " . $e->getMessage());
}

// Include Jalali Date helper library if available
if (file_exists('jdf.php')) {
    require_once 'jdf.php';
}

/**
 * Convert a Gregorian date string to Jalali (Shamsi) date format.
 * 
 * @param string $date_str Standard date string
 * @return string Formatted Jalali date or fallback date string
 */
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

// Containers for page data
$posts = [];
$teachers = [];

// Fetch latest posts and active teachers from database
if ($pdo && !$db_error) {
    try {
        // Query the latest 3 posts for the news section
        $stmtP = $pdo->prepare("SELECT id, title, author_name, image_path, created_at FROM posts ORDER BY created_at DESC LIMIT 3");
        $stmtP->execute();
        $posts = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Query up to 12 teachers with joined user details
        $stmtT = $pdo->prepare("SELECT u.id, u.name, u.name_en, t.specialty, t.specialty_en, t.profile_image FROM teachers t JOIN users u ON t.user_id = u.id LIMIT 12");
        $stmtT->execute();
        $teachers = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Query Failed: " . $e->getMessage());
    }
}

// Handle language switching via GET parameter and preserve URL clean state
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Define active language and text direction
$lang = $_SESSION['lang'] ?? 'fa';
$dir = $lang === 'fa' ? 'rtl' : 'ltr';

// Internationalization (i18n) dictionary for Persian and English content
$i18n = [
    'fa' => [
        'font' => 'font-[Vazirmatn]',
        'seo' => [
            'title' => 'دبیرستان استعدادهای درخشان شهید باهنر ۳ کرج | سمپاد',
            'desc' => 'دبیرستان دوره اول استعدادهای درخشان شهید باهنر ۳ (سمپاد). برترین کادر آموزشی، آزمایشگاه‌های مجهز و محیطی پویا برای پرورش نخبگان و افتخارآفرینان آینده.',
            'keywords' => 'مدرسه تیزهوشان, سمپاد کرج, باهنر ۳, استعدادهای درخشان, ثبت نام مدرسه تیزهوشان, دبیرستان دوره اول'
        ],
        'topbar' => [
            'phone' => '۰۲۶-۳۲۵۲۲۰۰۳',
            'email' => 'info@bahonarkaraj.ir',
            'hours' => 'شنبه تا چهارشنبه: ۷:۳۰ الی ۱۵:۰۰'
        ],
        'nav' => [
            'home' => 'خانه',
            'about' => 'درباره ما',
            'teachers' => 'اساتید',
            'gallery' => 'گالری',
            'blog' => 'اخبار و مقالات',
            'contact' => 'ارتباط با ما',
            'portal' => 'پورتال کاربری',
            'login' => 'ورود'
        ],
        'slider' => [
            'slide1_title' => 'آینده را از اینجا بسازید',
            'slide1_desc' => 'سازمان ملی پرورش استعدادهای درخشان؛ محیطی برای رشد همه‌جانبه نخبگان دانش‌آموزی.',
            'slide1_btn' => 'آشنایی با مدرسه',
            'slide2_title' => 'پژوهش و یادگیری عملی',
            'slide2_desc' => 'بهره‌گیری از مجهزترین آزمایشگاه‌ها و کارگاه‌های رباتیک برای درک عمیق علوم.',
            'slide3_title' => 'اساتید مجرب و افتخارآفرین',
            'slide3_desc' => 'کادر آموزشی متخصص ما، مسیر موفقیت در المپیادها و مسابقات علمی را هموار می‌کنند.',
            'slide4_title' => 'سالن اجتماعات مدرن',
            'slide4_desc' => 'میزبان همایش‌های علمی، کارسوق‌ها و جشن‌های ملی افتخارآفرینان سمپاد.',
            'slide5_title' => 'کلاس‌های هوشمند طبقه سوم',
            'slide5_desc' => 'محیطی تخصصی و آرام در طبقه سوم جهت تمرکز نخبگان بر یادگیری مفاهیم پیشرفته.',
            'slide6_title' => 'سایت تخصصی برنامه نویسی',
            'slide6_desc' => 'مرکز نوآوری دیجیتال؛ آموزش کدنویسی و هوش مصنوعی در سایت مجهز کامپیوتر.',
            'slide7_title' => 'واحد مدیریت و مشاوره',
            'slide7_desc' => 'هدایت تحصیلی و برنامه‌ریزی فردی در طبقه دوم؛ قلب تپنده برنامه‌ریزی آموزشی.',
            'slide8_title' => 'کتابخانه و آزمایشگاه پایه',
            'slide8_desc' => 'دسترسی به منابع غنی علمی و آزمایشگاه‌های پایه در طبقه اول دبیرستان.',
            'slide9_title' => 'زمین چمن مصنوعی اختصاصی',
            'slide9_desc' => 'نشاط و سلامت دانش‌آموزی در زمین چمن چندمنظوره و استاندارد مدرسه.',
            'slide10_title' => 'سالن ورزشی سرپوشیده',
            'slide10_desc' => 'فضای حرفه‌ای برای والیبال، بسکتبال و فعالیت‌های ورزشی در تمام فصول سال.',
        ],

        'stats' => [
            's1_num' => '۳۰۰+',
            's1_lbl' => 'دانش‌آموز فعال',
            's2_num' => '۴۵+',
            's2_lbl' => 'استاد مجرب',
            's3_num' => '۱۵۰+',
            's3_lbl' => 'مقام در رشته های مختلف',
            's4_num' => '۱۲',
            's4_lbl' => 'سال تجربه'
        ],
        'features' => [
            'subtitle' => 'چرا باهنر ۳؟',
            'title' => 'استاندارد برتر آموزشی در سطح کشور',
            'f1_title' => 'کادر آموزشی طراز اول',
            'f1_desc' => 'حضور اساتید برتر، طراحان سوالات کشوری و مدال‌آوران المپیادها.',
            'f2_title' => 'آزمایشگاه‌های پیشرفته',
            'f2_desc' => 'تجهیزات مدرن فیزیک، شیمی و زیست برای یادگیری پژوهش‌محور.',
            'f3_title' => 'کارسوق‌های مهارتی',
            'f3_desc' => 'آموزش برنامه‌نویسی، رباتیک و مهارت‌های نرم در کنار دروس اصلی.',
            'f4_title' => 'مشاوره و هدایت تحصیلی',
            'f4_desc' => 'حضور مشاوران مجرب برای برنامه‌ریزی فردی و ارتقای سلامت روان.'
        ],
        'faculty' => [
            'subtitle' => 'کادر مدرسه',
            'title' => 'معماران موفقیت دانش‌آموزان',
            'empty' => 'لیست اساتید در حال بروزرسانی است.',
            'view_all' => 'نمایش همه اساتید'
        ],
        'blog' => [
            'subtitle' => 'مجله خبری',
            'title' => 'آخرین اخبار و اطلاعیه‌های آموزشی',
            'read' => 'ادامه مطلب',
            'empty' => 'مقاله‌ای جهت نمایش یافت نشد.'
        ],
        'contact' => [
            'title' => 'پاسخگوی سوالات شما هستیم',
            'desc' => 'برای کسب اطلاعات بیشتر درباره شرایط ثبت‌نام و آزمون‌های ورودی با ما در تماس باشید.',
            'address_lbl' => 'آدرس:',
            'address' => 'کرج، بلوار مطهری، نرسیده به آزادگان، خ شهید ساوجی، نبش اردلان ۳',
            'phone_lbl' => 'شماره‌های تماس:',
            'email_lbl' => 'پست الکترونیک:'
        ],
        'footer' => [
            'about' => 'دبیرستان استعدادهای درخشان شهید باهنر ۳، از سال ۱۳۹۲ با هدف شناسایی و پرورش نخبگان در کرج تاسیس گردید.',
            'links' => 'لینک‌های مفید',
            'copyright' => 'تمامی حقوق برای دبیرستان شهید باهنر ۳ محفوظ است.',
            'dev' => 'توسعه: محمدامین مدنی'
        ]
    ],
    'en' => [
        'font' => 'font-[Inter]',
        'seo' => [
            'title' => 'Bahonar 3 Exceptional Talents High School | NODET',
            'desc' => 'Bahonar 3 Exceptional Talents Junior High School (NODET). Providing top-tier education, advanced labs, and a dynamic environment for future leaders.',
            'keywords' => 'NODET, Exceptional Talents, High School, Karaj, Smart School, Education'
        ],
        'topbar' => [
            'phone' => '+98 26 3252 2003',
            'email' => 'info@bahonarkaraj.ir',
            'hours' => 'Sat - Wed: 7:30 AM to 3:00 PM'
        ],
        'nav' => [
            'home' => 'Home',
            'about' => 'About Us',
            'teachers' => 'Teachers',
            'gallery' => 'Gallery',
            'blog' => 'News & Blog',
            'contact' => 'Contact Us',
            'portal' => 'Student Portal',
            'login' => 'Login'
        ],
        'slider' => [
            'slide1_title' => 'Build the Future Here',
            'slide1_desc' => 'National Organization for Development of Exceptional Talents; a place for comprehensive growth.',
            'slide1_btn' => 'Discover More',
            'slide2_title' => 'Practical Learning',
            'slide2_desc' => 'Utilizing the most equipped laboratories and robotics workshops for deep science understanding.',
            'slide3_title' => 'Distinguished Teachers',
            'slide3_desc' => 'Our expert educational staff pave the way for success in Olympiads and competitions.',
            'slide4_title' => 'Modern Assembly Hall',
            'slide4_desc' => 'Hosting scientific seminars, workshops, and national elite celebrations.',
            'slide5_title' => '3rd Floor Smart Classrooms',
            'slide5_desc' => 'A specialized and quiet environment on the 3rd floor for elite students to focus on advanced learning.',
            'slide6_title' => 'Programming Hub',
            'slide6_desc' => 'Digital innovation center; teaching coding and AI in our advanced computer lab.',
            'slide7_title' => 'Administration & Counseling',
            'slide7_desc' => 'Academic guidance and personal planning on the 2nd floor; the heart of educational strategy.',
            'slide8_title' => 'Library & Science Lab',
            'slide8_desc' => 'Access to vast scientific resources and foundational labs on the first floor.',
            'slide9_title' => 'Dedicated Artificial Turf',
            'slide9_desc' => 'Student vitality and health on our multi-purpose and standard football field.',
            'slide10_title' => 'Indoor Sports Hall',
            'slide10_desc' => 'Professional space for volleyball, basketball, and sports activities all year round.',
        ],
        'stats' => [
            's1_num' => '300+',
            's1_lbl' => 'Active Students',
            's2_num' => '45+',
            's2_lbl' => 'Expert Teachers',
            's3_num' => '150+',
            's3_lbl' => 'Province Medals',
            's4_num' => '12',
            's4_lbl' => 'Years Experience'
        ],
        'features' => [
            'subtitle' => 'Why Bahonar 3?',
            'title' => 'Top Educational Standard in the Country',
            'f1_title' => 'Top-Tier Teachers',
            'f1_desc' => 'Presence of top professors, national exam designers, and Olympiad medalists.',
            'f2_title' => 'Advanced Laboratories',
            'f2_desc' => 'Modern physics, chemistry, and biology equipment for research-based learning.',
            'f3_title' => 'Skill Workshops',
            'f3_desc' => 'Teaching programming, robotics, and soft skills alongside core subjects.',
            'f4_title' => 'Counseling & Guidance',
            'f4_desc' => 'Experienced counselors for personalized planning and mental health promotion.'
        ],
        'faculty' => [
            'subtitle' => 'Our Teachers',
            'title' => 'Architects of Student Success',
            'empty' => 'Teachers list is currently updating.',
            'view_all' => 'View All Teachers'
        ],
        'blog' => [
            'subtitle' => 'News Journal',
            'title' => 'Latest Educational News & Announcements',
            'read' => 'Read More',
            'empty' => 'No articles found.'
        ],
        'contact' => [
            'title' => 'We Are Here to Answer',
            'desc' => 'Contact us for more information about registration requirements and entrance exams.',
            'address_lbl' => 'Address:',
            'address' => 'Corner of Ardalan 3, Savoji St, Motahari Blvd, Karaj',
            'phone_lbl' => 'Phone Numbers:',
            'email_lbl' => 'Email:'
        ],
        'footer' => [
            'about' => 'Bahonar 3 Exceptional Talents High School was established in 2013 to identify and nurture elite students in Karaj.',
            'links' => 'Useful Links',
            'copyright' => 'All rights reserved by Bahonar 3 High School.',
            'dev' => 'Developed by: Amin Madani'
        ]
    ]
];

// Assign active language translation array
$t = $i18n[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo e($lang); ?>" dir="<?php echo e($dir); ?>" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- SEO Meta Tags -->
    <title><?php echo e($t['seo']['title']); ?></title>
    <meta name="description" content="<?php echo e($t['seo']['desc']); ?>">
    <meta name="keywords" content="<?php echo e($t['seo']['keywords']); ?>">
    <meta name="author" content="Amin Madani">
    <link rel="icon" type="image/png" href="./images/logo.png">
    
    <!-- Resource Preconnection -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Preload LCP Hero Image for Instant Mobile & Desktop Rendering -->
    <link rel="preload" as="image" href="./images/slide1.webp" imagesrcset="./images/slide1-mobile.webp 768w, ./images/slide1.webp 1200w" imagesizes="100vw" fetchpriority="high">

    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://bahonarkaraj.ir">

    <!-- Open Graph Metadata for Social Sharing -->
    <meta property="og:site_name" content="<?php echo e($t['seo']['title']); ?>">
    <meta property="og:title" content="<?php echo e($t['seo']['title']); ?>">
    <meta property="og:description" content="<?php echo e($t['seo']['desc']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bahonarkaraj.ir">
    <meta property="og:image" content="https://bahonarkaraj.ir/images/og-cover.jpg">

    <!-- Structured Data (JSON-LD) for Search Engines -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "EducationalOrganization",
      "name": "<?php echo e($t['seo']['title']); ?>",
      "description": "<?php echo e($t['seo']['desc']); ?>",
      "url": "https://bahonarkaraj.ir",
      "logo": "https://bahonarkaraj.ir/images/logo.webp",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Motahari Blvd, Savoji St",
        "addressLocality": "Karaj",
        "addressRegion": "Alborz",
        "postalCode": "31497",
        "addressCountry": "IR"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+98-26-32522003",
        "contactType": "admissions"
      }
    }
    </script>

    <!-- Core Precompiled Tailwind + Custom Stylesheet (Critical CSS) -->
    <link rel="stylesheet" href="css/landing.min.css">

    <!-- Non-critical External Fonts & Third-Party Library Stylesheets (Loaded Asynchronously) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;700;800;900&display=swap" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="css/all.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="css/swiper-bundle.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="css/aos.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;700;800;900&display=swap">
        <link rel="stylesheet" href="css/all.min.css">
        <link rel="stylesheet" href="css/swiper-bundle.min.css">
        <link rel="stylesheet" href="css/aos.css">
    </noscript>

</head>

<body
    class="<?php echo e($t['font']); ?> antialiased selection:bg-blue-600 selection:text-white dir-<?php echo e($dir); ?>">

    <!-- Top Bar with Contact Info and Language Switcher -->
    <div class="top-bar hidden md:block py-2">
        <div class="container mx-auto px-4 max-w-7xl flex justify-between items-center">
            <div class="flex items-center gap-6">
                <a href="tel:<?php echo e($t['topbar']['phone']); ?>"
                    class="hover:text-blue-300 transition-colors flex items-center gap-2" dir="ltr">
                    <i class="fa-solid fa-phone"></i> <?php echo e($t['topbar']['phone']); ?>
                </a>
                <a href="mailto:<?php echo e($t['topbar']['email']); ?>"
                    class="hover:text-blue-300 transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-envelope"></i> <?php echo e($t['topbar']['email']); ?>
                </a>
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-clock"></i> <?php echo e($t['topbar']['hours']); ?>
                </span>
            </div>

            <!-- Language Switcher Button -->
            <div class="flex items-center gap-3">
                <a href="?lang=<?php echo $lang === 'fa' ? 'en' : 'fa'; ?>"
                    onclick="sessionStorage.setItem('scroll', window.scrollY);"
                    class="bg-white/10 hover:bg-white/20 px-3 py-1 rounded text-xs font-bold transition-colors">
                    <i class="fa-solid fa-globe me-1"></i> <?php echo $lang === 'fa' ? 'English' : 'فارسی'; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="main-nav sticky top-0 z-50 py-4" id="header">
        <div class="container mx-auto px-4 max-w-7xl flex justify-between items-center">
            <!-- School Brand / Logo -->
            <a href="index.php" class="flex items-center gap-3">
                <img src="./images/logo.webp" alt="Logo" class="h-12 w-auto" width="48" height="48"
                    onerror="this.src='./images/logo.png'">
                <div class="flex flex-col">
                    <span class="font-bold text-xl text-slate-800 leading-tight">
                        <?php echo $lang === 'fa' ? 'باهنـر ۳' : 'BAHONAR 3'; ?>
                    </span>
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">
                        <?php echo $lang === 'fa' ? 'استعدادهای درخشان' : 'Exceptional Talents'; ?>
                    </span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden lg:flex items-center gap-8">
                <a href="index.php" class="text-slate-600 font-medium hover:text-blue-600 transition-colors"><?php echo e($t['nav']['home']); ?></a>
                <a href="#about" class="text-slate-600 font-medium hover:text-blue-600 transition-colors"><?php echo e($t['nav']['about']); ?></a>
                <a href="#teachers" class="text-slate-600 font-medium hover:text-blue-600 transition-colors"><?php echo e($t['nav']['teachers']); ?></a>
                <a href="#blog" class="text-slate-600 font-medium hover:text-blue-600 transition-colors"><?php echo e($t['nav']['blog']); ?></a>
                <a href="#contact" class="text-slate-600 font-medium hover:text-blue-600 transition-colors"><?php echo e($t['nav']['contact']); ?></a>
            </nav>

            <!-- User Auth Buttons & Mobile Menu Toggle -->
            <div class="flex items-center gap-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard" class="hidden sm:flex btn-primary">
                        <i class="fa-regular fa-user-circle me-2"></i> <?php echo e($t['nav']['portal']); ?>
                    </a>
                <?php else: ?>
                    <a href="login" class="hidden sm:flex btn-primary">
                        <?php echo e($t['nav']['login']); ?>
                    </a>
                <?php endif; ?>

                <button id="mobileMenuBtn" aria-label="منوی اصلی" class="lg:hidden w-10 h-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-lg hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Overlay & Sliding Sidebar -->
    <div class="mobile-menu-overlay" id="mobileOverlay"></div>
    <div class="mobile-menu-content p-6 flex flex-col" id="mobileMenu">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4 mb-6">
            <span class="font-bold text-lg text-slate-800">منوی دسترسی</span>
            <button id="closeMobileMenu" aria-label="بستن منو" class="w-8 h-8 rounded bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <nav class="flex flex-col gap-4 font-medium text-slate-700">
            <a href="index.php" class="hover:text-blue-600 transition-colors"><i class="fa-solid fa-house w-6 text-slate-400"></i> <?php echo e($t['nav']['home']); ?></a>
            <a href="#about" class="hover:text-blue-600 transition-colors"><i class="fa-solid fa-circle-info w-6 text-slate-400"></i> <?php echo e($t['nav']['about']); ?></a>
            <a href="#teachers" class="hover:text-blue-600 transition-colors"><i class="fa-solid fa-users w-6 text-slate-400"></i> <?php echo e($t['nav']['teachers']); ?></a>
            <a href="#blog" class="hover:text-blue-600 transition-colors"><i class="fa-solid fa-newspaper w-6 text-slate-400"></i> <?php echo e($t['nav']['blog']); ?></a>
            <a href="#contact" class="hover:text-blue-600 transition-colors"><i class="fa-solid fa-phone w-6 text-slate-400"></i> <?php echo e($t['nav']['contact']); ?></a>
        </nav>
        <div class="mt-auto pt-6 border-t border-slate-100">
            <a href="login" class="flex justify-center btn-primary w-full"><?php echo e($t['nav']['login']); ?></a>
            <div class="flex justify-center mt-4">
                <a href="?lang=<?php echo $lang === 'fa' ? 'en' : 'fa'; ?>" class="text-sm font-bold text-slate-500 hover:text-blue-600">
                    <i class="fa-solid fa-globe me-1"></i>
                    <?php echo $lang === 'fa' ? 'Switch to English' : 'تغییر به فارسی'; ?>
                </a>
            </div>
        </div>
    </div>

    <main>
        <!-- Hero Section with Fullscreen Image Slider -->
        <section class="relative bg-slate-900 overflow-hidden">
            <div class="swiper hero-swiper">
                <div class="swiper-wrapper">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="swiper-slide">
                            <div class="hero-slide-bg">
                                <img 
                                    src="./images/<?php echo ($i <= 3) ? 'slide'.$i : 'part'.($i-3); ?>.webp" 
                                    <?php if ($i === 1): ?>
                                    srcset="./images/slide1-mobile.webp 768w, ./images/slide1.webp 1200w"
                                    sizes="100vw"
                                    <?php endif; ?>
                                    alt="School Section <?php echo $i; ?>"
                                    width="1200" height="600"
                                    loading="<?php echo ($i === 1) ? 'eager' : 'lazy'; ?>"
                                    <?php echo ($i === 1) ? 'fetchpriority="high"' : 'decoding="async"'; ?>
                                    style="width:100%; height:100%; object-fit:cover;"
                                >
                            </div>
                            <div class="hero-overlay"></div>
                            <div class="container mx-auto px-4 max-w-7xl hero-content">
                                <div class="max-w-2xl text-white" <?php echo ($i === 1) ? 'data-aos="fade-up"' : ''; ?>>
                                    <?php if ($i === 1): ?>
                                        <span class="inline-block py-1 px-3 bg-amber-500 rounded text-xs font-bold uppercase tracking-wider mb-4 text-slate-900">NODET Academy</span>
                                    <?php endif; ?>
                                    
                                    <h2 class="text-4xl md:text-6xl font-black mb-6 leading-tight drop-shadow-lg">
                                        <?php echo e($t['slider']['slide' . $i . '_title']); ?>
                                    </h2>
                                    <p class="text-lg text-slate-200 mb-8 font-light leading-relaxed drop-shadow">
                                        <?php echo e($t['slider']['slide' . $i . '_desc']); ?>
                                    </p>

                                    <?php if ($i === 1): ?>
                                        <a href="#about" class="btn-primary !bg-amber-500 hover:!bg-amber-600 !text-slate-900 !px-8 !py-4 !text-lg">
                                            <?php echo e($t['slider']['slide1_btn']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="swiper-button-next" aria-label="اسلاید بعدی"></div>
                <div class="swiper-button-prev" aria-label="اسلاید قبلی"></div>
                <div class="swiper-pagination mb-4"></div>
            </div>
        </section>

        <!-- Key Achievements and Statistics Cards -->
        <section class="relative z-10 -mt-16 mb-20 px-4">
            <div class="container mx-auto max-w-7xl">
                <div class="card-standard p-6 md:p-10 bg-white">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-slate-200 <?php echo $lang === 'fa' ? 'divide-x-reverse' : ''; ?>">
                        <div class="text-center px-4">
                            <div class="text-3xl md:text-4xl font-black text-blue-600 mb-2"><?php echo e($t['stats']['s1_num']); ?></div>
                            <div class="text-sm font-medium text-slate-600"><?php echo e($t['stats']['s1_lbl']); ?></div>
                        </div>
                        <div class="text-center px-4">
                            <div class="text-3xl md:text-4xl font-black text-blue-600 mb-2"><?php echo e($t['stats']['s2_num']); ?></div>
                            <div class="text-sm font-medium text-slate-600"><?php echo e($t['stats']['s2_lbl']); ?></div>
                        </div>
                        <div class="text-center px-4">
                            <div class="text-3xl md:text-4xl font-black text-amber-700 mb-2"><?php echo e($t['stats']['s3_num']); ?></div>
                            <div class="text-sm font-medium text-slate-600"><?php echo e($t['stats']['s3_lbl']); ?></div>
                        </div>
                        <div class="text-center px-4">
                            <div class="text-3xl md:text-4xl font-black text-blue-600 mb-2"><?php echo e($t['stats']['s4_num']); ?></div>
                            <div class="text-sm font-medium text-slate-600"><?php echo e($t['stats']['s4_lbl']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features / About Us Section -->
        <section id="about" class="py-20">
            <div class="container mx-auto px-4 max-w-7xl">
                <div class="section-title-wrapper" data-aos="fade-up">
                    <span class="section-subtitle"><?php echo e($t['features']['subtitle']); ?></span>
                    <h2 class="section-title"><?php echo e($t['features']['title']); ?></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="card-standard p-8 text-center" data-aos="fade-up" data-aos-delay="100">
                        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3"><?php echo e($t['features']['f1_title']); ?></h3>
                        <p class="text-slate-600 text-sm leading-relaxed"><?php echo e($t['features']['f1_desc']); ?></p>
                    </div>

                    <div class="card-standard p-8 text-center" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fa-solid fa-flask"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3"><?php echo e($t['features']['f2_title']); ?></h3>
                        <p class="text-slate-600 text-sm leading-relaxed"><?php echo e($t['features']['f2_desc']); ?></p>
                    </div>

                    <div class="card-standard p-8 text-center" data-aos="fade-up" data-aos-delay="300">
                        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fa-solid fa-microchip"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3"><?php echo e($t['features']['f3_title']); ?></h3>
                        <p class="text-slate-600 text-sm leading-relaxed"><?php echo e($t['features']['f3_desc']); ?></p>
                    </div>

                    <div class="card-standard p-8 text-center" data-aos="fade-up" data-aos-delay="400">
                        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center text-3xl mx-auto mb-6">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3"><?php echo e($t['features']['f4_title']); ?></h3>
                        <p class="text-slate-600 text-sm leading-relaxed"><?php echo e($t['features']['f4_desc']); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Teaching Staff Carousel Section -->
        <section id="teachers" class="py-20 bg-slate-100 border-y border-slate-200 overflow-hidden">
            <div class="container mx-auto px-4 max-w-7xl relative">
                <div class="section-title-wrapper" data-aos="fade-up">
                    <span class="section-subtitle"><?php echo e($t['faculty']['subtitle']); ?></span>
                    <h2 class="section-title"><?php echo e($t['faculty']['title']); ?></h2>
                </div>

                <div class="swiper teachers-swiper px-4 py-8">
                    <div class="swiper-wrapper">
                        <?php if (!empty($teachers)): ?>
                            <?php foreach ($teachers as $index => $teacher): ?>
                                <?php 
                                    $t_name = ($lang === 'en' && !empty($teacher['name_en'])) ? $teacher['name_en'] : $teacher['name'];
                                    $t_spec = ($lang === 'en' && !empty($teacher['specialty_en'])) ? $teacher['specialty_en'] : ($teacher['specialty'] ?? 'تخصص تعریف نشده');
                                ?>
                                <div class="swiper-slide h-auto">
                                    <a href="teachers/profile.php?id=<?php echo $teacher['id']; ?>" class="block h-full">
                                        <div class="card-standard p-6 text-center h-full flex flex-col items-center">
                                            <div class="w-32 h-32 mx-auto rounded-full overflow-hidden border-4 border-white shadow-md mb-4 bg-slate-200">
                                                <?php
                                                $raw_img = !empty($teacher['profile_image']) ? $teacher['profile_image'] : '';
                                                $img = $raw_img;
                                                if ($raw_img) {
                                                    $webp_version = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $raw_img);
                                                    if ($webp_version !== $raw_img && file_exists(__DIR__ . '/' . $webp_version)) {
                                                        $img = $webp_version;
                                                    }
                                                }
                                                $fallback = "https://ui-avatars.com/api/?name=" . urlencode($t_name) . "&background=1e3a8a&color=ffffff&size=256";
                                                ?>
                                                <img src="<?php echo e($img ? $img : $fallback); ?>" alt="<?php echo e($t_name); ?>" width="128" height="128" loading="lazy" class="w-full h-full object-cover" onerror="this.src='<?php echo $fallback; ?>'">
                                            </div>
                                            <h3 class="text-lg font-bold text-slate-800 mb-1"><?php echo e($t_name); ?></h3>
                                            <span class="inline-block px-3 py-1 bg-blue-50 text-blue-600 text-xs font-bold rounded-full">
                                                <?php echo e($t_spec); ?>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center py-10">
                                <p class="text-slate-500 font-medium"><?php echo e($t['faculty']['empty']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="swiper-button-next !text-blue-600 !w-10 !h-10 after:!text-lg bg-white shadow-lg rounded-full border border-slate-200" aria-label="استاد بعدی"></div>
                    <div class="swiper-button-prev !text-blue-600 !w-10 !h-10 after:!text-lg bg-white shadow-lg rounded-full border border-slate-200" aria-label="استاد قبلی"></div>
                </div>

                <div class="text-center mt-12">
                    <a href="teachers/index.php" class="btn-primary">
                        <?php echo e($t['faculty']['view_all']); ?>
                        <i class="fa-solid fa-arrow-<?php echo $lang === 'fa' ? 'left' : 'right'; ?> ms-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- News & Blog Highlights Section -->
        <section id="blog" class="py-20">
            <div class="container mx-auto px-4 max-w-7xl">
                <div class="section-title-wrapper" data-aos="fade-up">
                    <span class="section-subtitle"><?php echo e($t['blog']['subtitle']); ?></span>
                    <h2 class="section-title"><?php echo e($t['blog']['title']); ?></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $index => $post): ?>
                            <article class="card-standard flex flex-col overflow-hidden" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                                <a href="blog/post/index.php?id=<?php echo (int) $post['id']; ?>" aria-label="<?php echo e($post['title']); ?>" class="block w-full aspect-video bg-slate-200 relative overflow-hidden group">
                                    <?php
                                    $img_path = !empty($post['image_path']) ? $post['image_path'] : '';
                                    if ($img_path && file_exists(__DIR__ . '/' . $img_path)) {
                                        $img = "./" . $img_path;
                                    } elseif ($img_path && file_exists(__DIR__ . '/' . preg_replace('/\.(jpg|png)$/i', '.webp', $img_path))) {
                                        $img = "./" . preg_replace('/\.(jpg|png)$/i', '.webp', $img_path);
                                    } else {
                                        $img = "./images/posts/default.webp";
                                    }
                                    ?>
                                    <img src="<?php echo e($img); ?>" alt="<?php echo e($post['title']); ?>" width="400" height="225" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.src='./images/posts/default.webp'">
                                    
                                    <time datetime="<?php echo e($post['created_at']); ?>" class="absolute top-4 <?php echo $lang === 'fa' ? 'right-4' : 'left-4'; ?> bg-white px-3 py-1 rounded shadow-sm text-xs font-bold text-blue-600">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        <?php echo $lang === 'fa' ? toJalali($post['created_at']) : date('M d, Y', strtotime($post['created_at'])); ?>
                                    </time>
                                </a>
                                <div class="p-6 flex flex-col flex-grow">
                                    <div class="text-xs text-slate-500 mb-3 flex items-center gap-2">
                                        <i class="fa-solid fa-user-pen"></i> <?php echo e($post['author_name']); ?>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-800 mb-4 line-clamp-2 hover:text-blue-600 transition-colors">
                                        <a href="blog/post/index.php?id=<?php echo (int) $post['id']; ?>"><?php echo e($post['title']); ?></a>
                                    </h3>
                                    <div class="mt-auto">
                                        <a href="blog/post/index.php?id=<?php echo (int) $post['id']; ?>" aria-label="ادامه مطلب خبر <?php echo e($post['title']); ?>" class="text-blue-600 font-bold text-sm inline-flex items-center gap-2 hover:text-blue-800 transition-colors group">
                                            <?php echo e($t['blog']['read']); ?>
                                            <i class="fa-solid fa-arrow-<?php echo $lang === 'fa' ? 'left' : 'right'; ?> group-hover:translate-x-<?php echo $lang === 'fa' ? '-4px' : '4px'; ?> transition-transform"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-10">
                            <p class="text-slate-500 font-medium"><?php echo e($t['blog']['empty']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-12" data-aos="fade-up">
                    <a href="blog/index.php" class="btn-primary">
                        <?php echo $lang === 'fa' ? 'نمایش همه اخبار' : 'View All News'; ?>
                        <i class="fa-solid fa-arrow-<?php echo $lang === 'fa' ? 'left' : 'right'; ?> ms-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Contact Information and Google Maps Section -->
        <section id="contact" class="py-20 bg-slate-100 border-t border-slate-200">
            <div class="container mx-auto px-4 max-w-7xl">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div data-aos="fade-up">
                        <span class="section-subtitle text-start"><?php echo e($t['nav']['contact']); ?></span>
                        <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-6">
                            <?php echo e($t['contact']['title']); ?>
                        </h2>
                        <p class="text-slate-600 mb-10 leading-relaxed text-lg"><?php echo e($t['contact']['desc']); ?></p>

                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center text-blue-600 text-xl shrink-0">
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm mb-1"><?php echo e($t['contact']['address_lbl']); ?></h3>
                                    <p class="text-slate-600"><?php echo e($t['contact']['address']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center text-blue-600 text-xl shrink-0">
                                    <i class="fa-solid fa-phone"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm mb-1"><?php echo e($t['contact']['phone_lbl']); ?></h3>
                                    <p class="text-slate-600" dir="ltr"><?php echo e($t['topbar']['phone']); ?></p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center text-blue-600 text-xl shrink-0">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm mb-1"><?php echo e($t['contact']['email_lbl']); ?></h3>
                                    <a href="mailto:<?php echo e($t['topbar']['email']); ?>" class="text-blue-600 hover:underline" dir="ltr"><?php echo e($t['topbar']['email']); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Smart Lazy Map Container -->
                    <div class="card-standard p-2 h-[450px]" data-aos="zoom-in" id="mapWrapper">
                        <iframe
                            data-src="https://maps.google.com/maps?q=%D8%A8%D8%A7%D9%87%D9%86%D8%B1%203&amp;t=&amp;z=14&amp;ie=UTF8&amp;iwloc=&amp;output=embed"
                            id="mapIframe" title="School Map Location" class="w-full h-full rounded-xl border-0" allowfullscreen=""
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Page Footer -->
    <footer class="bg-slate-900 text-slate-300 pt-16 pb-8">
        <div class="container mx-auto px-4 max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 mb-12">
                <div class="md:col-span-5">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="./images/logo.webp" alt="Logo" class="h-10 w-auto" width="40" height="40" loading="lazy" decoding="async" onerror="this.src='./images/logo.png'">
                        <span class="font-bold text-xl text-white">
                            <?php echo $lang === 'fa' ? 'باهنر ۳' : 'BAHONAR 3'; ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed mb-6">
                        <?php echo e($t['footer']['about']); ?>
                    </p>
                </div>

                <div class="md:col-span-3">
                    <h3 class="text-white font-bold mb-6 text-base"><?php echo e($t['footer']['links']); ?></h3>
                    <ul class="space-y-3">
                        <li><a href="#about" class="text-sm text-slate-400 hover:text-white transition-colors"><?php echo e($t['nav']['about']); ?></a></li>
                        <li><a href="#teachers" class="text-sm text-slate-400 hover:text-white transition-colors"><?php echo e($t['nav']['teachers']); ?></a></li>
                        <li><a href="#blog" class="text-sm text-slate-400 hover:text-white transition-colors"><?php echo e($t['nav']['blog']); ?></a></li>
                        <li><a href="#contact" class="text-sm text-slate-400 hover:text-white transition-colors"><?php echo e($t['nav']['contact']); ?></a></li>
                    </ul>
                </div>

                <div class="md:col-span-4">
                    <h3 class="text-white font-bold mb-6 text-base"><?php echo e($t['nav']['contact']); ?></h3>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-sm">
                            <i class="fa-solid fa-phone text-blue-500"></i>
                            <span dir="ltr"><?php echo e($t['topbar']['phone']); ?></span>
                        </li>
                        <li class="flex items-center gap-3 text-sm">
                            <i class="fa-solid fa-envelope text-blue-500"></i>
                            <span dir="ltr"><?php echo e($t['topbar']['email']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs">
                <p>&copy; <?php echo date('Y'); ?>. <?php echo e($t['footer']['copyright']); ?></p>
                <a href="https://aminmadani.ir" target="_blank" rel="noopener" class="hover:text-white transition-colors">
                    <?php echo e($t['footer']['dev']); ?>
                </a>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries (Loaded Locally with Defer) -->
    <script src="js/aos.js" defer></script>
    <script src="js/swiper-bundle.min.js" defer></script>

    <!-- Client-side Logic Script -->
    <script>
        // Restore scroll position after dynamic language switch reloading
        window.addEventListener('load', function () {
            if (sessionStorage.getItem('scroll') !== null) {
                window.scrollTo(0, parseInt(sessionStorage.getItem('scroll')));
                sessionStorage.removeItem('scroll');
            }
        });

        function initLibraries() {
            // Initialize Animate On Scroll (AOS) plugin
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    once: true,
                    offset: 50,
                    duration: 600,
                    easing: 'ease-out-cubic',
                });
            }

            // Configure full-screen Hero Swiper slider instance
            if (typeof Swiper !== 'undefined') {
                new Swiper(".hero-swiper", {
                    loop: true,
                    speed: 1000,
                    autoplay: { delay: 5000, disableOnInteraction: false },
                    effect: 'fade',
                    fadeEffect: { crossFade: true },
                    pagination: { el: ".swiper-pagination", clickable: true },
                    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
                });

                // Configure responsive Teachers Swiper carousel
                new Swiper(".teachers-swiper", {
                    slidesPerView: 1, 
                    spaceBetween: 20,
                    loop: true,
                    autoplay: { delay: 3000, disableOnInteraction: false },
                    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
                    breakpoints: {
                        640: { slidesPerView: 2 },
                        1024: { slidesPerView: 4 },
                    },
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLibraries);
        } else {
            initLibraries();
        }

        // Dynamic header styling adaptation on user scroll
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }, { passive: true });

        // Toggle mobile navigation drawer overlay
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('closeMobileMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function toggleMenu() {
            mobileMenu.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        }

        if (mobileBtn) mobileBtn.addEventListener('click', toggleMenu);
        if (closeBtn) closeBtn.addEventListener('click', toggleMenu);
        if (mobileOverlay) mobileOverlay.addEventListener('click', toggleMenu);

        // Auto-close mobile drawer when any link is clicked
        document.querySelectorAll('.mobile-menu-content nav a').forEach(link => {
            link.addEventListener('click', toggleMenu);
        });

        // Smart Intersection Observer for delayed Google Maps iframe loading
        const mapObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = document.getElementById('mapIframe');
                    if (iframe && !iframe.src && iframe.dataset.src) {
                        iframe.src = iframe.dataset.src;
                    }
                    mapObserver.unobserve(entry.target);
                }
            });
        }, { rootMargin: '100px' });

        const mapWrapper = document.getElementById('mapWrapper');
        if (mapWrapper) mapObserver.observe(mapWrapper);
    </script>
</body>

</html>