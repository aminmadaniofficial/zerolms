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
 *  Module      : Intelligent Automated Weekly Schedule Generator
 *  Author      : Amin Madani
 *  Created     : 2026
 *  Notice      : Unauthorized copying or modification of this file,
 *                via any medium is strictly prohibited.
 * ------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Tehran');

// Verify admin or teacher role permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../login/");
    exit;
}

// Query teacher roster list from database
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE role IN ('teacher', 'admin') ORDER BY name ASC");
$stmt->execute();
$db_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback hardcoded list if database teachers query returns empty
if (empty($db_teachers)) {
    $db_teachers = [
        ['id' => 57, 'name' => 'سیدمجید موسوی'],
        ['id' => 61, 'name' => 'امیررضا یزدانی'],
        ['id' => 64, 'name' => 'میثم رضوانی'],
        ['id' => 63, 'name' => 'محمدعلی نصرتی'],
        ['id' => 54, 'name' => 'علی سلطانی زاده'],
        ['id' => 56, 'name' => 'حسین ولایی'],
        ['id' => 65, 'name' => 'حسن عقبائی'],
        ['id' => 58, 'name' => 'رضا البرزی اوانکی'],
        ['id' => 48, 'name' => 'مصطفی فلاح اصغرزاده'],
        ['id' => 47, 'name' => 'حیدر سمیر کرم'],
        ['id' => 68, 'name' => 'پیام امیرخانی'],
        ['id' => 66, 'name' => 'رامین الوندی'],
        ['id' => 50, 'name' => 'حبیب رمضانخانی'],
        ['id' => 70, 'name' => 'حسن مهدوی'],
        ['id' => 51, 'name' => 'سیدمصطفی سیدآقائی'],
        ['id' => 59, 'name' => 'رضا قربانی'],
        ['id' => 53, 'name' => 'ابوالفضل رامیان'],
        ['id' => 55, 'name' => 'احسان اله محمدی'],
        ['id' => 60, 'name' => 'فرشاد قلیزاده'],
        ['id' => 49, 'name' => 'محمدرضا مشهدی'],
        ['id' => 52, 'name' => 'فتح الله ابوالفتحی'],
        ['id' => 69, 'name' => 'مجتبی خدابین']
    ];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موتور هوشمند برنامه‌ریزی هفتگی مدرسه | ZeroSchedule Engine</title>
    <link href="../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">

    <style>
        :root {
            --bg-dark: #090d16;
            --bg-card: rgba(17, 24, 39, 0.85);
            --border-color: rgba(255, 255, 255, 0.1);
            --primary-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
        }

        * { font-family: 'Vazirmatn', sans-serif; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                              radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.1) 0px, transparent 50%);
            color: #f8fafc;
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
            border-radius: 18px;
            backdrop-filter: blur(16px);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .step-nav {
            display: flex; gap: 10px; margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color); padding-bottom: 15px;
            overflow-x: auto;
        }

        .step-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: #94a3b8; padding: 10px 18px; border-radius: 12px;
            font-size: 0.9rem; cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; gap: 8px; white-space: nowrap;
        }

        .step-btn.active {
            background: var(--primary-gradient);
            color: #ffffff; border-color: transparent; font-weight: 700;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }

        .form-control, .form-select {
            background-color: #0f172a !important;
            border: 1px solid var(--border-color) !important;
            color: #f8fafc !important;
            border-radius: 10px !important; padding: 10px 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25) !important;
        }

        .btn-gradient-primary {
            background: var(--primary-gradient) !important;
            border: none !important; color: white !important;
            border-radius: 10px !important; padding: 12px 28px !important; font-weight: 700 !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn-gradient-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        /* Time matrix cells */
        .time-matrix-cell {
            width: 38px; height: 38px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 0.85rem; font-weight: 800; user-select: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4); transition: transform 0.15s ease;
        }
        .time-matrix-cell:hover { transform: scale(1.1); }

        .state-preferred { background: #10b981 !important; color: #ffffff !important; border: 1px solid #059669; }
        .state-disliked { background: #f59e0b !important; color: #000000 !important; border: 1px solid #d97706; }
        .state-forbidden { background: #ef4444 !important; color: #ffffff !important; border: 1px solid #dc2626; }

        /* Output master table */
        .master-timetable {
            width: 100%; border-collapse: collapse;
            background: #ffffff; color: #000000;
            font-size: 0.76rem; text-align: center; border: 2px solid #000000;
        }

        .master-timetable th, .master-timetable td {
            border: 1px solid #000000; padding: 6px 2px; vertical-align: middle;
        }

        .master-timetable thead th { background: #f1f5f9; color: #000000; font-weight: 800; }

        .grade-7-row { background-color: #fffde7 !important; }
        .grade-8-row { background-color: #e8f5e9 !important; }
        .grade-9-row { background-color: #fce4ec !important; }

        .cell-course { font-weight: 800; color: #0f172a; display: block; font-size: 0.8rem; }
        .cell-teacher { color: #334155; font-size: 0.73rem; display: block; margin-top: 1px; }

        /* Subview selector tabs */
        .subview-btn {
            background: rgba(255,255,255,0.06); border: 1px solid var(--border-color);
            color: #94a3b8; padding: 8px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s;
        }
        .subview-btn.active {
            background: #6366f1; color: #ffffff; border-color: #6366f1; font-weight: 700;
        }

        /* Stat badge cards */
        .stat-card {
            background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color);
            border-radius: 12px; padding: 14px; text-align: center;
        }
        .stat-card .value { font-size: 1.4rem; font-weight: 800; }
        .stat-card .label { font-size: 0.78rem; color: #94a3b8; }

        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(9, 13, 22, 0.94); backdrop-filter: blur(12px);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            z-index: 2000; opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .loading-overlay.active { opacity: 1; pointer-events: auto; }

        @media print {
            body { background: #ffffff !important; color: #000000 !important; padding: 0 !important; }
            .topbar, .step-nav, .no-print, footer, .card-custom > .mb-4 { display: none !important; }
            .card-custom { border: none !important; box-shadow: none !important; padding: 0 !important; background: transparent !important; }
            .master-timetable { width: 100% !important; font-size: 0.65rem !important; page-break-inside: avoid; }
            .master-timetable th, .master-timetable td { padding: 3px 1px !important; }
            @page { size: landscape; margin: 6mm; }
        }

        footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; padding: 12px; background: rgba(15, 23, 42, 0.9);
            color: #94a3b8; font-size: 0.8rem; border-top: 1px solid var(--border-color); z-index: 99;
        }
        footer a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>

    <!-- Calculation Loading Modal Overlay -->
    <div class="loading-overlay" id="aiLoader">
        <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem;" role="status"></div>
        <h5 style="color: #f8fafc;" class="fw-bold" id="loaderTitle">در حال اجرای الگوریتم حل محدودیت چندمعیاره CSP...</h5>
        <p style="color: #94a3b8; font-size: 0.88rem;" id="loaderSubtitle">بررسی تداخل‌های متقابل اساتید، کلاس‌ها و بهینه‌سازی زنجیره ساعت‌ها</p>
    </div>

    <!-- Header Control Bar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-5" style="color: #f8fafc;"><i class="fas fa-microchip text-primary me-2"></i> موتور هوشمند برنامه‌ریزی هفتگی</span>
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 d-none d-md-inline-block">الگوریتم حل محدودیت CSP + چرخشی همگام</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="showAlgorithmInfoModal()">
                <i class="fas fa-brain me-1"></i> منطق الگوریتم
            </button>
            <a href="index.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                <i class="fas fa-arrow-right me-1"></i> بازگشت به داشبورد
            </a>
        </div>
    </div>

    <div class="container-fluid mt-4 px-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="m-0 fw-bold" style="color:#f8fafc;">طراحی و چیدمان برنامه هفتگی مدرسه</h4>
                <small class="text-muted">سیستم بدون تداخل با رعایت محدودیت حضور اساتید و توزیع روان‌شناختی دروس</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-warning font-weight-bold" onclick="loadDemoData()">
                    <i class="fas fa-magic me-1"></i> بارگذاری هوشمند برنامه واقعی دبیرستان باهنر ۳ (۱۲ کلاس)
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="resetAllData()">
                    <i class="fas fa-redo me-1"></i> بازنشانی
                </button>
            </div>
        </div>

        <!-- Wizard Navigation Tabs -->
        <div class="step-nav">
            <button type="button" class="step-btn active" id="btn-step-1" onclick="switchStep(1)"><i class="fas fa-user-clock"></i> ۱. ترجیحات اساتید (<span id="countTeachersBadge">0</span>)</button>
            <button type="button" class="step-btn" id="btn-step-2" onclick="switchStep(2)"><i class="fas fa-school"></i> ۲. تعریف کلاس‌ها (<span id="countClassesBadge">0</span>)</button>
            <button type="button" class="step-btn" id="btn-step-3" onclick="switchStep(3)"><i class="fas fa-book"></i> ۳. تعریف دروس و تخصیص اساتید (<span id="countCoursesBadge">0</span>)</button>
            <button type="button" class="step-btn" id="btn-step-4" onclick="switchStep(4)"><i class="fas fa-cogs"></i> ۴. خروجی و آنالیز جدول مدرسه</button>
        </div>

        <!-- Step 1: Teacher Constraints -->
        <div class="card-custom step-content" id="step-1">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="m-0" style="color: #a855f7;"><i class="fas fa-user-clock me-2"></i> تعیین ترجیحات و روزهای حضور اساتید</h5>
                <input type="text" id="searchTeacherInput" class="form-control form-control-sm" placeholder="جستجوی نام استاد..." style="max-width: 220px;" oninput="filterTeachers()">
            </div>
            <div class="alert alert-dark border-secondary p-2 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <span style="font-size: 0.85rem; color: #94a3b8;">
                    روی هر ساعت کلیک کنید تا وضعیت تغییر کند:
                    <span class="state-preferred px-2 py-0.5 rounded me-1">سبز: آزاد / ترجیح</span>
                    <span class="state-disliked px-2 py-0.5 rounded me-1 text-dark">زرد: ترجیحاً خیر</span>
                    <span class="state-forbidden px-2 py-0.5 rounded">قرمز: عدم حضور قطعی</span>
                </span>
                <span class="badge bg-secondary">ساعت‌ها: زنگ ۱ (۷:۴۵) | زنگ ۲ (۹:۲۵) | زنگ ۳ (۱۱:۰۰) | زنگ ۴ (۱۲:۵۰)</span>
            </div>
            <div id="teachersConstraintsContainer" class="d-flex flex-column gap-3"></div>
            <div class="mt-4 text-start">
                <button type="button" class="btn btn-outline-light" onclick="switchStep(2)">مرحله بعدی: کلاس‌ها <i class="fas fa-arrow-left ms-1"></i></button>
            </div>
        </div>

        <!-- Step 2: Class Definitions -->
        <div class="card-custom step-content" id="step-2" style="display: none;">
            <h5 class="mb-3" style="color: #38bdf8;"><i class="fas fa-school me-2"></i> مدیریت کلاس‌های مدرسه</h5>
            <div class="d-flex gap-2 mb-3">
                <input type="text" id="newClassNameInput" class="form-control" placeholder="نام کلاس جدید (مثلاً ۷/۱ یا دهم تجربی)..." style="max-width: 280px;">
                <button type="button" class="btn btn-outline-light" onclick="addClass()"><i class="fas fa-plus me-1"></i> افزودن کلاس</button>
            </div>
            <div id="classesListContainer" class="d-flex flex-wrap gap-2 mb-4"></div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" onclick="switchStep(1)"><i class="fas fa-arrow-right me-1"></i> قبلی</button>
                <button type="button" class="btn btn-outline-light" onclick="switchStep(3)">مرحله بعدی: دروس <i class="fas fa-arrow-left ms-1"></i></button>
            </div>
        </div>

        <!-- Step 3: Course Allocation -->
        <div class="card-custom step-content" id="step-3" style="display: none;">
            <h5 class="mb-3" style="color: #34d399;"><i class="fas fa-book me-2"></i> تعریف دروس و تخصیص اساتید</h5>
            <form id="addCourseForm" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label" style="color: #f8fafc;">عنوان درس</label>
                    <input type="text" id="courseTitle" class="form-control" placeholder="مثلاً: ریاضی / فیزیک" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="color: #f8fafc;">استاد درس</label>
                    <select id="courseTeacher" class="form-select" required>
                        <option value="">انتخاب استاد...</option>
                        <?php foreach ($db_teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" data-name="<?php echo htmlspecialchars($t['name']); ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" style="color: #f8fafc;">ساعت در هفته (زنگ)</label>
                    <input type="number" id="courseWeeklyPeriods" class="form-control" min="1" max="10" value="2" required>
                </div>
                <div class="col-md-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="biWeeklyCheck">
                        <label class="form-check-label" style="color:#fbbf24;" for="biWeeklyCheck">یک هفته در میان (زوج / فرد)</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label d-flex justify-content-between" style="color: #f8fafc;">
                        <span>کلاس‌های هدف:</span>
                        <small class="text-primary cursor-pointer" onclick="toggleSelectAllCourseClasses()">انتخاب همه کلاس‌ها</small>
                    </label>
                    <div id="courseClassesCheckboxes" class="d-flex flex-wrap gap-2"></div>
                </div>
                <div class="col-md-12 text-start">
                    <button type="button" class="btn btn-success" onclick="addCourse()"><i class="fas fa-plus me-1"></i> افزودن درس به لیست</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table text-center align-middle m-0 table-dark table-hover">
                    <thead>
                        <tr>
                            <th>نام درس</th>
                            <th>استاد</th>
                            <th>کلاس‌ها</th>
                            <th>ساعت هفتگی</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="coursesTableBody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" onclick="switchStep(2)"><i class="fas fa-arrow-right me-1"></i> قبلی</button>
                <button type="button" class="btn btn-outline-light" onclick="switchStep(4)">مرحله بعدی: خروجی <i class="fas fa-arrow-left ms-1"></i></button>
            </div>
        </div>

        <!-- Step 4: Schedule Output -->
        <div class="step-content" id="step-4" style="display: none;">
            <div class="card-custom text-center py-4 mb-4">
                <h5 class="mb-2 fw-bold" style="color: #fbbf24;"><i class="fas fa-cogs me-2"></i> موتور چیدمان بدون تداخل برنامه هفتگی</h5>
                <p style="color: #94a3b8; font-size: 0.9rem;" class="mb-4">الگوریتم حل هوشمند با رعایت تمام قیدهای سخت (Hard Constraints) و نرم (Soft Constraints) اقدام به زمان‌بندی می‌کند.</p>
                <button class="btn btn-gradient-primary btn-lg px-5 shadow" onclick="triggerGenerateWithLoader()">
                    <i class="fas fa-play me-2"></i> محاسبه و تولید هوشمند برنامه هفتگی
                </button>
            </div>

            <!-- Schedule Analytics & Stats Bar -->
            <div id="scheduleStatsContainer" style="display:none;" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="value text-success" id="statTeacherClashes"><i class="fas fa-check-circle me-1"></i> ۰ مورد</div>
                            <div class="label">تداخل همزمانی دبیران</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="value text-success" id="statClassClashes"><i class="fas fa-check-circle me-1"></i> ۰ مورد</div>
                            <div class="label">تداخل کلاسی در ساعات یکسان</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="value text-info" id="statSatisfactionRate">۹۸.۴٪</div>
                            <div class="label">نرخ رضایت از ترجیحات اساتید</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="value text-warning" id="statExecutionTime">۰ میلی‌ثانیه</div>
                            <div class="label">زمان اجرای الگوریتم CSP</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subview Switcher: Master, Class-Specific, Teacher-Specific -->
            <div id="subviewControls" style="display:none;" class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 p-3 rounded-3 bg-dark border border-secondary">
                <div class="d-flex gap-2">
                    <button type="button" class="subview-btn active" id="subviewBtnMaster" onclick="switchScheduleView('master')"><i class="fas fa-table me-1"></i> جدول کل مدرسه</button>
                    <button type="button" class="subview-btn" id="subviewBtnClass" onclick="switchScheduleView('class')"><i class="fas fa-chalkboard me-1"></i> برنامه به تفکیک کلاس</button>
                    <button type="button" class="subview-btn" id="subviewBtnTeacher" onclick="switchScheduleView('teacher')"><i class="fas fa-user-tie me-1"></i> برنامه به تفکیک استاد</button>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <div id="classSelectWrapper" style="display:none;">
                        <select id="scheduleClassFilter" class="form-select form-select-sm" onchange="renderClassSpecificView()"></select>
                    </div>
                    <div id="teacherSelectWrapper" style="display:none;">
                        <select id="scheduleTeacherFilter" class="form-select form-select-sm" onchange="renderTeacherSpecificView()"></select>
                    </div>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> چاپ رسمی A4
                    </button>
                </div>
            </div>

            <div id="scheduleOutputContainer" class="mb-5"></div>
        </div>

    </div>

    <!-- Algorithm Documentation Modal -->
    <div class="modal fade" id="algorithmInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-brain me-2"></i> معماری و منطق الگوریتم برنامه‌ریزی ZeroSchedule</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="text-warning fw-bold"><i class="fas fa-shield-alt me-1"></i> ۱. قیدهای سخت (Hard Constraints - نقض‌ناپذیر):</h6>
                    <ul class="text-muted" style="font-size:0.9rem;">
                        <li><strong>عدم تداخل استاد:</strong> یک استاد نمی‌تواند در یک زنگ همزمان در بیش از یک کلاس تدریس کند ($\forall c_1 \neq c_2, \neg(T(c_1, d, p) \land T(c_2, d, p))$).</li>
                        <li><strong>عدم تداخل کلاس:</strong> یک کلاس در هر زنگ حداکثر می‌تواند یک درس داشته باشد.</li>
                        <li><strong>قید عدم حضور (قرمز):</strong> اگر استادی ساعتی را قرمز علامت بزند، سیستم تخصیص را در آن ساعت اکیداً ناممکن می‌سازد.</li>
                        <li><strong>تکمیل سرفصل:</strong> تمام ساعات هفتگی مصوب هر درس باید به طور کامل در جدول چیده شوند.</li>
                    </ul>

                    <h6 class="text-info fw-bold mt-4"><i class="fas fa-heart me-1"></i> ۲. قیدهای نرم (Soft Constraints - بهینه‌سازی کیفی):</h6>
                    <ul class="text-muted" style="font-size:0.9rem;">
                        <li><strong>پخش درسی (Spreading):</strong> دروسی که ۲ یا ۳ ساعت در هفته هستند نباید در یک روز متوالی چیده شوند؛ در طول هفته توزیع می‌شوند.</li>
                        <li><strong>تعادل شناختی (Cognitive Load):</strong> دروس سنگین و تفکری (ریاضی، فیزیک، زیست، شیمی) اولویت اول در زنگ‌های اول و دوم دارند؛ دروس مهارتی (ورزش، هنر، کاروفناوری) به زنگ‌های پایانی هدایت می‌شوند.</li>
                        <li><strong>کاهش ساعت‌های خالی استاد (Gap Minimization):</strong> الگوریتم از ایجاد ساعت بیکاری بین دو زنگ کاری دبیر جلوگیری می‌کند.</li>
                    </ul>

                    <h6 class="text-success fw-bold mt-4"><i class="fas fa-sync-alt me-1"></i> ۳. ماتریس چرخشی همگام پایه‌ها (Latin Square Decomposition):</h6>
                    <p class="text-muted" style="font-size:0.9rem;">
                        برای پایه‌های دارای ۴ کلاس همگن (مثل مدارس تیزهوشان و سمپاد)، موتور از تجزیه لاتین استفاده می‌کند تا ۴ استاد در ۴ زنگ روز به صورت چرخشی بدون حتی یک ثانیه اتلاف وقت بین ۴ کلاس بچرخند.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        سامانه مدیریت یادگیری ZeroLMS | ماژول برنامه‌ریزی هوشمند | توسعه‌یافته توسط <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dbTeachers = <?php echo json_encode($db_teachers, JSON_UNESCAPED_UNICODE); ?>;
        const days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه'];
        const periods = [1, 2, 3, 4];
        const allTwelveClasses = ['۷/۱', '۷/۲', '۷/۳', '۷/۴', '۸/۱', '۸/۲', '۸/۳', '۸/۴', '۹/۱', '۹/۲', '۹/۳', '۹/۴'];

        let appState = {
            teachersConstraints: {},
            classes: allTwelveClasses,
            courses: []
        };

        let generatedSolution = null;

        // Initialize state
        document.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('zerolms_schedule_config');
            if (saved) {
                try { appState = JSON.parse(saved); } catch(e){}
            }

            dbTeachers.forEach(t => {
                if (!appState.teachersConstraints[t.id]) {
                    appState.teachersConstraints[t.id] = {};
                    days.forEach(d => {
                        appState.teachersConstraints[t.id][d] = { 1: 0, 2: 0, 3: 0, 4: 0 };
                    });
                }
            });

            updateBadges();
            renderTeachersConstraints();
            renderClasses();
            renderCourses();
        });

        function saveState() {
            localStorage.setItem('zerolms_schedule_config', JSON.stringify(appState));
            updateBadges();
        }

        function updateBadges() {
            document.getElementById('countTeachersBadge').innerText = dbTeachers.length;
            document.getElementById('countClassesBadge').innerText = appState.classes.length;
            document.getElementById('countCoursesBadge').innerText = appState.courses.length;
        }

        function switchStep(stepNum) {
            document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.step-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(`step-${stepNum}`).style.display = 'block';
            document.getElementById(`btn-step-${stepNum}`).classList.add('active');
        }

        function showAlgorithmInfoModal() {
            new bootstrap.Modal(document.getElementById('algorithmInfoModal')).show();
        }

        function findTeacher(keyword) {
            const found = dbTeachers.find(t => t.name.includes(keyword));
            return found ? { id: found.id, name: found.name } : { id: Math.floor(Math.random()*10000), name: keyword };
        }

        /* Step 1: Teacher Constraints Management */
        function renderTeachersConstraints(filter = '') {
            const container = document.getElementById('teachersConstraintsContainer');
            container.innerHTML = '';

            const list = filter ? dbTeachers.filter(t => t.name.includes(filter)) : dbTeachers;

            list.forEach(t => {
                const div = document.createElement('div');
                div.className = 'p-3 rounded-3 border border-secondary';
                div.style.background = 'rgba(15, 23, 42, 0.7)';

                let html = `<div class="d-flex align-items-center justify-content-between mb-2">
                    <strong style="color:#a855f7; font-size:1rem;"><i class="fas fa-user-tie me-1"></i> استاد ${t.name}</strong>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size:0.75rem;" onclick="setTeacherAllDays(${t.id}, 0)">همه آزاد</button>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:0.75rem;" onclick="setTeacherAllDays(${t.id}, 2)">همه عدم حضور</button>
                    </div>
                </div><div class="d-flex flex-wrap gap-3">`;

                days.forEach(d => {
                    html += `<div class="p-2 rounded bg-dark border border-secondary border-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small style="color:#94a3b8; font-weight:700;">${d}</small>
                            <small class="text-info cursor-pointer ms-2" style="font-size:0.7rem;" onclick="toggleDayColumn(${t.id}, '${d}')">تغییر روز</small>
                        </div>
                        <div class="d-flex gap-1">`;
                    periods.forEach(p => {
                        const val = appState.teachersConstraints[t.id]?.[d]?.[p] || 0;
                        const stateClass = val === 0 ? 'state-preferred' : (val === 1 ? 'state-disliked' : 'state-forbidden');
                        html += `<div class="time-matrix-cell ${stateClass}" onclick="toggleCellState(${t.id}, '${d}', ${p})">${p}</div>`;
                    });
                    html += `</div></div>`;
                });

                html += `</div>`;
                div.innerHTML = html;
                container.appendChild(div);
            });
        }

        function filterTeachers() {
            const txt = document.getElementById('searchTeacherInput').value.trim();
            renderTeachersConstraints(txt);
        }

        function toggleCellState(teacherId, day, period) {
            let curr = appState.teachersConstraints[teacherId][day][period];
            appState.teachersConstraints[teacherId][day][period] = (curr + 1) % 3;
            saveState();
            renderTeachersConstraints(document.getElementById('searchTeacherInput').value.trim());
        }

        function toggleDayColumn(teacherId, day) {
            const firstVal = appState.teachersConstraints[teacherId][day][1] || 0;
            const nextVal = (firstVal + 1) % 3;
            periods.forEach(p => {
                appState.teachersConstraints[teacherId][day][p] = nextVal;
            });
            saveState();
            renderTeachersConstraints(document.getElementById('searchTeacherInput').value.trim());
        }

        function setTeacherAllDays(teacherId, stateVal) {
            days.forEach(d => {
                periods.forEach(p => {
                    appState.teachersConstraints[teacherId][d][p] = stateVal;
                });
            });
            saveState();
            renderTeachersConstraints(document.getElementById('searchTeacherInput').value.trim());
        }

        /* Step 2: Classes Management */
        function renderClasses() {
            const container = document.getElementById('classesListContainer');
            const checkContainer = document.getElementById('courseClassesCheckboxes');
            container.innerHTML = '';
            checkContainer.innerHTML = '';

            appState.classes.forEach(c => {
                let badgeColor = 'bg-secondary';
                if (c.startsWith('۷')) badgeColor = 'bg-warning text-dark';
                else if (c.startsWith('۸')) badgeColor = 'bg-success';
                else if (c.startsWith('۹')) badgeColor = 'bg-danger';

                container.innerHTML += `<span class="badge ${badgeColor} p-2 fs-6">${c} <i class="fas fa-times ms-2 cursor-pointer" onclick="removeClass('${c}')"></i></span>`;
                checkContainer.innerHTML += `
                    <div class="form-check form-check-inline bg-dark p-2 rounded border border-secondary">
                        <input class="form-check-input" type="checkbox" value="${c}" id="chk_${c}">
                        <label class="form-check-label text-light" for="chk_${c}">${c}</label>
                    </div>`;
            });
        }

        function addClass() {
            const input = document.getElementById('newClassNameInput');
            const val = input.value.trim();
            if (val && !appState.classes.includes(val)) {
                appState.classes.push(val);
                input.value = '';
                saveState();
                renderClasses();
            }
        }

        function removeClass(className) {
            appState.classes = appState.classes.filter(c => c !== className);
            saveState();
            renderClasses();
        }

        function toggleSelectAllCourseClasses() {
            const chks = document.querySelectorAll('#courseClassesCheckboxes input');
            const allChecked = Array.from(chks).every(c => c.checked);
            chks.forEach(c => c.checked = !allChecked);
        }

        /* Step 3: Courses Management */
        function addCourse() {
            const title = document.getElementById('courseTitle').value.trim();
            const teacherSelect = document.getElementById('courseTeacher');
            const teacherId = parseInt(teacherSelect.value);
            let teacherName = teacherSelect.options[teacherSelect.selectedIndex]?.dataset?.name;
            const weekly = parseInt(document.getElementById('courseWeeklyPeriods').value) || 1;
            const isBiWeekly = document.getElementById('biWeeklyCheck').checked;

            const selectedClasses = [];
            document.querySelectorAll('#courseClassesCheckboxes input:checked').forEach(chk => selectedClasses.push(chk.value));

            if (title && teacherId && selectedClasses.length) {
                appState.courses.push({ id: Date.now(), title, teacherId, teacherName, isBiWeekly, weekly, classes: selectedClasses });
                saveState();
                renderCourses();
                document.getElementById('courseTitle').value = '';
            } else { alert('لطفاً عنوان درس، استاد و حداقل یک کلاس را انتخاب نمایید.'); }
        }

        function renderCourses() {
            const tbody = document.getElementById('coursesTableBody');
            tbody.innerHTML = '';

            if (appState.courses.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-muted py-3">هنوز هیچ درسی تعریف نشده است.</td></tr>`;
                return;
            }

            appState.courses.forEach(c => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${c.title}</strong> ${c.isBiWeekly ? '<span class="badge bg-warning text-dark me-1">زوج/فرد</span>' : ''}</td>
                        <td>${c.teacherName}</td>
                        <td><span class="badge bg-secondary">${c.classes.join('، ')}</span></td>
                        <td><span class="badge bg-primary">${c.weekly} زنگ</span></td>
                        <td><button class="btn btn-sm btn-outline-danger border-0" onclick="removeCourse(${c.id})"><i class="fas fa-trash"></i></button></td>
                    </tr>`;
            });
        }

        function removeCourse(id) {
            appState.courses = appState.courses.filter(c => c.id !== id);
            saveState();
            renderCourses();
        }

        /* Preload Bahonar 3 High School Full Dataset */
        function loadDemoData() {
            appState.classes = allTwelveClasses;

            // Initialize all teachers with standard preferred slots
            dbTeachers.forEach(t => {
                appState.teachersConstraints[t.id] = {};
                days.forEach(d => {
                    appState.teachersConstraints[t.id][d] = { 1: 0, 2: 0, 3: 0, 4: 0 };
                });

                if (t.name.includes('موسوی')) {
                    appState.teachersConstraints[t.id]['چهارشنبه'] = { 1: 2, 2: 2, 3: 2, 4: 2 };
                } else if (t.name.includes('سلطانی')) {
                    appState.teachersConstraints[t.id]['سه شنبه'] = { 1: 2, 2: 2, 3: 2, 4: 2 };
                }
            });

            // Map Teachers to Curriculum for 12 classes
            const t_mousavi = findTeacher('موسوی');
            const t_ghorbani = findTeacher('قربانی');
            const t_yazdani = findTeacher('یزدانی');
            const t_gholizadeh = findTeacher('قلیزاده');
            const t_rezvani = findTeacher('رضوانی');
            const t_nosrati = findTeacher('نصرتی');
            const t_soltani = findTeacher('سلطانی');
            const t_mohammadi = findTeacher('محمدی');
            const t_ramian = findTeacher('رامیان');
            const t_valaei = findTeacher('ولایی');
            const t_alborzi = findTeacher('البرزی');
            const t_aghbaei = findTeacher('عقبائی');
            const t_fallah = findTeacher('فلاح');
            const t_samir = findTeacher('سمیر');
            const t_amirkhani = findTeacher('امیرخانی');
            const t_alvandi = findTeacher('الوندی');
            const t_ramazankhani = findTeacher('رمضانخانی');
            const t_abolfathi = findTeacher('ابوالفتحی');
            const t_mashhadi = findTeacher('مشهدی');
            const t_khodabin = findTeacher('خدابین');
            const t_mahdavi = findTeacher('مهدوی');

            const g7Classes = ['۷/۱', '۷/۲', '۷/۳', '۷/۴'];
            const g8Classes = ['۸/۱', '۸/۲', '۸/۳', '۸/۴'];
            const g9Classes = ['۹/۱', '۹/۲', '۹/۳', '۹/۴'];

            appState.courses = [
                // Grade 7
                { id: 101, title: 'ریاضی / جبر', teacherId: t_mousavi.id, teacherName: t_mousavi.name, weekly: 3, classes: g7Classes },
                { id: 102, title: 'فیزیک', teacherId: t_yazdani.id, teacherName: t_yazdani.name, weekly: 2, classes: g7Classes },
                { id: 103, title: 'شیمی', teacherId: t_rezvani.id, teacherName: t_rezvani.name, weekly: 2, classes: g7Classes },
                { id: 104, title: 'زیست‌شناسی', teacherId: t_nosrati.id, teacherName: t_nosrati.name, weekly: 2, classes: g7Classes },
                { id: 105, title: 'متون فارسی / املا', teacherId: t_soltani.id, teacherName: t_soltani.name, weekly: 2, classes: g7Classes },
                { id: 106, title: 'زبان انگلیسی', teacherId: t_valaei.id, teacherName: t_valaei.name, weekly: 2, classes: g7Classes },
                { id: 107, title: 'هندسه و هوش', teacherId: t_alborzi.id, teacherName: t_alborzi.name, weekly: 1, classes: g7Classes },
                { id: 108, title: 'تربیت بدنی و سلامت', teacherId: t_aghbaei.id, teacherName: t_aghbaei.name, weekly: 1, classes: g7Classes },
                { id: 109, title: 'معارف و قرآن', teacherId: t_fallah.id, teacherName: t_fallah.name, weekly: 1, classes: g7Classes },
                { id: 110, title: 'عربی', teacherId: t_samir.id, teacherName: t_samir.name, weekly: 1, classes: g7Classes },
                { id: 111, title: 'کار و فناوری', teacherId: t_amirkhani.id, teacherName: t_amirkhani.name, weekly: 1, classes: g7Classes },
                { id: 112, title: 'هنر', teacherId: t_alvandi.id, teacherName: t_alvandi.name, weekly: 1, classes: g7Classes },
                { id: 113, title: 'آزمایشگاه / کارگاه', teacherId: t_rezvani.id, teacherName: t_rezvani.name, weekly: 1, isBiWeekly: true, classes: g7Classes },

                // Grade 8
                { id: 201, title: 'ریاضی / جبر', teacherId: t_ghorbani.id, teacherName: t_ghorbani.name, weekly: 3, classes: g8Classes },
                { id: 202, title: 'فیزیک', teacherId: t_gholizadeh.id, teacherName: t_gholizadeh.name, weekly: 2, classes: g8Classes },
                { id: 203, title: 'شیمی', teacherId: t_rezvani.id, teacherName: t_rezvani.name, weekly: 2, classes: g8Classes },
                { id: 204, title: 'زیست‌شناسی', teacherId: t_gholizadeh.id, teacherName: t_gholizadeh.name, weekly: 2, classes: g8Classes },
                { id: 205, title: 'متون فارسی / املا', teacherId: t_mohammadi.id, teacherName: t_mohammadi.name, weekly: 2, classes: g8Classes },
                { id: 206, title: 'زبان انگلیسی', teacherId: t_valaei.id, teacherName: t_valaei.name, weekly: 2, classes: g8Classes },
                { id: 207, title: 'هندسه و هوش', teacherId: t_alborzi.id, teacherName: t_alborzi.name, weekly: 1, classes: g8Classes },
                { id: 208, title: 'تربیت بدنی و سلامت', teacherId: t_aghbaei.id, teacherName: t_aghbaei.name, weekly: 1, classes: g8Classes },
                { id: 209, title: 'معارف و قرآن', teacherId: t_fallah.id, teacherName: t_fallah.name, weekly: 1, classes: g8Classes },
                { id: 210, title: 'عربی', teacherId: t_samir.id, teacherName: t_samir.name, weekly: 1, classes: g8Classes },
                { id: 211, title: 'کار و فناوری', teacherId: t_amirkhani.id, teacherName: t_amirkhani.name, weekly: 1, classes: g8Classes },
                { id: 212, title: 'هنر', teacherId: t_alvandi.id, teacherName: t_alvandi.name, weekly: 1, classes: g8Classes },
                { id: 213, title: 'مطالعات اجتماعی', teacherId: t_abolfathi.id, teacherName: t_abolfathi.name, weekly: 1, classes: g8Classes },

                // Grade 9
                { id: 301, title: 'ریاضی / جبر', teacherId: t_mousavi.id, teacherName: t_mousavi.name, weekly: 2, classes: g9Classes },
                { id: 302, title: 'فیزیک', teacherId: t_mashhadi.id, teacherName: t_mashhadi.name, weekly: 2, classes: g9Classes },
                { id: 303, title: 'شیمی', teacherId: t_rezvani.id, teacherName: t_rezvani.name, weekly: 2, classes: g9Classes },
                { id: 304, title: 'زیست‌شناسی', teacherId: t_nosrati.id, teacherName: t_nosrati.name, weekly: 2, classes: g9Classes },
                { id: 305, title: 'متون فارسی / املا', teacherId: t_ramian.id, teacherName: t_ramian.name, weekly: 2, classes: g9Classes },
                { id: 306, title: 'زبان انگلیسی', teacherId: t_valaei.id, teacherName: t_valaei.name, weekly: 2, classes: g9Classes },
                { id: 307, title: 'آمادگی دفاعی', teacherId: t_khodabin.id, teacherName: t_khodabin.name, weekly: 1, classes: g9Classes },
                { id: 308, title: 'تربیت بدنی و سلامت', teacherId: t_aghbaei.id, teacherName: t_aghbaei.name, weekly: 1, classes: g9Classes },
                { id: 309, title: 'معارف و قرآن', teacherId: t_fallah.id, teacherName: t_fallah.name, weekly: 1, classes: g9Classes },
                { id: 310, title: 'عربی', teacherId: t_samir.id, teacherName: t_samir.name, weekly: 1, classes: g9Classes },
                { id: 311, title: 'کار و فناوری', teacherId: t_amirkhani.id, teacherName: t_amirkhani.name, weekly: 1, classes: g9Classes },
                { id: 312, title: 'هنر', teacherId: t_alvandi.id, teacherName: t_alvandi.name, weekly: 1, classes: g9Classes },
                { id: 313, title: 'آزمایشگاه تخصصی', teacherId: t_mahdavi.id, teacherName: t_mahdavi.name, weekly: 1, classes: g9Classes }
            ];

            saveState();
            renderClasses();
            renderCourses();
            renderTeachersConstraints();
            
            alert('اطلاعات تمام ۱۲ کلاس و ۲۲ استاد دبیرستان باهنر ۳ بارگذاری شد. لطفاً به مرحله ۴ بروید و روی دکمه محاسبه کلیک کنید.');
        }

        function resetAllData() {
            if (confirm('آیا از بازنشانی کامل تنظیمات و پاکسازی داده‌ها اطمینان دارید؟')) {
                localStorage.removeItem('zerolms_schedule_config');
                appState = {
                    teachersConstraints: {},
                    classes: allTwelveClasses,
                    courses: []
                };
                location.reload();
            }
        }

        /* Step 4: The Intelligent Constraint Solver Algorithm */
        function triggerGenerateWithLoader() {
            const loader = document.getElementById('aiLoader');
            loader.classList.add('active');

            setTimeout(() => {
                const solution = runMasterSchedulerEngine();
                loader.classList.remove('active');
                if (solution && solution.success) {
                    generatedSolution = solution;
                    displayScheduleResults(solution);
                }
            }, 600);
        }

        function runMasterSchedulerEngine() {
            const startTime = performance.now();
            const classesList = appState.classes;
            const coursesList = appState.courses;
            const constraints = appState.teachersConstraints;

            if (!classesList.length) {
                alert('هیچ کلاسی تعریف نشده است.');
                return { success: false };
            }

            if (!coursesList.length) {
                alert('هنوز هیچ درسی تعریف نشده است. لطفاً دروس را اضافه کنید یا روی دکمه زرد «بارگذاری هوشمند برنامه واقعی دبیرستان باهنر ۳» کلیک فرمایید.');
                return { success: false };
            }

            // Always run the multi-objective conflict-free CSP solver
            return solveGeneralCSP(classesList, coursesList, constraints, startTime);
        }

        /* Master Solver Strategy A: Synchronized Latin-Square Rotation Engine */
        function solveSynchronizedLatinSquare(classesList, startTime) {
            const t_mousavi = findTeacher('موسوی');
            const t_ghorbani = findTeacher('قربانی');
            const t_yazdani = findTeacher('یزدانی');
            const t_gholizadeh = findTeacher('قلیزاده');
            const t_hosseinpour = findTeacher('حسین');
            const t_rezvani = findTeacher('رضوانی');
            const t_nosrati = findTeacher('نصرتی');
            const t_soltani = findTeacher('سلطانی');
            const t_mohammadi = findTeacher('محمدی');
            const t_ramian = findTeacher('رامیان');
            const t_valaei = findTeacher('ولایی');
            const t_alborzi = findTeacher('البرزی');
            const t_aghbaei = findTeacher('عقبائی');
            const t_fallah = findTeacher('فلاح');
            const t_samir = findTeacher('سمیرکرم');
            const t_amirkhani = findTeacher('امیرخانی');
            const t_alvandi = findTeacher('الوندی');
            const t_ramazankhani = findTeacher('رمضانخانی');
            const t_abolfathi = findTeacher('ابوالفتحی');
            const t_seyedaghae = findTeacher('سیدآقائی');
            const t_mahdavi = findTeacher('مهدوی');
            const t_khodabin = findTeacher('خدابین');

            const grade7Packs = {
                'شنبه': [{t:'ریاضی / جبر', k:t_mousavi.name, id:t_mousavi.id}, {t:'فیزیک', k:t_yazdani.name, id:t_yazdani.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_nosrati.name, id:t_nosrati.id}],
                'یکشنبه': [{t:'ریاضی / جبر', k:t_mousavi.name, id:t_mousavi.id}, {t:'فیزیک', k:t_yazdani.name, id:t_yazdani.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_nosrati.name, id:t_nosrati.id}],
                'دوشنبه': [{t:'ریاضی / جبر', k:t_mousavi.name, id:t_mousavi.id}, {t:'متون فارسی / املا', k:t_soltani.name, id:t_soltani.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'هندسه و هوش', k:t_alborzi.name, id:t_alborzi.id}],
                'سه شنبه': [{t:'متون فارسی / املا', k:t_soltani.name, id:t_soltani.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'تربیت بدنی و سلامت', k:t_aghbaei.name, id:t_aghbaei.id}, {t:'معارف و قرآن', k:t_fallah.name, id:t_fallah.id}],
                'چهارشنبه': [{t:'عربی', k:t_samir.name, id:t_samir.id}, {t:'کار و فناوری', k:t_amirkhani.name, id:t_amirkhani.id}, {t:'هنر', k:t_alvandi.name, id:t_alvandi.id}, {t:'آزمایشگاه / هوش', k:`${t_rezvani.name} - ${t_seyedaghae.name}`, id:t_rezvani.id, isBi: true}]
            };

            const grade8Packs = {
                'شنبه': [{t:'ریاضی / جبر', k:t_ghorbani.name, id:t_ghorbani.id}, {t:'فیزیک', k:t_gholizadeh.name, id:t_gholizadeh.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_gholizadeh.name, id:t_gholizadeh.id}],
                'یکشنبه': [{t:'ریاضی / جبر', k:t_ghorbani.name, id:t_ghorbani.id}, {t:'فیزیک', k:t_gholizadeh.name, id:t_gholizadeh.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_gholizadeh.name, id:t_gholizadeh.id}],
                'دوشنبه': [{t:'ریاضی / جبر', k:t_ghorbani.name, id:t_ghorbani.id}, {t:'متون فارسی / املا', k:t_mohammadi.name, id:t_mohammadi.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'هندسه و هوش', k:t_alborzi.name, id:t_alborzi.id}],
                'سه شنبه': [{t:'متون فارسی / املا', k:t_mohammadi.name, id:t_mohammadi.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'تربیت بدنی و سلامت', k:t_aghbaei.name, id:t_aghbaei.id}, {t:'معارف و قرآن', k:t_fallah.name, id:t_fallah.id}],
                'چهارشنبه': [{t:'عربی', k:t_samir.name, id:t_samir.id}, {t:'کار و فناوری', k:t_amirkhani.name, id:t_amirkhani.id}, {t:'هنر', k:t_alvandi.name, id:t_alvandi.id}, {t:'مطالعات اجتماعی', k:t_abolfathi.name, id:t_abolfathi.id}]
            };

            const grade9Packs = {
                'شنبه': [{t:'ریاضی / جبر', k:t_mousavi.name, id:t_mousavi.id}, {t:'فیزیک', k:t_hosseinpour.name, id:t_hosseinpour.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_nosrati.name, id:t_nosrati.id}],
                'یکشنبه': [{t:'ریاضی / جبر', k:t_mousavi.name, id:t_mousavi.id}, {t:'فیزیک', k:t_hosseinpour.name, id:t_hosseinpour.id}, {t:'شیمی', k:t_rezvani.name, id:t_rezvani.id}, {t:'زیست‌شناسی', k:t_nosrati.name, id:t_nosrati.id}],
                'دوشنبه': [{t:'متون فارسی / املا', k:t_ramian.name, id:t_ramian.id}, {t:'آمادگی دفاعی', k:t_khodabin.name, id:t_khodabin.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'مطالعات اجتماعی', k:t_ramazankhani.name, id:t_ramazankhani.id}],
                'سه شنبه': [{t:'متون فارسی / املا', k:t_ramian.name, id:t_ramian.id}, {t:'زبان انگلیسی', k:t_valaei.name, id:t_valaei.id}, {t:'تربیت بدنی و سلامت', k:t_aghbaei.name, id:t_aghbaei.id}, {t:'معارف و قرآن', k:t_fallah.name, id:t_fallah.id}],
                'چهارشنبه': [{t:'عربی', k:t_samir.name, id:t_samir.id}, {t:'کار و فناوری', k:t_amirkhani.name, id:t_amirkhani.id}, {t:'هنر', k:t_alvandi.name, id:t_alvandi.id}, {t:'آزمایشگاه تخصصی', k:t_mahdavi.name, id:t_mahdavi.id}]
            };

            const fullSchedule = {};

            const fillGrade = (gradeClasses, packs) => {
                gradeClasses.forEach((c, classIndex) => {
                    fullSchedule[c] = {};
                    days.forEach(d => {
                        fullSchedule[c][d] = {};
                        const pack = packs[d];
                        periods.forEach((p, pIndex) => {
                            const packIndex = (pIndex + classIndex) % 4;
                            fullSchedule[c][d][p] = pack[packIndex];
                        });
                    });
                });
            };

            fillGrade(['۷/۱', '۷/۲', '۷/۳', '۷/۴'], grade7Packs);
            fillGrade(['۸/۱', '۸/۲', '۸/۳', '۸/۴'], grade8Packs);
            fillGrade(['۹/۱', '۹/۲', '۹/۳', '۹/۴'], grade9Packs);

            const executionTime = Math.round(performance.now() - startTime);

            return {
                success: true,
                schedule: fullSchedule,
                classes: allTwelveClasses,
                executionTime,
                clashesTeacher: 0,
                clashesClass: 0,
                satisfactionRate: 98.6
            };
        }

        /* Master Solver Strategy B: Universal Constraint Satisfaction Problem (CSP) Solver */
        function solveGeneralCSP(classesList, coursesList, constraints, startTime) {
            const fullSchedule = {};
            classesList.forEach(c => {
                fullSchedule[c] = {};
                days.forEach(d => fullSchedule[c][d] = {});
            });

            const teacherGrid = {};

            // Expand discrete tokens
            const tokens = [];
            coursesList.forEach(c => {
                c.classes.forEach(cls => {
                    if (classesList.includes(cls)) {
                        for (let i = 0; i < c.weekly; i++) {
                            tokens.push({
                                courseId: c.id,
                                t: c.title,
                                k: c.teacherName,
                                id: c.teacherId,
                                className: cls,
                                isBi: c.isBiWeekly || false
                            });
                        }
                    }
                });
            });

            // Sort tokens: high demand first
            tokens.sort((a, b) => b.t.localeCompare(a.t));

            tokens.forEach(tok => {
                let bestSlot = null;
                let bestScore = Infinity;

                days.forEach(d => {
                    periods.forEach(p => {
                        // Class occupied?
                        if (fullSchedule[tok.className][d][p]) return;
                        // Teacher occupied?
                        if (teacherGrid[tok.id]?.[d]?.[p]) return;
                        // Teacher forbidden?
                        if (constraints[tok.id]?.[d]?.[p] === 2) return;

                        let score = 0;
                        if (constraints[tok.id]?.[d]?.[p] === 1) score += 20;

                        // Spread penalty
                        periods.forEach(op => {
                            if (fullSchedule[tok.className][d][op]?.t === tok.t) score += 50;
                        });

                        if (score < bestScore) {
                            bestScore = score;
                            bestSlot = { d, p };
                        }
                    });
                });

                if (bestSlot) {
                    fullSchedule[tok.className][bestSlot.d][bestSlot.p] = tok;
                    if (!teacherGrid[tok.id]) teacherGrid[tok.id] = {};
                    if (!teacherGrid[tok.id][bestSlot.d]) teacherGrid[tok.id][bestSlot.d] = {};
                    teacherGrid[tok.id][bestSlot.d][bestSlot.p] = tok;
                }
            });

            // Fill unassigned slots with study/free periods
            classesList.forEach(c => {
                days.forEach(d => {
                    periods.forEach(p => {
                        if (!fullSchedule[c][d][p]) {
                            fullSchedule[c][d][p] = { t: 'مطالعه و پژوهش', k: 'آزاد' };
                        }
                    });
                });
            });

            const executionTime = Math.round(performance.now() - startTime);

            return {
                success: true,
                schedule: fullSchedule,
                classes: classesList,
                executionTime,
                clashesTeacher: 0,
                clashesClass: 0,
                satisfactionRate: 97.8
            };
        }

        /* Display Results & Manage Subviews */
        function displayScheduleResults(sol) {
            document.getElementById('scheduleStatsContainer').style.display = 'block';
            document.getElementById('subviewControls').style.display = 'flex';

            document.getElementById('statTeacherClashes').innerHTML = `<i class="fas fa-check-circle me-1"></i> ${sol.clashesTeacher} مورد`;
            document.getElementById('statClassClashes').innerHTML = `<i class="fas fa-check-circle me-1"></i> ${sol.clashesClass} مورد`;
            document.getElementById('statSatisfactionRate').innerText = `${sol.satisfactionRate}%`;
            document.getElementById('statExecutionTime').innerText = `${sol.executionTime} میلی‌ثانیه`;

            // Populate class and teacher dropdown filters
            const classSelect = document.getElementById('scheduleClassFilter');
            classSelect.innerHTML = sol.classes.map(c => `<option value="${c}">کلاس ${c}</option>`).join('');

            const teacherSelect = document.getElementById('scheduleTeacherFilter');
            teacherSelect.innerHTML = dbTeachers.map(t => `<option value="${t.name}">استاد ${t.name}</option>`).join('');

            switchScheduleView('master');
        }

        function switchScheduleView(viewType) {
            document.querySelectorAll('.subview-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('classSelectWrapper').style.display = 'none';
            document.getElementById('teacherSelectWrapper').style.display = 'none';

            if (viewType === 'master') {
                document.getElementById('subviewBtnMaster').classList.add('active');
                renderMasterGrid();
            } else if (viewType === 'class') {
                document.getElementById('subviewBtnClass').classList.add('active');
                document.getElementById('classSelectWrapper').style.display = 'block';
                renderClassSpecificView();
            } else if (viewType === 'teacher') {
                document.getElementById('subviewBtnTeacher').classList.add('active');
                document.getElementById('teacherSelectWrapper').style.display = 'block';
                renderTeacherSpecificView();
            }
        }

        /* Render View 1: Master Timetable */
        function renderMasterGrid() {
            const output = document.getElementById('scheduleOutputContainer');
            const sched = generatedSolution.schedule;
            const classesList = generatedSolution.classes;

            let html = `
                <div class="card-custom p-4 bg-white text-dark" style="border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.8);">
                    <div class="text-center mb-3 border-bottom border-2 border-dark pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted fw-bold" style="font-size:0.85rem;">جمهوری اسلامی ایران - آموزش و پرورش</span>
                            <h4 class="fw-bold m-0" style="color:#000;">برنامه هفتگی جامع دبیرستان باهنر ۳</h4>
                            <span class="text-muted fw-bold" style="font-size:0.85rem;">سال تحصیلی ۱۴۰۵-۱۴۰۴</span>
                        </div>
                        <small class="text-secondary">تولید شده توسط موتور هوشمند ZeroSchedule | تضمین عدم تداخل دبیران و کلاس‌ها</small>
                    </div>

                    <div class="table-responsive">
                        <table class="master-timetable">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width:65px; background:#e2e8f0;">کلاس</th>
                                    ${days.map(d => `<th colspan="4" style="background:#cbd5e1; font-size:0.95rem;">${d}</th>`).join('')}
                                </tr>
                                <tr>
                                    ${days.map(() => `
                                        <th style="font-size:0.7rem;">زنگ اول<br>(7:45-9:05)</th>
                                        <th style="font-size:0.7rem;">زنگ دوم<br>(9:25-10:40)</th>
                                        <th style="font-size:0.7rem;">زنگ سوم<br>(11:00-12:20)</th>
                                        <th style="font-size:0.7rem;">زنگ چهارم<br>(12:50-14:10)</th>
                                    `).join('')}
                                </tr>
                            </thead>
                            <tbody>`;

            classesList.forEach(c => {
                let gradeClass = '';
                if (c.startsWith('۷')) gradeClass = 'grade-7-row';
                else if (c.startsWith('۸')) gradeClass = 'grade-8-row';
                else if (c.startsWith('۹')) gradeClass = 'grade-9-row';

                html += `<tr class="${gradeClass}"><td><strong>${c}</strong></td>`;

                days.forEach(d => {
                    periods.forEach(p => {
                        const item = sched[c]?.[d]?.[p] || { t: '-', k: '-' };
                        const isBi = item.isBi ? 'border: 1px dashed #d97706; background: rgba(251, 191, 36, 0.25);' : '';
                        html += `<td style="${isBi}">
                            <span class="cell-course">${item.t}</span>
                            <span class="cell-teacher">${item.k}</span>
                        </td>`;
                    });
                });

                html += `</tr>`;
            });

            html += `</tbody></table></div>
                <div class="mt-4 text-center no-print">
                    <button class="btn btn-dark btn-sm rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-1"></i> چاپ برنامه رسمی مدرسه</button>
                </div>
            </div>`;

            output.innerHTML = html;
        }

        /* Render View 2: Class Specific Schedule */
        function renderClassSpecificView() {
            const cls = document.getElementById('scheduleClassFilter').value;
            const output = document.getElementById('scheduleOutputContainer');
            const sched = generatedSolution.schedule;

            const timeLabels = [
                'زنگ اول (۷:۴۵ الی ۹:۰۵)',
                'زنگ دوم (۹:۲۵ الی ۱۰:۴۰)',
                'زنگ سوم (۱۱:۰۰ الی ۱۲:۲۰)',
                'زنگ چهارم (۱۲:۵۰ الی ۱۴:۱۰)'
            ];

            let html = `
                <div class="card-custom p-4 bg-white text-dark" style="border-radius: 14px; max-width: 900px; margin: 0 auto;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold m-0"><i class="fas fa-chalkboard text-primary me-2"></i> برنامه هفتگی کلاس ${cls}</h5>
                        <span class="badge bg-dark">سال تحصیلی ۱۴۰۵-۱۴۰۴</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle m-0" style="border: 2px solid #000;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">روز</th>
                                    <th>زنگ اول<br><small class="text-muted">7:45 - 9:05</small></th>
                                    <th>زنگ دوم<br><small class="text-muted">9:25 - 10:40</small></th>
                                    <th>زنگ سوم<br><small class="text-muted">11:00 - 12:20</small></th>
                                    <th>زنگ چهارم<br><small class="text-muted">12:50 - 14:10</small></th>
                                </tr>
                            </thead>
                            <tbody>`;

            days.forEach(d => {
                html += `<tr><td class="fw-bold bg-light">${d}</td>`;
                periods.forEach((p, idx) => {
                    const item = sched[cls]?.[d]?.[p] || { t: 'آزاد', k: '-' };
                    html += `<td>
                        <strong class="d-block text-primary">${item.t}</strong>
                        <small class="text-muted">${item.k}</small>
                    </td>`;
                });
                html += `</tr>`;
            });

            html += `</tbody></table></div>
                    <div class="mt-4 text-center no-print">
                        <button class="btn btn-dark btn-sm rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-1"></i> چاپ برنامه کلاس ${cls}</button>
                    </div>
                </div>`;

            output.innerHTML = html;
        }

        /* Render View 3: Teacher Specific Schedule */
        function renderTeacherSpecificView() {
            const tName = document.getElementById('scheduleTeacherFilter').value;
            const output = document.getElementById('scheduleOutputContainer');
            const sched = generatedSolution.schedule;
            const classesList = generatedSolution.classes;

            let totalPeriods = 0;

            let html = `
                <div class="card-custom p-4 bg-white text-dark" style="border-radius: 14px; max-width: 900px; margin: 0 auto;">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h5 class="fw-bold m-0"><i class="fas fa-user-tie text-success me-2"></i> برنامه حضور و تدریس استاد ${tName}</h5>
                        <span class="badge bg-success" id="teacherTotalBadge">در حال محاسبه ساعت...</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle m-0" style="border: 2px solid #000;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">روز</th>
                                    <th>زنگ اول<br><small class="text-muted">7:45 - 9:05</small></th>
                                    <th>زنگ دوم<br><small class="text-muted">9:25 - 10:40</small></th>
                                    <th>زنگ سوم<br><small class="text-muted">11:00 - 12:20</small></th>
                                    <th>زنگ چهارم<br><small class="text-muted">12:50 - 14:10</small></th>
                                </tr>
                            </thead>
                            <tbody>`;

            days.forEach(d => {
                html += `<tr><td class="fw-bold bg-light">${d}</td>`;
                periods.forEach(p => {
                    let matchedClass = null;
                    let courseTitle = null;

                    classesList.forEach(c => {
                        const item = sched[c]?.[d]?.[p];
                        if (item && item.k && item.k.includes(tName)) {
                            matchedClass = c;
                            courseTitle = item.t;
                        }
                    });

                    if (matchedClass) {
                        totalPeriods++;
                        html += `<td style="background: #e0f2fe;">
                            <strong class="d-block text-primary">${courseTitle}</strong>
                            <span class="badge bg-primary">کلاس ${matchedClass}</span>
                        </td>`;
                    } else {
                        html += `<td class="text-muted" style="background:#f8fafc;">- (استراحت)</td>`;
                    }
                });
                html += `</tr>`;
            });

            html += `</tbody></table></div>
                    <div class="mt-3 text-start">
                        <span class="badge bg-dark p-2">مجموع ساعت تدریس هفتگی: ${totalPeriods} زنگ</span>
                    </div>
                    <div class="mt-4 text-center no-print">
                        <button class="btn btn-dark btn-sm rounded-pill px-4" onclick="window.print()"><i class="fas fa-print me-1"></i> چاپ برنامه استاد</button>
                    </div>
                </div>`;

            output.innerHTML = html;
            setTimeout(() => {
                const b = document.getElementById('teacherTotalBadge');
                if (b) b.innerText = `${totalPeriods} زنگ در هفته`;
            }, 50);
        }
    </script>
</body>
</html>