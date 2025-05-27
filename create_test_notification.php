<?php
// create_test_notification.php - Creates a test notification for debugging purposes

require_once 'config.php';

// Only allow admin users to access this script
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: login.php');
    exit;
}

// Check if the notifications table exists
$conn = connectDB();
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Create the notifications table if it doesn't exist
if (!$table_exists) {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        application_type VARCHAR(20) NOT NULL,
        application_id INT NOT NULL,
        message TEXT NOT NULL,
        notification_type VARCHAR(20) DEFAULT 'review',
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (application_id),
        INDEX (is_read)
    )";
    
    if (!$conn->query($sql)) {
        die("Error creating notifications table: " . $conn->error);
    } else {
        $table_exists = true;
    }
}

// Get form data
$message = isset($_POST['message']) ? $_POST['message'] : 'This is a test notification';
$type = isset($_POST['type']) ? $_POST['type'] : 'training';
$user_id = $_SESSION['user_id']; // Send to current user (admin)

// Find a random application of the given type
if ($type === 'training') {
    $sql = "SELECT id FROM training_applications ORDER BY RAND() LIMIT 1";
} else {
    $sql = "SELECT id FROM gcr_applications ORDER BY RAND() LIMIT 1";
}

$result = $conn->query($sql);
$application_id = 1; // Default if no applications found

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $application_id = $row['id'];
}

// Create the notification
$sql = "INSERT INTO notifications (
    user_id, application_type, application_id, message, notification_type, is_read
) VALUES (?, ?, ?, ?, 'review', 0)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param('isis', $user_id, $type, $application_id, $message);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    header('Location: notifications.php?success=Test notification created successfully');
} else {
    header('Location: notifications.php?error=Failed to create test notification');
}
?>