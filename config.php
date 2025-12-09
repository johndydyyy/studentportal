<?php
// Error reporting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'student_portal';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Set character set
try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
    
} catch(PDOException $e) {
    // Log the error instead of displaying it to the user
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display a user-friendly message
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        die("Database connection error. Please try again later or contact the administrator.");
    } else {
        header('Location: /error.php?code=db_connection');
        exit();
    }
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Define base URL and other constants
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/studentportal');
define('SITE_NAME', 'Student Portal');

// Security function to prevent XSS
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
