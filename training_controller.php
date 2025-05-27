<?php
// Handles training application processing

require_once 'config.php';
require_once 'workflow_manager.php';

class TrainingController {
    private $conn;
    private $workflow;
    
    public function __construct() {
        $this->conn = connectDB();
        $this->workflow = new WorkflowManager();
    }
    
    /**
     * Submit a new training application
     */
    public function submitApplication($post_data, $files) {
        // Validate required fields
        $required_fields = [
            'programmeTitle', 'venue', 'organiser', 'dateTime', 'fee',
            'requestorName', 'postGrade', 'unitDivision', 'requestorDate', 
            'justification'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($post_data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required."];
            }
        }
        
        // Check if files were uploaded
        if (empty($files['brochure']['name'][0])) {
            return ['success' => false, 'message' => 'Please attach at least one training brochure.'];
        }
        // Enforce max 5 files for brochure
        if (count($files['brochure']['name']) > 5) {
            return ['success' => false, 'message' => 'You can upload a maximum of 5 files for the brochure.'];
        }
        
        // Prepare data for submission
        $application_data = [
            'programme_title' => $post_data['programmeTitle'],
            'venue' => $post_data['venue'],
            'organiser' => $post_data['organiser'],
            'date_time' => $post_data['dateTime'],
            'fee' => $post_data['fee'],
            'requestor_name' => $post_data['requestorName'],
            'post_grade' => $post_data['postGrade'],
            'unit_division' => $post_data['unitDivision'],
            'requestor_date' => $post_data['requestorDate'],
            'justification' => $post_data['justification']
        ];
        
        // Submit application
        $application_id = $this->workflow->submitTrainingApplication($application_data);
        
        if (!$application_id) {
            return ['success' => false, 'message' => 'Failed to submit application. Please try again.'];
        }
        
        // Process files
        $upload_dir = UPLOAD_DIR . 'training/' . $application_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $uploaded_files = [];
        $file_count = count($files['brochure']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            // Skip empty uploads
            if (empty($files['brochure']['name'][$i])) continue;
            
            // Validate file size
            if ($files['brochure']['size'][$i] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'File size exceeds the limit of 5MB.'];
            }
            
            // Validate file type
            $file_type = $files['brochure']['type'][$i];
            if (!in_array($file_type, ALLOWED_FILE_TYPES)) {
                return ['success' => false, 'message' => 'Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, PNG.'];
            }
            
            // Generate unique filename
            $file_name = $files['brochure']['name'][$i];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($files['brochure']['tmp_name'][$i], $file_path)) {
                $uploaded_files[] = [
                    'name' => $file_name,
                    'path' => $file_path,
                    'type' => $file_type,
                    'size' => $files['brochure']['size'][$i]
                ];
            }
        }
        
        // Save document references
        if (!empty($uploaded_files)) {
            $this->workflow->saveTrainingDocuments($application_id, $uploaded_files);
        }
        
        return [
            'success' => true, 
            'message' => 'Training application submitted successfully.', 
            'application_id' => $application_id
        ];
    }
    
    /**
     * Process HOD decision
     */
    public function processHODDecision($post_data) {
        $application_id = $post_data['application_id'];
        $decision = $post_data['hod_decision'];
        $comments = $post_data['hod_comments'];
        
        $data = [
            'comments' => $comments
        ];
        
        $result = $this->workflow->processTrainingWorkflow($application_id, $decision, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'HOD decision processed successfully.'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to process HOD decision. Please try again.'
            ];
        }
    }
    
    // This is the modified processHRDecision method to remove the evaluation requirement
    
    /**
     * Process HR decision
     */
    public function processHRDecision($post_data) {
        error_log("HR process called with data: " . print_r($post_data, true));
        $application_id = $post_data['application_id'];
        $comments = $post_data['hr_comments'];
        $budget_status = $post_data['budget_status'];
        $credit_hours = $post_data['credit_hours'];
        $budget_comments = $post_data['budget_comments'] ?? '';
        $reference_number = $post_data['reference_number'];
        $signature_data = $post_data['signature_data'] ?? '';
        
        $data = [
            'comments' => $comments,
            'budget_status' => $budget_status,
            'credit_hours' => $credit_hours,
            'budget_comments' => $budget_comments,
            'reference_number' => $reference_number,
            'signature_data' => $signature_data
        ];
        
        $result = $this->workflow->processTrainingWorkflow($application_id, null, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'HR review processed successfully.'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to process HR review. Please try again.'
            ];
        }
    }
    
   /**
     * Process GM decision
     */
    public function processGMDecision($post_data) {
        $application_id = $post_data['application_id'];
        $decision = $post_data['gm_decision'];
        $comments = isset($post_data['gm_comments']) ? $post_data['gm_comments'] : '';
        $gm_date = isset($post_data['gm_date']) ? $post_data['gm_date'] : date('Y-m-d');
        
        $data = [
            'comments' => $comments,
            'gm_date' => $gm_date
        ];
        
        $result = $this->workflow->processTrainingWorkflow($application_id, $decision, $data);
        
        if ($result) {
            return [
                'success' => true, 
                'message' => 'GM decision processed successfully.'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Failed to process GM decision. Please try again.'
            ];
        }
    }
    
    /**
     * Get training application details
     */
    public function getApplication($id) {
        $sql = "SELECT t.*, 
                u.name as applicant_name, 
                u.department as applicant_department,
                hod.name as hod_name,
                hr.name as hr_name
                FROM training_applications t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN users hod ON t.hod_id = hod.id
                LEFT JOIN users hr ON t.hr_id = hr.id
                WHERE t.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        $stmt->close();
        
        if ($application) {
            // Get documents
            $sql = "SELECT * FROM training_documents WHERE training_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $documents = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            $application['documents'] = $documents;
            
            // Get history
            $sql = "SELECT h.*, u.name as user_name, u.role as user_role
                    FROM application_history h
                    JOIN users u ON h.performed_by = u.id
                    WHERE h.application_type = 'training' AND h.application_id = ?
                    ORDER BY h.timestamp DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $history = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            $application['history'] = $history;
        }
        
        return $application;
    }
    
    /**
     * Get applications for the current user based on role
     */
    public function getUserApplications($status = null) {
        $user_id = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['user_role'] ?? null;
        
        if (!$user_id || !$role) {
            error_log("Missing session variables in getUserApplications: user_id=" . ($user_id ? 'set' : 'not set') . ", role=" . ($role ? 'set' : 'not set'));
            return [];
        }
        
        $sql_conditions = [];
        $params = [];
        $param_types = '';
        
        // Filter by status if provided
        if ($status) {
            $sql_conditions[] = "t.status = ?";
            $params[] = $status;
            $param_types .= 's';
        }
        
        // Different queries based on user role
        switch ($role) {
            case 'staff':
                $sql_conditions[] = "t.user_id = ?";
                $params[] = $user_id;
                $param_types .= 'i';
                break;
                
            case 'hod':
                // HODs see applications pending their approval and from their department
                $sql_conditions[] = "(t.status = 'pending_hod' OR (t.user_id IN (SELECT id FROM users WHERE department = (SELECT department FROM users WHERE id = ?))))";
                $params[] = $user_id;
                $param_types .= 'i';
                break;
                
            case 'hr':
                // HR sees all applications that have passed HOD stage
                $sql_conditions[] = "t.status IN ('pending_hr', 'pending_gm', 'approved', 'rejected')";
                break;
                
            case 'gm':
                // GM sees applications pending their approval and approved/rejected ones
                $sql_conditions[] = "t.status IN ('pending_gm', 'approved', 'rejected')";
                break;
                
            case 'admin':
                // Admin sees all applications
                break;
        }
        
        $where_clause = '';
        if (!empty($sql_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $sql_conditions);
        }
        
        $sql = "SELECT t.*, 
                u.name as applicant_name, 
                u.department as applicant_department,
                t.status as status_text
                FROM training_applications t
                LEFT JOIN users u ON t.user_id = u.id
                $where_clause
                ORDER BY t.created_at DESC";
        
        // Debug SQL query
        error_log("SQL Query: " . $sql);
        
        $stmt = $this->conn->prepare($sql);
        
        // Check if prepare was successful
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->conn->error);
            return []; // Return empty array instead of false
        }
        
        // Bind parameters if there are any
        if (!empty($params)) {
            try {
                $stmt->bind_param($param_types, ...$params);
            } catch (Exception $e) {
                error_log("Bind param error: " . $e->getMessage());
                return [];
            }
        }
        
        // Execute with error handling
        try {
            $result = $stmt->execute();
            if (!$result) {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return [];
            }
            
            $result = $stmt->get_result();
            $applications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Process status text
            foreach ($applications as &$app) {
                switch ($app['status']) {
                    case 'pending_submission':
                        $app['status_text'] = 'Draft';
                        break;
                    case 'pending_hod':
                        $app['status_text'] = 'Pending HOD Approval';
                        break;
                    case 'pending_hr':
                        $app['status_text'] = 'Pending HR Review';
                        break;
                    case 'pending_gm':
                        $app['status_text'] = 'Pending GM Approval';
                        break;
                    case 'approved':
                        $app['status_text'] = 'Approved';
                        break;
                    case 'rejected':
                        $app['status_text'] = 'Rejected';
                        break;
                    default:
                        $app['status_text'] = ucwords(str_replace('_', ' ', $app['status']));
                }
            }
            
            return $applications;
            
        } catch (Exception $e) {
            error_log("Execute error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Download a document
     */
    public function getDocument($document_id) {
        $sql = "SELECT d.*, t.user_id 
                FROM training_documents d
                JOIN training_applications t ON d.training_id = t.id
                WHERE d.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $document_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $document = $result->fetch_assoc();
        $stmt->close();
        
        // Check if document exists and user has access
        if ($document && ($document['user_id'] == $_SESSION['user_id'] || $_SESSION['user_role'] != 'staff')) {
            return $document;
        }
        
        return false;
    }
}