<?php
session_start();
require_once 'config.php';

$error = '';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch(PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>
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
            background: linear-gradient(rgba(13, 148, 136, 0.9), rgba(4, 120, 87, 0.9)), 
                        url('images/sjitbackground.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            transform: rotate(30deg);
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
            z-index: 0;
        }

        .form-container {
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            min-height: 600px;
            display: flex;
            position: relative;
            z-index: 1;
        }

        /* Sidebar Styles */
        .form-sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            flex: 1;
        }

        .form-sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }

        .form-sidebar h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            position: relative;
            line-height: 1.2;
        }

        .form-sidebar p {
            opacity: 0.95;
            line-height: 1.7;
            margin-bottom: 2.5rem;
            font-size: 1.05rem;
            position: relative;
        }

        /* Form Content */
        .form-content {
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
            background-color: white;
        }

        .form-content h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.75rem;
            position: relative;
            display: inline-block;
        }

        .form-content h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1.25rem 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #fff;
            color: var(--text-primary);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
            opacity: 1;
        }

        /* Input with icon */
        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
        }

        .input-with-padding {
            padding-left: 2.75rem !important;
        }

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s ease;
            background: none;
            border: none;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3), 0 4px 6px -2px rgba(79, 70, 229, 0.1);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-block {
            width: 100%;
            padding: 1rem;
            font-size: 1.05rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-error {
            background-color: #fef2f2;
            border-left: 4px solid var(--error-color);
            color: var(--error-color);
        }

        .alert-success {
            background-color: #ecfdf5;
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }

        /* Login link */
        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.25rem;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .form-container {
                max-width: 90%;
            }
        }

        @media (max-width: 768px) {
            .form-container {
                flex-direction: column;
                margin: 1rem auto;
                max-width: 95%;
            }
            
            .form-sidebar {
                padding: 2.5rem 2rem;
                text-align: center;
            }
            
            .form-sidebar h1 {
                font-size: 2rem;
            }
            
            .form-content {
                padding: 2.5rem 2rem;
            }
            
            .form-content h2 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .form-sidebar {
                padding: 2rem 1.5rem;
            }
            
            .form-content {
                padding: 2rem 1.5rem;
            }
            
            .form-sidebar h1 {
                font-size: 1.75rem;
            }
            
            .form-content h2 {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 0.75rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="form-container flex flex-col md:flex-row">
            <!-- Left Sidebar -->
            <div class="form-sidebar hidden md:block md:w-2/5">
                <div class="text-center relative z-10">
                    <div class="mb-8">
                        <img src="images/sjitlogo.png" alt="Saint Joseph Institute of Technology" class="h-20 mx-auto mb-4">
                        <h1 class="text-2xl font-bold text-white">Saint Joseph Institute of Technology</h1>
                        <p class="text-teal-100 mt-2">Student Portal</p>
                    </div>
                    <p class="mb-6">Access your educational resources, grades, and more in one place.</p>
                    <div class="flex justify-center mb-8">
                        <i class="fas fa-graduation-cap text-6xl opacity-75"></i>
                    </div>
                    <div class="mt-6 pt-4 border-t border-teal-100">
                        <p class="text-white mb-4">Don't have an account?</p>
                        <a href="signup.php" class="inline-block w-full bg-white text-teal-700 hover:bg-gray-100 font-medium py-2 px-4 rounded-md text-center transition duration-200">
                            Create an Account
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Login Form -->
            <div class="w-full md:w-3/5 p-8 bg-white">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Student Login</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php" class="space-y-4">
                    <div class="form-group">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 focus:outline-none transition duration-200"
                                   placeholder="Enter your email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 focus:outline-none transition duration-200 pr-10"
                                   placeholder="Enter your password">
                            <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember_me" type="checkbox" 
                                   class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-6">
                        <button type="submit" class="w-full bg-gradient-to-r from-teal-600 to-emerald-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-teal-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                        </button>
                    </div>
                    
                    <!-- Sign Up Link -->
                    <div class="text-center mt-4">
                        <p class="text-sm text-gray-600">
                            Don't have an account? 
                            <a href="signup.php" class="font-medium text-teal-600 hover:text-teal-700">
                                Sign up now
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Auto-focus email field on page load
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>
