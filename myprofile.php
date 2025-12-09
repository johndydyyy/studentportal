<?php
session_start();
require_once 'config.php';

// Check for success message
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']); // Clear the message after displaying
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_id'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$middle_name = '';
$email = $_SESSION['email'] ?? '';
$home_address = '';
$home_phone = '';
$date_of_birth = '';
$gender = '';
$place_of_birth = '';
$religion = '';
$guardian_first_name = '';
$guardian_last_name = '';
$guardian_address = '';
$photo = '';
$success = '';
$error = '';

// Handle form submission
// Handle profile photo removal first
if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
    try {
        // Get current photo path
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_photo = $stmt->fetchColumn();
        
        // Delete the photo file if it's not the default and exists
        if ($current_photo && $current_photo !== 'default.jpg' && file_exists($current_photo)) {
            @unlink($current_photo);
        }
        
        // Update database with default photo
        $updateStmt = $pdo->prepare("UPDATE users SET profile_image = 'default.jpg' WHERE id = ?");
        $updateStmt->execute([$user_id]);
        
        // Update session
        $_SESSION['profile_image'] = 'default.jpg';
        $photo = 'default.jpg';
        
        $success = "Profile photo removed successfully!";
        
    } catch (PDOException $e) {
        error_log("Error removing profile photo: " . $e->getMessage());
        $error = "An error occurred while removing your profile photo. Please try again.";
    }
}
// Handle profile photo upload
elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        // Check for upload errors
        $upload_error = $_FILES['profile_photo']['error'];
        if ($upload_error !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize directive in php.ini (max " . ini_get('upload_max_filesize') . ")",
                UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE directive in HTML form",
                UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
                UPLOAD_ERR_NO_FILE => "No file was uploaded",
                UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk. Please check folder permissions.",
                UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
            ];
            $error = $error_messages[$upload_error] ?? "Unknown upload error (Code: $upload_error)";
        } else {
            // Process file upload
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_photo']['type'];
            $file_size = $_FILES['profile_photo']['size'];
            $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
            
            // Verify file type
            if (!in_array($file_type, $allowed_types)) {
                $error = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
            } 
            // Verify file size
            elseif ($file_size > $max_file_size) {
                $error = "File too large. Please upload an image under 5MB.";
            } 
            // Proceed with upload
            else {
                $upload_dir = 'uploads/profiles/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $error = "Failed to create upload directory. Please check folder permissions.";
                    }
                }
                
                // Check if directory is writable
                if (empty($error) && !is_writable($upload_dir)) {
                    $error = "Upload directory is not writable. Please check folder permissions (directory: " . realpath($upload_dir) . ")";
                }
                
                if (empty($error)) {
                    // Generate unique filename
                    $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                    $photo_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $photo_path = $upload_dir . $photo_filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $photo_path)) {
                        try {
                            // Get old photo path before updating
                            $oldPhotoStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                            $oldPhotoStmt->execute([$user_id]);
                            $oldPhoto = $oldPhotoStmt->fetchColumn();
                            
                            // Update database with relative path
                            $relative_photo_path = 'uploads/profiles/' . $photo_filename;
                            $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                            $updateStmt->execute([$relative_photo_path, $user_id]);
                            
                            // Verify update was successful
                            $verifyStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
                            $verifyStmt->execute([$user_id]);
                            $updatedPhoto = $verifyStmt->fetchColumn();
                            
                            if ($updatedPhoto === $relative_photo_path) {
                                // Delete old photo if exists and is not default.jpg
                                if ($oldPhoto && $oldPhoto !== 'default.jpg' && $oldPhoto !== $relative_photo_path && file_exists($oldPhoto)) {
                                    @unlink($oldPhoto);
                                }
                                
                                $success = "Profile picture updated successfully!";
                                $photo = $relative_photo_path;
                                
                                // Update session with new photo path
                                if (isset($_SESSION['profile_image'])) {
                                    $_SESSION['profile_image'] = $relative_photo_path;
                                }
                            } else {
                                throw new Exception("Failed to verify the photo update in the database.");
                            }
                        } catch (PDOException $e) {
                            error_log("Error updating photo in database: " . $e->getMessage());
                            error_log("SQL Error Code: " . $e->getCode());
                            error_log("SQL State: " . ($e->errorInfo[0] ?? 'N/A'));
                            $error = "Photo uploaded but failed to save to database. Error: " . $e->getMessage();
                            // Clean up uploaded file
                            if (isset($photo_path) && file_exists($photo_path)) {
                                @unlink($photo_path);
                            }
                        }
                    } else {
                        $error = "Failed to move uploaded file. Please check if the uploads directory has write permissions. ";
                        $error .= "Upload directory: " . realpath($upload_dir) . " ";
                        $error .= "Is writable: " . (is_writable($upload_dir) ? 'yes' : 'no');
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error uploading photo: " . $e->getMessage());
        $error = "An error occurred while uploading your photo: " . $e->getMessage();
    }
}

// Handle profile information update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_profile'])) {
    try {
        // Get form data
            $first_name = trim($_POST['first_name'] ?? $first_name);
            $last_name = trim($_POST['last_name'] ?? $last_name);
            $email = trim($_POST['email'] ?? $email);
            $middle_name = trim($_POST['middle_name'] ?? '');
            $home_address = trim($_POST['home_address'] ?? '');
            $home_phone = trim($_POST['home_phone'] ?? '');
            $date_of_birth = trim($_POST['date_of_birth'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $place_of_birth = trim($_POST['place_of_birth'] ?? '');
            $religion = trim($_POST['religion'] ?? '');
            $guardian_first_name = trim($_POST['guardian_first_name'] ?? '');
            $guardian_last_name = trim($_POST['guardian_last_name'] ?? '');
            $guardian_address = trim($_POST['guardian_address'] ?? '');
            
            // Update user data in database
            $updateStmt = $pdo->prepare("\n                UPDATE users \n                SET \n                    first_name = ?,\n                    last_name = ?,\n                    email = ?,\n                    middle_name = ?,\n                    home_address = ?,\n                    home_phone = ?,\n                    date_of_birth = ?,\n                    gender = ?,\n                    place_of_birth = ?,\n                    religion = ?,\n                    guardian_first_name = ?,\n                    guardian_last_name = ?,\n                    guardian_address = ?,\n                    updated_at = NOW()\n                WHERE id = ?\n            ");
            
            $updateStmt->execute([
                $first_name,
                $last_name,
                $email,
                $middle_name,
                $home_address,
                $home_phone,
                $date_of_birth,
                $gender,
                $place_of_birth,
                $religion,
                $guardian_first_name,
                $guardian_last_name,
                $guardian_address,
                $user_id
            ]);
            
            // Update session variables if needed
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['middle_name'] = $middle_name;
            
            // Set success message and redirect
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: myprofile.php");
            exit();
            
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $error = "An error occurred while updating your profile. Please try again.";
        }
    }
    
// Fetch student profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        $middle_name = $student['middle_name'] ?? '';
        $home_address = $student['home_address'] ?? $student['current_address'] ?? '';
        $home_phone = $student['home_phone'] ?? $student['phone'] ?? '';
        $date_of_birth = $student['date_of_birth'] ?? '';
        $gender = $student['gender'] ?? '';
        $place_of_birth = $student['place_of_birth'] ?? '';
        $religion = $student['religion'] ?? '';
        $guardian_first_name = $student['guardian_first_name'] ?? '';
        $guardian_last_name = $student['guardian_last_name'] ?? '';
        $guardian_address = $student['guardian_address'] ?? '';
        // Use photo column if exists, otherwise use profile_image
        $photo = $student['photo'] ?? $student['profile_image'] ?? '';
        // Don't use default.jpg as a real photo
        if ($photo === 'default.jpg' || empty($photo)) {
            $photo = '';
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching profile data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
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

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 2rem;
        }

        .photo-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            background: var(--light-bg);
        }

        .photo-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(13, 148, 136, 0.05);
        }

        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin: 0 auto;
        }

        .profile-photo {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
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
            <a href="myprofile.php" class="active">
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
                <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
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

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Profile Form -->
        <form method="POST" action="myprofile.php" enctype="multipart/form-data" class="max-w-5xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-teal-600 to-emerald-700 px-8 py-5">
                <h2 class="text-2xl font-bold text-white">Student Profile</h2>
                <p class="text-teal-100 mt-1">Update your personal and academic information</p>
            </div>
            
            <!-- Main Form Content -->
            <div class="p-8">
                <!-- Profile Picture Section -->
                <div class="mb-8">
                    <div class="flex flex-col items-center md:flex-row md:items-start space-y-4 md:space-y-0 md:space-x-8">
                        <!-- Profile Picture Upload -->
                        <div class="relative group cursor-pointer" onclick="document.getElementById('profile_photo').click()">
                            <?php if (!empty($photo) && file_exists($photo)): ?>
                                <img id="profile_preview" src="<?php echo $photo; ?>" alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md">
                            <?php else: ?>
                                <div id="profile_initials" class="w-32 h-32 rounded-full bg-gradient-to-r from-teal-500 to-emerald-600 flex items-center justify-center text-4xl text-white font-bold shadow-md">
                                    <?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 right-0 bg-white p-2 rounded-full border-2 border-teal-500 group-hover:bg-teal-50 transition-colors">
                                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="hidden" onchange="previewPhoto(this)">
                        </div>
                        
                        <!-- Upload Instructions -->
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Profile Photo</h3>
                            <p class="text-sm text-gray-600 mb-4">Click on the photo to upload a new one. JPG, PNG or GIF (Max 5MB).</p>
                            
                            <!-- File Info (shown after selection) -->
                            <div id="file_info" class="hidden bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                                <p class="text-sm">Selected file: <span id="file_name" class="font-medium"></span></p>
                                <p class="text-xs mt-1">Click "Save Changes" below to update your profile photo.</p>
                            </div>
                            
                            <?php if (!empty($photo)): ?>
                                <div class="mt-4">
                                    <button type="button" onclick="if(confirm('Are you sure you want to remove your profile photo?')) { document.getElementById('remove_photo').value = '1'; document.forms[0].submit(); }" class="text-xs text-red-600 hover:text-red-800 font-medium">
                                        <i class="fas fa-trash-alt mr-1"></i> Remove photo
                                    </button>
                                    <input type="hidden" id="remove_photo" name="remove_photo" value="0">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Information Section -->
                <div class="bg-gray-50 rounded-xl p-6 space-y-6">
                    <div class="flex items-center">
                        <div class="w-1.5 h-6 bg-teal-500 rounded-full mr-3"></div>
                        <h3 class="text-lg font-semibold text-gray-800">Personal Information</h3>
                    </div>

            <!-- Form Fields -->
                        <!-- Student ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Student ID <span class="text-red-500">*</span></label>
                            <input type="text" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>" 
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm bg-white transition duration-200" 
                                placeholder="Enter student ID" readonly>
                        </div>
                        
                        <!-- Name Fields -->
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                    placeholder="First name" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Middle Name</label>
                                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                    placeholder="Middle name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                    placeholder="Last name" required>
                            </div>
                        </div>
                        
                        <!-- Additional Personal Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of birth</label>
                                <div class="relative">
                                    <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth); ?>"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200">
                                </div>
                            </div>
                            <!-- Gender -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-venus-mars text-gray-400"></i>
                                    </div>
                                    <select name="gender" class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm appearance-none bg-white transition duration-200">
                                        <option value="" <?php echo empty($gender) ? 'selected' : ''; ?>>Select Gender</option>
                                        <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        <option value="Prefer not to say" <?php echo $gender === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <!-- Place of Birth -->
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Place of Birth</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                                    </div>
                                    <input type="text" name="place_of_birth" value="<?php echo htmlspecialchars($place_of_birth); ?>" 
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                        placeholder="City, Country">
                                </div>
                            </div>
                            <!-- Religion -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Religion</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-pray text-gray-400"></i>
                                    </div>
                                    <input type="text" name="religion" value="<?php echo htmlspecialchars($religion); ?>" 
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                        placeholder="Religion">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="bg-gray-50 rounded-xl p-6 space-y-6">
                            <div class="flex items-center">
                                <div class="w-1.5 h-6 bg-teal-500 rounded-full mr-3"></div>
                                <h3 class="text-lg font-semibold text-gray-800">Contact Information</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Email Address -->
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        </div>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                            placeholder="your.email@example.com" required>
                                    </div>
                                </div>
                                
                                <!-- Phone -->
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-phone text-gray-400"></i>
                                        </div>
                                        <input type="tel" name="home_phone" value="<?php echo htmlspecialchars($home_phone); ?>"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200"
                                            placeholder="Enter phone number">
                                    </div>
                                </div>

                                <!-- Home Address -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Home Address</label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3">
                                            <i class="fas fa-home text-gray-400"></i>
                                        </div>
                                        <textarea name="home_address" rows="3"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200"
                                            placeholder="Enter your complete home address"><?php echo htmlspecialchars($home_address); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Guardian Info -->
                        <div class="border-t border-gray-100 pt-5 mt-2">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Guardian Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Guardian First Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Guardian's First Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" name="guardian_first_name" value="<?php echo htmlspecialchars($guardian_first_name); ?>" 
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                            placeholder="Guardian's first name">
                                    </div>
                                </div>
                                
                                <!-- Guardian Last Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Guardian's Last Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" name="guardian_last_name" value="<?php echo htmlspecialchars($guardian_last_name); ?>" 
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200" 
                                            placeholder="Guardian's last name">
                                    </div>
                                </div>
                                
                                <!-- Guardian Address -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Guardian's Address</label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3">
                                            <i class="fas fa-home text-gray-400"></i>
                                        </div>
                                        <textarea name="guardian_address" rows="2"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-sm transition duration-200"
                                            placeholder="Enter guardian's complete address"><?php echo htmlspecialchars($guardian_address); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Buttons -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 mt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            Cancel
                        </a>
                        <button type="submit" name="save_profile" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </div>
        </form>
        </div>
    </div>

    <script>
        // Function to preview selected profile photo
        function previewPhoto(input) {
            const preview = document.getElementById('profile_preview');
            const initials = document.getElementById('profile_initials');
            const fileInfo = document.getElementById('file_info');
            const fileName = document.getElementById('file_name');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                // Show file info
                fileName.textContent = file.name;
                fileInfo.classList.remove('hidden');
                
                // Show preview if it's an image
                if (file.type.match('image.*')) {
                    reader.onload = function(e) {
                        if (preview) {
                            preview.src = e.target.result;
                            preview.classList.remove('hidden');
                        } else {
                            // Create preview element if it doesn't exist
                            const newPreview = document.createElement('img');
                            newPreview.id = 'profile_preview';
                            newPreview.src = e.target.result;
                            newPreview.alt = 'Profile Preview';
                            newPreview.className = 'w-32 h-32 rounded-full object-cover border-4 border-white shadow-md';
                            
                            const uploadArea = document.querySelector('.relative.group');
                            uploadArea.insertBefore(newPreview, uploadArea.firstChild);
                        }
                        
                        // Hide initials
                        if (initials) initials.classList.add('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            }
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photoPreview');
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Profile Photo Preview" class="photo-preview mb-3">
                        <p class="text-gray-600 mb-1">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>Photo selected
                        </p>
                        <p class="text-xs text-gray-500">Click to change</p>
                    `;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
