<?php
// notification_manager.php
require_once 'config.php';

class NotificationManager {
    private $conn;
    
    public function __construct() {
        $this->conn = connectDB();
        // Ensure notifications table exists
        $this->ensureNotificationsTableExists();
    }
    
    /**
     * Ensure notifications table exists
     */
    private function ensureNotificationsTableExists() {
        $result = $this->conn->query("SHOW TABLES LIKE 'notifications'");
        if ($result->num_rows === 0) {
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
            
            if (!$this->conn->query($sql)) {
                error_log("Failed to create notifications table: " . $this->conn->error);
            } else {
                error_log("Successfully created notifications table");
            }
        }
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($user_id) {
        // Check if notifications table exists first
        $this->ensureNotificationsTableExists();
        
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error in getUnreadCount: " . $this->conn->error);
            return 0;
        }
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] ?? 0;
    }
    
    /**
     * Notify about training application status change
     */
    public function notifyTrainingStatusChange($application_id, $new_status, $comments = '') {
        // Get application details
        $application = $this->getApplicationDetails('training', $application_id);
        if (!$application) {
            error_log("Cannot find training application with ID: $application_id");
            return false;
        }
        
        // Log the status change for debugging
        error_log("Training application status changed to $new_status for application ID: $application_id");
        
        // Determine message and notification type based on status
        $message = '';
        $type = 'review';
        
        switch ($new_status) {
            case 'pending_hr':
                // Notify HR users
                $result = $this->notifyRoleUsers('hr', 'training', $application_id, 
                    "Training application needs your review", 'review');
                error_log("Notified HR users about training application ID: $application_id, result: " . ($result ? "success" : "failure"));
                
                // Also notify admin users (fixed here)
                $admin_result = $this->notifyRoleUsers('admin', 'training', $application_id, 
                    "Training application needs admin review", 'review');
                error_log("Notified admin users about training application ID: $application_id, result: " . ($admin_result ? "success" : "failure"));
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'training',
                    $application_id,
                    "Your training application has been recommended by HOD and is pending HR review",
                    'approval'
                );
                break;
                
            case 'pending_gm':
                // Notify GM users
                $this->notifyRoleUsers('gm', 'training', $application_id, 
                    "Training application needs your approval", 'review');
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'training',
                    $application_id,
                    "Your training application has been processed by HR and is pending GM approval",
                    'review'
                );
                break;
                
            case 'approved':
                // Notify HR and admin about approval
                $hr_result = $this->notifyRoleUsers('hr', 'training', $application_id, 
                    "Training application has been approved by GM", 'approval');
                error_log("Notified HR users about training approval, result: " . ($hr_result ? "success" : "failure"));
                
                $admin_result = $this->notifyRoleUsers('admin', 'training', $application_id, 
                    "Training application has been approved by GM", 'approval');
                error_log("Notified admin users about training approval, result: " . ($admin_result ? "success" : "failure"));
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'training',
                    $application_id,
                    "Your training application has been approved by GM",
                    'approval'
                );
                break;
                
            case 'rejected':
                // Notify HR and admin about rejection
                $this->notifyRoleUsers('hr', 'training', $application_id, 
                    "Training application has been rejected. Reason: " . $comments, 'rejection');
                $this->notifyRoleUsers('admin', 'training', $application_id, 
                    "Training application has been rejected. Reason: " . $comments, 'rejection');
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'training',
                    $application_id,
                    "Your training application has been rejected. Reason: " . $comments,
                    'rejection'
                );
                break;
        }
        
        return true;
    }
    
    /**
     * Notify about GCR application status change
     */
    public function notifyGCRStatusChange($application_id, $new_status, $comments = '') {
        // Get application details
        $application = $this->getApplicationDetails('gcr', $application_id);
        if (!$application) {
            error_log("Cannot find GCR application with ID: $application_id");
            return false;
        }
        
        // Log the status change for debugging
        error_log("GCR application status changed to $new_status for application ID: $application_id");
        
        // Determine message and notification type based on status
        switch ($new_status) {
            case 'pending_gm':
                // Notify GM users
                $this->notifyRoleUsers('gm', 'gcr', $application_id, 
                    "GCR application needs your approval", 'review');
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'gcr',
                    $application_id,
                    "Your GCR application has been verified by HR and is pending GM approval",
                    'review'
                );
                break;
                
            case 'pending_hr2':
                // Notify HR users
                $result = $this->notifyRoleUsers('hr', 'gcr', $application_id, 
                    "GCR application needs final recording", 'review');
                error_log("Notified HR users about GCR application ID: $application_id for final recording, result: " . ($result ? "success" : "failure"));
                
                // Notify admin users as well
                $admin_result = $this->notifyRoleUsers('admin', 'gcr', $application_id, 
                    "GCR application needs final admin recording", 'review');
                error_log("Notified admin users about GCR final recording, result: " . ($admin_result ? "success" : "failure"));
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'gcr',
                    $application_id,
                    "Your GCR application has been approved by GM and is pending final HR recording",
                    'approval'
                );
                break;
                
            case 'approved':
                // Notify HR and admin about approval
                $hr_result = $this->notifyRoleUsers('hr', 'gcr', $application_id, 
                    "GCR application has been fully approved and recorded", 'approval');
                error_log("Notified HR users about GCR approval, result: " . ($hr_result ? "success" : "failure"));
                
                // Notify admin users with proper error handling
                $admin_result = $this->notifyRoleUsers('admin', 'gcr', $application_id, 
                    "GCR application has been fully approved and recorded", 'approval');
                error_log("Notified admin users about GCR approval, result: " . ($admin_result ? "success" : "failure"));
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'gcr',
                    $application_id,
                    "Your GCR application has been fully approved and recorded",
                    'approval'
                );
                break;
                
            case 'rejected':
                // Notify HR and admin about rejection
                $this->notifyRoleUsers('hr', 'gcr', $application_id, 
                    "GCR application has been rejected. Reason: " . $comments, 'rejection');
                $this->notifyRoleUsers('admin', 'gcr', $application_id, 
                    "GCR application has been rejected. Reason: " . $comments, 'rejection');
                
                // Notify applicant
                $this->createNotification(
                    $application['user_id'],
                    'gcr',
                    $application_id,
                    "Your GCR application has been rejected. Reason: " . $comments,
                    'rejection'
                );
                break;
        }
        
        return true;
    }
    
    /**
     * Notify admins about new application
     */
    public function notifyAdminsNewApplication($type, $application_id, $data) {
        $title = '';
        $role_to_notify = '';
        
        if ($type === 'training') {
            $title = $data['programme_title'] ?? 'Training Application';
            $role_to_notify = 'hod';
            $message = "New training application for '$title' requires your review";
            
            // Also notify HR and admin - fixed explicit calls with better logging
            $hr_result = $this->notifyRoleUsers('hr', $type, $application_id, 
                "New training application for '$title' submitted", 'submission');
            error_log("Notified HR users about new training application, result: " . ($hr_result ? "success" : "failure"));
            
            $admin_result = $this->notifyRoleUsers('admin', $type, $application_id, 
                "New training application for '$title' submitted", 'submission');
            error_log("Notified admin users about new training application, result: " . ($admin_result ? "success" : "failure"));
            
        } else { // GCR
            $year = $data['year'] ?? date('Y');
            $days = $data['days_requested'] ?? '0';
            $role_to_notify = 'hr';
            $message = "New GCR application for $year ($days days) requires your verification";
            
            // Also notify admin - with better logging
            $admin_result = $this->notifyRoleUsers('admin', $type, $application_id, 
                "New GCR application for $year ($days days) submitted", 'submission');
            error_log("Notified admin users about new GCR application, result: " . ($admin_result ? "success" : "failure"));
        }
        
        // Notify appropriate role users and log the result
        $result = $this->notifyRoleUsers($role_to_notify, $type, $application_id, $message, 'submission');
        error_log("Notified $role_to_notify users about new $type application ID: $application_id, result: " . ($result ? "success" : "failure"));
        
        return $result;
    }
    
    /**
     * Notify all users with a specific role - public method
     * 
     * @param string $role The role to notify
     * @param string $app_type The application type
     * @param int $app_id The application ID
     * @param string $message The notification message
     * @param string $notification_type The notification type
     * @return bool Success or failure
     */
    public function notifyRoleUsers($role, $app_type, $app_id, $message, $notification_type = 'review') {
        $users = $this->getUsersByRole($role);
        if (empty($users)) {
            error_log("No users found with role: $role");
            return false;
        }
        
        error_log("Found " . count($users) . " users with role '$role' to notify about $app_type application ID: $app_id");
        
        $success = true;
        foreach ($users as $user) {
            error_log("Sending notification to user ID: " . $user['id'] . ", Name: " . $user['name'] . ", Role: $role");
            
            $result = $this->createNotification(
                $user['id'],
                $app_type,
                $app_id,
                $message,
                $notification_type
            );
            
            if (!$result) {
                error_log("Failed to create notification for user " . $user['id'] . " (" . $user['name'] . ")");
                $success = false;
            } else {
                error_log("Successfully created notification for user " . $user['id'] . " (" . $user['name'] . ")");
            }
        }
        
        return $success;
    }
    
    /**
     * Create a notification record
     */
    private function createNotification($user_id, $app_type, $app_id, $message, $notification_type = 'review') {
        // Ensure notifications table exists
        $this->ensureNotificationsTableExists();
        
        // Validate user exists
        if (!$this->userExists($user_id)) {
            error_log("Cannot create notification: User ID $user_id does not exist");
            return false;
        }
        
        $sql = "INSERT INTO notifications (
                    user_id, 
                    application_type, 
                    application_id, 
                    message, 
                    notification_type, 
                    is_read, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in createNotification: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('isiss', 
            $user_id,
            $app_type,
            $app_id,
            $message,
            $notification_type
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to create notification for user $user_id: " . $stmt->error);
        } else {
            // Log successful notification creation
            error_log("Created notification for user $user_id: $message");
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Check if user exists
     */
    private function userExists($user_id) {
        $sql = "SELECT 1 FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in userExists: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        if (!$exists) {
            error_log("User ID $user_id does not exist in the database");
        }
        
        return $exists;
    }
    
    /**
     * Get application details
     */
    private function getApplicationDetails($type, $id) {
        $table = ($type === 'training') ? 'training_applications' : 'gcr_applications';
        
        $sql = "SELECT * FROM $table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in getApplicationDetails: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $app = $result->fetch_assoc();
        $stmt->close();
        
        if (!$app) {
            error_log("Application not found in getApplicationDetails. Type: $type, ID: $id");
        }
        
        return $app;
    }
    
    /**
     * Get users by role
     */
    private function getUsersByRole($role) {
        // Check if users table exists and has the role column
        $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($result->num_rows === 0) {
            error_log("Users table doesn't have a 'role' column");
            return [];
        }
        
        $sql = "SELECT id, name FROM users WHERE LOWER(role) = LOWER(?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in getUsersByRole: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param('s', $role);
        if (!$stmt->execute()) {
            error_log("SQL Execute Error in getUsersByRole: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        error_log("Found " . count($users) . " users with role: $role");
        if (count($users) === 0) {
            // Log all existing users and their roles for debugging
            $all_users = $this->getAllUsers();
            error_log("All users in database: " . json_encode($all_users));
        }
        
        return $users;
    }
    
    /**
     * Get all users for debugging
     */
    private function getAllUsers() {
        $sql = "SELECT id, name, role FROM users";
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("SQL Error in getAllUsers: " . $this->conn->error);
            return [];
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}