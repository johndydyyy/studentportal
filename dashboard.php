<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$photo = '';

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: logout.php");
        exit();
    }
    
    // Use photo column if exists, otherwise use profile_image
    $photo = $user['photo'] ?? $user['profile_image'] ?? '';
    // Don't use default.jpg as a real photo
    if ($photo === 'default.jpg') {
        $photo = '';
    }
    
    // Get enrolled courses count
    $courseStmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'enrolled'");
    $courseStmt->execute([$user_id]);
    $courseCount = $courseStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent enrollments
    $recentStmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, c.schedule, e.enrolled_at 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? 
        ORDER BY e.enrolled_at DESC 
        LIMIT 5
    ");
    $recentStmt->execute([$user_id]);
    $recentCourses = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $courseCount = 0;
    $recentCourses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d9488;  /* Teal-600 */
            --primary-hover: #0f766e;  /* Teal-700 */
            --secondary-color: #047857; /* Emerald-700 */
            --accent-color: #d97706;   /* Amber-600 */
            --text-primary: #1f2937;   /* Gray-800 */
            --text-secondary: #4b5563;  /* Gray-600 */
            --border-color: #e5e7eb;   /* Gray-200 */
            --input-focus: #a7f3d0;    /* Emerald-200 */
            --error-color: #dc2626;    /* Red-600 */
            --success-color: #059669;  /* Green-600 */
            --light-bg: #f0fdfa;       /* Teal-50 */
        }
        
        body {
            background: linear-gradient(rgba(13, 148, 136, 0.05), rgba(4, 120, 87, 0.05)), 
                        url('images/sjitbackground.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card.secondary {
            background: linear-gradient(135deg, var(--accent-color) 0%, #f59e0b 100%);
        }

        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="flex items-center">
                <img src="images/sjitlogo.png" alt="SJIT" class="h-10 mr-2">
                <div>
                    <h2 class="text-lg font-bold">Student Portal</h2>
                    <p class="text-xs text-teal-100">SJIT</p>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav mt-4">
            <a href="dashboard.php" class="active">
                <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
            </a>
            <a href="myprofile.php">
                <i class="fas fa-user-circle mr-3"></i>My Profile
            </a>
            <a href="schedule.php">
                <i class="fas fa-calendar-alt mr-3"></i>Class Schedule
            </a>
            <a href="logout.php" class="mt-4 border-t border-teal-800 pt-4">
                <i class="fas fa-sign-out-alt mr-3"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="flex items-center">
                <button class="md:hidden mr-4 text-gray-600" onclick="toggleSidebar()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($student_id); ?></p>
                </div>
                <?php if ($photo && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Profile" class="h-10 w-10 rounded-full object-cover border-2 border-teal-600">
                <?php else: ?>
                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-teal-600 to-emerald-700 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Welcome Card -->
        <div class="card">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h2>
                    <p class="text-gray-600">Here's an overview of your academic progress.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <i class="fas fa-graduation-cap text-6xl text-teal-600 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-teal-100 text-sm mb-1">Enrolled Courses</p>
                        <h3 class="text-3xl font-bold"><?php echo $courseCount; ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-book text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card secondary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100 text-sm mb-1">Upcoming Classes</p>
                        <h3 class="text-3xl font-bold"><?php echo count($recentCourses); ?></h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm mb-1">Academic Status</p>
                        <h3 class="text-2xl font-bold">Active</h3>
                    </div>
                    <div class="bg-white bg-opacity-20 p-4 rounded-full">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Courses -->
        <div class="card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Recent Enrollments</h3>
                <a href="schedule.php" class="text-teal-600 hover:text-teal-700 text-sm font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            
            <?php if (count($recentCourses) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($recentCourses as $course): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($course['course_code']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                    <div class="mt-2 flex items-center text-xs text-gray-500">
                                        <i class="fas fa-clock mr-2"></i>
                                        <span><?php echo htmlspecialchars($course['schedule'] ?? 'Schedule TBA'); ?></span>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-xs font-medium">
                                    Enrolled
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-book-open text-4xl mb-3 opacity-50"></i>
                    <p>No courses enrolled yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="myprofile.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors">
                    <i class="fas fa-user-edit text-2xl text-teal-600 mb-2"></i>
                    <span class="text-sm font-medium text-gray-700">Edit Profile</span>
                </a>
                <a href="schedule.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors">
                    <i class="fas fa-calendar text-2xl text-teal-600 mb-2"></i>
                    <span class="text-sm font-medium text-gray-700">View Schedule</span>
                </a>
                <a href="#" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors">
                    <i class="fas fa-file-download text-2xl text-teal-600 mb-2"></i>
                    <span class="text-sm font-medium text-gray-700">Download Forms</span>
                </a>
                <a href="#" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:border-teal-500 hover:bg-teal-50 transition-colors">
                    <i class="fas fa-question-circle text-2xl text-teal-600 mb-2"></i>
                    <span class="text-sm font-medium text-gray-700">Help & Support</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>
