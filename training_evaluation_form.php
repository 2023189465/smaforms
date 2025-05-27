<?php
// training_evaluation_form.php - Standalone Training evaluation form page

require_once 'config.php';
require_once 'TrainingEvaluationController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$evaluationController = new TrainingEvaluationController();
$error_message = '';
$success_message = '';
$evaluation = null;

// Get user profile information with proper error handling
$user_profile = array(); // Initialize to an empty array as a fallback
$conn = connectDB();
if ($conn) {
    $sql = "SELECT name, position, department FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $user_profile = $result->fetch_assoc() ?: array();
        }
        $stmt->close();
    }
    $conn->close();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Map content_relevance to content_rating
    if (isset($_POST['content_relevance'])) {
        $_POST['content_rating'] = $_POST['content_relevance'];
    }
    
    // Map instructor_effectiveness to facilitator_rating
    if (isset($_POST['instructor_effectiveness'])) {
        $_POST['facilitator_rating'] = $_POST['instructor_effectiveness'];
    }
    
    // Map knowledge_subject to knowledge_gained
    if (isset($_POST['knowledge_subject'])) {
        $_POST['knowledge_gained'] = $_POST['knowledge_subject'];
    }
    
    // Map engagement_level to skill_improvement
    if (isset($_POST['engagement_level'])) {
        $_POST['skill_improvement'] = $_POST['engagement_level'];
    }
    
    // Map overall_satisfaction to feedback
    if (isset($_POST['overall_satisfaction'])) {
        $_POST['feedback'] = $_POST['overall_satisfaction'];
    }
    
    // Map duration_training to duration
    if (isset($_POST['duration_training'])) {
        $_POST['duration'] = $_POST['duration_training'];
    }
    
    // Map implementation_plan to application_plan
    if (isset($_POST['implementation_plan'])) {
        $_POST['application_plan'] = $_POST['implementation_plan'];
    }
    
    $result = $evaluationController->submitEvaluation($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Redirect after successful submission
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Check if evaluation ID is provided for editing
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $evaluation_id = $_GET['id'];
    $evaluation = $evaluationController->getEvaluation($evaluation_id);
    
    if ($evaluation && $evaluation['user_id'] != $_SESSION['user_id']) {
        // User cannot access another user's evaluation
        header('Location: dashboard.php?error=unauthorized_access');
        exit;
    }
    
    // Map content_rating to content_relevance for display
    if (isset($evaluation['content_rating'])) {
        $evaluation['content_relevance'] = $evaluation['content_rating'];
    }
    
    // Map facilitator_rating to instructor_effectiveness for display
    if (isset($evaluation['facilitator_rating'])) {
        $evaluation['instructor_effectiveness'] = $evaluation['facilitator_rating'];
    }
    
    // Map knowledge_gained to knowledge_subject for display
    if (isset($evaluation['knowledge_gained'])) {
        $evaluation['knowledge_subject'] = $evaluation['knowledge_gained'];
    }
    
    // Map skill_improvement to engagement_level for display
    if (isset($evaluation['skill_improvement'])) {
        $evaluation['engagement_level'] = $evaluation['skill_improvement'];
    }
    
    // Map feedback to overall_satisfaction for display
    if (isset($evaluation['feedback'])) {
        $evaluation['overall_satisfaction'] = $evaluation['feedback'];
    }
    
    // Map duration to duration_training for display
    if (isset($evaluation['duration'])) {
        $evaluation['duration_training'] = $evaluation['duration'];
    }
    
    // Map application_plan to implementation_plan for display
    if (isset($evaluation['application_plan'])) {
        $evaluation['implementation_plan'] = $evaluation['application_plan'];
    }
} else {
    // If no ID provided, this is a new evaluation form
    $evaluation = [
        'id' => null,
        'user_id' => $_SESSION['user_id'],
        'status' => 'pending'
    ];
}

// Page title
$page_title = 'Training Evaluation Form';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
            <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                    <i class="bi bi-clipboard-check me-2"></i> TRAINING EVALUATION FORM
                    </h3>
                <p class="mb-0 small">HUMAN RESOURCE UNIT (2025/V1)</p>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                <?php if ($evaluation && isset($evaluation['status']) && $evaluation['status'] === 'submitted'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i> This evaluation has been submitted. Thank you for your feedback!
                    </div>
                    
                    <!-- Display read-only version of the submitted evaluation -->
                    <!-- This would display the evaluation in a read-only format -->
                    
                <?php else: ?>
                    <!-- Evaluation Form -->
                    <form id="evaluationForm" method="POST" action="" class="needs-validation" novalidate>
                        <?php if ($evaluation && isset($evaluation['id'])): ?>
                            <input type="hidden" name="evaluation_id" value="<?php echo $evaluation['id']; ?>">
                        <?php endif; ?>
                        
                        <!-- Personal Information Section -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label for="name" class="form-label required-field">NAME</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                    value="<?php echo htmlspecialchars(isset($user_profile['name']) ? $user_profile['name'] : ''); ?>" 
                                    required readonly>
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="post_grade" class="form-label required-field">POST & GRADE</label>
                                <input type="text" class="form-control" id="post_grade" name="post_grade" 
                                    value="<?php echo htmlspecialchars(isset($user_profile['position']) ? $user_profile['position'] : ''); ?>" 
                                    required readonly>
                                <div class="invalid-feedback">Please enter your post and grade.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="division_unit" class="form-label required-field">DIVISION / UNIT</label>
                                <input type="text" class="form-control" id="division_unit" name="division_unit" 
                                    value="<?php echo htmlspecialchars(isset($user_profile['department']) ? $user_profile['department'] : ''); ?>" 
                                    required readonly>
                                <div class="invalid-feedback">Please enter your division/unit.</div>
                            </div>
                        </div>
                        
                        <!-- Training Details Section -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="training_title" class="form-label required-field">TRAINING TITLE</label>
                                <input type="text" class="form-control" id="training_title" name="training_title" 
                                    value="<?php echo isset($evaluation['training_title']) ? htmlspecialchars($evaluation['training_title']) : ''; ?>" 
                                    required>
                                <div class="invalid-feedback">Please enter the training title.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="organiser" class="form-label required-field">ORGANISER</label>
                                <input type="text" class="form-control" id="organiser" name="organiser" 
                                    value="<?php echo isset($evaluation['organiser']) ? htmlspecialchars($evaluation['organiser']) : ''; ?>" 
                                    required>
                                <div class="invalid-feedback">Please enter the organiser.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="training_date" class="form-label required-field">DATE</label>
                                <input type="date" class="form-control" id="training_date" name="training_date" 
                                    value="<?php echo isset($evaluation['training_date']) ? date('Y-m-d', strtotime($evaluation['training_date'])) : date('Y-m-d'); ?>" 
                                    required>
                                <div class="invalid-feedback">Please select the training date.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="venue" class="form-label required-field">VENUE</label>
                                <input type="text" class="form-control" id="venue" name="venue" 
                                    value="<?php echo isset($evaluation['venue']) ? htmlspecialchars($evaluation['venue']) : ''; ?>" 
                                    required>
                                <div class="invalid-feedback">Please enter the venue.</div>
                            </div>
                        </div>
                        
                        <!-- Evaluation Criteria Description -->
                        <div class="alert alert-info">
                            <h5>Evaluation Criteria</h5>
                            <p class="mb-0">Please rate each criterion using the following scale:</p>
                            <ul class="list-unstyled">
                                <li><strong>1 – Poor</strong>: Did not meet expectations; significant improvements are needed</li>
                                <li><strong>2 – Fair</strong>: Had some strengths but many areas that require improvement</li>
                                <li><strong>3 – Good</strong>: Met expectations, with a few strengths and some minor weaknesses</li>
                                <li><strong>4 – Very Good</strong>: Exceeded expectations; well done with minor areas for improvement</li>
                                <li><strong>5 – Excellent</strong>: It was outstanding; all aspects were exceptional and left a lasting positive impact</li>
                            </ul>
                        </div>

                        <!-- Evaluation Section -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50%">CRITERIA</th>
                                        <th style="width: 20%">RATING (1-5)</th>
                                        <th style="width: 30%">REMARKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Content Relevance -->
                                    <tr>
                                        <td>Content Relevance</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="content_relevance_5" name="content_relevance" value="5" <?php echo (isset($evaluation['content_relevance']) && $evaluation['content_relevance'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="content_relevance_5">★</label>
                                                <input type="radio" id="content_relevance_4" name="content_relevance" value="4" <?php echo (isset($evaluation['content_relevance']) && $evaluation['content_relevance'] == 4) ? 'checked' : ''; ?>>
                                                <label for="content_relevance_4">★</label>
                                                <input type="radio" id="content_relevance_3" name="content_relevance" value="3" <?php echo (isset($evaluation['content_relevance']) && $evaluation['content_relevance'] == 3) ? 'checked' : ''; ?>>
                                                <label for="content_relevance_3">★</label>
                                                <input type="radio" id="content_relevance_2" name="content_relevance" value="2" <?php echo (isset($evaluation['content_relevance']) && $evaluation['content_relevance'] == 2) ? 'checked' : ''; ?>>
                                                <label for="content_relevance_2">★</label>
                                                <input type="radio" id="content_relevance_1" name="content_relevance" value="1" <?php echo (isset($evaluation['content_relevance']) && $evaluation['content_relevance'] == 1) ? 'checked' : ''; ?>>
                                                <label for="content_relevance_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Hidden field to ensure content_rating is always submitted -->
                                            <input type="hidden" name="content_rating" id="content_rating" value="<?php echo isset($evaluation['content_relevance']) ? $evaluation['content_relevance'] : ''; ?>">
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="content_relevance_remarks" rows="2"><?php echo isset($evaluation['content_relevance_remarks']) ? htmlspecialchars($evaluation['content_relevance_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Instructor Effectiveness -->
                                    <tr>
                                        <td>Instructor Effectiveness</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="instructor_effectiveness_5" name="instructor_effectiveness" value="5" <?php echo (isset($evaluation['instructor_effectiveness']) && $evaluation['instructor_effectiveness'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="instructor_effectiveness_5">★</label>
                                                <input type="radio" id="instructor_effectiveness_4" name="instructor_effectiveness" value="4" <?php echo (isset($evaluation['instructor_effectiveness']) && $evaluation['instructor_effectiveness'] == 4) ? 'checked' : ''; ?>>
                                                <label for="instructor_effectiveness_4">★</label>
                                                <input type="radio" id="instructor_effectiveness_3" name="instructor_effectiveness" value="3" <?php echo (isset($evaluation['instructor_effectiveness']) && $evaluation['instructor_effectiveness'] == 3) ? 'checked' : ''; ?>>
                                                <label for="instructor_effectiveness_3">★</label>
                                                <input type="radio" id="instructor_effectiveness_2" name="instructor_effectiveness" value="2" <?php echo (isset($evaluation['instructor_effectiveness']) && $evaluation['instructor_effectiveness'] == 2) ? 'checked' : ''; ?>>
                                                <label for="instructor_effectiveness_2">★</label>
                                                <input type="radio" id="instructor_effectiveness_1" name="instructor_effectiveness" value="1" <?php echo (isset($evaluation['instructor_effectiveness']) && $evaluation['instructor_effectiveness'] == 1) ? 'checked' : ''; ?>>
                                                <label for="instructor_effectiveness_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Hidden field to ensure facilitator_rating is always submitted -->
                                            <input type="hidden" name="facilitator_rating" id="facilitator_rating" value="<?php echo isset($evaluation['instructor_effectiveness']) ? $evaluation['instructor_effectiveness'] : ''; ?>">
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="instructor_effectiveness_remarks" rows="2"><?php echo isset($evaluation['instructor_effectiveness_remarks']) ? htmlspecialchars($evaluation['instructor_effectiveness_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Knowledge of Subject Matter -->
                                    <tr>
                                        <td>Knowledge of Subject Matter</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="knowledge_subject_5" name="knowledge_subject" value="5" <?php echo (isset($evaluation['knowledge_subject']) && $evaluation['knowledge_subject'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="knowledge_subject_5">★</label>
                                                <input type="radio" id="knowledge_subject_4" name="knowledge_subject" value="4" <?php echo (isset($evaluation['knowledge_subject']) && $evaluation['knowledge_subject'] == 4) ? 'checked' : ''; ?>>
                                                <label for="knowledge_subject_4">★</label>
                                                <input type="radio" id="knowledge_subject_3" name="knowledge_subject" value="3" <?php echo (isset($evaluation['knowledge_subject']) && $evaluation['knowledge_subject'] == 3) ? 'checked' : ''; ?>>
                                                <label for="knowledge_subject_3">★</label>
                                                <input type="radio" id="knowledge_subject_2" name="knowledge_subject" value="2" <?php echo (isset($evaluation['knowledge_subject']) && $evaluation['knowledge_subject'] == 2) ? 'checked' : ''; ?>>
                                                <label for="knowledge_subject_2">★</label>
                                                <input type="radio" id="knowledge_subject_1" name="knowledge_subject" value="1" <?php echo (isset($evaluation['knowledge_subject']) && $evaluation['knowledge_subject'] == 1) ? 'checked' : ''; ?>>
                                                <label for="knowledge_subject_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Map this to knowledge_gained field that is required -->
                                            <input type="hidden" name="knowledge_gained" id="knowledge_gained" value="<?php echo isset($evaluation['knowledge_subject']) ? $evaluation['knowledge_subject'] : ''; ?>">
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="knowledge_subject_remarks" rows="2"><?php echo isset($evaluation['knowledge_subject_remarks']) ? htmlspecialchars($evaluation['knowledge_subject_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Engagement Level -->
                                    <tr>
                                        <td>Engagement Level</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="engagement_level_5" name="engagement_level" value="5" <?php echo (isset($evaluation['engagement_level']) && $evaluation['engagement_level'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="engagement_level_5">★</label>
                                                <input type="radio" id="engagement_level_4" name="engagement_level" value="4" <?php echo (isset($evaluation['engagement_level']) && $evaluation['engagement_level'] == 4) ? 'checked' : ''; ?>>
                                                <label for="engagement_level_4">★</label>
                                                <input type="radio" id="engagement_level_3" name="engagement_level" value="3" <?php echo (isset($evaluation['engagement_level']) && $evaluation['engagement_level'] == 3) ? 'checked' : ''; ?>>
                                                <label for="engagement_level_3">★</label>
                                                <input type="radio" id="engagement_level_2" name="engagement_level" value="2" <?php echo (isset($evaluation['engagement_level']) && $evaluation['engagement_level'] == 2) ? 'checked' : ''; ?>>
                                                <label for="engagement_level_2">★</label>
                                                <input type="radio" id="engagement_level_1" name="engagement_level" value="1" <?php echo (isset($evaluation['engagement_level']) && $evaluation['engagement_level'] == 1) ? 'checked' : ''; ?>>
                                                <label for="engagement_level_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Map this to skill_improvement field that might be required -->
                                            <input type="hidden" name="skill_improvement" id="skill_improvement" value="<?php echo isset($evaluation['engagement_level']) ? $evaluation['engagement_level'] : ''; ?>">
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="engagement_level_remarks" rows="2"><?php echo isset($evaluation['engagement_level_remarks']) ? htmlspecialchars($evaluation['engagement_level_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Materials Provided -->
                                    <tr>
                                        <td>Materials Provided</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="materials_provided_5" name="materials_provided" value="5" <?php echo (isset($evaluation['materials_provided']) && $evaluation['materials_provided'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="materials_provided_5">★</label>
                                                <input type="radio" id="materials_provided_4" name="materials_provided" value="4" <?php echo (isset($evaluation['materials_provided']) && $evaluation['materials_provided'] == 4) ? 'checked' : ''; ?>>
                                                <label for="materials_provided_4">★</label>
                                                <input type="radio" id="materials_provided_3" name="materials_provided" value="3" <?php echo (isset($evaluation['materials_provided']) && $evaluation['materials_provided'] == 3) ? 'checked' : ''; ?>>
                                                <label for="materials_provided_3">★</label>
                                                <input type="radio" id="materials_provided_2" name="materials_provided" value="2" <?php echo (isset($evaluation['materials_provided']) && $evaluation['materials_provided'] == 2) ? 'checked' : ''; ?>>
                                                <label for="materials_provided_2">★</label>
                                                <input type="radio" id="materials_provided_1" name="materials_provided" value="1" <?php echo (isset($evaluation['materials_provided']) && $evaluation['materials_provided'] == 1) ? 'checked' : ''; ?>>
                                                <label for="materials_provided_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="materials_provided_remarks" rows="2"><?php echo isset($evaluation['materials_provided_remarks']) ? htmlspecialchars($evaluation['materials_provided_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Pace of Training -->
                                    <tr>
                                        <td>Pace of Training</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="pace_training_5" name="pace_training" value="5" <?php echo (isset($evaluation['pace_training']) && $evaluation['pace_training'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="pace_training_5">★</label>
                                                <input type="radio" id="pace_training_4" name="pace_training" value="4" <?php echo (isset($evaluation['pace_training']) && $evaluation['pace_training'] == 4) ? 'checked' : ''; ?>>
                                                <label for="pace_training_4">★</label>
                                                <input type="radio" id="pace_training_3" name="pace_training" value="3" <?php echo (isset($evaluation['pace_training']) && $evaluation['pace_training'] == 3) ? 'checked' : ''; ?>>
                                                <label for="pace_training_3">★</label>
                                                <input type="radio" id="pace_training_2" name="pace_training" value="2" <?php echo (isset($evaluation['pace_training']) && $evaluation['pace_training'] == 2) ? 'checked' : ''; ?>>
                                                <label for="pace_training_2">★</label>
                                                <input type="radio" id="pace_training_1" name="pace_training" value="1" <?php echo (isset($evaluation['pace_training']) && $evaluation['pace_training'] == 1) ? 'checked' : ''; ?>>
                                                <label for="pace_training_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="pace_training_remarks" rows="2"><?php echo isset($evaluation['pace_training_remarks']) ? htmlspecialchars($evaluation['pace_training_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Facilities/Location -->
                                    <tr>
                                        <td>Facilities/Location</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="facilities_location_5" name="facilities_location" value="5" <?php echo (isset($evaluation['facilities_location']) && $evaluation['facilities_location'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="facilities_location_5">★</label>
                                                <input type="radio" id="facilities_location_4" name="facilities_location" value="4" <?php echo (isset($evaluation['facilities_location']) && $evaluation['facilities_location'] == 4) ? 'checked' : ''; ?>>
                                                <label for="facilities_location_4">★</label>
                                                <input type="radio" id="facilities_location_3" name="facilities_location" value="3" <?php echo (isset($evaluation['facilities_location']) && $evaluation['facilities_location'] == 3) ? 'checked' : ''; ?>>
                                                <label for="facilities_location_3">★</label>
                                                <input type="radio" id="facilities_location_2" name="facilities_location" value="2" <?php echo (isset($evaluation['facilities_location']) && $evaluation['facilities_location'] == 2) ? 'checked' : ''; ?>>
                                                <label for="facilities_location_2">★</label>
                                                <input type="radio" id="facilities_location_1" name="facilities_location" value="1" <?php echo (isset($evaluation['facilities_location']) && $evaluation['facilities_location'] == 1) ? 'checked' : ''; ?>>
                                                <label for="facilities_location_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="facilities_location_remarks" rows="2"><?php echo isset($evaluation['facilities_location_remarks']) ? htmlspecialchars($evaluation['facilities_location_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Duration of Training -->
                                    <tr>
                                        <td>Duration of Training</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="duration_training_5" name="duration_training" value="5" <?php echo (isset($evaluation['duration_training']) && $evaluation['duration_training'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="duration_training_5">★</label>
                                                <input type="radio" id="duration_training_4" name="duration_training" value="4" <?php echo (isset($evaluation['duration_training']) && $evaluation['duration_training'] == 4) ? 'checked' : ''; ?>>
                                                <label for="duration_training_4">★</label>
                                                <input type="radio" id="duration_training_3" name="duration_training" value="3" <?php echo (isset($evaluation['duration_training']) && $evaluation['duration_training'] == 3) ? 'checked' : ''; ?>>
                                                <label for="duration_training_3">★</label>
                                                <input type="radio" id="duration_training_2" name="duration_training" value="2" <?php echo (isset($evaluation['duration_training']) && $evaluation['duration_training'] == 2) ? 'checked' : ''; ?>>
                                                <label for="duration_training_2">★</label>
                                                <input type="radio" id="duration_training_1" name="duration_training" value="1" <?php echo (isset($evaluation['duration_training']) && $evaluation['duration_training'] == 1) ? 'checked' : ''; ?>>
                                                <label for="duration_training_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Map this to duration field that might be required -->
                                            <input type="hidden" name="duration" id="duration" value="<?php echo isset($evaluation['duration_training']) ? $evaluation['duration_training'] : ''; ?>">
                                        </td>
                                        <td>
                                        <textarea class="form-control" name="duration_training_remarks" rows="2"><?php echo isset($evaluation['duration_training_remarks']) ? htmlspecialchars($evaluation['duration_training_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Overall Satisfaction -->
                                    <tr>
                                        <td>Overall Satisfaction</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="overall_satisfaction_5" name="overall_satisfaction" value="5" <?php echo (isset($evaluation['overall_satisfaction']) && $evaluation['overall_satisfaction'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="overall_satisfaction_5">★</label>
                                                <input type="radio" id="overall_satisfaction_4" name="overall_satisfaction" value="4" <?php echo (isset($evaluation['overall_satisfaction']) && $evaluation['overall_satisfaction'] == 4) ? 'checked' : ''; ?>>
                                                <label for="overall_satisfaction_4">★</label>
                                                <input type="radio" id="overall_satisfaction_3" name="overall_satisfaction" value="3" <?php echo (isset($evaluation['overall_satisfaction']) && $evaluation['overall_satisfaction'] == 3) ? 'checked' : ''; ?>>
                                                <label for="overall_satisfaction_3">★</label>
                                                <input type="radio" id="overall_satisfaction_2" name="overall_satisfaction" value="2" <?php echo (isset($evaluation['overall_satisfaction']) && $evaluation['overall_satisfaction'] == 2) ? 'checked' : ''; ?>>
                                                <label for="overall_satisfaction_2">★</label>
                                                <input type="radio" id="overall_satisfaction_1" name="overall_satisfaction" value="1" <?php echo (isset($evaluation['overall_satisfaction']) && $evaluation['overall_satisfaction'] == 1) ? 'checked' : ''; ?>>
                                                <label for="overall_satisfaction_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                            <!-- Map this to feedback field that might be required -->
                                            <input type="hidden" name="feedback" id="feedback" value="<?php echo isset($evaluation['overall_satisfaction']) ? $evaluation['overall_satisfaction'] : ''; ?>">
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="overall_satisfaction_remarks" rows="2"><?php echo isset($evaluation['overall_satisfaction_remarks']) ? htmlspecialchars($evaluation['overall_satisfaction_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>

                                    <!-- Overall Rating -->
                                    <tr>
                                        <td>Overall Rating</td>
                                        <td>
                                            <div class="star-rating">
                                                <input type="radio" id="overall_rating_5" name="overall_rating" value="5" <?php echo (isset($evaluation['overall_rating']) && $evaluation['overall_rating'] == 5) ? 'checked' : ''; ?> required>
                                                <label for="overall_rating_5">★</label>
                                                <input type="radio" id="overall_rating_4" name="overall_rating" value="4" <?php echo (isset($evaluation['overall_rating']) && $evaluation['overall_rating'] == 4) ? 'checked' : ''; ?>>
                                                <label for="overall_rating_4">★</label>
                                                <input type="radio" id="overall_rating_3" name="overall_rating" value="3" <?php echo (isset($evaluation['overall_rating']) && $evaluation['overall_rating'] == 3) ? 'checked' : ''; ?>>
                                                <label for="overall_rating_3">★</label>
                                                <input type="radio" id="overall_rating_2" name="overall_rating" value="2" <?php echo (isset($evaluation['overall_rating']) && $evaluation['overall_rating'] == 2) ? 'checked' : ''; ?>>
                                                <label for="overall_rating_2">★</label>
                                                <input type="radio" id="overall_rating_1" name="overall_rating" value="1" <?php echo (isset($evaluation['overall_rating']) && $evaluation['overall_rating'] == 1) ? 'checked' : ''; ?>>
                                                <label for="overall_rating_1">★</label>
                                            </div>
                                            <div class="rating-description">
                                                <span>1 - Poor</span>
                                                <span class="float-end">5 - Excellent</span>
                                            </div>
                                        </td>
                                        <td>
                                            <textarea class="form-control" name="overall_rating_remarks" rows="2"><?php echo isset($evaluation['overall_rating_remarks']) ? htmlspecialchars($evaluation['overall_rating_remarks']) : ''; ?></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Additional Feedback Section -->
                        <div class="mb-4">
                            <div class="mb-3">
                                <label for="additional_comments" class="form-label"><strong>Additional Comments:</strong></label>
                                <textarea class="form-control" id="additional_comments" name="additional_comments" rows="3"><?php echo isset($evaluation['additional_comments']) ? htmlspecialchars($evaluation['additional_comments']) : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="suggestions" class="form-label"><strong>Suggestions for Improvement:</strong></label>
                                <textarea class="form-control" id="suggestions" name="suggestions" rows="3"><?php echo isset($evaluation['suggestions']) ? htmlspecialchars($evaluation['suggestions']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Impact Assessment Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Impact Assessment</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="key_takeaway" class="form-label required-field">1. What is one key takeaway from this training session?</label>
                                    <textarea class="form-control" id="key_takeaway" name="key_takeaway" rows="3" required><?php echo isset($evaluation['key_takeaway']) ? htmlspecialchars($evaluation['key_takeaway']) : ''; ?></textarea>
                                    <div class="invalid-feedback">Please share your key takeaway.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="implementation_plan" class="form-label required-field">2. How do you plan to implement what you learned in this session into your current role?</label>
                                    <textarea class="form-control" id="implementation_plan" name="implementation_plan" rows="3" required><?php echo isset($evaluation['implementation_plan']) ? htmlspecialchars($evaluation['implementation_plan']) : ''; ?></textarea>
                                    <div class="invalid-feedback">Please share your implementation plan.</div>
                                </div>
                                
                                <!-- Create hidden field for application_plan to match database field -->
                                <input type="hidden" name="application_plan" id="application_plan" value="<?php echo isset($evaluation['implementation_plan']) ? htmlspecialchars($evaluation['implementation_plan']) : ''; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label required-field">3. Would you recommend this training to others?</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recommend" id="recommend_yes" value="Yes" <?php echo (isset($evaluation['recommend']) && $evaluation['recommend'] == 'Yes') ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="recommend_yes">Yes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recommend" id="recommend_no" value="No" <?php echo (isset($evaluation['recommend']) && $evaluation['recommend'] == 'No') ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="recommend_no">No</label>
                                </div>
                                    <div class="mt-2">
                                        <label for="recommend_reason" class="form-label">Why or why not?</label>
                                        <textarea class="form-control" id="recommend_reason" name="recommend_reason" rows="2"><?php echo isset($evaluation['recommend_reason']) ? htmlspecialchars($evaluation['recommend_reason']) : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="future_topics" class="form-label">4. Future training topics: What topics would you like to see in the upcoming in-house training sessions?</label>
                                    <textarea class="form-control" id="future_topics" name="future_topics" rows="3"><?php echo isset($evaluation['future_topics']) ? htmlspecialchars($evaluation['future_topics']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-check-circle me-2"></i> Submit Evaluation
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles */
.required-field::after {
    content: " *";
    color: #dc3545;
}

.table-bordered th, .table-bordered td {
    vertical-align: middle;
}

.form-select, .form-control {
    border: 1px solid #ced4da;
}

.card-header {
    font-weight: bold;
}

/* Star Rating Styles */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    color: #ddd;
    font-size: 1.5rem;
    cursor: pointer;
    transition: color 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffc107;
}

.star-rating input:checked + label {
    color: #ffc107;
}

.rating-description {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    var form = document.getElementById('evaluationForm');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
            
            // Copy implementation_plan to application_plan
            document.getElementById('application_plan').value = document.getElementById('implementation_plan').value;
        }, false);
    }

    // Star rating hover effect
    const starRatings = document.querySelectorAll('.star-rating');
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('label');
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const index = Array.from(stars).indexOf(this);
                stars.forEach((s, i) => {
                    if (i >= index) {
                        s.style.color = '#ffc107';
                    }
                });
            });

            star.addEventListener('mouseout', function() {
                const checkedInput = rating.querySelector('input:checked');
                if (checkedInput) {
                    const checkedIndex = Array.from(rating.querySelectorAll('input')).indexOf(checkedInput);
                    stars.forEach((s, i) => {
                        s.style.color = i >= checkedIndex ? '#ffc107' : '#ddd';
                    });
                } else {
                    stars.forEach(s => s.style.color = '#ddd');
                }
            });
        });
    });
    
    // Update content_rating hidden field when content_relevance is changed
    const contentRelevanceInputs = document.querySelectorAll('input[name="content_relevance"]');
    const contentRatingField = document.getElementById('content_rating');
    
    contentRelevanceInputs.forEach(input => {
        input.addEventListener('change', function() {
            contentRatingField.value = this.value;
        });
    });
    
    // Update facilitator_rating hidden field when instructor_effectiveness is changed
    const instructorEffectivenessInputs = document.querySelectorAll('input[name="instructor_effectiveness"]');
    const facilitatorRatingField = document.getElementById('facilitator_rating');
    
    instructorEffectivenessInputs.forEach(input => {
        input.addEventListener('change', function() {
            facilitatorRatingField.value = this.value;
        });
    });
    
    // Update knowledge_gained field 
    const knowledgeSubjectInputs = document.querySelectorAll('input[name="knowledge_subject"]');
    const knowledgeGainedField = document.getElementById('knowledge_gained');
    
    knowledgeSubjectInputs.forEach(input => {
        input.addEventListener('change', function() {
            knowledgeGainedField.value = this.value;
        });
    });
    
    // Update skill_improvement field
    const engagementLevelInputs = document.querySelectorAll('input[name="engagement_level"]');
    const skillImprovementField = document.getElementById('skill_improvement');
    
    engagementLevelInputs.forEach(input => {
        input.addEventListener('change', function() {
            skillImprovementField.value = this.value;
        });
    });
    
    // Update duration field
    const durationTrainingInputs = document.querySelectorAll('input[name="duration_training"]');
    const durationField = document.getElementById('duration');
    
    durationTrainingInputs.forEach(input => {
        input.addEventListener('change', function() {
            durationField.value = this.value;
        });
    });
    
    // Update feedback field
    const overallSatisfactionInputs = document.querySelectorAll('input[name="overall_satisfaction"]');
    const feedbackField = document.getElementById('feedback');
    
    overallSatisfactionInputs.forEach(input => {
        input.addEventListener('change', function() {
            feedbackField.value = this.value;
        });
    });
});
</script>

<?php include 'footer.php'; ?>