-- Create the database
CREATE DATABASE IF NOT EXISTS `student_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `student_portal`;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `guardian_first_name` varchar(50) DEFAULT NULL,
  `guardian_last_name` varchar(50) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `home_phone` varchar(20) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courses table
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(2) NOT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `semester` enum('Fall','Spring','Summer') NOT NULL,
  `academic_year` year(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enrollments table
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('enrolled','dropped','completed','failed') DEFAULT 'enrolled',
  `grade` varchar(2) DEFAULT NULL,
  `points` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_course` (`student_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grades table
CREATE TABLE IF NOT EXISTS `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `assessment_type` varchar(50) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `assessment_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements table
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` date DEFAULT NULL,
  `is_important` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample courses
INSERT INTO `courses` (`course_code`, `course_name`, `description`, `credits`, `instructor`, `schedule`, `room`, `semester`, `academic_year`) VALUES
('CS101', 'Introduction to Computer Science', 'Fundamentals of computer science and programming', 3, 'Dr. Smith', 'Mon/Wed 10:00-11:30', 'SCI-101', 'Fall', 2025),
('MATH201', 'Calculus I', 'Introduction to differential and integral calculus', 4, 'Prof. Johnson', 'Tue/Thu 13:00-14:30', 'MATH-205', 'Fall', 2025),
('ENG110', 'Academic Writing', 'Develops academic writing and research skills', 3, 'Dr. Williams', 'Mon/Wed 13:00-14:30', 'HUM-102', 'Fall', 2025);

-- Create a test student (password: Student@123)
INSERT INTO `users` (
    `student_id`, `email`, `password`, `first_name`, `last_name`, `middle_name`,
    `date_of_birth`, `gender`, `place_of_birth`, `religion`,
    `guardian_first_name`, `guardian_last_name`, `guardian_address`,
    `phone`, `home_phone`, `current_address`, `home_address`, `status`
) VALUES (
    'STD2025001', 'student@university.edu', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'John', 'Doe', 'Michael', '2000-01-15', 'Male', 'New York, USA', 'Christian',
    'Robert', 'Doe', '123 Guardian Street, City, State',
    '1234567890', '0987654321', '123 University Ave, City, State', '456 Home Street, City, State', 'active'
);

-- Enroll test student in courses
INSERT INTO `enrollments` (`student_id`, `course_id`, `enrollment_date`, `status`) VALUES
(1, 1, '2025-08-20', 'enrolled'),
(1, 2, '2025-08-20', 'enrolled');
