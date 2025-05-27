<?php
// login.php - User authentication page

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Debug session information
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Session variables before login: " . print_r($_SESSION, true));

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Check for session expired error
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error_message = 'Your session has expired. Please log in again.';
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $conn = connectDB();
        
        // Fetch user by username
        $sql = "SELECT id, username, password, name, role, position, department FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Debug information
            error_log("Login attempt for user: " . $username);
            error_log("Stored password hash: " . $user['password']);
            error_log("Password verification result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Clear any existing session data
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION = array(); // Clear existing session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_position'] = $user['position'];
                $_SESSION['user_department'] = $user['department'];
                $_SESSION['created'] = time();
                
                // Debug session variables after setting
                error_log("Session variables after login: " . print_r($_SESSION, true));
                
                // Verify session variables were set
                if (!isLoggedIn()) {
                    $error_message = 'Failed to establish session. Please try again.';
                    error_log("Session verification failed. Session variables: " . print_r($_SESSION, true));
                    session_destroy();
                } else {
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                // Add a small delay to prevent brute force attacks
                sleep(1);
                $error_message = 'Invalid password. Please try again.';
            }
        } else {
            // Add a small delay to prevent brute force attacks
            sleep(1);
            $error_message = 'User not found. Please check your username.';
        }
        
        $stmt->close();
        $conn->close();
    }
}

// Page title
$page_title = 'Login - SMA Forms System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f8fa;
        }
        .login-container {
            max-width: 450px;
            margin: 100px auto;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 5px;
            height: 50px;
        }
        .btn-login {
            height: 50px;
            border-radius: 5px;
        }
        .version {
            text-align: center;
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="logo-container">
            <img src="assets/images/sma-logo.png" alt="SMA Logo" class="logo">
        </div>
        
        <div class="card login-card">
            <div class="login-header">
                <h4 class="mb-0">SMA Forms System</h4>
            </div>
            <div class="login-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="version">
            Version 1.0.0 &copy; <?php echo date('Y'); ?> Sarawak Multimedia Authority
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>