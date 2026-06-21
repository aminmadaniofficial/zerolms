<?php
session_start();
require_once '../db.php';
require_once '../log.php';
date_default_timezone_set('Asia/Tehran');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit();
}

$user_role = $_SESSION['role'];
$username = $_SESSION['username'];

$stmt = $pdo->prepare("SELECT name FROM users WHERE username = :username LIMIT 1");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$realName = $user['name'] ?? $username;
$stmt = $pdo->prepare("SELECT extension_token FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow['extension_token']) {
    $newToken = bin2hex(random_bytes(16));
    $update = $pdo->prepare("UPDATE users SET extension_token = ? WHERE username = ?");
    $update->execute([$newToken, $username]);
    $extensionToken = $newToken;
} else {
    $extensionToken = $tokenRow['extension_token'];
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl"
    style="cursor: url(https://cdn.custom-cursor.com/db/cursor/32/Infinity_Gauntlet_Cursor.png) , default !important">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدرسه</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <link href="../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/loader.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .fa-spin-hover:hover,
        .fa-spin-hover:active {
            animation: spin 0.6s linear;
        }

        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                right: -300px;
                width: 250px;
                z-index: 9999;
                transition: right 0.4s ease;
            }

            .sidebar.show {
                right: 0 !important;
            }
        }

        #hamburger {
            position: relative;
            z-index: 10000;
           
            cursor: pointer !important;
           
            padding: 10px;
           
        }
    </style>
</head>
<script>
    async function loadGallery() {
        console.log('Loading gallery...');
        try {
            const response = await fetch('fetch_gallery.php', { cache: 'no-cache' });
            if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
            const data = await response.json();
            console.log('Gallery data:', data);
            if (!data.success) throw new Error(data.error || 'خطای ناشناخته');
            const galleryList = document.querySelector('.gallery-list .list-group');
            galleryList.innerHTML = '';
            if (data.images.length === 0) {
                galleryList.innerHTML = '<li class="list-group-item">هیچ عکسی در گالری وجود ندارد.</li>';
            } else {
                data.images.forEach(image => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex align-items-center';
                    li.innerHTML = `
                                <img src="../${image.image_path}" alt="${image.title || ''}" class="gallery-img me-3">
                                <div class="flex-grow-1" style="color:white;">
                                    <strong>${image.title || 'بدون عنوان'}</strong><br>
                                    <small>آپلود شده توسط: ${image.uploaded_by || 'ناشناس'} در ${image.created_at}</small>
                                </div>
                                <div>
                                    <button class="btn btn-warning btn-sm me-2 edit-gallery-btn" data-id="${image.id}" data-title="${image.title || ''}" data-path="${image.image_path}">ویرایش</button>
                                    <button class="btn btn-danger btn-sm delete-gallery-btn" data-id="${image.id}" data-path="${image.image_path}">حذف</button>
                                </div>`;
                    galleryList.appendChild(li);
                });
                document.querySelectorAll('.edit-gallery-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.getAttribute('data-id');
                        const title = btn.getAttribute('data-title');
                        document.getElementById('editGalleryId').value = id;
                        document.getElementById('editGalleryTitle').value = title;
                        new bootstrap.Modal(document.getElementById('editGalleryModal')).show();
                    });
                });
                document.querySelectorAll('.delete-gallery-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('آیا مطمئن هستید که می‌خواهید این عکس را حذف کنید؟')) return;
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', btn.getAttribute('data-id'));
                        formData.append('image_path', btn.getAttribute('data-path'));
                        formData.append('csrf_token', '<?php echo htmlspecialchars($csrf_token); ?>');
                        try {
                            const response = await fetch('gallery.php', {
                                method: 'POST',
                                body: formData,
                                cache: 'no-cache'
                            });
                            if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                            const result = await response.json();
                            document.getElementById('galleryMessage').innerHTML = `
                                        <div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                                            ${result.success ? result.message : 'خطا: ' + result.error}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>`;
                            if (result.success) loadGallery();
                        } catch (err) {
                            document.getElementById('galleryMessage').innerHTML = `
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            خطا در ارتباط با سرور: ${err.message}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>`;
                        }
                    });
                });
            }
        } catch (err) {
            console.error('Gallery load error:', err);
            document.getElementById('galleryMessage').innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            خطا در بارگذاری گالری: ${err.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`;
        }
    }
    async function loadLogs(page = 1) {
        try {
            const response = await fetch(`fetch_logs.php?page=${page}`, { cache: 'no-cache' });
            if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
            const data = await response.json();
            let html = `
                    <div style="margin-left:0; margin-top:20px; width:100%; margin:auto;color:#999999;">
                        <div class="table-responsive" style="max-width:900px; width:100%;margin:auto;">
                            <table class="table table-striped table-bordered text-center align-middle">
                                <thead class="table-primary">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th style="min-width:120px;">کاربر</th>
                                        <th style="min-width:100px;">عمل</th>
                                        <th style="min-width:120px;">نوع هدف</th>
                                        <th style="min-width:120px;">شناسه هدف</th>
                                        <th style="min-width:150px;">تاریخ</th>
                                    </tr>
                                </thead>
                                <tbody>`;
            if (data.logs.length === 0) {
                html += `<tr><td colspan="6">هیچ لاگی وجود ندارد</td></tr>`;
            } else {
                data.logs.forEach(log => {
                    html += `
                            <tr>
                                <td>${log.id}</td>
                                <td>${log.username || 'سیستم'}</td>
                                <td>${log.action}</td>
                                <td>${log.target_type}</td>
                                <td>${log.target_id || ''}</td>
                                <td>${log.created_at}</td>
                            </tr>`;
                });
            }
            html += `</tbody></table></div></div>`;
            document.getElementById('logsTable').innerHTML = html;

            let pagHtml = '<ul class="pagination justify-content-center mt-3">';
            if (data.page > 1) {
                pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${data.page - 1});return false;">قبلی</a></li>`;
            }
            for (let p = 1; p <= data.totalPages; p++) {
                pagHtml += `<li class="page-item ${p === data.page ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadLogs(${p});return false;">${p}</a>
                            </li>`;
            }
            if (data.page < data.totalPages) {
                pagHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${data.page + 1});return false;">بعدی</a></li>`;
            }
            pagHtml += '</ul>';
            document.getElementById('logsPagination').innerHTML = pagHtml;
        } catch (err) {
            document.getElementById('logsTable').innerHTML = `<div class="alert alert-danger">خطا در بارگذاری لاگ‌ها: ${err.message}</div>`;
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(sec => {
                sec.style.display = sec.id === sectionId ? 'block' : 'none';
            });
        }

        const mainBtn = document.getElementById('showMainContent');
        if (mainBtn) {
            mainBtn.addEventListener('click', function (e) {
                e.preventDefault();

                showSection('mainContent');
            });
        }

        const classesBtn = document.getElementById('showClasses');
        if (classesBtn) {
            classesBtn.addEventListener('click', function (e) {
                e.preventDefault();

                showSection('classesSection');
            });
        }

        const settingsBtn = document.getElementById('showSettings');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', function (e) {
                e.preventDefault();
                showSection('settingsSection');
                const msg = document.getElementById('settingsMessage');
                if (msg) msg.innerHTML = '';
            });
        }

        const logsBtn = document.getElementById('showLogs');
        if (logsBtn) {
            logsBtn.addEventListener('click', function (e) {
                e.preventDefault();
                showSection('logsSection');
                if (typeof loadLogs === "function") loadLogs(1);
            });
        }

        const galleryBtn = document.getElementById('showGallery');
        if (galleryBtn) {
            galleryBtn.addEventListener('click', function (e) {
                e.preventDefault();
                showSection('gallerySection');
                loadGallery();
            });
        }
    });
</script>
<?php date_default_timezone_set('Asia/Tehran'); ?>
<div class="watch">
    <div class="frame">
        <div class="text">
            <div id="hours"><?php echo date('H'); ?></div>
            <div id="minutes"><?php echo date('i'); ?></div>
        </div>
    </div>
</div>

<body>
    <div class="loader-overlay" id="loader">
        <div class="">
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
            <div class="loading loading02" dir="rtl" style="font-family: font-iran-normal;">
                <span>بـ</span>
                <span>ـا</span>
                <span>ر</span>
                <span>گـ</span>
                <span>ـذ</span>
                <span>ا</span>
                <span>ر</span>
                <span>ی</span>


            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loader = document.getElementById('loader');
            document.querySelectorAll('.card, .list-group-item ').forEach((item, index) => {
                item?.style.setProperty('--index', index);
            });
            setTimeout(() => {
                if (loader) loader.classList.add('hidden');
                setTimeout(() => {
                    if (loader) loader.style.display = 'none';
                    document.querySelector('.topbar')?.classList.add('animate');
                    document.querySelector('.sidebar')?.classList.add('animate');
                    document.querySelectorAll('.card, .list-group-item').forEach(item => {
                        item?.classList.add('animate');
                    });
                    document.querySelector('footer')?.classList.add('animate');
                }, 500);
            }, 2500);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <div class="sidebar" id="sidebar">
        <div class="text-center mb-4">
            <img src="../images/logo.svg" alt="لوگو" class="logo" style="max-width: 150px;">
        </div>
        <nav class="nav flex-column">
            <a href="#" class="nav-link" id="showMainContent"><i class="fas fa-home"></i> داشبورد</a>
            <?php if ($user_role === 'admin'): ?>
                <a href="./manage_users" class="nav-link"><i class="fas fa-users-cog"></i> مدیریت کاربران</a>
                <a href="./manage_classes" class="nav-link"><i class="fas fa-chalkboard-teacher"></i> مدیریت کلاس‌ها و
                    دروس</a>
                <a href="#" class="nav-link" id="showGallery"><i class="fas fa-image"></i> مدیریت گالری</a>
                <a href="#" class="nav-link" id="showLogs"><i class="fas fa-boombox"></i> مشاهده لاگ‌ها</a>
                <a href="./manage_blog" class="nav-link"><i class="fas fa-file"></i> مدیریت بلاگ و پست‌ها</a>
                <a href="./homeworks/teacher.php" class="nav-link"><i class="fas fa-sticky-note"></i>مدیریت تکالیف</a>
                <a href="./exams/" class="nav-link"><i class="fas fa-pencil"></i>مدیریت آزمون ها</a>
                <a href="./attendance/" class="nav-link"><i class="fas fa-check"></i>مدیریت حضور و غیاب</a>
                <a href="./reportcard/" class="nav-link"><i class="fas fa-map"></i>مدیریت نمرات</a>
                <a href="./gamification/" class="nav-link"><i class="fas fa-star"></i>مدیریت امتیازات و نشان ها</a>
                <a href="./forms/manage_forms.php" class="nav-link"><i class="fas fa-clipboard-list"></i> مدیریت فرم‌ها</a>
                <a href="#" class="nav-link" id="showClasses"><i class="fas fa-video"></i> کلاس‌ها و دروس</a>
            <?php elseif ($user_role === 'teacher'): ?>
                <a href="#" class="nav-link" id="showClasses"><i class="fas fa-video"></i> کلاس‌ها و دروس</a>
                <a href="./homeworks/teacher.php" class="nav-link"><i class="fas fa-sticky-note"></i>مدیریت تکالیف</a>
                <a href="./exams/" class="nav-link"><i class="fas fa-pencil"></i>مدیریت آزمون ها</a>
                <a href="./attendance/" class="nav-link"><i class="fas fa-check"></i>مدیریت حضور و غیاب</a>
                <a href="./reportcard/" class="nav-link"><i class="fas fa-map"></i>مدیریت نمرات</a>
                <a href="./manage_blog" class="nav-link"><i class="fas fa-file"></i> مدیریت بلاگ و پست‌ها</a>
                <a href="#" class="nav-link" id="showGallery"><i class="fas fa-image"></i> مدیریت گالری</a>
            <?php elseif ($user_role === 'student'): ?>
                <a href="./gamification/" class="nav-link"><i class="fas fa-star"></i>امتیازات و نشان های من</a>
                <a href="#" class="nav-link" id="showClasses"><i class="fas fa-video"></i> کلاس‌ها و دروس</a>
                <a href="./homeworks/" class="nav-link"><i class="fas fa-pencil-alt"></i>تکالیف</a>
                <a href="./exams/" class="nav-link"><i class="fas fa-pencil"></i>آزمون ها</a>
                <a href="./attendance/" class="nav-link"><i class="fas fa-check"></i>مشاهده حضور و غیاب</a>
                <a href="./reportcard/" class="nav-link"><i class="fas fa-map"></i>نمرات و کارنامه</a>
            <?php endif; ?>
            <a href="./ai" class="nav-link"><i class="fas fa-robot"></i>هوش مصنوعی</a>
            <a href="#" class="nav-link" id="showSettings"><i class="fas fa-cog"></i> تنظیمات</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
    </div>
    <div class="topbar">
        <span class="hamburger" id="hamburger"><i class="fas fa-bars fa-spin-hover"></i></span>
        <span class="clock-icon"><i class="fas fa-clock"></i></span>
        <span style="text-align:center; font-size: 1.2rem; font-weight: bold;">داشبورد مدرسه</span>
    </div>

    <div class="main-content dashboard-section" style="display: none;" id="mainContent">
        <div class="container mt-4">

            <h2 class="welcomeText">خوش آمدید، <?php echo htmlspecialchars($realName); ?></h2>
            <?php if ($user_role === 'admin'): ?>
                <p>به داشبورد مدیریت خوش آمدید. از طریق سایدبار می‌توانید بخش‌های مختلف را مدیریت کنید.</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card p-3">
                            <h5>مدیریت سیستم</h5>
                            <p>کاربران، کلاس‌ها و دروس را مدیریت کنید.</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card p-3">
                            <h5>گزارش‌ها</h5>
                            <p>لاگ‌های سیستم را بررسی کنید.</p>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_role === 'teacher'): ?>
                <h3>دروس شما</h3>
                <ul>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT cc.id, cc.course_name, c.name AS class_name
                        FROM ClassCourses cc
                        JOIN Classes c ON cc.class_id = c.id
                        JOIN ClassCourseTeachers cct ON cc.id = cct.class_course_id
                        WHERE cct.teacher_id = :teacher_id
                        ORDER BY c.name, cc.course_name ASC
                    ");
                    $stmt->execute(['teacher_id' => $_SESSION['user_id']]);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($courses) {
                        foreach ($courses as $course) {
                            echo "<li><a href='classes/index.php?course_id={$course['id']}' class='text-decoration-none'>" . htmlspecialchars($course['class_name'] . ' - ' . $course['course_name']) . "</a></li>";
                        }
                    } else {
                        echo "<li>هیچ درسی تخصیص نیافته است.</li>";
                    }
                    ?>
                </ul>
            <?php elseif ($user_role === 'student'): ?>
                <h3>کلاس شما</h3>
                <ul>
                    <?php
                    $stmt = $pdo->prepare("SELECT c.id, c.name FROM Classes c WHERE c.id = (SELECT class_id FROM users WHERE id = :student_id)");
                    $stmt->execute(['student_id' => $_SESSION['user_id']]);
                    $class = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($class) {
                        echo "<li>" . htmlspecialchars($class['name']) . "</li>";
                    } else {
                        echo "<li>شما در هیچ کلاسی ثبت‌نام نشده‌اید.</li>";
                    }
                    ?>
                </ul>
                <h3>جزوات اخیر</h3>
                <ul>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT n.title, n.file_path 
                        FROM Notes n 
                        JOIN ClassCourses cc ON n.class_course_id = cc.id 
                        JOIN users u ON u.class_id = cc.class_id 
                        WHERE u.id = :student_id 
                        ORDER BY n.created_at DESC LIMIT 5
                    ");
                    $stmt->execute(['student_id' => $_SESSION['user_id']]);
                    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($notes) {
                        foreach ($notes as $note) {
                            echo "<li><a href='../{$note['file_path']}' download>" . htmlspecialchars($note['title']) . "</a></li>";
                        }
                    } else {
                        echo "<li>جزوه‌ای یافت نشد.</li>";
                    }
                    ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div id="classesSection" class="mt-4 dashboard-section" style="display:none;">
        <div class="container mt-4">
            <h3>کلاس‌ها و دروس</h3>
            <div class="class-list">
                <ul class="list-group">
                    <?php
                    if ($user_role === 'admin') {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM ClassCourses cc
                            JOIN Classes c ON cc.class_id = c.id
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute();
                    } elseif ($user_role === 'teacher') {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM ClassCourses cc
                            JOIN Classes c ON cc.class_id = c.id
                            JOIN ClassCourseTeachers cct ON cc.id = cct.class_course_id
                            WHERE cct.teacher_id = :user_id
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT cc.id, cc.course_name, c.name AS class_name
                            FROM ClassCourses cc
                            JOIN Classes c ON cc.class_id = c.id
                            JOIN users u ON u.class_id = c.id
                            WHERE u.id = :user_id
                            ORDER BY c.name, cc.course_name ASC
                        ");
                        $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    }
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($courses) {
                        foreach ($courses as $course) {
                            echo "<li class='list-group-item'><a href='classes/index.php?course_id={$course['id']}' class='text-decoration-none'>" . htmlspecialchars($course['class_name'] . ' - ' . $course['course_name']) . "</a></li>";
                        }
                    } else {
                        echo "<li class='list-group-item'>هیچ درسی یافت نشد.</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
    <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
        <div id="gallerySection" class="mt-4 dashboard-section" style="display:none;text-align:center;">
            <div class="container mt-4">
                <h3>مدیریت گالری</h3>
                <div class="gallery-form mb-4">
                    <h5>افزودن عکس جدید</h5>
                    <form id="galleryForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-3">
                            <label for="galleryTitle" class="form-label">عنوان عکس</label>
                            <input type="text" class="form-control" id="galleryTitle" name="title">
                        </div>
                        <div class="mb-3">
                            <label for="galleryImage" class="form-label">انتخاب عکس</label>
                            <input type="file" class="form-control" id="galleryImage" name="image" accept="image/*"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary">افزودن عکس</button>
                    </form>
                    <div id="galleryMessage" class="mt-3"></div>
                </div>
                <h5>لیست عکس‌ها</h5>
                <div class="gallery-list">
                    <!-- gallery content will be loaded here via AJAX -->
                    <ul class="list-group">
                        <!-- gallery content will be loaded here via AJAX -->
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($user_role === 'admin'): ?>
        <div id="logsSection" class="mt-4 dashboard-section" style="display:none;">
            <div class="container mt-4">
                <h3>لاگ‌ها</h3>
                <div id="logsTable"></div>
                <nav id="logsPagination" class="mt-3"></nav>
            </div>
        </div>
    <?php endif; ?>
    <div id="settingsSection" class="mt-4 dashboard-section" style="display:none;">
        <div class="container mt-4">
            <h3>تنظیمات حساب</h3>

            <!-- نمایش توکن اکستنشن -->
            <div class="alert alert-warning">
                <h5>🔑 توکن اکستنشن هشدار معلم</h5>
                <p>این کد رو توی اکستنشن کروم وارد کن تا نوتیفیکیشن با صدای معلم بگیری!</p>
                <div class="input-group">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($extensionToken); ?>"
                        readonly id="extensionToken">
                    <button class="btn btn-outline-primary" type="button" onclick="copyToken()">
                        <i class="fas fa-copy"></i> کپی
                    </button>
                    <button class="btn btn-outline-danger ms-2" type="button" onclick="regenerateToken()">
                        <i class="fas fa-sync-alt"></i> تولید مجدد
                    </button>
                </div>
                <small class="text-muted">هر بار تولید مجدد = اکستنشن قبلی قطع میشه!</small>
            </div>

            <div class="settings-form">
                <div id="settingsMessage"></div>
                <form id="settingsForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <h5>تغییر رمز عبور</h5>
                    <!-- بقیه فرم همون قبلی -->
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">رمز عبور فعلی</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">رمز عبور جدید</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">تکرار رمز عبور جدید</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
                    </div>

                    <h5>تغییر نام کاربری</h5>
                    <div class="mb-3">
                        <label for="newUsername" class="form-label">نام کاربری جدید</label>
                        <input type="text" class="form-control" id="newUsername" name="new_username"
                            placeholder="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="usernamePassword" class="form-label">رمز عبور فعلی (برای تغییر نام کاربری)</label>
                        <input type="password" class="form-control" id="usernamePassword" name="username_password">
                    </div>

                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                </form>
            </div>
        </div>
    </div>

    <script>

        function copyToken() {
            const tokenInput = document.getElementById('extensionToken');
            tokenInput.select();
            document.execCommand('copy');
            alert('توکن کپی شد! حالا برو تو اکستنشن بچسبون 🔥');
        }


        function regenerateToken() {
            if (!confirm('مطمئنی؟ اکستنشن قبلی دیگه کار نمی‌کنه!')) return;

            fetch('regenerate_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=<?php echo $csrf_token; ?>'
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('extensionToken').value = data.new_token;
                        alert('توکن جدید ساخته شد! حالا تو اکستنشن وارد کن');
                    }
                });
        }
    </script>
    <div class="modal fade" id="editGalleryModal" tabindex="-1" aria-labelledby="editGalleryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGalleryModalLabel">ویرایش عکس</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editGalleryForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="id" id="editGalleryId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editGalleryTitle" class="form-label">عنوان عکس</label>
                            <input type="text" class="form-control" id="editGalleryTitle" name="title">
                        </div>
                        <div class="mb-3">
                            <label for="editGalleryImage" class="form-label">انتخاب عکس جدید (اختیاری)</label>
                            <input type="file" class="form-control" id="editGalleryImage" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clockIcon = document.querySelector('.clock-icon');
            const watch = document.querySelector('.watch');


            if (clockIcon && watch) {
                clockIcon.addEventListener('click', () => {
                    watch.classList.toggle('active');
                });

                watch.addEventListener('click', () => {
                    watch.classList.remove('active');
                });
            }

            function updateTime() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                if (document.getElementById('hours')) document.getElementById('hours').textContent = hours;
                if (document.getElementById('minutes')) document.getElementById('minutes').textContent = minutes;
            }
            setInterval(updateTime, 1000);
            updateTime();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburger');

            if (hamburger && sidebar) {
                
                ['click', 'touchstart'].forEach(eventType => {
                    hamburger.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sidebar.classList.toggle('show');
                    }, { passive: false });
                });
            }

            if (sidebar) {
                sidebar.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth < 992) sidebar.classList.remove('show');
                    });
                });
            }

            
            ['click', 'touchstart'].forEach(eventType => {
                document.body.addEventListener(eventType, (e) => {
                    if (window.innerWidth < 992 && sidebar && hamburger) {
                        if (!sidebar.contains(e.target) && !hamburger.contains(e.target) && sidebar.classList.contains('show')) {
                            sidebar.classList.remove('show');
                        }
                    }
                });
            });
        });



        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(sec => {
                sec.style.display = sec.id === sectionId ? 'block' : 'none';
            });
        }



        <?php if ($user_role === 'admin' || $user_role === 'teacher'): ?>
            document.getElementById('showGallery')?.addEventListener('click', function (e) {
                e.preventDefault();
                showSection('gallerySection');
                if (document.getElementById('galleryMessage')) document.getElementById('galleryMessage').innerHTML = '';
                loadGallery();
            });

            document.getElementById('galleryForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(document.getElementById('galleryForm'));
                formData.append('action', 'upload');
                try {
                    const response = await fetch('gallery.php', {
                        method: 'POST',
                        body: formData,
                        cache: 'no-cache'
                    });
                    if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                    const result = await response.json();
                    const galleryMessage = document.getElementById('galleryMessage');
                    if (galleryMessage) {
                        galleryMessage.innerHTML = `<div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                        ${result.success ? result.message : 'خطا: ' + result.error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                    }
                    if (result.success) {
                        document.getElementById('galleryForm').reset();
                        loadGallery();
                    }
                } catch (err) {
                    if (document.getElementById('galleryMessage')) {
                        document.getElementById('galleryMessage').innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا در ارتباط با سرور: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                    }
                }
            });

            document.getElementById('editGalleryForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(document.getElementById('editGalleryForm'));
                formData.append('action', 'edit');
                try {
                    const response = await fetch('gallery.php', {
                        method: 'POST',
                        body: formData,
                        cache: 'no-cache'
                    });
                    if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                    const result = await response.json();
                    const galleryMessage = document.getElementById('galleryMessage');
                    if (galleryMessage) {
                        galleryMessage.innerHTML = `<div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                        ${result.success ? result.message : 'خطا: ' + result.error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                    }
                    if (result.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editGalleryModal')).hide();
                        loadGallery();
                    }
                } catch (err) {
                    if (document.getElementById('galleryMessage')) {
                        document.getElementById('galleryMessage').innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا در ارتباط با سرور: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                    }
                }
            });
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>


            document.getElementById('showLogs')?.addEventListener('click', function (e) {
                e.preventDefault();
                showSection('logsSection');
                loadLogs(1);
            });
        <?php endif; ?>




        document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(document.getElementById('settingsForm'));
            try {
                const response = await fetch('update_settings.php', {
                    method: 'POST',
                    body: formData,
                    cache: 'no-cache'
                });
                if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                const result = await response.json();
                const settingsMessage = document.getElementById('settingsMessage');
                if (settingsMessage) {
                    settingsMessage.innerHTML = `<div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                        ${result.success ? result.message : 'خطا: ' + (result.error || 'نامشخص')}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                }
                if (result.success) {
                    document.getElementById('settingsForm').reset();
                }
            } catch (err) {
                if (document.getElementById('settingsMessage')) {
                    document.getElementById('settingsMessage').innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا در ارتباط با سرور: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>