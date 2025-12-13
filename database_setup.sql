-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `student_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `student_portal`;

-- Drop existing users table if it exists to avoid conflicts
DROP TABLE IF EXISTS `users`;

-- Create users table with all required fields
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `guardian_first_name` varchar(50) DEFAULT NULL,
  `guardian_last_name` varchar(50) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`student_id`, `email`, `password`, `first_name`, `last_name`, `role`) 
VALUES ('ADMIN001', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin');

