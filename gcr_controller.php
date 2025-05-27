<?php
// Handles GCR application processing

require_once 'config.php';
require_once 'workflow_manager.php';

class GCRController {
    private $conn;
    private $workflow;
    
    public function __construct() {
        $this->conn = connectDB();
        $this->workflow = new WorkflowManager();
    }
    
    /**
     * Submit a new GCR application
     * @param array $post_data Submitted form data
     * @return array Submission result
     */
    public function submitApplication($post_data) {
        // Comprehensive logging
        error_log("GCR Form Data Received: " . print_r($post_data, true));
        
        // Validate required fields with more robust checking
        $required_fields = [
            'year' => 'Year', 
            'applicant_name' => 'Applicant Name', 
            'applicant_position' => 'Position', 
            'applicant_department' => 'Department', 
            'days_requested' => 'Days Requested', 
            'signature_data' => 'Signature'
        ];
        
        // Check each required field
        foreach ($required_fields as $field => $label) {
            // Trim and check for empty
            $value = trim($post_data[$field] ?? '');
            
            if (empty($value)) {
                error_log("Missing required field: $label");
                return [
                    'success' => false, 
                    'message' => "$label is required."
                ];
            }
        }
        
        // Validate year (must be current or past year)
        $current_year = date('Y');
        if (!is_numeric($post_data['year']) || 
            $post_data['year'] > $current_year || 
            $post_data['year'] < ($current_year - 5)) {
            error_log("Invalid year provided: " . $post_data['year']);
            return [
                'success' => false, 
                'message' => 'Invalid year. Must be a valid year within the last 5 years.'
            ];
        }
        
        // Validate days requested
        $days_requested = filter_var($post_data['days_requested'], FILTER_VALIDATE_INT);
        if ($days_requested === false || $days_requested <= 0 || $days_requested > 365) {
            error_log("Invalid days requested: " . $post_data['days_requested']);
            return [
                'success' => false, 
                'message' => 'Days requested must be a positive number between 1 and 365.'
            ];
        }
        
        // Validate signature
        if (strpos($post_data['signature_data'], 'data:image') !== 0) {
            error_log("Invalid signature data format");
            return [
                'success' => false, 
                'message' => 'Invalid signature. Please provide a valid signature.'
            ];
        }
        
        // Prepare data for submission
        $application_data = [
            'year' => $post_data['year'],
            'applicant_name' => $post_data['applicant_name'],
            'applicant_position' => $post_data['applicant_position'],
            'applicant_department' => $post_data['applicant_department'],
            'days_requested' => $days_requested,
            'signature_data' => $post_data['signature_data']
        ];
        
        // Submit application
        try {
            $application_id = $this->workflow->submitGCRApplication($application_data);
            
            if (!$application_id) {
                error_log("Failed to submit GCR application");
                return [
                    'success' => false, 
                    'message' => 'Failed to submit application. Database error occurred.'
                ];
            }
            
            return [
                'success' => true, 
                'message' => 'GCR application submitted successfully.', 
                'application_id' => $application_id
            ];
        } catch (Exception $e) {
            error_log("Exception in GCR application submission: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'An unexpected error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Process HR1 verification
     */
    public function processHR1Verification($post_data) {
        // Add validation
        if (empty($post_data['application_id'])) {
            return ['success' => false, 'message' => 'Invalid application ID.'];
        }
        
        $application_id = $post_data['application_id'];
        $signature = isset($post_data['hr1_signature']) ? $post_data['hr1_signature'] : '';
        
        $data = [
            'signature' => $signature
        ];
        
        // For debugging
        error_log("HR1 Verification - App ID: $application_id, Signature length: " . strlen($signature));
        
        $result = $this->workflow->processGCRWorkflow($application_id, null, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'HR verification processed successfully.'
            ];
        } else {
            error_log("HR1 Verification failed for application ID: $application_id");
            return [
                'success' => false, 
                'message' => 'Failed to process HR verification. Please try again.'
            ];
        }
    }
    
    /**
     * Process GM decision
     * @param array $post_data Form data for GM decision
     * @return array Decision processing result
     */
    public function processGMDecision($post_data) {
        // Log the received data for debugging
        error_log("GM Decision post data: " . print_r($post_data, true));
        
        // Validate required inputs
        $validation_errors = [];
        
        if (empty($post_data['application_id'])) {
            $validation_errors[] = 'Application ID is required.';
        }
        
        if (empty($post_data['gm_decision'])) {
            $validation_errors[] = 'Decision is required.';
        }
        
        // If decision is approved, validate days
        if (isset($post_data['gm_decision']) && $post_data['gm_decision'] === 'approved') {
            $days_approved = filter_var($post_data['days_approved'] ?? 0, FILTER_VALIDATE_INT);
            if ($days_approved === false || $days_approved <= 0) {
                $validation_errors[] = 'Valid number of days is required for approval.';
            }
        }
        
        // Return validation errors if any
        if (!empty($validation_errors)) {
            error_log("GM Decision Validation Errors: " . implode('; ', $validation_errors));
            return [
                'success' => false,
                'message' => implode(' ', $validation_errors)
            ];
        }
        
        try {
            // Verify application is in correct status before processing
            $application = $this->getApplication($post_data['application_id']);
            if (!$application) {
                error_log("Application not found for GM decision: " . $post_data['application_id']);
                return [
                    'success' => false, 
                    'message' => 'Application not found.'
                ];
            }
            
            if ($application['status'] !== 'pending_gm') {
                error_log("Application not in pending_gm status for GM decision. Current status: " . $application['status']);
                return [
                    'success' => false, 
                    'message' => 'Application is not in the correct status for GM decision.'
                ];
            }
        
            // Prepare data for workflow
            $data = [
                'days_approved' => $post_data['gm_decision'] === 'approved' 
                    ? $post_data['days_approved'] 
                    : 0,
                'comments' => $post_data['gm_comments'] ?? '',
                'gm_signature' => $post_data['signature_data'] ?? '' 
            ];
            
            // Log the data for debugging
            error_log("Preparing GM decision data: " . print_r($data, true));
            
            $result = $this->workflow->processGCRWorkflow(
                $post_data['application_id'], 
                $post_data['gm_decision'], 
                $data
            );
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'GM decision processed successfully.'
                ];
            } else {
                error_log("Failed to process GM decision for application ID: " . $post_data['application_id']);
                return [
                    'success' => false, 
                    'message' => 'Failed to process GM decision. Please check the application status.'
                ];
            }
        } catch (Exception $e) {
            error_log("Exception in GM decision: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process HR2 final recording
     */
    public function processHR2Recording($post_data) {
        // Add validation
        if (empty($post_data['application_id'])) {
            return ['success' => false, 'message' => 'Invalid application ID.'];
        }
        
        $application_id = $post_data['application_id'];
        $comments = isset($post_data['hr2_comments']) ? $post_data['hr2_comments'] : '';
        
        // Make sure we use hr2_signature consistently
        // For HR2, make sure to use the consistent field name
        $data = [
            'hr2_signature' => isset($post_data['hr2_signature']) ? $post_data['hr2_signature'] : '',
            'comments' => $comments
        ];
        
        // Debug the signature data
        error_log("HR2 Signature data length: " . strlen($post_data['hr2_signature'] ?? ''));
        error_log("HR2 Processing data: " . print_r($data, true));
        
        $result = $this->workflow->processGCRWorkflow($application_id, null, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'HR recording processed successfully.'
            ];
        } else {
            error_log("HR2 Recording failed for application ID: $application_id");
            return [
                'success' => false, 
                'message' => 'Failed to process HR recording. Please try again.'
            ];
        }
    }

    /**
     * Process HR3 Lampiran A verification
     */
    public function processHR3LampiranA($post_data) {
        // Add validation
        if (empty($post_data['application_id'])) {
            return ['success' => false, 'message' => 'Invalid application ID.'];
        }
        
        $application_id = $post_data['application_id'];
        
        // Log for debugging
        error_log("Processing HR3 Lampiran A for application ID: " . $application_id);
        error_log("Post data: " . print_r($post_data, true));
        
        // Prepare data for Lampiran A
        $data = [
            'hr3_signature' => isset($post_data['hr3_signature']) ? $post_data['hr3_signature'] : '',
            'comments' => isset($post_data['hr3_comments']) ? $post_data['hr3_comments'] : '',
            'lampiran_a_details' => [
                'employee_id' => $post_data['employee_id'] ?? '',
                'total_days_balance' => $post_data['total_days_balance'] ?? 0,
                'gc_days_approved' => $post_data['gc_days_approved'] ?? 0,
                'remaining_days' => $post_data['remaining_days'] ?? 0,
                'verified_date' => $post_data['verified_date'] ?? date('Y-m-d')
            ]
        ];
        
        // Process in workflow
        $result = $this->workflow->processGCRWorkflow($application_id, null, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'Lampiran A processed successfully.'
            ];
        } else {
            error_log("Failed to process Lampiran A for application ID: " . $application_id);
            return [
                'success' => false, 
                'message' => 'Failed to process Lampiran A. Please try again.'
            ];
        }
    }

    /**
     * Get Lampiran A data for GCR application
     * @param int $application_id Application ID
     * @return array|null Lampiran A data or null if not found
     */
    public function getLampiranA($application_id) {
        if (empty($application_id)) {
            error_log("Invalid application ID for getLampiranA");
            return null;
        }
        
        try {
            // Check if we have Lampiran A data in the database
            $sql = "SELECT * FROM gcr_lampiran_a WHERE application_id = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                error_log("SQL Prepare Error in getLampiranA: " . $this->conn->error);
                return null;
            }
            
            $stmt->bind_param('i', $application_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                // No Lampiran A data found
                $stmt->close();
                
                // Try to get HR3 processing data from history
                $hr3_data = $this->getApplicationProcessingData($application_id, 'hr3');
                
                if ($hr3_data && isset($hr3_data['lampiran_a_details'])) {
                    // Format the data consistently
                    $lampiran_data = [
                        'application_id' => $application_id,
                        'employee_id' => $hr3_data['lampiran_a_details']['employee_id'] ?? '',
                        'total_days_balance' => $hr3_data['lampiran_a_details']['total_days_balance'] ?? 0,
                        'gc_days_approved' => $hr3_data['lampiran_a_details']['gc_days_approved'] ?? 0,
                        'remaining_days' => $hr3_data['lampiran_a_details']['remaining_days'] ?? 0,
                        'verified_date' => $hr3_data['lampiran_a_details']['verified_date'] ?? date('Y-m-d'),
                        'hr3_signature' => $hr3_data['hr3_signature'] ?? ''
                    ];
                    
                    return $lampiran_data;
                }
                
                return null;
            }
            
            $lampiran_data = $result->fetch_assoc();
            $stmt->close();
            
            // Get HR3 signature separately
            $hr3_data = $this->getApplicationProcessingData($application_id, 'hr3');
            if ($hr3_data && isset($hr3_data['hr3_signature'])) {
                $lampiran_data['hr3_signature'] = $hr3_data['hr3_signature'];
            }
            
            return $lampiran_data;
        } catch (Exception $e) {
            error_log("Exception in getLampiranA: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get application processing data for a specific step
     * @param int $application_id Application ID
     * @param string $step Step name (hr1, gm, hr2, hr3, gm_final)
     * @return array|null Processing data or null if not found
     */
    public function getApplicationProcessingData($application_id, $step) {
        // Validate input
        if (empty($application_id) || empty($step)) {
            error_log("Invalid parameters for getApplicationProcessingData: app_id=$application_id, step=$step");
            return null;
        }
        
        try {
            // If we're getting hr3 data, try to get it from the lampiran_a table first
            if ($step === 'hr3') {
                // Get Lampiran A data
                $lampiran = $this->workflow->getLampiranA($application_id);
                
                if ($lampiran) {
                    // Format the data as needed for the view
                    return [
                        'signature' => $lampiran['hr3_signature'] ?? '',
                        'lampiran_a' => [
                            'employee_id' => $lampiran['employee_id'] ?? '',
                            'total_days_balance' => $lampiran['total_days_balance'] ?? 0,
                            'gc_days_approved' => $lampiran['gc_days_approved'] ?? 0,
                            'remaining_days' => $lampiran['remaining_days'] ?? 0,
                            'verified_date' => $lampiran['verified_date'] ?? date('Y-m-d')
                        ]
                    ];
                }
            }
            
            // If we don't have Lampiran A data or it's a different step, 
            // Try to get it from processing history
            $sql = "SELECT * FROM gcr_processing_history 
                    WHERE application_id = ? AND step = ? 
                    ORDER BY processed_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('is', $application_id, $step);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Decode the JSON data
                $processing_data = json_decode($row['processing_data'], true);
                return $processing_data;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Exception in getApplicationProcessingData: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process GM final signature
     */
    public function processGMFinalSignature($post_data) {
        if (empty($post_data['application_id'])) {
            return ['success' => false, 'message' => 'Invalid application ID.'];
        }
        
        $application_id = $post_data['application_id'];
        
        // Debug the signature
        error_log("GM Final Signature Data Length: " . strlen($post_data['gm_final_signature'] ?? ''));
        
        $data = [
            'gm_final_signature' => $post_data['gm_final_signature'] ?? '' // FIXED: Use the correct field name
        ];
        
        $result = $this->workflow->processGCRWorkflow($application_id, null, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'GM final signature processed successfully. GCR application completed.'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to process GM final signature. Please try again.'
            ];
        }
    }
    
    /**
     * Get GCR application details
     * @param int $id Application ID
     * @return array|false Application details or false if not found
     */
    public function getApplication($id) {
        // Log the attempt
        error_log("Attempting to get GCR application with ID: " . $id);
        
        // Use a simpler query first to test basic connectivity
        $sql = "SELECT * FROM gcr_applications WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("SQL Execute Error: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if (!$application) {
            error_log("No application found with ID: " . $id);
            return false;
        }
        
        error_log("Found application: " . print_r($application, true));
        
        // Now get user details
        $sql = "SELECT name, department, position FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $application['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                $application['applicant_name'] = $user['name'];
                $application['applicant_department'] = $user['department'];
                $application['applicant_position'] = $user['position'];
            }
            $stmt->close();
        }
        
        return $application;
    }
    
    /**
     * Get applications for the current user based on role
     * @param string|null $status Filter by application status
     * @return array List of applications
     */
    public function getUserApplications($status = null) {
        $user_id = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['user_role'] ?? '';
        
        // Debug log
        error_log("Getting GCR applications for user_id: $user_id, role: $role, status filter: " . ($status ? $status : "none"));
        
        $sql_conditions = [];
        $params = [];
        $param_types = '';
        
        // Filter by status if provided
        if ($status) {
            $sql_conditions[] = "g.status = ?";
            $params[] = $status;
            $param_types .= 's';
        }
        
        // Different queries based on user role
        switch ($role) {
            case 'staff':
                $sql_conditions[] = "g.user_id = ?";
                $params[] = $user_id;
                $param_types .= 'i';
                break;
                
            case 'hr':
            case 'admin':
                // HR and admin see all applications at HR1, HR2 stages or completed
                if (empty($status)) {
                    // If no specific status is provided, show all applications that HR would manage
                    $sql_conditions[] = "(g.status IN ('pending_hr1', 'pending_hr2', 'approved', 'rejected') OR g.user_id = ?)";
                    $params[] = $user_id;
                    $param_types .= 'i';
                }
                break;
                
            case 'gm':
                // GM sees applications pending their approval and approved/rejected ones
                if (empty($status)) {
                    $sql_conditions[] = "(g.status IN ('pending_gm', 'approved', 'rejected') OR g.user_id = ?)";
                    $params[] = $user_id;
                    $param_types .= 'i';
                }
                break;
                
            default:
                // Default case, just show user's own applications
                $sql_conditions[] = "g.user_id = ?";
                $params[] = $user_id;
                $param_types .= 'i';
                break;
        }
        
        $where_clause = '';
        if (!empty($sql_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $sql_conditions);
        }
        
        $sql = "SELECT g.*, 
                u.name as applicant_name, 
                u.department as applicant_department,
                CASE 
                    WHEN g.status = 'pending_submission' THEN 'Draft'
                    WHEN g.status = 'pending_hr1' THEN 'Pending HR Verification'
                    WHEN g.status = 'pending_gm' THEN 'Pending GM Approval'
                    WHEN g.status = 'pending_hr2' THEN 'Pending HR Final Recording'
                    WHEN g.status = 'approved' THEN 'Approved'
                    WHEN g.status = 'rejected' THEN 'Rejected'
                    ELSE CONCAT(UPPER(SUBSTRING(REPLACE(g.status, '_', ' '), 1, 1)), LOWER(SUBSTRING(REPLACE(g.status, '_', ' '), 2)))
                END as status_text
                FROM gcr_applications g
                LEFT JOIN users u ON g.user_id = u.id
                $where_clause
                ORDER BY g.created_at DESC";
        
        error_log("GCR SQL Query: $sql");
        
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Error in getUserApplications: " . $this->conn->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Validate all applications to ensure they have required fields
        foreach ($applications as $key => $app) {
            if (!isset($app['id']) || !isset($app['status'])) {
                error_log("Invalid application data: " . print_r($app, true));
                // Remove invalid application or add default values
                // $applications[$key]['status'] = 'unknown';
                // $applications[$key]['status_text'] = 'Unknown Status';
            }
        }
        
        error_log("Found " . count($applications) . " GCR applications");
        return $applications;
    }
    
    /**
     * Get applications for HR processing
     * @return array List of pending HR applications
     */
    public function getHRPendingApplications() {
        $sql = "SELECT g.*, 
                u.name as applicant_name, 
                u.department as applicant_department,
                CASE 
                    WHEN g.status = 'pending_submission' THEN 'Draft'
                    WHEN g.status = 'pending_hr1' THEN 'Pending HR Verification'
                    WHEN g.status = 'pending_gm' THEN 'Pending GM Approval'
                    WHEN g.status = 'pending_hr2' THEN 'Pending HR Final Recording'
                    WHEN g.status = 'approved' THEN 'Approved'
                    WHEN g.status = 'rejected' THEN 'Rejected'
                    ELSE CONCAT(UPPER(SUBSTRING(REPLACE(g.status, '_', ' '), 1, 1)), LOWER(SUBSTRING(REPLACE(g.status, '_', ' '), 2)))
                END as status_text
                FROM gcr_applications g
                LEFT JOIN users u ON g.user_id = u.id
                WHERE g.status IN ('pending_hr1', 'pending_hr2')
                ORDER BY g.created_at DESC";
        
        $result = $this->conn->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        error_log("SQL Error in getHRPendingApplications: " . $this->conn->error);
        return [];
    }
    
    /**
     * Get applications for GM approval
     * @return array List of pending GM applications
     */
    public function getGMPendingApplications() {
        $sql = "SELECT g.*, 
                u.name as applicant_name, 
                u.department as applicant_department,
                CASE 
                    WHEN g.status = 'pending_submission' THEN 'Draft'
                    WHEN g.status = 'pending_hr1' THEN 'Pending HR Verification'
                    WHEN g.status = 'pending_gm' THEN 'Pending GM Approval'
                    WHEN g.status = 'pending_hr2' THEN 'Pending HR Final Recording'
                    WHEN g.status = 'approved' THEN 'Approved'
                    WHEN g.status = 'rejected' THEN 'Rejected'
                    ELSE CONCAT(UPPER(SUBSTRING(REPLACE(g.status, '_', ' '), 1, 1)), LOWER(SUBSTRING(REPLACE(g.status, '_', ' '), 2)))
                END as status_text
                FROM gcr_applications g
                LEFT JOIN users u ON g.user_id = u.id
                WHERE g.status = 'pending_gm'
                ORDER BY g.created_at DESC";
        
        $result = $this->conn->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        error_log("SQL Error in getGMPendingApplications: " . $this->conn->error);
        return [];
    }
}