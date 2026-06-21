<?php
session_start();

if(isset($_SESSION['user_id']) && !isset($_GET['success'])){
    header("Location: ../dashboard/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم مدرسه</title>
    <link href="../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <!-- <script disable-devtool-auto src='https://cdn.jsdelivr.net/npm/disable-devtool'></script> -->
    <style>
        body {
            background: linear-gradient(135deg, #00d9ff, #1fd5db, #007bff);
            font-family: 'font-iran-normal', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent);
            animation: pulse 15s infinite ease-in-out;
            z-index: 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 217, 255, 0.3);
            width: 100%;
            max-width: 420px;
            text-align: right;
            position: relative;
            z-index: 1;
            animation: float 4s ease-in-out infinite;
            border: 1px solid rgba(0, 217, 255, 0.2);
        }

        .login-card h2 {
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            color: #00d9ff;
            font-size: 2.2rem;
            text-shadow: 0 0 10px rgba(0, 217, 255, 0.7);
            animation: neonGlow 2s ease-in-out infinite;
        }

        .form-control {
            border-radius: 15px;
            padding: 14px 18px;
            border: 1px solid #00d9ff;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.4s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.7);
            border-color: #00b8d4;
            transform: scale(1.03);
            background: #fff;
        }

        .btn-login {
            background: linear-gradient(45deg, #00d9ff, #007bff);
            color: #fff;
            border-radius: 15px;
            width: 100%;
            padding: 14px;
            font-weight: 700;
            border: none;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            z-index: 1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            transition: 0.6s ease;
        }

        .btn-login:hover::before {
            left: 150%;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #00b8d4, #005bff);
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 217, 255, 0.5);
        }

        .input-group-text {
            background: linear-gradient(45deg, #00d9ff, #007bff);
            color: #fff;
            border-radius: 0 15px 15px 0;
            border: none;
            transition: transform 0.4s ease;
            padding: 0 15px;
        }

        .input-group-text:hover {
            transform: scale(1.15);
        }

        .alert {
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 25px;
            animation: bounceIn 0.6s ease-in-out;
            background: rgba(255, 255, 255, 0.9);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: #fff;
            z-index: 1;
        }

        .login-footer a {
            color: #00d9ff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            color: #00b8d4;
            text-shadow: 0 0 5px rgba(0, 217, 255, 0.5);
        }

       
        @keyframes pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.1; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes neonGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(0, 217, 255, 0.7), 0 0 20px rgba(0, 217, 255, 0.4); }
            50% { text-shadow: 0 0 20px rgba(0, 217, 255, 0.9), 0 0 30px rgba(0, 217, 255, 0.6); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 25px 15px;
                max-width: 95%;
            }

            .login-card h2 {
                font-size: 1.8rem;
            }

            .btn-login {
                padding: 12px;
                font-size: 0.9rem;
            }

            .form-control {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div style="flex: 1; display: flex; align-items: center; justify-content: center;">
        <div class="login-card">
    <?php if(isset($_GET['forgot'])): ?>
        <h2>فراموشی رمز عبور</h2>
        <div class="alert alert-info text-center">
            لطفاً با مدرسه تماس بگیرید و درخواست تغییر رمز عبور خود را اعلام کنید.
        </div>
        <div class="text-center">
            <a href="index.php" class="btn btn-login">بازگشت به ورود</a>
        </div>
    <?php else: ?>
        <h2>ورود به مدرسه</h2>
        <form action="login_process.php" method="POST" autocomplete="on">
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center">نام کاربری یا رمز عبور اشتباه است!</div>
            <?php endif; ?>
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    ورود موفقیت آمیز بود، در حال انتقال به داشبورد...
                </div>
                <script>
                    setTimeout(function(){
                        window.location.href = "../dashboard/";
                    }, 3000);
                </script>
            <?php endif; ?>

            <div class="mb-3">
                <label for="username" class="form-label">نام کاربری</label>
                <input style="direction:ltr;" type="text" class="form-control" autocomplete="username" id="username" name="username" placeholder="نام کاربری خود را وارد نمایید ..." required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">رمز عبور</label>
                <div class="input-group">
                    <input style="direction:ltr;" type="password" class="form-control" autocomplete="current-password" id="password" name="password" placeholder="رمز عبور خود را وارد نمایید ..." required>
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">ورود</button>
        </form>
        <div class="text-center mt-3">
            <a href="?forgot=1" style="color:#007bff; text-decoration:none; font-weight:600;">
                رمز عبور خود را فراموش کرده‌ام؟
            </a>
        </div>
    <?php endif; ?>
</div>

    </div>

    <footer style="text-align: center; padding: 10px 0; font-size: 14px; color: #fff;">
        برنامه‌نویسی شده توسط 
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>