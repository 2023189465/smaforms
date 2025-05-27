<?php
// Handles workflow transitions for both form types

require_once 'config.php';
require_once 'notification_manager.php';

class WorkflowManager {
    private $conn;
    private $notification_manager;

    public function __construct() {
        $this->conn = connectDB();
        $this->notification_manager = new NotificationManager();
    }
    
    /**
     * Move Training application to next status
     */
    public function processTrainingWorkflow($application_id, $decision, $data = []) {
        // Validate input parameters
        if (empty($application_id)) {
            error_log("Invalid application ID in processTrainingWorkflow");
            return false;
        }

        // Get current application status
        $sql = "SELECT status FROM training_applications WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error in processTrainingWorkflow: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if (!$application) {
            error_log("Application not found with ID: $application_id");
            return false;
        }
        
        $current_status = $application['status'];
        $next_status = $this->getNextTrainingStatus($current_status, $decision);
        
        if (!$next_status) {
            error_log("No valid next status found for current status: $current_status and decision: $decision");
            return false;
        }
        
        // Prepare the update based on current status
        $result = false;
        switch ($current_status) {
            case 'pending_hod':
                $result = $this->updateTrainingHODDecision($application_id, $decision, $next_status, $data);
                break;
                
            case 'pending_hr':
                $result = $this->updateTrainingHRDecision($application_id, $next_status, $data);
                break;
                
            case 'pending_gm':
                $result = $this->updateTrainingGMDecision($application_id, $decision, $next_status, $data);
                break;
                
            default:
                error_log("Invalid current status for processing: $current_status");
                return false;
        }
        
        // Send notification if update was successful
        if ($result) {
            $this->notification_manager->notifyTrainingStatusChange($application_id, $next_status, $data['comments'] ?? '');
        }
        
        return $result;
    }

    /**
     * Create a notification for workflow status changes
     * 
     * @param string $type Application type (training or gcr)
     * @param int $application_id The application ID
     * @param int $user_id User to notify
     * @param string $message Notification message
     * @param string $notification_type Type of notification (submission, approval, review, etc)
     * @return bool Success or failure
     */
    private function createNotification($type, $application_id, $user_id, $message, $notification_type = 'review') {
        $conn = $this->conn;
        
        $sql = "INSERT INTO notifications (
                    user_id, application_type, application_id, message, notification_type, is_read, created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isiss', 
            $user_id,
            $type,
            $application_id,
            $message,
            $notification_type
        );
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Move GCR application to next status
     */
    public function processGCRWorkflow($application_id, $decision = null, $data = []) {
        // Validate input parameters
        if (empty($application_id)) {
            error_log("Invalid application ID in processGCRWorkflow");
            return false;
        }

        // Get current application status
        $sql = "SELECT status FROM gcr_applications WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error in processGCRWorkflow: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if (!$application) {
            error_log("Application not found with ID: $application_id");
            return false;
        }
        
        $current_status = $application['status'];
        $next_status = $this->getNextGCRStatus($current_status, $decision);

        error_log("GCR Workflow - Current status: $current_status, Decision: " . ($decision ?: 'null') . ", Next status: " . ($next_status ?: 'none'));
        
        if (!$next_status) {
            error_log("No valid next status found for current status: $current_status and decision: $decision");
            return false;
        }
        
        // Prepare the update based on current status
        $result = false;
        switch ($current_status) {
            case 'pending_hr1':
                $result = $this->updateGCRHR1Decision($application_id, $next_status, $data);
                break;
                
            case 'pending_gm':
                $result = $this->updateGCRGMDecision($application_id, $decision, $next_status, $data);
                break;
                
            case 'pending_hr2':
                $result = $this->updateGCRHR2Decision($application_id, $next_status, $data);
                break;
                
            // Add these new cases
            case 'pending_hr3':
                $result = $this->updateGCRHR3Decision($application_id, $next_status, $data);
                break;
                
            case 'pending_gm_final':
                $result = $this->updateGCRGMFinalDecision($application_id, $next_status, $data);
                break;
                
            default:
                error_log("Invalid current status for processing: $current_status");
                return false;
        }
        
        // Send notification if update was successful
        if ($result) {
            $this->notification_manager->notifyGCRStatusChange($application_id, $next_status, $data['comments'] ?? '');
        }
        
        return $result;
    }
    
    /**
     * Update HR1 decision for GCR application
     */
    private function updateGCRHR1Decision($application_id, $next_status, $data) {
        // Add debugging
        error_log("Updating GCR HR1 Decision - App ID: $application_id, Next Status: $next_status");
        error_log("HR1 data: " . print_r($data, true));
        
        $sql = "UPDATE gcr_applications 
                SET status = ?, 
                    hr1_id = ?, 
                    hr1_signature = ?, 
                    hr1_date = CURRENT_DATE
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error in updateGCRHR1Decision: " . $this->conn->error);
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        $signature = isset($data['signature']) ? $data['signature'] : '';
        
        $stmt->bind_param('sisi', 
            $next_status, 
            $user_id, 
            $signature,
            $application_id
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("SQL Execute Error in updateGCRHR1Decision: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows > 0) {
            // Log successful update
            logApplicationHistory('gcr', $application_id, 'HR verified', $next_status, $data['comments'] ?? '');
            error_log("GCR HR1 Decision updated successfully - New status: $next_status");
            return true;
        }
        
        error_log("No rows updated in updateGCRHR1Decision for application $application_id");
        return false;
    }
    
    /**
     * Update GM decision for GCR application
     */
    private function updateGCRGMDecision($application_id, $decision, $next_status, $data) {
        $sql = "UPDATE gcr_applications 
                SET status = ?, 
                    gm_decision = ?, 
                    gm_comments = ?,
                    gm_days_approved = ?,
                    gm_date = CURRENT_DATE 
                WHERE id = ?";
        
        $days_approved = ($decision === 'approved') ? $data['days_approved'] : 0;
        $comments = isset($data['comments']) ? $data['comments'] : null;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssii', 
            $next_status, 
            $decision, 
            $comments,
            $days_approved,
            $application_id
        );
        
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            logApplicationHistory('gcr', $application_id, 'GM ' . $decision, $next_status, $comments);
        }
        
        return $success;
    }
    
    /**
     * Update HR2 decision for GCR application
     */
    private function updateGCRHR2Decision($application_id, $next_status, $data) {
        error_log("Updating GCR HR2 Decision - App ID: $application_id, Next Status: $next_status");
        
        $sql = "UPDATE gcr_applications 
                SET status = ?, 
                    hr2_id = ?, 
                    hr2_signature = ?, 
                    hr2_date = CURRENT_DATE
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in updateGCRHR2Decision: " . $this->conn->error);
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        $signature = isset($data['signature']) ? $data['signature'] : '';
        
        $stmt->bind_param('sisi', 
            $next_status, 
            $user_id, 
            $signature, 
            $application_id
        );

        // Execute statement
        $execute_result = $stmt->execute();
        
        if (!$execute_result) {
            error_log("SQL Execute Error in updateGCRHR2Decision: " . $stmt->error);
            $stmt->close();
            return false;
        }

        // Get affected rows
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        // Log successful update and send notifications
        if ($affected_rows > 0) {
            $comment = "Final HR recording completed";
            logApplicationHistory('gcr', $application_id, 'HR recorded', $next_status, $comment);
            error_log("GCR HR2 Decision updated successfully - New status: $next_status");
            
            // Send notifications for final approval
            $this->notification_manager->notifyGCRStatusChange($application_id, $next_status, $comment);
            return true;
        }

        error_log("No rows updated in updateGCRHR2Decision for application $application_id");
        return false;
    }

    /**
     * Update HR3 decision for GCR application - Lampiran A
     */
    private function updateGCRHR3Decision($application_id, $next_status, $data) {
        error_log("Updating GCR HR3 Decision (Lampiran A) - App ID: $application_id, Next Status: $next_status");
        
        // First, save the Lampiran A details
        $lampiran_a_id = $this->saveLampiranA($application_id, $data);
        
        if (!$lampiran_a_id) {
            error_log("Failed to save Lampiran A details for application ID: $application_id");
            return false;
        }
        
        // Then update the application status
        $sql = "UPDATE gcr_applications 
                SET status = ?, 
                    hr3_id = ?, 
                    hr3_signature = ?, 
                    hr3_date = CURRENT_DATE
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        $signature = isset($data['hr3_signature']) ? $data['hr3_signature'] : '';
        
        $stmt->bind_param('sisi', 
            $next_status, 
            $user_id, 
            $signature,
            $application_id
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("SQL Execute Error: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected_rows > 0) {
            // Log successful update
            logApplicationHistory('gcr', $application_id, 'HR3 verified Lampiran A', $next_status, $data['comments'] ?? '');
            return true;
        }
        
        return false;
    }

/**
 * Update GM final decision for GCR application - Final Lampiran A approval
 */
private function updateGCRGMFinalDecision($application_id, $next_status, $data) {
    error_log("Updating GCR GM Final Decision - App ID: $application_id, Next Status: $next_status");
    
    // Update the Lampiran A with GM signature
    $result = $this->updateLampiranAWithGMSignature($application_id, $data);
    
    if (!$result) {
        error_log("Failed to update Lampiran A with GM signature");
        return false;
    }
    
    // Then update the application status
    $sql = "UPDATE gcr_applications 
            SET status = ?, 
                gm_final_id = ?, 
                gm_final_signature = ?, 
                gm_final_date = CURRENT_DATE
            WHERE id = ?";
    
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL Prepare Error: " . $this->conn->error);
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $signature = isset($data['gm_final_signature']) ? $data['gm_final_signature'] : '';
    
    $stmt->bind_param('sisi', 
        $next_status, 
        $user_id, 
        $signature,
        $application_id
    );
    
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("SQL Execute Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected_rows > 0) {
        // Log successful update
        logApplicationHistory('gcr', $application_id, 'GM finalized application with Lampiran A', $next_status, '');
        return true;
    }
    
    return false;
}

/**
 * Save Lampiran A data
 */
private function saveLampiranA($application_id, $data) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $lampiran_data = isset($data['lampiran_a_details']) ? $data['lampiran_a_details'] : [];
    
    if (empty($lampiran_data)) {
        error_log("Empty Lampiran A data for application: $application_id");
        return false;
    }
    
    $sql = "INSERT INTO gcr_lampiran_a (
                application_id, employee_id, total_days_balance, 
                gc_days_approved, remaining_days, hr3_signature,
                verified_by, verified_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL Prepare Error: " . $this->conn->error);
        return false;
    }
    
    $verified_date = isset($lampiran_data['verified_date']) ? $lampiran_data['verified_date'] : date('Y-m-d');
    $employee_id = isset($lampiran_data['employee_id']) ? $lampiran_data['employee_id'] : '';
    $total_days = isset($lampiran_data['total_days_balance']) ? $lampiran_data['total_days_balance'] : 0;
    $days_approved = isset($lampiran_data['gc_days_approved']) ? $lampiran_data['gc_days_approved'] : 0;
    $remaining = isset($lampiran_data['remaining_days']) ? $lampiran_data['remaining_days'] : 0;
    $signature = isset($data['hr3_signature']) ? $data['hr3_signature'] : '';
    
    $stmt->bind_param(
        'isiissis', 
        $application_id, 
        $employee_id, 
        $total_days, 
        $days_approved, 
        $remaining, 
        $signature,
        $user_id,
        $verified_date
    );
    
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("SQL Execute Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $lampiran_id = $stmt->insert_id;
    $stmt->close();
    
    return $lampiran_id;
}

/**
 * Update Lampiran A with GM final signature
 */
private function updateLampiranAWithGMSignature($application_id, $data) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $finalized_date = isset($data['finalized_date']) ? $data['finalized_date'] : date('Y-m-d');
    $signature = isset($data['gm_final_signature']) ? $data['gm_final_signature'] : '';
    
    $sql = "UPDATE gcr_lampiran_a 
            SET gm_final_signature = ?, finalized_by = ?, finalized_date = ? 
            WHERE application_id = ?";
    
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL Prepare Error: " . $this->conn->error);
        return false;
    }
    
    $stmt->bind_param('sisi', $signature, $user_id, $finalized_date, $application_id);
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("SQL Execute Error: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    return $affected_rows > 0;
}

/**
 * Get Lampiran A data for an application
 * @param int $application_id Application ID
 * @return array|null Lampiran A data or null if not found
 */
public function getLampiranA($application_id) {
    $sql = "SELECT l.*, 
            v.name as verified_by_name, 
            f.name as finalized_by_name 
            FROM gcr_lampiran_a l
            LEFT JOIN users v ON l.verified_by = v.id
            LEFT JOIN users f ON l.finalized_by = f.id
            WHERE l.application_id = ?";
    
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL Prepare Error in getLampiranA: " . $this->conn->error);
        return null;
    }
    
    $stmt->bind_param('i', $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lampiran = $result->fetch_assoc();
    $stmt->close();
    
    return $lampiran;
}
    
    /**
     * Update HOD decision for Training application
     */
    private function updateTrainingHODDecision($application_id, $decision, $next_status, $data) {
        $sql = "UPDATE training_applications 
                SET status = ?, 
                    hod_id = ?, 
                    hod_decision = ?, 
                    hod_comments = ?, 
                    hod_date = CURRENT_DATE 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in updateTrainingHODDecision: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('sissi', 
            $next_status, 
            $_SESSION['user_id'], 
            $decision, 
            $data['comments'], 
            $application_id
        );
        
        $success = $stmt->execute();
        $stmt->close();
    
        if ($success) {
            logApplicationHistory('training', $application_id, 'HOD ' . ($decision == 'recommended' ? 'recommended' : 'not recommended'), $next_status, $data['comments']);
            
            // Add notification for HR if recommended
            if ($decision == 'recommended') {
                // Get HR users
                $hr_users = $this->getUsersByRole('hr');
                foreach ($hr_users as $hr_user) {
                    $this->createNotification(
                        'training', 
                        $application_id, 
                        $hr_user['id'], 
                        'A training application requires your review',
                        'review'
                    );
                }
            }
            
            // Notify the applicant about the HOD decision
            $applicant_id = $this->getApplicationOwnerId('training', $application_id);
            $status_text = ($decision == 'recommended') ? 'recommended' : 'not recommended';
            $this->createNotification(
                'training', 
                $application_id, 
                $applicant_id, 
                'Your training application has been ' . $status_text . ' by HOD',
                $decision == 'recommended' ? 'approval' : 'rejection'
            );
        }
        
        return $success;
    }

    // Helper method to get users by role
    private function getUsersByRole($role) {
        $sql = "SELECT id, name FROM users WHERE role = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $users;
    }

    // Helper method to get application owner
    private function getApplicationOwnerId($type, $application_id) {
        $table = ($type === 'training') ? 'training_applications' : 'gcr_applications';
        $sql = "SELECT user_id FROM $table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['user_id'] ?? null;
    }
    
    /**
     * Update HR decision for Training application
     */
    private function updateTrainingHRDecision($application_id, $next_status, $data) {
        // Check for SQL errors when preparing the statement
        $sql = "UPDATE training_applications 
                SET status = ?, 
                    hr_id = ?, 
                    hr_comments = ?, 
                    budget_status = ?, 
                    credit_hours = ?, 
                    budget_comments = ?, 
                    hr_date = CURRENT_DATE,
                    reference_number = ?  /* Changed from COALESCE to always update */
                WHERE id = ?";
        
        $ref_number = isset($data['reference_number']) && !empty($data['reference_number']) 
            ? $data['reference_number'] 
            : generateReferenceNumber('training');
        
        // Log the reference number being saved for debugging
        error_log("Updating reference number to: " . $ref_number);
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if statement preparation was successful
        if ($stmt === false) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return false;
        }
        
        // Make sure budget_status and credit_hours have values if they weren't provided
        $budget_status = isset($data['budget_status']) ? $data['budget_status'] : 'no';
        $credit_hours = isset($data['credit_hours']) ? $data['credit_hours'] : 0;
        $budget_comments = isset($data['budget_comments']) ? $data['budget_comments'] : '';
        $comments = isset($data['comments']) ? $data['comments'] : '';
        
        $stmt->bind_param('sisssisi', 
            $next_status, 
            $_SESSION['user_id'], 
            $comments, 
            $budget_status, 
            $credit_hours, 
            $budget_comments, 
            $ref_number,
            $application_id
        );
        
        $success = $stmt->execute();
        
        // Check for execution errors
        if (!$success) {
            error_log("SQL Execute Error: " . $stmt->error);
        } else {
            // Log successful update of reference number
            error_log("Successfully updated application ID $application_id with reference number: $ref_number");
        }
        
        $stmt->close();
        
        if ($success) {
            logApplicationHistory('training', $application_id, 'HR processed', $next_status, $comments);
        }
        
        return $success;
    }

   /**
     * Update GM decision for Training application
     */
    private function updateTrainingGMDecision($application_id, $decision, $next_status, $data) {
        $sql = "UPDATE training_applications 
                SET status = ?, 
                    gm_decision = ?, 
                    gm_date = ?,
                    gm_comments = ?
                WHERE id = ?";
        
        $gm_date = isset($data['gm_date']) ? $data['gm_date'] : date('Y-m-d');
        $comments = isset($data['comments']) ? $data['comments'] : null;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssi', 
            $next_status, 
            $decision,
            $gm_date,
            $comments,
            $application_id
        );
        
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            logApplicationHistory('training', $application_id, 'GM ' . $decision, $next_status, $comments);
        }
        
        return $success;
    }
    
    /**
     * Determine next status for Training workflow
     */
    private function getNextTrainingStatus($current_status, $decision) {
        $workflow = [
            'pending_hod' => [
                'recommended' => 'pending_hr',
                'not_recommended' => 'rejected'
            ],
            'pending_hr' => 'pending_gm',
            'pending_gm' => [
                'approved' => 'approved',
                'rejected' => 'rejected'
            ]
        ];
        
        if (isset($workflow[$current_status])) {
            if (is_array($workflow[$current_status])) {
                return $workflow[$current_status][$decision] ?? false;
            } else {
                return $workflow[$current_status];
            }
        }
        
        return false;
    }
    
    /**
     * Determine next status for GCR workflow
     */
    private function getNextGCRStatus($current_status, $decision) {
        $workflow = [
            'pending_hr1' => 'pending_gm',
            'pending_gm' => [
                'approved' => 'pending_hr2',
                'rejected' => 'rejected'
            ],
            'pending_hr2' => 'pending_hr3',         // Add this line
            'pending_hr3' => 'pending_gm_final',    // Add this line  
            'pending_gm_final' => 'approved'        // Add this line
        ];
        
        // Check if the current status exists in workflow
        if (!isset($workflow[$current_status])) {
            error_log("No workflow defined for status: $current_status");
            return false;
        }
        
        // If workflow for this status is a simple string (not an array of decisions)
        if (!is_array($workflow[$current_status])) {
            return $workflow[$current_status];
        }
        
        // If workflow requires a decision
        if (!isset($workflow[$current_status][$decision])) {
            error_log("No workflow defined for status: $current_status and decision: $decision");
            return false;
        }
        
        return $workflow[$current_status][$decision];
    }
    
    /**
     * Submit new Training application
     */
    public function submitTrainingApplication($data) {
        $sql = "INSERT INTO training_applications (
            user_id, programme_title, venue, organiser, date_time, fee,
            requestor_name, post_grade, unit_division, requestor_date, justification, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_hod')";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in submitTrainingApplication: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('issssdsssss', 
            $_SESSION['user_id'],
            $data['programme_title'],
            $data['venue'],
            $data['organiser'],
            $data['date_time'],
            $data['fee'],
            $data['requestor_name'],
            $data['post_grade'],
            $data['unit_division'],
            $data['requestor_date'],
            $data['justification']
        );
        
        $success = $stmt->execute();
        $application_id = $stmt->insert_id;
        $stmt->close();
        
        if ($success) {
            logApplicationHistory('training', $application_id, 'Application submitted', 'pending_hod');
            
            // Notify HOD about new application
            $this->notification_manager->notifyAdminsNewApplication('training', $application_id, $data);
            
            return $application_id;
        }
        
        return false;
    }
    
    /**
     * Submit new GCR application
     */
    public function submitGCRApplication($data) {
        // Debug log
        error_log("Submitting GCR application: " . print_r($data, true));
        
        $sql = "INSERT INTO gcr_applications (
                    user_id, year, applicant_name, applicant_position, 
                    applicant_department, days_requested, signature_data, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_hr1')";
        
        // Check SQL
        error_log("GCR SQL: " . $sql);
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return false;
        }
        
        // Get data for binding
        $user_id = $_SESSION['user_id'];
        $year = $data['year'];
        $applicant_name = $data['applicant_name'];
        $applicant_position = $data['applicant_position'];
        $applicant_department = $data['applicant_department'];
        $days_requested = $data['days_requested'];
        $signature_data = $data['signature_data'];
        
        // Debug log the values
        error_log("User ID: $user_id");
        error_log("Year: $year");
        error_log("Name: $applicant_name");
        error_log("Position: $applicant_position");
        error_log("Department: $applicant_department");
        error_log("Days: $days_requested");
        error_log("Signature: " . substr($signature_data, 0, 50) . "...");
        
        $stmt->bind_param('iisssis', 
            $user_id,
            $year,
            $applicant_name,
            $applicant_position,
            $applicant_department,
            $days_requested,
            $signature_data
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("SQL Execute Error: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $application_id = $stmt->insert_id;
        $stmt->close();
        
        if ($success) {
            // Verify that the application was inserted correctly
            $verify_sql = "SELECT * FROM gcr_applications WHERE id = ?";
            $verify_stmt = $this->conn->prepare($verify_sql);
            $verify_stmt->bind_param('i', $application_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $verify_app = $verify_result->fetch_assoc();
            $verify_stmt->close();
            
            error_log("Verified GCR application: " . print_r($verify_app, true));
            
            // Log the application history
            logApplicationHistory('gcr', $application_id, 'Application submitted', 'pending_hr1');
            
            // Notify HR about new application
            $this->notification_manager->notifyAdminsNewApplication('gcr', $application_id, $data);
            
            return $application_id;
        }
        
        return false;
    }
    
    /**
     * Save Training application documents
     */
    public function saveTrainingDocuments($application_id, $files) {
        $success = true;
        
        foreach ($files as $file) {
            $sql = "INSERT INTO training_documents (
                        training_id, file_name, file_path, file_type, file_size
                    ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("SQL Prepare Error in saveTrainingDocuments: " . $this->conn->error);
                $success = false;
                continue;
            }
            
            $stmt->bind_param('isssi', 
                $application_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size']
            );
            
            if (!$stmt->execute()) {
                error_log("Error saving document: " . $stmt->error);
                $success = false;
            }
            
            $stmt->close();
        }
        
        return $success;
    }
}