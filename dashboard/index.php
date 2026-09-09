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
 * ------------------------------------------------------------
 */

session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../log.php';

// Set default timezone for system timestamp operations
date_default_timezone_set('Asia/Tehran');

// Authenticate user session existence
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit();
}

$user_role = $_SESSION['role'];
$username = $_SESSION['username'];

// Fetch logged-in user real name
$stmt = $pdo->prepare("SELECT name FROM users WHERE username = :username LIMIT 1");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$realName = $user['name'] ?? $username;

// Retrieve or generate Chrome extension token for active user
$stmt = $pdo->prepare("SELECT extension_token FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow || empty($tokenRow['extension_token'])) {
    $newToken = bin2hex(random_bytes(16));
    $update = $pdo->prepare("UPDATE users SET extension_token = ? WHERE username = ?");
    $update->execute([$newToken, $username]);
    $extensionToken = $newToken;
} else {
    $extensionToken = $tokenRow['extension_token'];
}

// Generate CSRF token for secure AJAX form submissions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Role label helper
$roleLabel = match($user_role) {
    'admin' => 'مدیریت کل سیستم',
    'teacher' => 'دبیر سامانه',
    'student' => 'دانش‌آموز',
    default => 'کاربر سامانه'
};
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد یکپارچه | ZeroLMS</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    
    <!-- Bootstrap RTL & FontAwesome -->
    <link href="../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../css/all.min.css" rel="stylesheet">
    <link href="../css/fontawesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Unified High-Contrast Dashboard Stylesheet & Preloader -->
    <link rel="stylesheet" href="assets/style.css?v=3.1">
    <link rel="stylesheet" href="assets/loader.css?v=3.1">
    
    <!-- Chart.js Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Full Original SVG Origami Preloader Harmonized with Modern Dark Theme -->
    <div class="loader-overlay" id="loader">
        <div class="loader-container-center">
<div id="svg-container">
                <svg viewBox="-100 100 800 800" preserveAspectRatio="xMidYMid meet" class="svg-hw">
                    <path id="path7050" class="path" d="m 187.44537,731.24092 15.72591,-20.08687 -17.17956,-0.13215 z">
                    </path>
                    <path id="path7062" class="path" d="m 186.55602,711.0219 -0.92505,-28.54449 18.15934,28.28019 z">
                    </path>
                    <path id="path7064" class="path" d="m 197.05666,701.24277 7.66472,-38.72007 -19.20716,18.2821 z">
                    </path>
                    <path id="path7065" class="path" d="m 185.81422,680.80973 12.99609,-49.2053 6.34322,29.99815 z">
                    </path>
                    <path id="path7066" class="path" d="m 205.15353,661.60258 14.933,-35.28417 -21.27622,5.41817 z">
                    </path>
                    <path id="path7067" class="path" d="m 220.08653,626.04918 32.64115,-24.31568 -54.04952,29.73385 z">
                    </path>
                    <path id="path7068" class="path" d="m 198.67816,631.46735 17.70816,-27.35514 35.81276,-2.11441 z">
                    </path>
                    <path id="path7069" class="path" d="m 216.75417,603.98006 42.81673,-42.94889 -6.73967,40.96663 z">
                    </path>
                    <path id="path7070" class="path" d="m 253.33123,601.9978 71.62553,-33.8305 -64.35726,-7.92903 z">
                    </path>
                    <path id="path7071" class="path" d="m 258.93875,598.82619 26.82654,49.82071 3.96451,-63.82865 z">
                    </path>
                    <path id="path7072" class="path" d="M 286.02959,650.57196 299.90538,646.5325 288.6726,600.9406 z">
                    </path>
                    <path id="path7076" class="path" d="m 286.16174,649.57196 12.81859,49.95286 1.0572,-52.86017 z">
                    </path>
                    <path id="path7080" class="path" d="m 300.03753,646.5325 11.23279,55.37102 -12.58636,-3.52271 z">
                    </path>
                    <path id="path7081" class="path" d="m 298.68396,698.38081 6.24314,39.59978 6.47537,-35.94491 z">
                    </path>
                    <path id="path7086" class="path" d="m 305.1914,737.58414 -18.63321,0.13215 14.80085,-22.99417 z">
                    </path>
                    <path id="path7088" class="path" d="m 244.27005,575.69987 -50.74576,-73.47564 65.81091,57.74974 z">
                    </path>
                    <path id="path7090" class="path" d="m 193.65644,502.22423 26.56224,-7.26827 2.24656,32.37685 z">
                    </path>
                    <path id="path7091" class="path" d="m 222.46524,527.33281 134.89545,-2.07099 -97.9298,34.94823 z">
                    </path>
                    <path id="path7092" class="path" d="m 357.1738,525.07493 -15.88556,-44.29267 -92.51002,45.78778 z">
                    </path>
                    <path id="path7094" class="path" d="m 341.28824,480.59537 -7.84933,-21.67912 -40.36801,45.6009 z">
                    </path>
                    <path id="path7096" class="path" d="m 292.51023,504.70404 -41.86312,-49.15179 81.60491,3.17711 z">
                    </path>
                    <path id="path7098" class="path" d="m 220.18422,495.98581 29.15467,31.77112 -26.87365,-0.57588 z">
                    </path>
                    <path id="path7100" class="path" d="m 218.74489,495.79893 72.76534,9.34444 -40.34156,-49.43357 z">
                    </path>
                    <path id="path7101" class="path" d="m 292.51023,505.14337  -43.14156,21.03357 -28,-31.1 z"></path>
                    <path id="path7102" class="path" d="M 220.74489,494.79893 212.148,468.07381 251.08645,456.55225 z">
                    </path>
                    <path id="path7104" class="path" d="m 211.96111,468.07381 -18.502,34.01378 -9.90512,-38.49911 z">
                    </path>
                    <path id="path7105" class="path" d="m 211.96111,468.07381 8.502,26.01378 -24.90512,6.89911 z">
                    </path>
                    <path id="path7106" class="path" d="M 211.77422,468.2607 239.06,397.61669 251.89956,455.36536 z">
                    </path>
                    <path id="path7107" class="path" d="m 238.87311,397.05602 -48.59112,18.31511 36.06957,13.39045 z">
                    </path>
                    <path id="path7108" class="path"
                        d="m 226.35156,429.761 -41.79757,31.45312 10.27889,-26.22023 31.1449,-6.046 z"></path>
                    <path id="path7109" class="path" d="m 225.97778,429.94789 -14,36.5 -27.5,-3.8 z"></path>
                    <path id="path7110" class="path" d="m 189.90822,415.74491 3.73777,18.68889 -18.87578,-19.24955 z">
                    </path>
                    <path id="path7111" class="path" d="m 189.90822,415.74491 4.73777,19.58889 27.87578,-7.24955 z">
                    </path>
                    <path id="path7112" class="path" d="m 173.83577,414.81047 -6.16734,14.39044 25.97756,5.41978 z">
                    </path>
                    <path id="path7114" class="path" d="m 168.66843,429.20091 8.5969,9.90512 17.38066,-4.11156 z">
                    </path>
                    <path id="path7116" class="path" d="m 173.64888,414.62358 41.11556,-44.66645 -25.04311,45.78778 z">
                    </path>
                    <path id="path7124" class="path" d="m 214.20378,370.3309 -24.48245,2.99023 -2.42956,25.97756 z">
                    </path>
                    <path id="path7126" class="path" d="m 189.53444,373.13424 -10.83956,12.89533 8.22311,12.20356 z">
                    </path>
                    <path id="path7128" class="path" d="m 180.75066,389.39357 -1.30822,19.4889 7,-11 z"></path>
                    <path id="path7129" class="path" d="m 178.88177,386.02957 -36.81712,-30.46289 46.72223,17.75445 z">
                    </path>
                    <path id="path7130" class="path" d="m 188.78688,373.32113 -13.82978,-14.57734 -32.70556,-3.17711 z">
                    </path>
                    <path id="path7132" class="path" d="m 142.25154,355.56668  13.456,22.23978 21.63023,8.36277 z">
                    </path>
                    <path id="path7134" class="path" d="m 214.95133,370.5178 24.10867,26.35133 0.74756,-22.23978 z">
                    </path>
                    <path id="path7135" class="path" d="m 214.95133,370.5178 24.10867,26.35133 -48.74756,18.23978 z">
                    </path>
                    <path id="path7136" class="path" d="m 239.62067,388.08535 41.67623,-7.84933 -41.11557,-5.98045 z">
                    </path>
                    <path id="path7137" class="path" d="m 240.18133,374.25557  53.26335,-19.99711 -12.33467,25.79067 z">
                    </path>
                    <path id="path7138" class="path" d="m 293.81845,353.88468 -35.322,4.29844 -18.50201,16.25934 z">
                    </path>
                    <path id="path7140" class="path" d="m 202.99044,371.45224 -32.14489,-32.1449 22.61356,33.64001 z">
                    </path>
                    <path id="path7142" class="path" d="m 188.97377,357.62246 5.41978,-32.51868 2.24267,40.18112 z">
                    </path>
                    <path id="path7144" class="path" d="m 170.65866,339.30734 -11.96089,-1.49511 28.22022,25.60378 z">
                    </path>
                    <path id="path7146" class="path" d="m 170.65866,339.30734 0.37377,-52.88956 -8.22311,51.58134 z">
                    </path>
                    <path id="path7148" class="path" d="m 166.92088,311.08711 -5.79356,-43.54511 9.71823,19.24955 z">
                    </path>
                    <path id="path7149" class="path" d="m 170.84555,286.79155  14.01667,-37.75156 -18.12823,28.7809 z">
                    </path>
                    <path id="path7150" class="path" d="m 171.03244,309.592 18.12822,-12.52155 -18.12822,22.98733 z">
                    </path>
                    <path id="path7152" class="path" d="M 157.95021,337.43845 136.64487,308.47067 163.1831,333.3269 z">
                    </path>
                    <path id="path7154" class="path" d="m 145.98932,316.69378 11.774,-31.958 -5.60667,37.93845 z">
                    </path>
                    <path id="path7156" class="path" d="m 137.01865,308.65756 4.11156,-38.49912 0.93444,42.79756 z">
                    </path>
                    <path id="path7158" class="path" d="m 214.95133,370.14401 17.75445,-14.39044 -5.79356,16.44622 z">
                    </path>
                    <path id="path7160" class="path" d="m 233.26645,354.44535 -2.05578,-24.66934 -3.55089,29.71534 z">
                    </path>
                    <path id="path7162" class="path" d="m 233.26645,355.1929 19.43644,-1.30822 -25.60378,17.94133 z">
                    </path>
                    <path id="path7164" class="path" d="m 239.99444,362.66846 17.94134,-6.16734 -4.48533,-2.80333 z">
                    </path>
                    <path id="path7165" class="path" d="m 253.45045,353.69779  29.34156,-13.456 -24.85623,16.07244 z">
                    </path>
                    <path id="path7168" class="path" d="m 258.30956,356.68801 28.594,-11.40022 -4.29844,-4.85911 z">
                    </path>
                    <path id="path7170" class="path" d="m 287.27735,345.47468 16.82,-25.23001 -21.49223,19.99712 z">
                    </path>
                    <path id="path7172" class="path" d="m 304.09735,320.43156 4.11155,-34.01378 -9.71822,38.686 z">
                    </path>
                    <path id="path7174" class="path" d="m 292.69712,330.14978 -8.97066,-12.70844 -1.12134,22.80045 z">
                    </path>
                    <path id="path7176" class="path" d="m 288.95934,324.35623 7.66245,-14.57734 -13.08223,6.728 z">
                    </path>
                    <path id="path7178" class="path" d="m 283.53956,316.69378 -29.90222,-15.69867 29.34156,22.42667 z">
                    </path>
                    <path id="path7180" class="path" d="m 296.99556,309.21823 3.36401,-19.43645 -10.09201,22.80045 z">
                    </path>
                    <path id="path7182" class="path" d="m 300.35957,289.96867 -5.41978,-31.39734 0.56066,40.55489 z">
                    </path>
                    <path id="path7184" class="path" d="m 295.31357,294.82778 -10.09201,-5.79356 10.46578,10.65267 z">
                    </path>
                    <path id="path7188" class="path" d="m 356.98691,524.88804 -32.51867,42.98445 72.32601,-2.80333 z">
                    </path>
                    <path id="path7189" class="path" d="m 356.98691,524.88804 -32.51867,42.98445 -65.32601,-7.80333 z">
                    </path>
                    <path id="path7190" class="path" d="m 357.1738,525.07493 43.35823,-30.276 -3.17711,69.89645 z">
                    </path>
                    <path id="path7192" class="path" d="m 333.6258,458.72937 66.90623,35.88267 7.66244,-42.61067 z">
                    </path>
                    <path id="path7193" class="path" d="m 333.6258,458.72937 66.90623,35.88267 -42.66244,30.61067 z">
                    </path>
                    <path id="path7194" class="path" d="m 454.72982,481.52981 18.12822,17.94134 -10.83956,-5.60667 z">
                    </path>
                    <path id="path7195" class="path" d="m 462.01848,493.86448  -17.00689,-27.28578 -37.004,-14.76423 z">
                    </path>
                    <path id="path7196" class="path" d="m 408.00759,451.81447  53.63712,42.05001 -61.48646,0.37378 z">
                    </path>
                    <path id="path7197" class="path"
                        d="m 461.64471,493.86448 -61.48646,0.37378 50.46001,50.08623 11.40022,-50.46001 z"></path>
                    <path id="path7198" class="path" d="m 396.79425,564.88227 53.63712,-20.74467 -51,-50 z"></path>
                    <path id="path7200" class="path" d="m 461.8316,494.23826 6.728,17.94134 4.29844,-12.89534 z"></path>
                    <path id="path7202" class="path" d="m 450.61826,543.95071 17.94134,-31.77111 -8,-15 z"></path>
                    <path id="path7206" class="path" d="m 472.85804,499.47115 -0.56067,43.17134 -3.55088,-30.27601 z">
                    </path>
                    <path id="path7214" class="path" d="m 467.99893,512.55337 -13.64289,47.65668 -4,-16 z"></path>
                    <path id="path7216" class="path"
                        d="m 458.65448,546.38027 13.82978,-4.11156 0,0 -5.09201,-29.99712 z"></path>
                    <path id="path7215" class="path" d="m 458.65448,546.38027 13.82978,-4.11156 10.09201,19.99712 z">
                    </path>
                    <path id="path7218" class="path" d="m 396.98114,565.25605 37.93845,70.64401 15.88556,-91.57557 z">
                    </path>
                    <path id="path7219" class="path" d="m 450.61826,544.51138 11.58711,51.39445 -20.184,0.37378 z">
                    </path>
                    <path id="path7222" class="path" d="m 442.02173,596.27961   7.84933,23.17423 12.52156,-23.92179 z">
                    </path>
                    <path id="path7224" class="path" d="m 462.01848,595.71894 27.47268,35.32201 -39.99423,-11.21334 z">
                    </path>
                    <path id="path7225" class="path" d="m 470.42849,625.62117 -19.99423,-5.21334 30.08911,37.75157 z">
                    </path>
                    <path id="path7226" class="path" d="m 470.42849,626.62117 16.44622,57.56179 2.364,-51.95512 z">
                    </path>
                    <path id="path7228" class="path" d="m 488.86493,641.69362 20.55778,67.09312 -23.548,-26.35134 z">
                    </path>
                    <path id="path7230" class="path" d="m 510.42271,710.28674 -17.56755,5.79356 -6.54112,-32.89245 z">
                    </path>
                    <path id="path7231" class="path" d="m 510.42271,710.59985 -15.138,28.22023 -2.24266,-22.05289 z">
                    </path>
                    <path id="path7232" class="path" d="m 493.04205,717.76719  -16.44623,22.98733 18.31511,-0.18689 z">
                    </path>
                    <path id="path7233" class="path" d="m 439.59181,609.05361 20.18401,40.5549 -24.66934,-12.70845 z">
                    </path>
                    <path id="path7234" class="path" d="m 435.10648,637.00006  -3.73778,49.52557 28.59401,-36.25646 z">
                    </path>
                    <path id="path7235" class="path" d="m 459.96271,649.16917  -22.98734,64.10291 -5.79356,-27.84645 z">
                    </path>
                    <path id="path7236" class="path" d="m 431.18181,685.42563 -15.32489,43.35823 20.74467,-14.57734 z">
                    </path>
                    <path id="path7237" class="path" d="m 436.60159,713.45896 -12.33467,32.70556 -8.78378,-16.63311 z">
                    </path>
                    <path id="path7238" class="path" d="m 415.48314,729.53141  -15.51178,16.82 24.10867,0.18689 z">
                    </path>
                </svg>
            </div>
            <div class="loading loading02" dir="rtl">
                <span>بـ</span>
                <span>ـا</span>
                <span>ر</span>
                <span>گـ</span>
                <span>ـذ</span>
                <span>ا</span>
                <span>ر</span>
                <span>ی</span>
            </div>
            <div class="loader-subtext">سامانه جامع مدیریت یادگیری ZeroLMS</div>
        </div>
    </div>

    <!-- Apple Watch Widget -->
    <div class="watch">
        <div class="frame">
            <div class="text">
                <div id="hours"><?php echo date('H'); ?></div>
                <div id="minutes"><?php echo date('i'); ?></div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         1. SIDEBAR NAVIGATION
    ==================================================================== -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand Header -->
        <div class="sidebar-header">
            <div class="sidebar-brand-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="sidebar-brand-text">
                <h4>ZeroLMS</h4>
                <span>سامانه مدیریت یادگیری</span>
            </div>
        </div>

        <!-- Menu Links -->
        <nav class="sidebar-menu">
            <div class="sidebar-category-label">پیشخوان اصلی</div>
            <a href="#" class="nav-link active" id="showMainContent">
                <i class="fas fa-home"></i>
                <span>داشبورد اصلی</span>
            </a>
            <a href="#" class="nav-link" id="showClasses">
                <i class="fas fa-chalkboard"></i>
                <span>کلاس‌ها و دروس مجازی</span>
            </a>

            <div class="sidebar-category-label">ابزارهای هوشمند</div>
            <?php if ($user_role === 'admin'): ?>
                <a href="./schedule_builder.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>برنامه‌ریزی هوشمند هفتگی</span>
                    <span class="sidebar-badge">CSP</span>
                </a>
            <?php endif; ?>
            <a href="./ai" class="nav-link">
                <i class="fas fa-robot"></i>
                <span>دستیار هوش مصنوعی</span>
            </a>
            <?php if ($user_role === 'admin'): ?>
                <a href="./forms/manage_forms.php" class="nav-link">
                    <i class="fas fa-poll-h"></i>
                    <span>مدیریت فرم‌ها و نظرسنجی</span>
                </a>
            <?php endif; ?>

            <div class="sidebar-category-label">آموزش و آزمون</div>
            <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
                <a href="./homeworks/teacher.php" class="nav-link">
                    <i class="fas fa-tasks"></i>
                    <span>مدیریت تکالیف</span>
                </a>
                <a href="./exams/" class="nav-link">
                    <i class="fas fa-file-signature"></i>
                    <span>بانک آزمون‌های آنلاین</span>
                </a>
                <a href="./attendance/" class="nav-link">
                    <i class="fas fa-user-check"></i>
                    <span>سامانه حضور و غیاب</span>
                </a>
                <a href="./reportcard/" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>کارنامه و نمرات تحلیلی</span>
                </a>
            <?php else: ?>
                <a href="./homeworks/" class="nav-link">
                    <i class="fas fa-pencil-alt"></i>
                    <span>تکالیف من</span>
                </a>
                <a href="./exams/" class="nav-link">
                    <i class="fas fa-pen-nib"></i>
                    <span>آزمون‌های پیش‌رو</span>
                </a>
                <a href="./attendance/" class="nav-link">
                    <i class="fas fa-clipboard-check"></i>
                    <span>وضعیت حضور و غیاب</span>
                </a>
                <a href="./reportcard/" class="nav-link">
                    <i class="fas fa-award"></i>
                    <span>نمرات و کارنامه</span>
                </a>
                <a href="./gamification/" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>امتیازات و افتخارات</span>
                </a>
            <?php endif; ?>

            <?php if ($user_role === 'admin'): ?>
                <div class="sidebar-category-label">مدیریت سیستم</div>
                <a href="./manage_users" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    <span>مدیریت کاربران</span>
                </a>
                <a href="./manage_classes" class="nav-link">
                    <i class="fas fa-school"></i>
                    <span>مدیریت ساختار کلاس‌ها</span>
                </a>
                <a href="./manage_blog" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    <span>مدیریت وبلاگ و اطلاعیه‌ها</span>
                </a>
                <a href="#" class="nav-link" id="showGallery">
                    <i class="fas fa-images"></i>
                    <span>گالری تصاویر</span>
                </a>
                <a href="#" class="nav-link" id="showLogs">
                    <i class="fas fa-shield-alt"></i>
                    <span>لاگ‌های امنیتی</span>
                </a>
            <?php elseif ($user_role === 'teacher'): ?>
                <div class="sidebar-category-label">محتوا</div>
                <a href="./manage_blog" class="nav-link">
                    <i class="fas fa-newspaper"></i>
                    <span>وبلاگ و اطلاعیه‌ها</span>
                </a>
                <a href="#" class="nav-link" id="showGallery">
                    <i class="fas fa-images"></i>
                    <span>گالری تصاویر</span>
                </a>
            <?php endif; ?>

            <div class="sidebar-category-label">سیستم</div>
            <a href="#" class="nav-link" id="showSettings">
                <i class="fas fa-sliders-h"></i>
                <span>تنظیمات حساب</span>
            </a>
            <a href="../logout.php" class="nav-link text-danger mt-1">
                <i class="fas fa-power-off text-danger"></i>
                <span>خروج از حساب</span>
            </a>
        </nav>

        <!-- Sidebar User Card -->
        <div class="sidebar-footer">
            <div class="sidebar-user-card">
                <div class="sidebar-user-avatar">
                    <?php echo mb_substr($realName, 0, 1, 'UTF-8'); ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($realName); ?></div>
                    <div class="sidebar-user-role"><?php echo htmlspecialchars($roleLabel); ?></div>
                </div>
                <a href="../logout.php" title="خروج" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- ====================================================================
         2. TOPBAR
    ==================================================================== -->
    <header class="topbar">
        <div class="topbar-right">
            <button class="topbar-hamburger" id="hamburger" aria-label="منو">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-status-chip">
                <span class="pulse-dot"></span>
                <span>سامانه برخط</span>
            </div>
            <div class="topbar-datetime" id="topbarLiveDate">
                <i class="far fa-calendar-alt"></i>
                <span id="shamsiDateText">امروز</span>
            </div>
        </div>

        <div class="topbar-left">
            <button class="topbar-btn clock-icon" title="ساعت">
                <i class="fas fa-clock"></i>
                <span id="topbarClock"><?php echo date('H:i'); ?></span>
            </button>

            <button class="topbar-btn d-none d-md-inline-flex" onclick="copyExtensionQuickToken()" title="کپی توکن افزونه کروم">
                <i class="fas fa-puzzle-piece"></i>
                <span>افزونه معلم</span>
            </button>

            <div class="topbar-user-pill">
                <div class="topbar-user-avatar">
                    <?php echo mb_substr($realName, 0, 1, 'UTF-8'); ?>
                </div>
                <span class="fw-semibold"><?php echo htmlspecialchars($realName); ?></span>
                <span class="topbar-role-badge">
                    <?php echo htmlspecialchars($roleLabel); ?>
                </span>
            </div>
        </div>
    </header>

    <!-- ====================================================================
         3. MAIN CONTENT
    ==================================================================== -->
    <main class="main-content dashboard-section" id="mainContent" style="display: block;">
        <div class="container-fluid p-0">
            
            <!-- Hero Welcome Card -->
            <div class="hero-welcome-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="hero-badge-role mb-2">
                            <i class="fas fa-shield-alt me-1"></i>
                            <span>نقش کاربری: <?php echo htmlspecialchars($roleLabel); ?></span>
                        </div>
                        <h2 class="fw-bold text-white mb-2" style="font-size: 1.65rem;">
                            خوش آمدید، <?php echo htmlspecialchars($realName); ?> 👋
                        </h2>
                        <p class="m-0" style="color: #cbd5e1; font-size: 0.92rem;">
                            سامانه جامع مدیریت آموزشی ZeroLMS • وضعیت سیستم پایدار و آماده به کار است.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($user_role === 'admin'): ?>
                            <a href="./schedule_builder.php" class="hero-action-btn">
                                <i class="fas fa-calendar-alt"></i>
                                <span>برنامه‌ریزی هوشمند هفتگی</span>
                            </a>
                        <?php endif; ?>
                        <a href="./ai" class="hero-action-btn-secondary">
                            <i class="fas fa-robot"></i>
                            <span>دستیار هوش مصنوعی</span>
                        </a>
                        <button class="hero-action-btn-secondary" onclick="document.getElementById('showClasses').click();">
                            <i class="fas fa-chalkboard"></i>
                            <span>مشاهده کلاس‌ها</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- =======================================================
                 3.1. ADMIN VIEW
            ======================================================= -->
            <?php if ($user_role === 'admin'): ?>
                <?php
                $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
                $total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn() ?: 0;
                $total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn() ?: 0;
                $total_classes = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn() ?: 0;
                $total_homeworks = $pdo->query("SELECT COUNT(*) FROM homeworks")->fetchColumn() ?: 0;
                $total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn() ?: 0;
                ?>

                <!-- Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">کل کاربران سامانه</div>
                                <div class="stat-widget-value"><?php echo number_format($total_users); ?></div>
                                <div class="stat-widget-trend">
                                    <span><?php echo $total_students; ?> دانش‌آموز • <?php echo $total_teachers; ?> معلم</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">کلاس‌های فعال</div>
                                <div class="stat-widget-value"><?php echo number_format($total_classes); ?></div>
                                <div class="stat-widget-trend">
                                    <span>کلاس‌های ثبت‌شده</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">تکالیف تعریف‌شده</div>
                                <div class="stat-widget-value"><?php echo number_format($total_homeworks); ?></div>
                                <div class="stat-widget-trend">
                                    <span>تکالیف کلاسی</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">آزمون‌های آنلاین</div>
                                <div class="stat-widget-value"><?php echo number_format($total_exams); ?></div>
                                <div class="stat-widget-trend">
                                    <span>آزمون‌های فعال و پایان‌یافته</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Tiles -->
                <div class="mb-4">
                    <h5 class="fw-bold text-white mb-3">
                        <i class="fas fa-th-large me-2 text-primary"></i>
                        میز کار و دسترسی سریع
                    </h5>

                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <a href="./schedule_builder.php" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">برنامه‌ریزی هوشمند هفتگی (ZeroSchedule)</h6>
                                    <p class="feature-tile-desc">الگوریتم حل مسائل قیددار (CSP) برای چینش برنامه درسی بدون تداخل دبیر و کلاس</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>ورود به ماژول</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <a href="./ai" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">دستیار هوش مصنوعی (AI Tutor)</h6>
                                    <p class="feature-tile-desc">پاسخ‌گویی به پرسش‌های درسی، طرح سوالات مفهومی و رفع اشکال تعاملی</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>گفتگو با هوش مصنوعی</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <a href="./attendance/" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">سامانه حضور و غیاب هوشمند</h6>
                                    <p class="feature-tile-desc">ثبت سریع ورود و خروج، گزارش‌گیری آماری غیبت‌ها و اطلاع‌رسانی</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>مشاهده حضور و غیاب</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <a href="./reportcard/" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">کارنامه و نمرات تحلیلی</h6>
                                    <p class="feature-tile-desc">ثبت و تحلیل نمرات کلاسی، رصد پیشرفت تحصیلی و صدور کارنامه</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>ورود به کارنامه</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <a href="./exams/" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-pen-alt"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">بانک آزمون‌های آنلاین</h6>
                                    <p class="feature-tile-desc">طراحی و برگزاری آزمون‌های تستی و تشریحی همراه با تصحیح خودکار</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>مدیریت آزمون‌ها</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <a href="./gamification/" class="feature-tile">
                                <div class="feature-tile-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div>
                                    <h6 class="feature-tile-title">امتیازات و نشان‌های افتخار</h6>
                                    <p class="feature-tile-desc">باشگاه دانش‌آموزی، اعطای مدال‌های مهارتی و جدول رتبه‌بندی کلاسی</p>
                                </div>
                                <div class="feature-tile-arrow">
                                    <span>جدول امتیازات</span>
                                    <i class="fas fa-arrow-left"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card p-4 h-100">
                            <h6 class="fw-bold text-white mb-3">
                                <i class="fas fa-chart-pie me-2 text-primary"></i> تفکیک کاربران سامانه
                            </h6>
                            <div style="height: 250px; position: relative;">
                                <canvas id="adminUsersChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card p-4 h-100">
                            <h6 class="fw-bold text-white mb-3">
                                <i class="fas fa-chart-bar me-2 text-primary"></i> آمار محتوا و کلاس‌ها
                            </h6>
                            <div style="height: 250px; position: relative;">
                                <canvas id="adminActivityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const ctxUsers = document.getElementById('adminUsersChart')?.getContext('2d');
                        if (ctxUsers) {
                            new Chart(ctxUsers, {
                                type: 'doughnut',
                                data: {
                                    labels: ['دانش‌آموزان', 'دبیران', 'مدیران'],
                                    datasets: [{
                                        data: [
                                            <?php echo (int)$total_students; ?>, 
                                            <?php echo (int)$total_teachers; ?>, 
                                            <?php echo max(1, (int)$total_users - ($total_students + $total_teachers)); ?>
                                        ],
                                        backgroundColor: ['#2563eb', '#7c3aed', '#059669'],
                                        borderWidth: 2,
                                        borderColor: '#1e293b'
                                    }]
                                },
                                options: { 
                                    plugins: { 
                                        legend: { 
                                            position: 'bottom',
                                            labels: { color: '#f8fafc', font: { family: 'Vazirmatn', size: 12 }, padding: 16 } 
                                        } 
                                    }, 
                                    maintainAspectRatio: false 
                                }
                            });
                        }

                        const ctxAct = document.getElementById('adminActivityChart')?.getContext('2d');
                        if (ctxAct) {
                            new Chart(ctxAct, {
                                type: 'bar',
                                data: {
                                    labels: ['کلاس‌ها', 'تکالیف', 'آزمون‌ها'],
                                    datasets: [{
                                        label: 'تعداد',
                                        data: [
                                            <?php echo (int)$total_classes; ?>, 
                                            <?php echo (int)$total_homeworks; ?>, 
                                            <?php echo (int)$total_exams; ?>
                                        ],
                                        backgroundColor: ['#2563eb', '#059669', '#d97706'],
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    plugins: { legend: { display: false } },
                                    scales: { 
                                        y: { ticks: { color: '#cbd5e1', font: { family: 'Vazirmatn' } }, grid: { color: '#334155' } }, 
                                        x: { ticks: { color: '#f8fafc', font: { family: 'Vazirmatn' } }, grid: { display: false } } 
                                    },
                                    maintainAspectRatio: false
                                }
                            });
                        }
                    });
                </script>

            <!-- =======================================================
                 3.2. TEACHER VIEW
            ======================================================= -->
            <?php elseif ($user_role === 'teacher'): ?>
                <?php
                $stmt = $pdo->prepare("
                    SELECT cc.id, cc.course_name, c.name AS class_name
                    FROM classcourses cc
                    JOIN classes c ON cc.class_id = c.id
                    JOIN classcourseteachers cct ON cc.id = cct.class_course_id
                    WHERE cct.teacher_id = :teacher_id
                    ORDER BY c.name, cc.course_name ASC
                ");
                $stmt->execute(['teacher_id' => $_SESSION['user_id']]);
                $teacher_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $my_hw_count = $pdo->prepare("SELECT COUNT(*) FROM homeworks WHERE teacher_id = ?");
                $my_hw_count->execute([$_SESSION['user_id']]);
                $hw_total = $my_hw_count->fetchColumn() ?: 0;

                $my_ex_count = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE teacher_id = ?");
                $my_ex_count->execute([$_SESSION['user_id']]);
                $ex_total = $my_ex_count->fetchColumn() ?: 0;
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">دروس تخصیص‌یافته به شما</div>
                                <div class="stat-widget-value"><?php echo count($teacher_courses); ?></div>
                                <div class="stat-widget-trend"><span>فعال در ترم جاری</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">تکالیف تعریف‌شده</div>
                                <div class="stat-widget-value"><?php echo $hw_total; ?></div>
                                <div class="stat-widget-trend"><span>در انتظار بررسی پاسخ‌ها</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">آزمون‌های تعریف‌شده</div>
                                <div class="stat-widget-value"><?php echo $ex_total; ?></div>
                                <div class="stat-widget-trend"><span>آزمون‌های فعال</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">
                        <i class="fas fa-chalkboard me-2 text-primary"></i> کلاس‌های تدریس شما
                    </h5>
                    <div class="row g-3">
                        <?php if ($teacher_courses): ?>
                            <?php foreach ($teacher_courses as $tc): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card p-3 h-100" style="background-color: #0f172a !important;">
                                        <h6 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($tc['course_name']); ?></h6>
                                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($tc['class_name']); ?></p>
                                        <a href="classes/index.php?course_id=<?php echo $tc['id']; ?>" class="btn btn-sm btn-primary w-100 mt-auto">
                                            ورود به کلاس مجازی
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-4 text-muted">هنوز درسی به حساب شما متصل نشده است.</div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- =======================================================
                 3.3. STUDENT VIEW
            ======================================================= -->
            <?php elseif ($user_role === 'student'): ?>
                <?php
                $stmt = $pdo->prepare("SELECT c.id, c.name FROM classes c WHERE c.id = (SELECT class_id FROM users WHERE id = :student_id)");
                $stmt->execute(['student_id' => $_SESSION['user_id']]);
                $student_class = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $pdo->prepare("SELECT points FROM user_points WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $my_points = $stmt->fetchColumn() ?: 0;

                $notes = [];
                try {
                    $stmt = $pdo->prepare("
                        SELECT n.title, n.file_path, n.created_at 
                        FROM notes n 
                        JOIN classcourses cc ON n.class_course_id = cc.id 
                        JOIN users u ON u.class_id = cc.class_id 
                        WHERE u.id = :student_id 
                        ORDER BY n.created_at DESC LIMIT 6
                    ");
                    $stmt->execute(['student_id' => $_SESSION['user_id']]);
                    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {}
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">کلاس تحصیلی شما</div>
                                <div class="stat-widget-value fs-4 text-white">
                                    <?php echo $student_class ? htmlspecialchars($student_class['name']) : 'کلاس ثبت نشده'; ?>
                                </div>
                                <div class="stat-widget-trend"><span>سال تحصیلی جاری</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="stat-widget-card">
                            <div class="stat-icon-wrapper">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-widget-info">
                                <div class="stat-widget-label">مجموع امتیازات باشگاه دانش‌آموزی</div>
                                <div class="stat-widget-value text-white">
                                    <?php echo number_format($my_points); ?> <span class="fs-6 text-muted">امتیاز</span>
                                </div>
                                <div class="stat-widget-trend">
                                    <a href="./gamification/" style="color: var(--primary-light);">مشاهده جدول رتبه‌بندی &larr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Handouts -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">
                        <i class="fas fa-file-download me-2 text-primary"></i> جزوات و منابع درسی
                    </h5>
                    <div class="row g-3">
                        <?php if ($notes): ?>
                            <?php foreach ($notes as $note): ?>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 d-flex justify-content-between align-items-center" style="background-color: #0f172a; border: 1px solid var(--border-color);">
                                        <div>
                                            <strong class="text-white d-block mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($note['title']); ?></strong>
                                            <small class="text-muted" style="font-size: 0.78rem;">ثبت: <?php echo htmlspecialchars($note['created_at']); ?></small>
                                        </div>
                                        <a href="../<?php echo htmlspecialchars($note['file_path']); ?>" download class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i> دانلود
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-4 text-muted">جزوه‌ای برای کلاس شما ثبت نشده است.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- ====================================================================
         4. CLASSES SECTION
    ==================================================================== -->
    <div id="classesSection" class="main-content dashboard-section" style="display:none;">
        <div class="container-fluid p-0">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold text-white mb-1"><i class="fas fa-chalkboard text-primary me-2"></i> کلاس‌ها و دروس مجازی</h3>
                    <p class="text-muted m-0">لیست دوره‌ها و تالارهای درس</p>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('showMainContent').click();">
                    <i class="fas fa-arrow-right me-1"></i> بازگشت
                </button>
            </div>

            <div class="row g-3">
                <?php
                try {
                    if ($user_role === 'admin') {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM classcourses cc
                            JOIN classes c ON cc.class_id = c.id
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute();
                    } elseif ($user_role === 'teacher') {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM classcourses cc
                            JOIN classes c ON cc.class_id = c.id
                            JOIN classcourseteachers cct ON cc.id = cct.class_course_id
                            WHERE cct.teacher_id = :user_id
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM classcourses cc
                            JOIN classes c ON cc.class_id = c.id
                            WHERE c.id = (SELECT class_id FROM users WHERE id = :user_id)
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    }
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($courses) {
                        foreach ($courses as $course) {
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card p-3 h-100">
                                    <h6 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($course['class_name']); ?></p>
                                    <a href="classes/index.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary w-100 mt-auto">
                                        ورود به کلاس
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center py-5 text-muted">درسی یافت نشد.</div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12 alert alert-danger">خطا در دریافت لیست دروس: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         5. GALLERY SECTION
    ==================================================================== -->
    <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
        <div id="gallerySection" class="main-content dashboard-section" style="display:none;">
            <div class="container-fluid p-0">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-white mb-1"><i class="fas fa-images text-primary me-2"></i> مدیریت گالری تصاویر</h3>
                        <p class="text-muted m-0">بارگذاری و ویرایش تصاویر مدرسه</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('showMainContent').click();">
                        <i class="fas fa-arrow-right me-1"></i> بازگشت
                    </button>
                </div>

                <div class="card p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3">افزودن عکس جدید</h5>
                    <form id="galleryForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="galleryTitle" class="form-label">عنوان تصویر</label>
                                <input type="text" class="form-control" id="galleryTitle" name="title" placeholder="عنوان تصویر" required>
                            </div>
                            <div class="col-md-6">
                                <label for="galleryImage" class="form-label">انتخاب فایل عکس</label>
                                <input type="file" class="form-control" id="galleryImage" name="image" accept="image/*" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4">بارگذاری</button>
                            </div>
                        </div>
                    </form>
                    <div id="galleryMessage" class="mt-3"></div>
                </div>

                <div class="card p-4">
                    <h5 class="fw-bold text-white mb-3">تصاویر ثبت‌شده</h5>
                    <div class="gallery-list">
                        <ul class="list-group p-0" style="list-style: none;"></ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====================================================================
         6. AUDIT LOGS SECTION
    ==================================================================== -->
    <?php if ($user_role === 'admin'): ?>
        <div id="logsSection" class="main-content dashboard-section" style="display:none;">
            <div class="container-fluid p-0">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="fw-bold text-white mb-1"><i class="fas fa-shield-alt text-primary me-2"></i> لاگ‌های امنیتی سیستم</h3>
                        <p class="text-muted m-0">ثبت تمامی اقدامات و ورودهای کاربران</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('showMainContent').click();">
                        <i class="fas fa-arrow-right me-1"></i> بازگشت
                    </button>
                </div>

                <div class="card p-4">
                    <div id="logsTable"></div>
                    <nav id="logsPagination" class="mt-3"></nav>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====================================================================
         7. SETTINGS SECTION
    ==================================================================== -->
    <div id="settingsSection" class="main-content dashboard-section" style="display:none;">
        <div class="container-fluid p-0">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold text-white mb-1"><i class="fas fa-sliders-h text-primary me-2"></i> تنظیمات حساب کاربری</h3>
                    <p class="text-muted m-0">تغییر رمز عبور و تم رنگی</p>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('showMainContent').click();">
                    <i class="fas fa-arrow-right me-1"></i> بازگشت
                </button>
            </div>

            <!-- Theme Customizer -->
            <div class="card p-4 mb-4">
                <h5 class="fw-bold text-white mb-2">انتخاب تم رنگی داشبورد</h5>
                <p class="text-muted small mb-3">رنگ مورد علاقه خود را انتخاب کنید:</p>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm text-white px-3 py-2" onclick="changeTheme('default')" style="background-color: #2563eb; border-radius: 8px;">آبی پیش‌فرض</button>
                    <button class="btn btn-sm text-white px-3 py-2" onclick="changeTheme('emerald')" style="background-color: #059669; border-radius: 8px;">زمردی</button>
                    <button class="btn btn-sm text-white px-3 py-2" onclick="changeTheme('purple')" style="background-color: #7c3aed; border-radius: 8px;">بنفش</button>
                    <button class="btn btn-sm text-white px-3 py-2" onclick="changeTheme('amber')" style="background-color: #d97706; border-radius: 8px;">طلایی</button>
                    <button class="btn btn-sm text-white px-3 py-2" onclick="changeTheme('rose')" style="background-color: #e11d48; border-radius: 8px;">سرخ</button>
                </div>
            </div>

            <!-- Extension Token -->
            <div class="card p-4 mb-4">
                <h5 class="fw-bold text-white mb-2">توکن افزونه مرورگر معلم</h5>
                <p class="text-muted small mb-3">این کد را در اکستنشن کروم وارد نمایید:</p>
                <div class="input-group mb-2">
                    <input type="text" class="form-control font-monospace" value="<?php echo htmlspecialchars($extensionToken); ?>" readonly id="extensionToken" style="direction: ltr; text-align: left !important;">
                    <button class="btn btn-outline-primary" type="button" onclick="copyToken()">کپی توکن</button>
                    <button class="btn btn-outline-danger ms-2" type="button" onclick="regenerateToken()">تولید مجدد</button>
                </div>
            </div>

            <!-- Password Form -->
            <div class="card p-4">
                <h5 class="fw-bold text-white mb-3">تغییر کلمه عبور</h5>
                <div id="settingsMessage"></div>
                <form id="settingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="currentPassword" class="form-label">رمز عبور فعلی</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="newPassword" class="form-label">رمز عبور جدید</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <div class="col-md-4">
                            <label for="confirmPassword" class="form-label">تکرار رمز عبور جدید</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="newUsername" class="form-label">نام کاربری جدید (اختیاری)</label>
                            <input type="text" class="form-control" id="newUsername" name="new_username" placeholder="<?php echo htmlspecialchars($username); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="usernamePassword" class="form-label">تایید رمز عبور فعلی</label>
                            <input type="password" class="form-control" id="usernamePassword" name="username_password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">ذخیره تغییرات</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Gallery Modal -->
    <div class="modal fade" id="editGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-white">ویرایش عکس</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editGalleryForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="id" id="editGalleryId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editGalleryTitle" class="form-label">عنوان تصویر</label>
                            <input type="text" class="form-control" id="editGalleryTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="editGalleryImage" class="form-label">عکس جایگزین (اختیاری)</label>
                            <input type="file" class="form-control" id="editGalleryImage" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        ZeroLMS • طراحی و پیاده‌سازی توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dashboard Scripts -->
    <script>
        // Smooth preloader dismiss
        window.addEventListener('DOMContentLoaded', () => {
            const loader = document.getElementById('loader');
            setTimeout(() => {
                if (loader) {
                    loader.classList.add('hidden');
                    setTimeout(() => { loader.style.display = 'none'; }, 500);
                }
            }, 850);
        });

        // Shamsi Date & Clock
        (function initDateTime() {
            try {
                const dateEl = document.getElementById('shamsiDateText');
                if (dateEl && typeof Intl !== 'undefined') {
                    const faDate = new Intl.DateTimeFormat('fa-IR', {
                        dateStyle: 'full',
                        timeZone: 'Asia/Tehran'
                    }).format(new Date());
                    dateEl.textContent = faDate;
                }
            } catch(e) {}

            function updateTime() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const clockEl = document.getElementById('topbarClock');
                if (clockEl) clockEl.textContent = `${hours}:${minutes}`;
                if (document.getElementById('hours')) document.getElementById('hours').textContent = hours;
                if (document.getElementById('minutes')) document.getElementById('minutes').textContent = minutes;
            }
            setInterval(updateTime, 1000);
            updateTime();
        })();

        // Theme Switcher
        function changeTheme(themeName) {
            if (themeName === 'default') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.removeItem('zero_lms_theme');
            } else {
                document.documentElement.setAttribute('data-theme', themeName);
                localStorage.setItem('zero_lms_theme', themeName);
            }
        }
        (function loadSavedTheme() {
            const saved = localStorage.getItem('zero_lms_theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();

        // Navigation
        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(sec => {
                sec.style.display = (sec.id === sectionId) ? 'block' : 'none';
            });
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            if (window.innerWidth < 992) {
                document.getElementById('sidebar')?.classList.remove('show');
            }
        }

        document.getElementById('showMainContent')?.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('active');
            showSection('mainContent');
        });
        document.getElementById('showClasses')?.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('active');
            showSection('classesSection');
        });
        document.getElementById('showSettings')?.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('active');
            showSection('settingsSection');
            const msg = document.getElementById('settingsMessage');
            if (msg) msg.innerHTML = '';
        });
        document.getElementById('showGallery')?.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('active');
            showSection('gallerySection');
            loadGallery();
        });
        document.getElementById('showLogs')?.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('active');
            showSection('logsSection');
            loadLogs(1);
        });

        // Mobile Hamburger
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        if (hamburger && sidebar) {
            hamburger.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (window.innerWidth < 992 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }

        // Apple Watch Modal
        document.querySelectorAll('.clock-icon').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelector('.watch')?.classList.toggle('active');
            });
        });
        document.querySelector('.watch')?.addEventListener('click', function() {
            this.classList.remove('active');
        });

        // Copy Token Shortcut
        function copyExtensionQuickToken() {
            const token = "<?php echo htmlspecialchars($extensionToken); ?>";
            navigator.clipboard.writeText(token).then(() => {
                alert('توکن افزونه کپی شد: \n' + token);
            }).catch(() => {
                prompt('توکن افزونه:', token);
            });
        }

        function copyToken() {
            const tokenInput = document.getElementById('extensionToken');
            if (tokenInput) {
                tokenInput.select();
                document.execCommand('copy');
                alert('توکن کپی شد.');
            }
        }

        function regenerateToken() {
            if (!confirm('آیا از بازتولید توکن مطمئن هستید؟')) return;
            fetch('regenerate_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=<?php echo $csrf_token; ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('extensionToken').value = data.new_token;
                    alert('توکن جدید تولید شد.');
                }
            });
        }

        // Settings Form Submission
        document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('update_settings.php', {
                    method: 'POST',
                    body: formData,
                    cache: 'no-cache'
                });
                const result = await response.json();
                const msg = document.getElementById('settingsMessage');
                if (msg) {
                    msg.innerHTML = `<div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show">
                        ${result.success ? result.message : 'خطا: ' + (result.error || 'نامشخص')}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                }
                if (result.success) e.target.reset();
            } catch (err) {
                const msg = document.getElementById('settingsMessage');
                if (msg) msg.innerHTML = `<div class="alert alert-danger">خطا در ارتباط با سرور: ${err.message}</div>`;
            }
        });

        // Gallery Functions
        async function loadGallery() {
            try {
                const response = await fetch('fetch_gallery.php', { cache: 'no-cache' });
                if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                const data = await response.json();
                let list = document.querySelector('.gallery-list ul');
                if (!list) return;

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text || '';
                    return div.innerHTML;
                }

                list.innerHTML = '';
                if (!data.images || data.images.length === 0) {
                    list.innerHTML = '<li class="p-4 text-center text-muted">تصویری ثبت نشده است.</li>';
                } else {
                    data.images.forEach(image => {
                        const li = document.createElement('li');
                        li.className = 'p-3 mb-2 rounded-3 d-flex align-items-center justify-content-between';
                        li.style.backgroundColor = '#0f172a';
                        li.style.border = '1px solid var(--border-color)';
                        const safeTitle = escapeHtml(image.title || 'بدون عنوان');
                        const safeAuthor = escapeHtml(image.uploaded_by || 'ناشناس');
                        const safeDate = escapeHtml(image.created_at || '');
                        const safePath = encodeURI(image.image_path || '');
                        li.innerHTML = `
                            <div class="d-flex align-items-center gap-3">
                                <img src="../${safePath}" alt="${safeTitle}" style="width: 70px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div>
                                    <strong class="text-white d-block">${safeTitle}</strong>
                                    <small class="text-muted">${safeAuthor} • ${safeDate}</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning btn-sm edit-gallery-btn" data-id="${parseInt(image.id)}" data-title="${safeTitle}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-gallery-btn" data-id="${parseInt(image.id)}" data-path="${safePath}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                        list.appendChild(li);
                    });

                    document.querySelectorAll('.edit-gallery-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            document.getElementById('editGalleryId').value = btn.getAttribute('data-id');
                            document.getElementById('editGalleryTitle').value = btn.getAttribute('data-title');
                            new bootstrap.Modal(document.getElementById('editGalleryModal')).show();
                        });
                    });

                    document.querySelectorAll('.delete-gallery-btn').forEach(btn => {
                        btn.addEventListener('click', async () => {
                            if (!confirm('آیا از حذف این تصویر مطمئن هستید؟')) return;
                            const fd = new FormData();
                            fd.append('action', 'delete');
                            fd.append('id', btn.getAttribute('data-id'));
                            fd.append('image_path', btn.getAttribute('data-path'));
                            fd.append('csrf_token', '<?php echo $csrf_token; ?>');
                            const res = await fetch('gallery.php', { method: 'POST', body: fd });
                            const r = await res.json();
                            if (r.success) loadGallery();
                        });
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById('galleryForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'upload');
            const res = await fetch('gallery.php', { method: 'POST', body: fd });
            const r = await res.json();
            const msg = document.getElementById('galleryMessage');
            if (msg) {
                msg.innerHTML = `<div class="alert alert-${r.success ? 'success' : 'danger'} alert-dismissible fade show">
                    ${r.success ? r.message : 'خطا: ' + r.error}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            }
            if (r.success) {
                e.target.reset();
                loadGallery();
            }
        });

        document.getElementById('editGalleryForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'edit');
            const res = await fetch('gallery.php', { method: 'POST', body: fd });
            const r = await res.json();
            if (r.success) {
                bootstrap.Modal.getInstance(document.getElementById('editGalleryModal')).hide();
                loadGallery();
            }
        });

        // Logs Functions
        async function loadLogs(page = 1) {
            try {
                const response = await fetch(`fetch_logs.php?page=${page}`, { cache: 'no-cache' });
                if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                const data = await response.json();
                let html = `
                    <div class="table-responsive">
                        <table class="table text-center align-middle m-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>کاربر</th>
                                    <th>عملیات</th>
                                    <th>بخش هدف</th>
                                    <th>شناسه هدف</th>
                                    <th>تاریخ و زمان</th>
                                </tr>
                            </thead>
                            <tbody>`;
                if (!data.logs || data.logs.length === 0) {
                    html += `<tr><td colspan="6" class="py-4 text-muted">رویدادی یافت نشد.</td></tr>`;
                } else {
                    data.logs.forEach(log => {
                        html += `
                            <tr>
                                <td>${log.id}</td>
                                <td><span class="fw-bold text-white">${log.username || 'سیستم'}</span></td>
                                <td><span class="badge bg-secondary text-white px-2 py-1">${log.action}</span></td>
                                <td><span class="badge bg-dark border border-secondary text-white px-2 py-1">${log.target_type}</span></td>
                                <td>${log.target_id || '-'}</td>
                                <td class="text-sub small">${log.created_at}</td>
                            </tr>`;
                    });
                }
                html += `</tbody></table></div>`;
                
                const tableEl = document.getElementById('logsTable');
                if (tableEl) tableEl.innerHTML = html;

                const totalPages = data.totalPages || 1;
                const currentPage = data.page || 1;
                let pagHtml = '<ul class="pagination justify-content-center flex-wrap mt-4 gap-1">';
                if (totalPages > 1) {
                    if (currentPage > 1) {
                        pagHtml += `<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="#" onclick="loadLogs(${currentPage - 1});return false;">قبلی</a></li>`;
                    }
                    let start = Math.max(1, currentPage - 2);
                    let end = Math.min(totalPages, currentPage + 2);
                    for (let p = start; p <= end; p++) {
                        const active = (p === currentPage);
                        pagHtml += `<li class="page-item ${active ? 'active' : ''}">
                            <a class="page-link ${active ? 'bg-primary text-white border-primary' : 'bg-dark text-white border-secondary'}" href="#" onclick="loadLogs(${p});return false;">${p}</a>
                        </li>`;
                    }
                    if (currentPage < totalPages) {
                        pagHtml += `<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="#" onclick="loadLogs(${currentPage + 1});return false;">بعدی</a></li>`;
                    }
                }
                pagHtml += '</ul>';
                const pagEl = document.getElementById('logsPagination');
                if (pagEl) pagEl.innerHTML = pagHtml;
            } catch (err) {
                const tableEl = document.getElementById('logsTable');
                if (tableEl) tableEl.innerHTML = `<div class="alert alert-danger">خطا در دریافت لاگ‌ها: ${err.message}</div>`;
            }
        }

        // ====================================================================
        // FEEDBACK POPUP CONTROLLER
        // Appears a few seconds after login, disappears permanently after submit until next login
        // ====================================================================
        (function initFeedbackModal() {
            const feedbackPopup = document.getElementById('feedbackPopup');
            if (!feedbackPopup) return;

            const sessionId = "<?php echo session_id(); ?>";
            const storageKey = 'zerolms_feedback_submitted_' + sessionId;
            const dismissKey = 'zerolms_feedback_temp_dismiss_' + sessionId;

            // Do not show if already submitted in this login session
            if (sessionStorage.getItem(storageKey) === 'true') {
                return;
            }

            // Do not show if temporarily dismissed during this specific page view
            if (sessionStorage.getItem(dismissKey) === 'true') {
                return;
            }

            // Dynamic rating star labels
            const ratingLabels = {
                '5': 'عالی! 🌟',
                '4': 'خیلی خوب 👍',
                '3': 'خوب 🙂',
                '2': 'متوسط 😐',
                '1': 'نیاز به بهبود ⚠️'
            };

            const ratingInputs = feedbackPopup.querySelectorAll('input[name="rating"]');
            const ratingDescEl = document.getElementById('ratingDesc');
            ratingInputs.forEach(input => {
                input.addEventListener('change', (e) => {
                    if (ratingDescEl && ratingLabels[e.target.value]) {
                        ratingDescEl.textContent = ratingLabels[e.target.value];
                    }
                });
            });

            // Display popup after 4 seconds of entering dashboard
            setTimeout(() => {
                if (sessionStorage.getItem(storageKey) === 'true') return;
                if (sessionStorage.getItem(dismissKey) === 'true') return;

                feedbackPopup.classList.add('visible');
                feedbackPopup.setAttribute('aria-hidden', 'false');
            }, 4000);

            // Temporary dismissal (closed by user for current pageview, but will show again on refresh if not submitted)
            function dismissFeedback() {
                feedbackPopup.classList.remove('visible');
                feedbackPopup.setAttribute('aria-hidden', 'true');
                sessionStorage.setItem(dismissKey, 'true');
            }

            const closeBtn = document.getElementById('closeFeedbackBtn');
            const laterBtn = document.getElementById('feedbackLaterBtn');
            if (closeBtn) closeBtn.addEventListener('click', dismissFeedback);
            if (laterBtn) laterBtn.addEventListener('click', dismissFeedback);

            // Handle asynchronous form submission
            const form = document.getElementById('dashboardFeedbackForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const submitBtn = document.getElementById('feedbackSubmitBtn');
                    const origBtnText = submitBtn.innerHTML;

                    const ratingVal = form.querySelector('input[name="rating"]:checked')?.value || '5';
                    const commentVal = document.getElementById('feedbackComment')?.value || '';
                    const csrfVal = form.querySelector('input[name="csrf_token"]')?.value || '';

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال ثبت...';

                    try {
                        const formData = new FormData();
                        formData.append('csrf_token', csrfVal);
                        formData.append('rating', ratingVal);
                        formData.append('comment', commentVal);

                        const response = await fetch('save_feedback.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            // Lock permanently for THIS login session
                            sessionStorage.setItem(storageKey, 'true');

                            // Show sleek success state
                            document.getElementById('feedbackFormContent')?.classList.add('d-none');
                            document.getElementById('feedbackSuccessState')?.classList.remove('d-none');

                            setTimeout(() => {
                                feedbackPopup.classList.remove('visible');
                                feedbackPopup.setAttribute('aria-hidden', 'true');
                                setTimeout(() => {
                                    feedbackPopup.remove();
                                }, 400);
                            }, 1800);
                        } else {
                            alert(data.error || 'خطا در ثبت بازخورد.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origBtnText;
                        }
                    } catch (err) {
                        console.error('Feedback error:', err);
                        alert('خطا در برقراری ارتباط با سرور.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = origBtnText;
                    }
                });
            }
        })();
    </script>

    <!-- Interactive Feedback Popup Modal Card -->
    <?php if (empty($_SESSION['feedback_submitted'])): ?>
    <div class="feedback-popup-container" id="feedbackPopup" aria-hidden="true">
        <button type="button" class="feedback-close-btn" id="closeFeedbackBtn" title="بستن" aria-label="بستن">
            <i class="fas fa-times"></i>
        </button>

        <div id="feedbackFormContent">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="feedback-icon-badge">
                    <i class="fas fa-star text-warning"></i>
                </div>
                <div>
                    <h6 class="m-0 fw-bold text-white" style="font-size: 0.95rem;">بازخورد شما درباره سامانه</h6>
                    <small style="color: #94a3b8; font-size: 0.76rem;">تجربه شما برای بهبود ZeroLMS ارزشمند است</small>
                </div>
            </div>

            <form id="dashboardFeedbackForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="feedback-rating-section my-3 text-center">
                    <div class="rating-stars-wrapper" id="ratingStars">
                        <input type="radio" id="star5" name="rating" value="5" checked>
                        <label for="star5" title="عالی (۵ ستاره)"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star4" name="rating" value="4">
                        <label for="star4" title="خیلی خوب (۴ ستاره)"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star3" name="rating" value="3">
                        <label for="star3" title="خوب (۳ ستاره)"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star2" name="rating" value="2">
                        <label for="star2" title="متوسط (۲ ستاره)"><i class="fas fa-star"></i></label>
                        
                        <input type="radio" id="star1" name="rating" value="1">
                        <label for="star1" title="ضعیف (۱ ستاره)"><i class="fas fa-star"></i></label>
                    </div>
                    <div class="rating-label-text" id="ratingDesc">عالی! 🌟</div>
                </div>

                <div class="mb-3">
                    <textarea 
                        name="comment" 
                        id="feedbackComment" 
                        rows="2" 
                        class="form-control feedback-textarea" 
                        placeholder="نظر، انتقاد یا پیشنهادی دارید؟ (اختیاری)"></textarea>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 py-2 fw-semibold" id="feedbackSubmitBtn">
                        <i class="fas fa-paper-plane me-1"></i>
                        <span>ثبت نظر و امتیاز</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-2 px-3" id="feedbackLaterBtn" style="border-color: #334155; color: #94a3b8;">
                        بعداً
                    </button>
                </div>
            </form>
        </div>

        <div id="feedbackSuccessState" class="text-center py-3 d-none">
            <div class="feedback-success-icon mb-2">
                <i class="fas fa-check-circle text-success" style="font-size: 2.2rem;"></i>
            </div>
            <h6 class="text-white fw-bold mb-1">با تشکر از شما!</h6>
            <p class="m-0" style="color: #cbd5e1; font-size: 0.82rem;">نظر و امتیاز شما با موفقیت ثبت شد.</p>
        </div>
    </div>
    <?php endif; ?>
</body>

</html>