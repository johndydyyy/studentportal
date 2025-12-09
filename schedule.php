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
$photo = '';

// Get user photo
try {
    $userStmt = $pdo->prepare("SELECT COALESCE(photo, profile_image) as photo FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    $photo = $userData['photo'] ?? '';
    // Don't use default.jpg as a real photo
    if ($photo === 'default.jpg') {
        $photo = '';
    }
} catch (PDOException $e) {
    error_log("Error fetching user photo: " . $e->getMessage());
    $photo = '';
}

// Fetch enrolled courses with schedule
$schedule = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.course_code, c.course_name, c.schedule, c.room, c.instructor, 
               e.grade, e.points, c.credits
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.student_id = ? AND e.status = 'enrolled'
        ORDER BY FIELD(SUBSTRING_INDEX(c.schedule, ' ', 1), 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                 SUBSTRING_INDEX(SUBSTRING_INDEX(c.schedule, ' ', 2), ' ', -1)
    ");
    $stmt->execute([$user_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching schedule: " . $e->getMessage();
}

// Group schedule by day
$scheduleByDay = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => [],
    'Sunday' => []
];

foreach ($schedule as $class) {
    $day = strtok($class['schedule'], ' ');
    if (array_key_exists($day, $scheduleByDay)) {
        $scheduleByDay[$day][] = $class;
    }
}

// Current week dates
$today = new DateTime();
$weekStart = clone $today;
$weekStart->modify('monday this week');
$weekEnd = clone $weekStart;
$weekEnd->modify('sunday this week');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Student Portal</title>
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

        .day-card {
            min-height: 200px;
        }

        .current-day {
            border-left: 4px solid var(--primary-color);
            background-color: var(--light-bg);
        }

        .class-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
            </a>
            <a href="myprofile.php">
                <i class="fas fa-user-circle mr-3"></i>My Profile
            </a>
            <a href="schedule.php" class="active">
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
                <h1 class="text-2xl font-bold text-gray-800">My Class Schedule</h1>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="window.print()" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
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

        <!-- Week Navigation -->
        <div class="card">
            <div class="flex items-center justify-between">
                <button class="p-2 rounded-full hover:bg-gray-100">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h2 class="text-xl font-semibold">
                    <?php echo $weekStart->format('F j, Y'); ?> - <?php echo $weekEnd->format('F j, Y'); ?>
                </h2>
                <button class="p-2 rounded-full hover:bg-gray-100">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Schedule Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4">
            <?php 
            $currentDay = strtolower(date('l'));
            foreach ($scheduleByDay as $day => $classes): 
                $isCurrentDay = strtolower($day) === $currentDay;
            ?>
                <div class="card day-card <?php echo $isCurrentDay ? 'current-day' : ''; ?>">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold"><?php echo $day; ?></h3>
                        <?php if ($isCurrentDay): ?>
                            <span class="px-2 py-1 bg-teal-100 text-teal-800 text-xs font-medium rounded-full">Today</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($classes) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($classes as $class): 
                                $time = substr($class['schedule'], strlen($day) + 1);
                            ?>
                                <div class="class-card p-3 border border-gray-200 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($class['course_code']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($class['course_name']); ?></p>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500"><?php echo htmlspecialchars($time); ?></span>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-gray-100 text-sm">
                                        <p class="text-gray-600">
                                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                            <?php echo htmlspecialchars($class['room'] ?? 'Room TBA'); ?>
                                        </p>
                                        <p class="text-gray-600 mt-1">
                                            <i class="fas fa-chalkboard-teacher text-teal-600 mr-2"></i>
                                            <?php echo htmlspecialchars($class['instructor'] ?? 'Instructor TBA'); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-gray-400 py-6">
                            <i class="far fa-calendar-times text-3xl mb-2"></i>
                            <p>No classes scheduled</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Add active class to current day in navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentDayCard = document.querySelector('.current-day');
            if (currentDayCard) {
                currentDayCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>
