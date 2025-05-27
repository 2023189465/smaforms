<?php
// Database configuration

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smaforms');

// Application settings
define('APP_NAME', 'SMA Forms System');
define('APP_URL', 'http://localhost/smaforms');
define('UPLOAD_DIR', 'uploads/');

// File upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_FILES', 5);
define('ALLOWED_FILE_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

// Status definitions
// Training workflow
define('TRAINING_STATUSES', [
    'pending_submission' => 'Draft',
    'pending_hod' => 'Pending HOD Approval',
    'pending_hr' => 'Pending HR Review',
    'pending_gm' => 'Pending GM Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
]);

// GCR workflow
define('GCR_STATUSES', [
    'pending_submission' => 'Draft',
    'pending_hr1' => 'Pending HR Verification',
    'pending_gm' => 'Pending GM Approval',
    'pending_hr2' => 'Pending HR Final Recording',
    'approved' => 'Approved',
    'rejected' => 'Rejected'
]);

// Email notification settings
define('MAIL_FROM', 'noreply@sma.gov.my');
define('MAIL_ADMIN', 'admin@sma.gov.my');

// Connect to database
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Initialize session with better configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start the session
    session_start();
    
    // Regenerate session ID to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Additional check for required session variables
    $required_vars = ['user_id', 'username', 'user_name', 'user_role'];
    foreach ($required_vars as $var) {
        if (!isset($_SESSION[$var])) {
            return false;
        }
    }
    
    return true;
}

// Check user role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if ($_SESSION['user_role'] === 'admin') {
        return true;  // Admin has access to all roles
    }
    
    return $_SESSION['user_role'] === $role;
}

// Generate reference number
function generateReferenceNumber($type) {
    $prefix = ($type === 'training') ? 'TRN' : 'GCR';
    $year = date('Y');
    $conn = connectDB();
    
    // Get the latest number
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(reference_number, '-', -1) AS UNSIGNED)) as max_num 
            FROM " . ($type === 'training' ? 'training_applications' : 'gcr_applications') . " 
            WHERE reference_number LIKE 'SMA/$prefix-$year-%'";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $next_num = ($row['max_num'] ?? 0) + 1;
    
    return "SMA/$prefix-$year-" . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

// Log application history
function logApplicationHistory($type, $app_id, $action, $status, $comments = '') {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO application_history 
            (application_type, application_id, action, status, performed_by, comments) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sissss', $type, $app_id, $action, $status, $user_id, $comments);
    $stmt->execute();
    $stmt->close();
}