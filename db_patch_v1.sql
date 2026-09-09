-- DB Patch v1 for ZeroLMS
-- Adds CASCADE rules and UNIQUE constraints to resolve integrity and deletion bugs

-- 1. Unique constraints for gamification
ALTER TABLE `user_points` ADD UNIQUE KEY `unique_user_points` (`user_id`);
ALTER TABLE `user_badges` ADD UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`);

-- 2. Cascade rules for report_cards
ALTER TABLE `report_cards` DROP FOREIGN KEY `report_cards_ibfk_1`;
ALTER TABLE `report_cards` ADD CONSTRAINT `report_cards_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `report_cards` DROP FOREIGN KEY `report_cards_ibfk_2`;
ALTER TABLE `report_cards` ADD CONSTRAINT `report_cards_ibfk_2` FOREIGN KEY (`class_course_id`) REFERENCES `classcourses` (`id`) ON DELETE CASCADE;

-- 3. Cascade rules for user_points and user_badges
ALTER TABLE `user_points` DROP FOREIGN KEY `user_points_ibfk_1`;
ALTER TABLE `user_points` ADD CONSTRAINT `user_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_badges` DROP FOREIGN KEY `user_badges_ibfk_1`;
ALTER TABLE `user_badges` ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_badges` DROP FOREIGN KEY `user_badges_ibfk_2`;
ALTER TABLE `user_badges` ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

-- 4. Cascade & Set Null rules for form_responses
ALTER TABLE `form_responses` DROP FOREIGN KEY `form_responses_ibfk_1`;
ALTER TABLE `form_responses` ADD CONSTRAINT `form_responses_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

ALTER TABLE `form_responses` DROP FOREIGN KEY `form_responses_ibfk_2`;
ALTER TABLE `form_responses` ADD CONSTRAINT `form_responses_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
