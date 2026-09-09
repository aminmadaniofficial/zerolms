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

// Initialize PHP session scope and include database instance
session_start();
require_once '../db.php';
?>
<!DOCTYPE html>
<html class="no-js" lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>گالری دبیرستان استعدادهای درخشان شهید باهنر 3</title>
    <meta name="author" content="themeholy">
    <meta name="description" content="گالری تصاویر دبیرستان استعدادهای درخشان">
    <meta name="keywords" content="گالری, دبیرستان, استعدادهای درخشان, باهنر">
    <meta name="robots" content="INDEX,FOLLOW">
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="../images/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    
    <!-- Google Fonts Imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../css/app.min.css">
    <link rel="stylesheet" href="../css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Custom styling for photo gallery grid cards */
        .gallery-card {
            position: relative;
            overflow: hidden;
        }
        .gallery-img {
            position: relative;
        }
        .gallery-img img {
            width: 100%;
            height: auto;
            transition: transform 0.3s ease;
        }
        .gallery-card:hover .gallery-img img {
            transform: scale(1.05);
        }
        .gallery-title-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 10px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-size: 1.2rem;
        }
        .gallery-card:hover .gallery-title-overlay {
            opacity: 1;
        }
        .gallery-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gallery-card:hover .gallery-btn {
            opacity: 1;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .pagination .page-link {
            color: #007bff;
        }
        .pagination .page-link:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Preloader Screen Container -->
    <div class="preloader d-none">
        <button class="th-btn style3 preloaderCls">غیرفعال کردن لودر</button>
        <div class="preloader-inner"><span class="loader"></span></div>
    </div>
    
    <!-- Mobile Offcanvas Navigation Menu -->
    <div class="th-menu-wrapper">
        <div class="th-menu-area text-center">
            <button class="th-menu-toggle"><i class="fal fa-times"></i></button>
            <div class="mobile-logo"><a href="../index.php"><img src="../images/logo.svg" alt="bahonar3"></a></div>
            <div class="th-mobile-menu">
                <ul>
                    <li><a href="../index.php">خانه</a></li>
                    <li><a href="../team.html">اساتید</a></li>
                    <li><a href="./index.php">وبلاگ</a></li>
                    <li><a href="../contact.html">تماس</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Main Desktop Header and Top Navigation -->
    <header class="th-header header-layout6">
        <div class="sticky-wrapper">
            <div class="menu-area">
                <div class="container-fluid">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-xl-auto">
                            <div class="row align-items-center justify-content-between">
                                <div class="col-auto">
                                    <div class="header-logo"><a href="../index.php"><img style="background-color:#fff;border-radius:50px;" src="../images/logo-white.svg" alt="bahonar3"></a></div>
                                </div>
                                <div class="col-auto">
                                    <nav class="main-menu d-none d-lg-inline-block">
                                        <ul>
                                            <li><a href="../index.php">خانه</a></li>
                                            <li><a href="../index.php#about-sec">درباره ما</a></li>
                                            <li><a href="../index.php#team-sec">اساتید</a></li>
                                            <li><a href="./index.php">وبلاگ</a></li>
                                            <li><a href="../index.php#contact-sec">تماس با ما</a></li>
                                        </ul>
                                    </nav>
                                    <button type="button" class="th-menu-toggle d-block d-lg-none"><i class="far fa-bars"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto d-none d-xl-block">
                            <div class="row">
                                <div class="col-auto">
                                    <div class="header-button">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <a href="../dashboard" class="btn btn-outline-dark d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-lg me-2"></i> صفحه کاربری
                                            </a>
                                            <a href="../logout.php" class="btn btn-danger d-flex align-items-center ms-2">
                                                <i class="fas fa-sign-out-alt fa-lg me-2"></i> خروج
                                            </a>
                                        <?php else: ?>
                                            <a href="../login" class="btn btn-primary d-flex align-items-center">
                                                <i class="fas fa-sign-in-alt fa-lg me-2"></i> ورود به سایت
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="logo-bg"></div>
            </div>
        </div>
    </header>
    
    <!-- Hero Breadcrumb Header -->
    <div class="breadcumb-wrapper" data-bg-src="../images/breadcumb-bg.jpeg" data-overlay="title" data-opacity="8">
        <div class="breadcumb-shape" data-bg-src="../images/breadcumb_shape_1_1.png"></div>
        <div class="shape-mockup breadcumb-shape2 jump d-lg-block d-none" data-right="30px" data-bottom="30px">
            <img src="../images/breadcumb_shape_1_2.png" alt="shape">
        </div>
        <div class="shape-mockup breadcumb-shape3 jump-reverse d-lg-block d-none" data-left="50px" data-bottom="80px">
            <img src="../images/breadcumb_shape_1_3.png" alt="shape">
        </div>
        <div class="container">
            <div class="breadcumb-content text-center">
                <h1 class="breadcumb-title">گالری</h1>
                <ul class="breadcumb-menu">
                    <li><a href="../index.php">خانه</a></li>
                    <li>گالری</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Gallery Images Section -->
    <div class="space">
        <div class="container">
            <div class="row gy-4 " id="galleryContainer"></div>
            <nav aria-label="Page navigation">
                <ul class="pagination" id="paginationContainer"></ul>
            </nav>
        </div>
    </div>
    
    <!-- Application Footer -->
    <footer class="footer-wrapper footer-layout-default" data-bg-src="../../images/footer-bg.png">
        <div class="shape-mockup footer-shape1 jump" data-left="60px" data-top="70px"><img
                src="../../images/footer-bg-shape1.png" alt="img"></div>
        <div class="shape-mockup footer-shape2 jump-reverse" data-right="80px" data-bottom="120px"><img
                src="../../images/footer-bg-shape2.png" alt="img"></div>
        <div class="footer-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3">
                        <div class="footer-logo"><a href="index.php"><img src="../../fonts/logo-white.svg" alt="bahonar3"></a>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="">
                            <h5 class="newsletter-title" style="text-align: center;">همیشه با ما در ارتباط باشید</h5>
                            <p style="text-align: center;">برای جدیدترین اخبار، فعالیت‌ها و رویدادهای دبیرستان باهنر ۳،
                                ما را دنبال کنید و از آخرین اطلاعات مطلع شوید.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="widget-area">
            <div class="container">
                <div class="row justify-content-between">
                    <div class="col-md-6 col-xxl-3 col-xl-3">
                        <div class="widget footer-widget">
                            <div class="th-widget-about">
                                <h3 class="widget_title">درباره مجموعه ما</h3>
                                <p class="about-text">مدرسه استعداد های درخشان شهید باهنر ۳ کرج در سال ۱۳۹۲ تاسیس گردیده
                                    است. </p>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-auto">
                        <div class="widget widget_nav_menu footer-widget">
                            <h3 class="widget_title">دسترسی سریع</h3>
                            <div class="menu-all-pages-container">
                                <ul class="menu">
                                    <li><a href="./index.php">خانه</a></li>
                                    <li><a href="./index.php#team-sec">اساتید</a></li>
                                    <li><a href="./index.php#blog-sec">وبلاگ</a></li>
                                    <li><a target="_blank" href="https://aminmadani.ir">توسعه دهنده</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xxl-3 col-xl-3">
                        <div class="widget widget_contact footer-widget">
                            <h3 class="widget_title">با ما در تماس باشید</h3>
                            <div class="th-widget-contact">
                                <div class="info-box-wrap">
                                    <div class="info-box_icon"><i class="fas fa-location-dot"></i></div>
                                    <p class="info-box_text"> کرج، بلوار شهید مطهری، نرسیده به آزادگان، خیابان شهید
                                        ساوجی، نبش اردلان 3</p>
                                </div>
                                <div class="info-box-wrap">
                                    <div class="info-box_icon"><i class="fas fa-envelope"></i></div><a
                                        href="mailto:info@bahonar3.ir" class="info-box_link">info@bahonar3.ir</a>
                                </div>
                                <div class="info-box-wrap">
                                    <div class="info-box_icon"><i class="fas fa-phone"></i></div><a
                                        href="tel:02632522003" class="info-box_link">۰۲۶۳۲۵۲۲۰۰۳</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright-wrap">
            <div class="container">
                <div class="row justify-content-between align-items-center">
                    <div class="col-md-6">
                        <a href="https://aminmadani.ir" target="_blank" class="copyright-text">برنامه نویسی شده توسط
                            محمدامین مدنی محمدی ©️</a>
                    </div>
                    <div class="col-md-6 text-end d-none d-md-block">
                        <div class="footer-links">
                            <ul>
                                <li><a href="about.html">سیاست حفظ حریم خصوصی</a></li>
                                <li><a href="about.html">شرایط و ضوابط</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button Button Container -->
    <div class="scroll-top">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" style="transition: stroke-dashoffset 10ms linear 0s; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 307.919;"></path>
        </svg>
    </div>
    
    <!-- Core JS Dependencies -->
    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/app.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Fetch and render gallery items asynchronously via AJAX endpoint.
         * 
         * @param {number} page Page index to retrieve
         */
        async function loadGallery(page = 1) {
            try {
                const response = await fetch(`fetch_gallery.php?page=${page}&limit=20`, { cache: 'no-cache' });
                if (!response.ok) throw new Error('خطای شبکه: ' + response.status);
                const data = await response.json();
                if (!data.success) throw new Error(data.error || 'خطای ناشناخته');
                const galleryContainer = document.getElementById('galleryContainer');
                galleryContainer.innerHTML = '';
                function escapeHtml(text) {
                    if (!text) return '';
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                if (data.images.length === 0) {
                    galleryContainer.innerHTML = '<div class="col-12"><p class="text-center">هیچ عکسی در گالری وجود ندارد.</p></div>';
                } else {
                    data.images.forEach(image => {
                        const div = document.createElement('div');
                        div.className = 'col-md-6 col-lg-4 col-xl-3 filter-item';
                        const safeTitle = escapeHtml(image.title || 'بدون عنوان');
                        const safePath = encodeURI(image.image_path || '');
                        div.innerHTML = `
                            <div class="gallery-card">
                                <div class="gallery-img">
                                    <img src="../${safePath}" alt="${safeTitle}">
                                    <a href="../${safePath}" class="gallery-btn popup-image"><i class="fas fa-eye"></i></a>
                                    <div class="gallery-title-overlay">${safeTitle}</div>
                                </div>
                            </div>`;
                        galleryContainer.appendChild(div);
                    });
                }
                // Render numerical pagination buttons
                renderPagination(data.total_pages, page);
            } catch (err) {
                document.getElementById('galleryContainer').innerHTML = `<div class="col-12"><p class="text-center text-danger">خطا در بارگذاری گالری: ${err.message}</p></div>`;
            }
        }

        /**
         * Render pagination control list dynamically based on total pages.
         * 
         * @param {number} totalPages Total available pages
         * @param {number} currentPage Active page index
         */
        function renderPagination(totalPages, currentPage) {
            const paginationContainer = document.getElementById('paginationContainer');
            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;

            const ul = document.createElement('ul');
            ul.className = 'pagination';

            // Previous page item
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">قبلی</a>`;
            ul.appendChild(prevLi);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                ul.appendChild(li);
            }

            // Next page item
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">بعدی</a>`;
            ul.appendChild(nextLi);

            paginationContainer.appendChild(ul);

            // Add click listeners to dynamic pagination links
            document.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.getAttribute('data-page'));
                    if (page && !link.parentElement.classList.contains('disabled')) {
                        loadGallery(page);
                    }
                });
            });
        }

        // Trigger initial gallery load upon DOM load
        document.addEventListener('DOMContentLoaded', () => {
            loadGallery(1);
        });
    </script>
</body>
</html>