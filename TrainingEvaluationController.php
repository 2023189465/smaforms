<?php
// TrainingEvaluationController.php - Handles standalone training evaluation functionality

require_once 'config.php';
require_once 'notification_manager.php'; // Add notification manager

class TrainingEvaluationController {
    private $conn;
    private $notificationManager; // Add this property
    
    public function __construct() {
        $this->conn = connectDB();
        $this->notificationManager = new NotificationManager(); // Initialize notification manager
    }
    
    /**
     * Submit a training evaluation
     * 
     * @param array $data The evaluation data
     * @return array Success status and message
     */
    public function submitEvaluation($data) {
        // Add debugging
        error_log("Debug - submitEvaluation called with data: " . print_r($data, true));
        
        // Validate required fields
        $required_fields = [
            'overall_rating', 
            'content_rating', 
            'facilitator_rating', 
            'knowledge_gained', 
            'skill_improvement', 
            'application_plan',
            'training_title',
            'organiser',
            'venue',
            'training_date',
            'duration'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                error_log("Debug - Missing required field: $field");
                return ['success' => false, 'message' => "Field '$field' is required."];
            }
        }
        
        $user_id = $_SESSION['user_id'];
        error_log("Debug - User ID: $user_id");
        
        try {
            // Check if this is a new evaluation or an update to an existing one
            if (isset($data['evaluation_id']) && !empty($data['evaluation_id'])) {
                $evaluation_id = $data['evaluation_id'];
                error_log("Debug - Updating existing evaluation ID: $evaluation_id");
                
                // Update existing evaluation
                $sql = "UPDATE training_evaluations SET 
                        overall_rating = ?,
                        content_rating = ?,
                        facilitator_rating = ?,
                        knowledge_gained = ?,
                        skill_improvement = ?,
                        application_plan = ?,
                        feedback = ?,
                        training_title = ?,
                        organiser = ?,
                        venue = ?,
                        training_date = ?,
                        duration = ?,
                        status = 'completed',
                        submitted_date = CURRENT_TIMESTAMP,
                        completion_date = CURRENT_TIMESTAMP
                        WHERE id = ? AND user_id = ?";
                        
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("SQL Prepare Error: " . $this->conn->error);
                }
                
                // Store all values in variables to avoid reference issues
                $overall_rating = (int)$data['overall_rating'];
                $content_rating = (int)$data['content_rating'];
                $facilitator_rating = (int)$data['facilitator_rating'];
                $knowledge_gained = $data['knowledge_gained']; // Changed to text instead of int
                $skill_improvement = $data['skill_improvement']; // Changed to text instead of int
                $application_plan = $data['application_plan'];
                $feedback = $data['feedback'] ?? '';
                $training_title = $data['training_title'];
                $organiser = $data['organiser'];
                $venue = $data['venue'];
                $training_date = $data['training_date'];
                $duration = (int)$data['duration'];
                
                // Fixed type string to match parameter count and types
                if (!$stmt->bind_param('iisssssssssii', 
                    $overall_rating,
                    $content_rating,
                    $facilitator_rating,
                    $knowledge_gained,
                    $skill_improvement,
                    $application_plan,
                    $feedback,
                    $training_title,
                    $organiser,
                    $venue,
                    $training_date,
                    $duration,
                    $evaluation_id,
                    $user_id
                )) {
                    throw new Exception("Bind Param Error: " . $stmt->error);
                }
            } else {
                // Insert new evaluation
                error_log("Debug - Creating new evaluation");
                
                $sql = "INSERT INTO training_evaluations (
                        user_id, training_id, status, submitted_date, completion_date,
                        overall_rating, content_rating, facilitator_rating,
                        knowledge_gained, skill_improvement, application_plan,
                        feedback, training_title, organiser, venue,
                        training_date, duration
                    ) VALUES (?, NULL, 'completed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("SQL Prepare Error: " . $this->conn->error);
                }
                
                // Store all values in variables to avoid reference issues
                $user_id_param = $user_id;
                $overall_rating = (int)$data['overall_rating'];
                $content_rating = (int)$data['content_rating'];
                $facilitator_rating = (int)$data['facilitator_rating'];
                $knowledge_gained = $data['knowledge_gained'];
                $skill_improvement = $data['skill_improvement'];
                $application_plan = $data['application_plan'];
                $feedback = $data['feedback'] ?? '';
                $training_title = $data['training_title'];
                $organiser = $data['organiser'];
                $venue = $data['venue'];
                $training_date = $data['training_date'];
                $duration = (int)$data['duration'];
                
                // Fixed type string to match parameter count and types (13 parameters)
                if (!$stmt->bind_param('iiiissssssssi', 
                    $user_id_param,
                    $overall_rating,
                    $content_rating,
                    $facilitator_rating,
                    $knowledge_gained,
                    $skill_improvement,
                    $application_plan,
                    $feedback,
                    $training_title,
                    $organiser,
                    $venue,
                    $training_date,
                    $duration
                )) {
                    throw new Exception("Bind Param Error: " . $stmt->error);
                }
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute Error: " . $stmt->error);
            }
            
            // Get the ID for history logging
            if (isset($evaluation_id)) {
                $id_for_history = $evaluation_id;
            } else {
                $id_for_history = $stmt->insert_id;
            }
            
            $stmt->close();
            
            // Log the submission
            $this->logEvaluationHistory($id_for_history, 'Completed', 'Evaluation submitted');
            
            // Notify HR users about the completed evaluation
            $this->notifyHRAboutEvaluation($id_for_history, $training_title, $user_id);
            
            error_log("Debug - Evaluation submitted successfully: ID $id_for_history");
            
            return [
                'success' => true,
                'message' => 'Evaluation submitted successfully.'
            ];
        } catch (Exception $e) {
            error_log("Error in submitEvaluation: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit evaluation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Notify HR users about completed evaluation
     * 
     * @param int $evaluation_id The evaluation ID
     * @param string $training_title The training title
     * @param int $user_id The user ID who submitted the evaluation
     * @return bool Success or failure
     */
    private function notifyHRAboutEvaluation($evaluation_id, $training_title, $user_id) {
        try {
            // Get user information
            $sql = "SELECT name, department FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Prepare Error: " . $this->conn->error);
            }
            
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $user_name = $user['name'];
            $user_department = $user['department'];
            
            // Construct notification message
            $message = "A new training evaluation for \"$training_title\" has been submitted by $user_name from $user_department department. Please review.";
            
            // Use the notification manager to notify HR users
            $this->notificationManager->notifyRoleUsers('hr', 'evaluation', $evaluation_id, $message, 'submission');
            
            // Also notify admin users
            $this->notificationManager->notifyRoleUsers('admin', 'evaluation', $evaluation_id, $message, 'submission');
            
            error_log("Successfully sent notifications to HR and admin users about evaluation ID: $evaluation_id");
            
            return true;
        } catch (Exception $e) {
            error_log("Error notifying HR about evaluation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update evaluation status directly
     * 
     * @param int $evaluation_id The evaluation ID
     * @param string $status The new status
     * @return array Success status and message
     */
    public function updateEvaluationStatus($evaluation_id, $status = 'completed') {
        try {
            $sql = "UPDATE training_evaluations SET 
                    status = ?, 
                    completion_date = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare Error: " . $this->conn->error);
            }
            
            $stmt->bind_param('si', $status, $evaluation_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute Error: " . $stmt->error);
            }
            
            // Check if any rows were affected
            if ($stmt->affected_rows === 0) {
                throw new Exception("No records were updated. Evaluation ID might not exist.");
            }
            
            $stmt->close();
            
            // Log the status update
            $this->logEvaluationHistory($evaluation_id, 'Status Update', "Evaluation status updated to '$status'");
            
            return [
                'success' => true,
                'message' => "Evaluation status updated to '$status'."
            ];
        } catch (Exception $e) {
            error_log("Update evaluation status error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update evaluation status: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all evaluations (for HR and admin)
     * 
     * @return array The list of all evaluations
     */
    public function getAllEvaluations() {
        $sql = "SELECT e.*, u.name as employee_name, u.department,
                COALESCE(e.training_title, 'Untitled Training') as training_title
                FROM training_evaluations e
                JOIN users u ON e.user_id = u.id
                ORDER BY e.status ASC, e.completion_date DESC";
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            error_log("SQL Error in getAllEvaluations: " . $this->conn->error);
            return array(); // Return empty array if query failed
        }
        
        $evaluations = $result->fetch_all(MYSQLI_ASSOC);
        
        return $evaluations;
    }
    
    /**
     * Get pending evaluations
     *
     * @param int $user_id The user ID (optional)
     * @return array The list of pending evaluations
     */
    public function getPendingEvaluations($user_id = null) {
        $sql = "SELECT e.*, u.name as employee_name, u.department,
                COALESCE(e.training_title, 'Untitled Training') as training_title
                FROM training_evaluations e
                JOIN users u ON e.user_id = u.id
                WHERE e.status = 'pending'";
        
        if ($user_id) {
            $sql .= " AND e.user_id = ?";
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                error_log("SQL Prepare Error in getPendingEvaluations: " . $this->conn->error);
                return array(); // Return empty array if prepare failed
            }
            
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $evaluations = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $this->conn->query($sql);
            if (!$result) {
                error_log("SQL Query Error in getPendingEvaluations: " . $this->conn->error);
                return array(); // Return empty array if query failed
            }
            $evaluations = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return $evaluations;
    }
    
    /**
     * Get a user's evaluations
     * 
     * @param int $user_id The user ID
     * @return array The list of user's evaluations
     */
    public function getUserEvaluations($user_id) {
        $sql = "SELECT e.*, 
                COALESCE(e.training_title, 'Untitled Training') as training_title
                FROM training_evaluations e
                WHERE e.user_id = ?
                ORDER BY e.status ASC, e.completion_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in getUserEvaluations: " . $this->conn->error);
            return array(); // Return empty array if prepare failed
        }
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $evaluations = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $evaluations;
    }
    
    /**
     * Get evaluation details
     * 
     * @param int $id The evaluation ID
     * @return array The evaluation details
     */
    public function getEvaluation($id) {
        $sql = "SELECT e.*, u.name as user_name, u.department as user_department
                FROM training_evaluations e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in getEvaluation: " . $this->conn->error);
            return null; // Return null if prepare failed
        }
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $evaluation = $result->fetch_assoc();
        $stmt->close();
        
        if ($evaluation) {
            // Get history
            $sql = "SELECT h.*, u.name as user_name
                    FROM evaluation_history h
                    LEFT JOIN users u ON h.user_id = u.id
                    WHERE h.evaluation_id = ?
                    ORDER BY h.timestamp DESC";
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $history = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                $evaluation['history'] = $history;
            } else {
                error_log("SQL Prepare Error for history in getEvaluation: " . $this->conn->error);
                $evaluation['history'] = array();
            }
        }
        
        return $evaluation;
    }
    
    /**
     * Create a pending evaluation 
     * 
     * @param int $user_id The user ID who will submit the evaluation
     * @param int $training_id The training ID for which the evaluation is created
     * @return bool Success or failure
     */
    public function createPendingEvaluation($user_id, $training_id = null) {
        // Get training details if training_id is provided
        $training_title = '';
        $organiser = '';
        $venue = '';
        $training_date = date('Y-m-d');
        
        if ($training_id) {
            $sql = "SELECT programme_title, organiser, venue, date_time FROM training_applications WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $training_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $training_title = $row['programme_title'];
                    $organiser = $row['organiser'];
                    $venue = $row['venue'];
                    $training_date = date('Y-m-d', strtotime($row['date_time']));
                }
                $stmt->close();
            } else {
                error_log("SQL Prepare Error in createPendingEvaluation: " . $this->conn->error);
            }
        }
        
        // Insert new evaluation record
        $sql = "INSERT INTO training_evaluations (
                    user_id, training_id, status, due_date,
                    training_title, organiser, venue, training_date
                ) VALUES (?, ?, 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY),
                    ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in createPendingEvaluation: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('iissss', 
            $user_id, 
            $training_id,
            $training_title,
            $organiser,
            $venue,
            $training_date
        );
        
        $success = $stmt->execute();
        $evaluation_id = $stmt->insert_id;
        $stmt->close();
        
        if ($success) {
            // Log the creation of the evaluation
            $this->logEvaluationHistory($evaluation_id, 'Created', 'Evaluation request created');
            return true;
        }
        
        return false;
    }
    
    /**
     * Log evaluation history
     * 
     * @param int $evaluation_id The evaluation ID
     * @param string $action The action performed
     * @param string $comments Additional comments
     * @return bool Success or failure
     */
    private function logEvaluationHistory($evaluation_id, $action, $comments = '') {
        $user_id = $_SESSION['user_id'];
        
        $sql = "INSERT INTO evaluation_history 
                (evaluation_id, action, user_id, comments) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error in logEvaluationHistory: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('isis', $evaluation_id, $action, $user_id, $comments);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("SQL Execute Error in logEvaluationHistory: " . $stmt->error);
        }
        
        $stmt->close();
        
        return $success;
    }
}