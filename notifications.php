<?php
// Updated notifications.php with additional debugging and error handling

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Debug information
error_log("Loading notifications for user_id: $user_id, role: $user_role");

// Handle marking notifications as read
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    markNotificationAsRead($notification_id, $user_id);
    
    // Redirect back to notifications page
    header('Location: notifications.php');
    exit;
}

// Handle marking all notifications as read
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] === 'true') {
    markAllNotificationsAsRead($user_id);
    
    // Redirect back to notifications page
    header('Location: notifications.php');
    exit;
}

// Handle clearing all notifications
if (isset($_GET['clear_all']) && $_GET['clear_all'] === 'true') {
    clearAllNotifications($user_id);
    
    // Redirect back to notifications page
    header('Location: notifications.php?cleared=true');
    exit;
}

// Connect to database
$conn = connectDB();

// Check if the notifications table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
    error_log("Notifications table exists in the database");
    
    // Check if there are any notifications in the database
    $count_query = "SELECT COUNT(*) as count FROM notifications";
    $count_result = $conn->query($count_query);
    if ($count_result && $row = $count_result->fetch_assoc()) {
        error_log("Total notifications in database: " . $row['count']);
    }
} else {
    error_log("Notifications table does not exist - creating it now");
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
        error_log("Error creating notifications table: " . $conn->error);
    } else {
        $table_exists = true;
        error_log("Successfully created notifications table");
    }
}

// Get all notifications for the current user
$notifications = [];
$unread_count = 0;

if ($table_exists) {
    // First get the count
    try {
        // Get unread count using direct query instead of function
        $count_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param('i', $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $unread_count = $count_row['count'] ?? 0;
        $count_stmt->close();
        
        error_log("Unread count from direct query: $unread_count");
    } catch (Exception $e) {
        error_log("Error counting unread notifications: " . $e->getMessage());
    }
    
    // Get all notifications using direct query
    try {
        $notifications_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
        $notifications_stmt = $conn->prepare($notifications_sql);
        $notifications_stmt->bind_param('i', $user_id);
        $notifications_stmt->execute();
        $notifications_result = $notifications_stmt->get_result();
        $notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
        $notifications_stmt->close();
        
        error_log("Retrieved " . count($notifications) . " notifications for user $user_id");

    } catch (Exception $e) {
        error_log("Error retrieving notifications: " . $e->getMessage());
    }
    
    // Enhance notifications with additional info
    foreach ($notifications as &$notification) {
        try {
            // Add application title based on type
            if ($notification['application_type'] === 'training') {
                $sql = "SELECT programme_title, reference_number FROM training_applications WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $notification['application_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($app = $result->fetch_assoc()) {
                        $notification['application_title'] = $app['programme_title'];
                        $notification['reference_number'] = $app['reference_number'];
                    } else {
                        $notification['application_title'] = 'Training Application #' . $notification['application_id'];
                    }
                    $stmt->close();
                }
            } elseif ($notification['application_type'] === 'gcr') {
                $sql = "SELECT year, reference_number FROM gcr_applications WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $notification['application_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($app = $result->fetch_assoc()) {
                        $notification['application_title'] = 'GCR Application (' . $app['year'] . ')';
                        $notification['reference_number'] = $app['reference_number'];
                    } else {
                        $notification['application_title'] = 'GCR Application #' . $notification['application_id'];
                    }
                    $stmt->close();
                }
            } elseif ($notification['application_type'] === 'evaluation') {
                $sql = "SELECT training_title FROM training_evaluations WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $notification['application_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($app = $result->fetch_assoc()) {
                        $notification['application_title'] = 'Training Evaluation: ' . $app['training_title'];
                    } else {
                        $notification['application_title'] = 'Training Evaluation #' . $notification['application_id'];
                    }
                    $stmt->close();
                }
            } else {
                $notification['application_title'] = ucfirst($notification['application_type']) . ' Application';
            }
        } catch (Exception $e) {
            error_log("Error enhancing notification " . $notification['id'] . ": " . $e->getMessage());
            $notification['application_title'] = ucfirst($notification['application_type']) . ' Application';
        }
    }
} else {
    error_log("Not retrieving notifications because table doesn't exist");
}

// Page title
$page_title = 'Notifications';

/**
 * Mark a notification as read
 */
function markNotificationAsRead($notification_id, $user_id) {
    $conn = connectDB();
    
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $notification_id, $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        error_log("Marked notification $notification_id as read. Affected rows: $affected");
    } else {
        error_log("Error preparing mark read statement: " . $conn->error);
    }
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsAsRead($user_id) {
    $conn = connectDB();
    
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        error_log("Marked all notifications as read for user $user_id. Affected rows: $affected");
    } else {
        error_log("Error preparing mark all read statement: " . $conn->error);
    }
}

/**
 * Clear all notifications for a user
 */
function clearAllNotifications($user_id) {
    $conn = connectDB();
    
    $sql = "DELETE FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        error_log("Cleared all notifications for user $user_id. Affected rows: $affected");
    } else {
        error_log("Error preparing clear all notifications statement: " . $conn->error);
    }
}

/**
 * Get notification badge color based on type
 */
function getNotificationBadgeClass($type) {
    switch ($type) {
        case 'submission':
            return 'bg-primary';
        case 'approval':
            return 'bg-success';
        case 'rejection':
            return 'bg-danger';
        case 'review':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}
?>

<?php include 'header.php'; ?>

<div class="container py-4">
    <?php if (isset($_GET['cleared']) && $_GET['cleared'] === 'true'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        All notifications have been cleared successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-bell me-2"></i> Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $unread_count; ?> unread</span>
                            <?php endif; ?>
                        </h4>
                        <div>
                            <?php if (!empty($notifications)): ?>
                                <a href="notifications.php?mark_all_read=true" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-check-all me-1"></i> Mark All as Read
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearAllModal">
                                    <i class="bi bi-trash me-1"></i> Clear All
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> 
            <strong>Notifications system is not yet set up.</strong> The notification table doesn't exist in the database. Please contact an administrator.
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted mb-3">
                            <i class="bi bi-bell-slash"></i>
                        </div>
                        <h4>No Notifications</h4>
                        <p class="text-muted">You don't have any notifications at the moment.</p>
                        <a href="dashboard.php" class="btn btn-primary mt-2">
                            <i class="bi bi-grid-1x2 me-2"></i> Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'list-group-item-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="notification-icon me-3">
                                        <?php if ($notification['application_type'] === 'training'): ?>
                                            <i class="bi bi-mortarboard fs-4 text-primary"></i>
                                        <?php elseif ($notification['application_type'] === 'evaluation'): ?>
                                            <i class="bi bi-clipboard-check fs-4 text-info"></i>
                                        <?php else: ?>
                                            <i class="bi bi-file-text fs-4 text-success"></i>
                                        <?php endif; ?>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge rounded-pill bg-danger me-2">New</span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($notification['application_title'] ?? ucfirst($notification['application_type']) . ' Application'); ?>
                                            </h5>
                                            <?php if (!empty($notification['reference_number'])): ?>
                                                <div class="text-muted mb-1">
                                                    <i class="bi bi-hash me-1"></i> <?php echo htmlspecialchars($notification['reference_number']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                                <i class="bi bi-check"></i> Mark as Read
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($notification['application_type'] === 'evaluation'): ?>
                                            <a href="http://localhost/smaforms/view_evaluation.php?id=<?php echo $notification['application_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye me-1"></i> View Evaluation
                                            </a>
                                        <?php else: ?>
                                            <a href="view_application.php?type=<?php echo $notification['application_type']; ?>&id=<?php echo $notification['application_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye me-1"></i> View Application
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clear All Confirmation Modal -->
<div class="modal fade" id="clearAllModal" tabindex="-1" aria-labelledby="clearAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearAllModalLabel">Clear All Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                </div>
                <p>Are you sure you want to clear all notifications? This action cannot be undone.</p>
                <p class="text-muted small">This will permanently delete all your notifications from the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="notifications.php?clear_all=true" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Clear All Notifications
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for notifications */
.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f8f9fa;
}

.list-group-item-action {
    transition: background-color 0.2s ease;
    padding: 1rem;
    border-left: 3px solid transparent;
}

.list-group-item-action:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.list-group-item-light {
    border-left: 3px solid #0d6efd;
}
</style>

<?php include 'footer.php'; ?>