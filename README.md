# 🚀 سامانه جامع مدیریت یادگیری زیرو (ZeroLMS)

[![CI/CD Tests](https://github.com/aminmadaniofficial/zerolms/actions/workflows/ci.yml/badge.svg)](https://github.com/aminmadaniofficial/zerolms/actions/workflows/ci.yml)
![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker&logoColor=white)
![MariaDB](https://img.shields.io/badge/Database-MariaDB_10.11-003545?style=flat&logo=mariadb&logoColor=white)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![Platform](https://img.shields.io/badge/Platform-Web%20%7C%20IoT%20%7C%20Extension-blue)

> **مستندات رسمی اثر جهت ارائه در مرحله کشوری دوازدهمین دوره جشنواره نوجوان خوارزمی**
> **طراح و توسعه‌دهنده:** محمدامین مدنی محمدی | **مجموعه آموزشی:** دبیرستان استعدادهای درخشان شهید باهنر ۳ کرج

---

## 📋 فهرست مطالب
1. [درباره پروژه](#-درباره-پروژه)
2. [ویژگی‌ها و نوآوری‌های اصلی](#-ویژگی‌ها-و-نوآوری‌های-اصلی)
3. [معماری سیستم و زیرساخت داکر](#-معماری-سیستم-و-زیرساخت-داکر)
4. [پشته تکنولوژی‌ها (Tech Stack)](#-پشته-تکنولوژی‌ها-tech-stack)
5. [راهنمای نصب و راه‌اندازی (Deployment Guide)](#-راهنمای-نصب-و-راه‌اندازی-deployment-guide)
6. [تحلیل پایگاه داده (Database Schema)](#-تحلیل-پایگاه-داده-database-schema)
7. [تست‌ها و بنچمارک‌های پرفورمنس](#-تست‌ها-و-بنچمارک‌های-پرفورمنس)
8. [امنیت و حریم خصوصی](#-امنیت-و-حریم-خصوصی)

---

## 🎯 درباره پروژه
سامانه **ZeroLMS** یک پلتفرم هوشمند مدیریت یادگیری با هدف حل چالش‌های عدم تعامل در سامانه‌های آموزشی سنتی طراحی شده است. این سیستم با تلفیق **هوش مصنوعی استریم‌کننده**، **بازی‌وارسازی (Gamification)**، **سخت‌افزار حضور و غیاب RFID** و **افزونه کروم**، یک زیست‌بوم کامل آموزشی را تشکیل می‌دهد.

---

## 🌟 ویژگی‌ها و نوآوری‌های اصلی
- 🧠 **دستیار هوش مصنوعی زیرو (Zero AI):** پردازش زنده متون با پروتکل Server-Sent Events (SSE) و حافظه گفتگو.
- 📅 **موتور هوشمند برنامه‌ریزی هفتگی:** الگوریتم حل محدودیت چرخشی همگام (Latin-Square Solver) برای چیدمان ۱۲ کلاس بدون تداخل استاد.
- 🏷️ **گیمیفیکیشن و انگیزش:** مدال‌های افتخار، امتیازات پویا و جدول رتبه‌بندی در امتحانات.
- 📡 **حضور و غیاب سخت‌افزاری (IoT):** ثبت کارت‌های RFID PN532 بر اساس کد UFID و ارسال پیامک خودکار به والدین.
- 🔔 **افزونه کروم «هوشیار»:** اعلان زنده تکالیف، آزمون‌ها و غیبت‌ها (Manifest V3).
- 📊 **فرم‌ساز و نظرسنجی زنده:** ساخت فرم‌های پویا، تحلیل آماری با Chart.js و خروجی اکسل.

---

## 🏗️ معماری سیستم و زیرساخت داکر
سیستم بر پایه دو کانتینر ایزوله داکر و یک وب‌سرور Nginx به عنوان معکوس‌کننده پروکسی (Reverse Proxy) مدیریت می‌شود:

- `php_app_container`: کانتینر وب بر پایه PHP 8.2 و Apache
- `mysql_db_container`: کانتینر پایگاه داده MariaDB 10.11 (ایزوله در شبکه داخلی)
- `Nginx Reverse Proxy`: مدیریت پروتکل امن HTTPS و گواهی SSL Certbot

---

## 💻 پشته تکنولوژی‌ها (Tech Stack)
- **Backend:** PHP 8.2 (Pure OOP & MVC Architecture)
- **Database:** MariaDB 10.11 (31 Normalized Tables)
- **Frontend:** JavaScript ES6+, Bootstrap 5, Chart.js, Vazirmatn Font
- **DevOps:** Docker Compose, Nginx, Linux Debian/Ubuntu
- **Extension:** Chrome Manifest V3, Service Workers, Alarms API

---

## ⚙️ راهنمای نصب و راه‌اندازی (Deployment Guide)

```bash
# ۱. کلون کردن مخزن پروژه
git clone https://github.com/aminmadaniofficial/zerolms.git
cd zerolms

# ۲. ایجاد فایل متغیرهای محیطی
cp .env.example .env

# ۳. بالا آوردن کانتینرهای داکر
docker compose up -d

# ۴. ایمپورت پایگاه داده اولیه
docker exec -i mysql_db_container mysql -u zerolms -p1597538264Mm school_online < backupdatabase.sql
```

---

## 📊 تست‌ها و بنچمارک‌های پرفورمنس (Lighthouse Audit)
- **Performance:** 98 / 100
- **Accessibility:** 100 / 100
- **Best Practices:** 100 / 100
- **SEO:** 100 / 100

---

## 🛡️ امنیت و حریم خصوصی
- محافظت در برابر حملات SQL Injection با استفاده از PDO Prepared Statements.
- سیستم توکن امنیتی CSRF برای تمامی فرم‌ها و ارسال‌های POST.
- هش‌سازی رمز عبور کاربران با الگوریتم BCRYPT.
- استفاده از هدرهای امنیتی HTTPS و گواهی SSL پایداری A+.

---
**طراحی و توسعه توسط محمدامین مدنی محمدی | دبیرستان استعدادهای درخشان شهید باهنر ۳ کرج**

<!-- Security scan triggered at 2026-09-10 04:05:48 -->

<!-- Security scan triggered at 2026-09-11 07:23:12 -->