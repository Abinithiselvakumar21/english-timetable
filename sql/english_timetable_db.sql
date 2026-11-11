-- ========================================================
-- AI-Based Staff Timetable Management System
-- College English Department Database Schema
-- Database Name: english_timetable_db
-- ========================================================

CREATE DATABASE IF NOT EXISTS `english_timetable_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `english_timetable_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table (Authentication for Admin, HOD, Staff)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('ADMIN', 'HOD', 'STAFF') NOT NULL DEFAULT 'STAFF',
  `status` ENUM('ACTIVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Staff Table (Detailed Teacher Profile)
DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL UNIQUE,
  `staff_code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `designation` VARCHAR(50) NOT NULL DEFAULT 'Assistant Professor',
  `department` VARCHAR(50) NOT NULL DEFAULT 'English',
  `specialization` VARCHAR(150) NOT NULL COMMENT 'e.g. British Literature, Linguistics, Communicative English',
  `max_weekly_workload` INT NOT NULL DEFAULT 16 COMMENT 'Max periods allowed per week',
  `status` ENUM('ACTIVE', 'ON_LEAVE', 'INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Subjects Table (Separate Major and Foundation Subjects)
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(120) NOT NULL,
  `category` ENUM('MAJOR', 'FOUNDATION') NOT NULL COMMENT 'Categorized separately for management',
  `semester` INT NOT NULL COMMENT 'Semester 1 to 6',
  `weekly_hours` INT NOT NULL DEFAULT 4 COMMENT 'Required periods per week',
  `color_code` VARCHAR(10) NOT NULL DEFAULT '#0d6efd' COMMENT 'Blue for Major, Green for Foundation',
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_subject_category` (`category`),
  INDEX `idx_subject_sem` (`semester`)
) ENGINE=InnoDB;

-- 4. Classes Table (Degrees, Semesters, Sections)
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_name` VARCHAR(50) NOT NULL COMMENT 'e.g. BA English I Year',
  `academic_year` VARCHAR(20) NOT NULL COMMENT 'e.g. 2026-2027',
  `year` INT NOT NULL COMMENT '1, 2, or 3',
  `semester` INT NOT NULL COMMENT '1 to 6',
  `section` VARCHAR(5) NOT NULL DEFAULT 'A',
  `room_no` VARCHAR(20) NOT NULL DEFAULT 'Room-101',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_class_sem` (`semester`)
) ENGINE=InnoDB;

-- 5. Periods Table (College Timings and Breaks)
DROP TABLE IF EXISTS `periods`;
CREATE TABLE `periods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `period_number` INT NOT NULL UNIQUE,
  `period_name` VARCHAR(50) NOT NULL COMMENT 'e.g. Period 1, Lunch Break',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_break` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if break, 0 if teaching period',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Working Days Table
DROP TABLE IF EXISTS `working_days`;
CREATE TABLE `working_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `day_name` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL UNIQUE,
  `day_order` INT NOT NULL UNIQUE,
  `is_working` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 7. Staff Subject Allocation Table (Assign eligible staff to subjects & classes)
DROP TABLE IF EXISTS `staff_subjects`;
CREATE TABLE `staff_subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_staff_subject_class` (`staff_id`, `subject_id`, `class_id`),
  CONSTRAINT `fk_alloc_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alloc_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alloc_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Staff Availability Table (Unavailability constraints)
DROP TABLE IF EXISTS `staff_availability`;
CREATE TABLE `staff_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL,
  `day_name` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `period_id` INT NOT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Not Available, 1 = Available',
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_staff_day_period` (`staff_id`, `day_name`, `period_id`),
  CONSTRAINT `fk_avail_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avail_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Subject Requirements (Weekly periods target per class)
DROP TABLE IF EXISTS `subject_requirements`;
CREATE TABLE `subject_requirements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `required_hours` INT NOT NULL DEFAULT 4,
  `assigned_hours` INT NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_class_subject_req` (`class_id`, `subject_id`),
  CONSTRAINT `fk_req_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Timetable Versions (Draft, Review, Approved, Published)
DROP TABLE IF EXISTS `timetable_versions`;
CREATE TABLE `timetable_versions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version_code` VARCHAR(30) NOT NULL UNIQUE,
  `academic_year` VARCHAR(20) NOT NULL,
  `generated_by` INT NOT NULL,
  `status` ENUM('DRAFT', 'UNDER_REVIEW', 'APPROVED', 'REJECTED', 'PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  `hod_remarks` TEXT NULL,
  `approved_at` DATETIME NULL,
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ver_gen_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB;

-- 11. Timetable Slots Table (Master Integrated Timetable combining Major & Foundation)
DROP TABLE IF EXISTS `timetable`;
CREATE TABLE `timetable` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `staff_id` INT NOT NULL,
  `day_name` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `period_id` INT NOT NULL,
  `category` ENUM('MAJOR', 'FOUNDATION') NOT NULL,
  `room_no` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Clashes Prevention Indexes
  UNIQUE KEY `uniq_staff_slot` (`version_id`, `day_name`, `period_id`, `staff_id`),
  UNIQUE KEY `uniq_class_slot` (`version_id`, `day_name`, `period_id`, `class_id`),
  CONSTRAINT `fk_tt_ver` FOREIGN KEY (`version_id`) REFERENCES `timetable_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Notifications Table (Real-time and Cron Alert log)
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('CLASS_REMINDER', 'TIMETABLE_UPDATE', 'PUBLISH_ALERT', 'GENERAL') NOT NULL DEFAULT 'GENERAL',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `scheduled_at` DATETIME NULL,
  `sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- SEED DATA (Default College English Department Setup)
-- Default Password for all seeded users: admin123
-- Password Hash using password_hash('admin123', PASSWORD_BCRYPT):
-- $2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje
-- ========================================================

-- Users (Admin, HOD, 4 Teaching Staff)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin', 'admin@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'ADMIN', 'ACTIVE'),
(2, 'hod_english', 'hod.english@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'HOD', 'ACTIVE'),
(3, 'dr_kavitha', 'kavitha@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'STAFF', 'ACTIVE'),
(4, 'prof_anand', 'anand@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'STAFF', 'ACTIVE'),
(5, 'prof_meena', 'meena@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'STAFF', 'ACTIVE'),
(6, 'dr_suresh', 'suresh@college.edu', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lR0e.uYI9e11W3c6o2B6zQ26zNfje', 'STAFF', 'ACTIVE');

-- Staff Profiles
INSERT INTO `staff` (`id`, `user_id`, `staff_code`, `name`, `email`, `phone`, `designation`, `department`, `specialization`, `max_weekly_workload`) VALUES
(1, 2, 'ENG001', 'Dr. S. Sundararajan (HOD)', 'hod.english@college.edu', '9876543210', 'Associate Professor & HOD', 'English', 'Literary Criticism & Shakespeare', 10),
(2, 3, 'ENG002', 'Dr. M. Kavitha', 'kavitha@college.edu', '9876543211', 'Assistant Professor', 'English', 'British Literature & Poetry', 16),
(3, 4, 'ENG003', 'Prof. R. Anand', 'anand@college.edu', '9876543212', 'Assistant Professor', 'English', 'American Literature & Drama', 16),
(4, 5, 'ENG004', 'Prof. P. Meena', 'meena@college.edu', '9876543213', 'Assistant Professor', 'English', 'Spoken English & Writing Skills', 16),
(5, 6, 'ENG005', 'Dr. V. Suresh', 'suresh@college.edu', '9876543214', 'Assistant Professor', 'English', 'Communicative English & Grammar', 16);

-- Working Days (Monday - Friday)
INSERT INTO `working_days` (`day_name`, `day_order`, `is_working`) VALUES
('Monday', 1, 1),
('Tuesday', 2, 1),
('Wednesday', 3, 1),
('Thursday', 4, 1),
('Friday', 5, 1),
('Saturday', 6, 0);

-- College Periods (5 teaching periods + 1 lunch break)
INSERT INTO `periods` (`period_number`, `period_name`, `start_time`, `end_time`, `is_break`) VALUES
(1, 'Period 1', '09:00:00', '10:00:00', 0),
(2, 'Period 2', '10:00:00', '11:00:00', 0),
(3, 'Period 3', '11:15:00', '12:15:00', 0),
(4, 'Lunch Break', '12:15:00', '01:00:00', 1),
(5, 'Period 4', '01:00:00', '02:00:00', 0),
(6, 'Period 5', '02:00:00', '03:00:00', 0);

-- Classes
INSERT INTO `classes` (`id`, `class_name`, `academic_year`, `year`, `semester`, `section`, `room_no`) VALUES
(1, 'BA English I Year', '2026-2027', 1, 1, 'A', 'Room-101'),
(2, 'BA English II Year', '2026-2027', 2, 3, 'A', 'Room-102'),
(3, 'BA English III Year', '2026-2027', 3, 5, 'A', 'Room-103');

-- Subjects: MAJOR (Blue: #0d6efd) and FOUNDATION (Green: #198754)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `category`, `semester`, `weekly_hours`, `color_code`, `description`) VALUES
-- Major Subjects
(1, 'ENG101', 'British Literature', 'MAJOR', 1, 4, '#0d6efd', 'Core Major Subject on British classic authors'),
(2, 'ENG102', 'Indian Writing in English', 'MAJOR', 1, 4, '#0d6efd', 'Post-colonial and contemporary Indian writers in English'),
(3, 'ENG301', 'American Literature', 'MAJOR', 3, 4, '#0d6efd', '19th and 20th century American classics'),
(4, 'ENG302', 'Shakespeare Studies', 'MAJOR', 3, 4, '#0d6efd', 'Tragedies and Comedies of William Shakespeare'),
(5, 'ENG501', 'Literary Criticism', 'MAJOR', 5, 4, '#0d6efd', 'Classical to modern critical theory'),

-- Foundation Subjects
(6, 'FND101', 'Communicative English', 'FOUNDATION', 1, 4, '#198754', 'Language skills for first years'),
(7, 'FND102', 'Basic Grammar & Phonetics', 'FOUNDATION', 1, 3, '#198754', 'Grammar fundamentals and pronunciation'),
(8, 'FND301', 'Writing Skills & Composition', 'FOUNDATION', 3, 4, '#198754', 'Academic essay and report writing skills'),
(9, 'FND501', 'Spoken English & Presentation', 'FOUNDATION', 5, 3, '#198754', 'Interview techniques and public speaking');

-- Staff Subject Allocation (Specialization-based assignment)
INSERT INTO `staff_subjects` (`staff_id`, `subject_id`, `class_id`, `is_primary`) VALUES
-- BA I Year
(2, 1, 1, 1), -- Dr. Kavitha -> British Literature (Major)
(3, 2, 1, 1), -- Prof. Anand -> Indian Writing (Major)
(5, 6, 1, 1), -- Dr. Suresh -> Communicative English (Foundation)
(4, 7, 1, 1), -- Prof. Meena -> Basic Grammar (Foundation)

-- BA II Year
(3, 3, 2, 1), -- Prof. Anand -> American Literature (Major)
(1, 4, 2, 1), -- HOD Sundararajan -> Shakespeare Studies (Major)
(4, 8, 2, 1), -- Prof. Meena -> Writing Skills (Foundation)

-- BA III Year
(1, 5, 3, 1), -- HOD Sundararajan -> Literary Criticism (Major)
(5, 9, 3, 1); -- Dr. Suresh -> Spoken English & Presentation (Foundation)

-- Subject Requirements per class
INSERT INTO `subject_requirements` (`class_id`, `subject_id`, `required_hours`, `assigned_hours`) VALUES
(1, 1, 4, 0),
(1, 2, 4, 0),
(1, 6, 4, 0),
(1, 7, 3, 0),
(2, 3, 4, 0),
(2, 4, 4, 0),
(2, 8, 4, 0),
(3, 5, 4, 0),
(3, 9, 3, 0);
