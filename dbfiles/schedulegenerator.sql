-- 1. جدول تنظیمات عمومی ماژول زمان‌بندی (مانند تعریف زنگ‌ها و روزهای کاری به صورت ساختار یافته)
CREATE TABLE IF NOT EXISTS `sg_settings` (
  `key_name` VARCHAR(50) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2. جدول ثبت دسترسی و اولویت حضور معلمان در زنگ‌های مختلف
CREATE TABLE IF NOT EXISTS `sg_teacher_availability` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `teacher_id` INT NOT NULL,
  `day_of_week` ENUM('saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday') NOT NULL,
  `period` INT NOT NULL COMMENT 'شماره زنگ مثلا 1 تا 4',
  `status` ENUM('available', 'prefer_not', 'unavailable') NOT NULL DEFAULT 'available' COMMENT 'سبز، زرد، قرمز',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_teacher_day_period` (`teacher_id`, `day_of_week`, `period`),
  CONSTRAINT `fk_sg_availability_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 3. جدول مشخص‌کننده نیازهای درسی هر کلاس و دبیر تخصیص‌یافته به آن (بار آموزشی کلاس)
CREATE TABLE IF NOT EXISTS `sg_course_requirements` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `class_id` INT NOT NULL,
  `subject_name` VARCHAR(100) NOT NULL COMMENT 'نام درس مانند ریاضی، فیزیک و...',
  `teacher_id` INT DEFAULT NULL COMMENT 'دبیری که برای این درس در این کلاس تعیین شده است',
  `weekly_hours` INT NOT NULL DEFAULT '1' COMMENT 'تعداد زنگ‌های این درس در هفته برای این کلاس',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_class_subject` (`class_id`, `subject_name`),
  CONSTRAINT `fk_sg_req_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sg_req_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 4. جدول نهایی ذخیره‌سازی برنامه هفتگی تولید شده
CREATE TABLE IF NOT EXISTS `sg_timetable_results` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `class_id` INT NOT NULL,
  `subject_name` VARCHAR(100) NOT NULL,
  `teacher_id` INT NOT NULL,
  `day_of_week` ENUM('saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday') NOT NULL,
  `period` INT NOT NULL,
  PRIMARY KEY (`id`),
  -- جلوگیری از قرار گرفتن دو درس در یک زنگ برای یک کلاس
  UNIQUE KEY `idx_class_day_period` (`class_id`, `day_of_week`, `period`),
  -- جلوگیری از حضور همزمان یک معلم در دو کلاس در یک زنگ
  UNIQUE KEY `idx_teacher_day_period` (`teacher_id`, `day_of_week`, `period`),
  CONSTRAINT `fk_sg_res_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sg_res_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;