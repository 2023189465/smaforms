<?php
// test_notification.php - Create a test notification for HR users

require_once 'config.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: login.php');
    exit;
}

// Connect to database
$conn = connectDB();

// HR user IDs from your database
$hr_ids = [5, 6]; // The HR user IDs you confirmed

// Create a test notification for each HR user
$success = false;
foreach ($hr_ids as $hr_id) {
    $sql = "INSERT INTO notifications (
        user_id, application_type, application_id, message, 
        notification_type, is_read, created_at
    ) VALUES (?, 'training', 1, 'This is a test notification for HR', 'review', 0, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        continue;
    }
    
    $stmt->bind_param('i', $hr_id);
    
    if ($stmt->execute()) {
        $success = true;
        echo "Successfully created notification for HR user ID: $hr_id<br>";
    } else {
        echo "Failed to create notification for HR user ID: $hr_id: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

if ($success) {
    echo "<p>Test notifications created. Please check the HR users' notification page.</p>";
} else {
    echo "<p>Failed to create any test notifications.</p>";
}
?>