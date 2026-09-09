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

// Redirect logged-in user directly to dashboard unless success flag is present
if (isset($_SESSION['user_id']) && !isset($_GET['success'])) {
    header("Location: ../dashboard/");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه | ZeroLMS</title>
    
    <link href="../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">

    <style>
        :root {
            --bg-body: #0b1120;
            --card-bg: #1e293b;
            --border-color: #334155;
            --input-bg: #0f172a;
            --primary-blue: #2563eb;
            --primary-blue-hover: #1d4ed8;
            --text-main: #ffffff;
            --text-sub: #cbd5e1;
            --text-muted: #94a3b8;
        }

        * {
            font-family: 'Vazirmatn', sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-logo {
            width: 52px;
            height: 52px;
            background: var(--primary-blue);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }

        .login-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0 0 6px 0;
        }

        .login-subtitle {
            font-size: 0.88rem;
            color: var(--text-sub);
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-sub);
            margin-bottom: 8px;
            text-align: right;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 42px 12px 14px;
            font-size: 0.95rem;
            text-align: left;
            direction: ltr;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input-password {
            padding: 12px 44px 12px 48px !important;
        }

        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
            background-color: var(--input-bg);
            color: #ffffff;
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }

        .password-toggle {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--primary-blue);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-blue-hover);
        }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.88rem;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .alert-danger-custom {
            background-color: #450a0a;
            border: 1px solid #7f1d1d;
            color: #fecaca;
        }

        .alert-success-custom {
            background-color: #064e3b;
            border: 1px solid #065f46;
            color: #a7f3d0;
        }

        .back-home {
            text-align: center;
            margin-top: 24px;
        }

        .back-home a {
            color: var(--text-sub);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .back-home a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-layer-group"></i>
            </div>
            <h1 class="login-title">ورود به سامانه ZeroLMS</h1>
            <p class="login-subtitle">نام کاربری و رمز عبور خود را وارد نمایید</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-circle me-1"></i> نام کاربری یا کلمه عبور اشتباه است.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle me-1"></i> ورود موفقیت‌آمیز بود. در حال انتقال به داشبورد...
            </div>
            <script>
                setTimeout(() => { window.location.href = "../dashboard/"; }, 800);
            </script>
        <?php endif; ?>

        <form action="login_process.php" method="POST" autocomplete="on">
            <div class="form-group">
                <label for="username" class="form-label">نام کاربری</label>
                <div class="input-wrapper">
                    <input type="text" class="form-input" id="username" name="username" placeholder="نام کاربری" autocomplete="username" required>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">کلمه عبور</label>
                <div class="input-wrapper">
                    <input type="password" class="form-input form-input-password" id="password" name="password" placeholder="کلمه عبور" autocomplete="current-password" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()" title="نمایش کلمه عبور">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt me-1"></i> ورود به حساب
            </button>
        </form>

        <div class="back-home">
            <a href="../">
                <i class="fas fa-arrow-right me-1"></i> بازگشت به صفحه اصلی
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>