<?php
//  Main dashboard for users

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'training_controller.php';
require_once 'gcr_controller.php';
require_once 'TrainingEvaluationController.php'; 

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user information with null checks
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// Verify all required session variables are set
if (!$user_id || !$user_role || !$user_name) {
    // Clear session and redirect to login
    session_destroy();
    header('Location: login.php?error=session_expired');
    exit;
}

// Initialize controllers
$training_controller = new TrainingController();
$gcr_controller = new GCRController();
$evaluation_controller = new TrainingEvaluationController(); // Initialize Evaluation Controller

// Get pending applications based on user role
$pending_training = array();
$pending_gcr = array();
$pending_evaluations = array(); // Add pending evaluations array

switch ($user_role) {
    case 'staff':
        // For staff, get pending training evaluations
        $pending_evaluations = $evaluation_controller->getPendingEvaluations($user_id);
        break;
        
    case 'hod':
        $pending_training = $training_controller->getUserApplications('pending_hod');
        break;
        
    case 'hr':
        $pending_training = $training_controller->getUserApplications('pending_hr');
        $pending_gcr_hr1 = $gcr_controller->getUserApplications('pending_hr1');
        $pending_gcr_hr2 = $gcr_controller->getUserApplications('pending_hr2');
        $pending_gcr_hr3 = $gcr_controller->getUserApplications('pending_hr3'); // Add this line
        $pending_gcr = array_merge($pending_gcr_hr1, $pending_gcr_hr2, $pending_gcr_hr3); // Update this line
        // HR also needs to see all submitted evaluations
        $pending_evaluations = $evaluation_controller->getAllEvaluations();
        break;
        
    case 'gm':
        $pending_training = $training_controller->getUserApplications('pending_gm');
        $pending_gcr_gm = $gcr_controller->getUserApplications('pending_gm');
        $pending_gcr_gm_final = $gcr_controller->getUserApplications('pending_gm_final'); // Add this line
        $pending_gcr = array_merge($pending_gcr_gm, $pending_gcr_gm_final); // Update this line
        break;
        
    case 'admin':
        // Admins can see all
        $pending_training = $training_controller->getUserApplications();
        $pending_gcr = $gcr_controller->getUserApplications();
        $pending_evaluations = $evaluation_controller->getAllEvaluations();
        break;
}

// Get all applications for current user
$my_training = $training_controller->getUserApplications();
$my_gcr = $gcr_controller->getUserApplications();
$my_evaluations = $evaluation_controller->getUserEvaluations($user_id); // Get user's evaluations

// Handle success/error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

// Page title
$page_title = 'Dashboard';

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending_submission':
            return 'bg-secondary';
        case 'pending_hod':
            return 'bg-info';
        case 'pending_hr':
        case 'pending_hr1':
        case 'pending_hr2':
        case 'pending_hr3':  // Add this line
            return 'bg-primary';
        case 'pending_gm':
        case 'pending_gm_final':  // Add this line
            return 'bg-warning';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        case 'pending':
            return 'bg-warning';
        case 'completed':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}
?>

<?php include 'header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-grid-1x2 me-2"></i> Dashboard
                        </h4>
                        <div>
                            <span class="text-muted me-2">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                            <span class="badge bg-primary"><?php echo ucfirst($user_role); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Actions Cards -->
    <div class="row mb-4">
        <!-- Training Application -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-5 text-primary mb-3">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h5 class="card-title">Training Application</h5>
                    <p class="card-text">Submit a new training application form</p>
                    <a href="training_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> New Application
                    </a>
                </div>
            </div>
        </div>

        <!-- GCR Application -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-5 text-success mb-3">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h5 class="card-title">GCR Application</h5>
                    <p class="card-text">Submit a new GCR application form</p>
                    <a href="gcr_form.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i> New Application
                    </a>
                </div>
            </div>
        </div>

        <!-- Training Evaluation -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-5 text-info mb-3">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h5 class="card-title">Training Evaluation</h5>
                    <p class="card-text">Submit feedback for any training attended</p>
                    <a href="training_evaluation_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> New Evaluation
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Actions -->
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="display-5 text-warning mb-3">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h5 class="card-title">Pending Actions</h5>
                    <p class="card-text">Applications requiring your attention</p>
                    <h3 class="text-warning">
                        <?php echo count($pending_training) + count($pending_gcr); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    
    <!-- Pending Evaluations for Staff -->
    <?php if ($user_role === 'staff' && count($pending_evaluations) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check me-2"></i> Your Training Evaluations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> You have <?php echo count($pending_evaluations); ?> evaluations in progress. You can complete them at your convenience.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_evaluations as $evaluation): ?>
                                <tr>
                                    <td><?php echo $evaluation['id']; ?></td>
                                    <td><?php echo htmlspecialchars($evaluation['training_title'] ?? 'Untitled Evaluation'); ?></td>
                                    <td>
                                        <span class="badge bg-warning">In Progress</span>
                                    </td>
                                    <td>
                                        <a href="training_evaluation_form.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-clipboard-check me-1"></i> Complete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Pending Applications For Non-Staff Users -->
    <?php if ($user_role != 'staff' && (count($pending_training) > 0 || count($pending_gcr) > 0 || count($pending_evaluations) > 0)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split me-2"></i> Pending Applications
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="pendingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="training-tab" data-bs-toggle="tab" data-bs-target="#training" type="button" role="tab" aria-controls="training" aria-selected="true">
                                Training <span class="badge bg-primary rounded-pill ms-2"><?php echo count($pending_training); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="gcr-tab" data-bs-toggle="tab" data-bs-target="#gcr" type="button" role="tab" aria-controls="gcr" aria-selected="false">
                                GCR <span class="badge bg-success rounded-pill ms-2"><?php echo count($pending_gcr); ?></span>
                            </button>
                        </li>
                        <!-- New Evaluations Tab for HR and Admin -->
                        <?php if ($user_role === 'hr' || $user_role === 'admin'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="evaluations-tab" data-bs-toggle="tab" data-bs-target="#evaluations" type="button" role="tab" aria-controls="evaluations" aria-selected="false">
                                Evaluations <span class="badge bg-info rounded-pill ms-2"><?php echo count($pending_evaluations); ?></span>
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="tab-content p-3" id="pendingTabsContent">
                        <!-- Training Tab Content -->
                        <div class="tab-pane fade show active" id="training" role="tabpanel" aria-labelledby="training-tab">
                            <?php if (count($pending_training) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Reference</th>
                                            <th>Title</th>
                                            <th>Applicant</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_training as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <td><?php echo $application['reference_number'] ?? 'Pending'; ?></td>
                                            <td><?php echo htmlspecialchars($application['programme_title'] ?? 'Untitled Training'); ?></td>
                                            <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($application['status'] === 'pending_hod' && $user_role === 'hod'): ?>
                                                    <a href="training_hod_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Review
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_hr' && $user_role === 'admin'): ?>
                                                    <a href="training_hr_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Process
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_gm' && $user_role === 'gm'): ?>
                                                    <a href="training_gm_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_application.php?type=training&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No pending training applications.
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- GCR Tab Content -->
                        <div class="tab-pane fade" id="gcr" role="tabpanel" aria-labelledby="gcr-tab">
                            <?php if (count($pending_gcr) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Reference</th>
                                            <th>Applicant</th>
                                            <th>Year</th>
                                            <th>Days</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_gcr as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <td><?php echo $application['reference_number'] ?? 'Pending'; ?></td>
                                            <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                            <td><?php echo $application['year']; ?></td>
                                            <td><?php echo $application['days_requested']; ?></td>
                                            <td><?php echo date('d M Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($application['status'] === 'pending_hr1' && $user_role === 'hr'): ?>
                                                    <a href="gcr_hr1_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Verify
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_gm' && $user_role === 'gm'): ?>
                                                    <a href="gcr_gm_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_hr2' && $user_role === 'hr'): ?>
                                                    <a href="gcr_hr2_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Record
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_hr3' && $user_role === 'hr'): ?>
                                                    <a href="gcr_hr3_verification.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Verify Lampiran A
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_gm_final' && $user_role === 'gm'): ?>
                                                    <a href="gcr_gm_final.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Final Approval
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_application.php?type=gcr&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No pending GCR applications.
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Evaluations Tab Content (for HR and Admin) -->
                        <?php if ($user_role === 'hr' || $user_role === 'admin'): ?>
                        <div class="tab-pane fade" id="evaluations" role="tabpanel" aria-labelledby="evaluations-tab">
                            <?php if (count($pending_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Training Title</th>
                                            <th>Employee</th>
                                            <th>Submission Date</th>
                                            <th>Overall Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_evaluations as $evaluation): ?>
                                        <tr>
                                            <td><?php echo $evaluation['id']; ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['training_title'] ?? 'Untitled Training'); ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['employee_name']); ?></td>
                                            <td><?php echo isset($evaluation['submitted_date']) ? date('d M Y', strtotime($evaluation['submitted_date'])) : 'Not submitted'; ?></td>
                                            <td>
                                                <?php if (isset($evaluation['overall_rating'])): ?>
                                                    <?php 
                                                        $rating = $evaluation['overall_rating'];
                                                        $stars = '';
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            $stars .= ($i <= $rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>';
                                                        }
                                                        echo $stars;
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not rated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($evaluation['status']); ?>">
                                                    <?php echo ucwords($evaluation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No training evaluations found.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- My Recent Applications -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2"></i> My Recent Applications
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="myTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="my-training-tab" data-bs-toggle="tab" data-bs-target="#my-training" type="button" role="tab" aria-controls="my-training" aria-selected="true">
                                Training <span class="badge bg-primary rounded-pill ms-2"><?php echo count($my_training); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="my-gcr-tab" data-bs-toggle="tab" data-bs-target="#my-gcr" type="button" role="tab" aria-controls="my-gcr" aria-selected="false">
                                GCR <span class="badge bg-success rounded-pill ms-2"><?php echo count($my_gcr); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="my-evaluations-tab" data-bs-toggle="tab" data-bs-target="#my-evaluations" type="button" role="tab" aria-controls="my-evaluations" aria-selected="false">
                                Evaluations <span class="badge bg-info rounded-pill ms-2"><?php echo count($my_evaluations); ?></span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="myTabsContent">
                        <!-- My Training Tab -->
                        <div class="tab-pane fade show active" id="my-training" role="tabpanel" aria-labelledby="my-training-tab">
                            <?php if (count($my_training) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Reference</th>
                                            <th>Title</th>
                                            <th>Organiser</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_training as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <td><?php echo $application['reference_number'] ?? 'Pending'; ?></td>
                                            <td><?php echo htmlspecialchars($application['programme_title'] ?? 'Untitled Training'); ?></td>
                                            <td><?php echo htmlspecialchars($application['organiser']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_application.php?type=training&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No training applications found.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- My GCR Tab -->
                        <div class="tab-pane fade" id="my-gcr" role="tabpanel" aria-labelledby="my-gcr-tab">
                            <?php if (count($my_gcr) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Applicant Name</th> <!-- change to title/staff name -->
                                            <th>Year</th>
                                            <th>Days Requested</th>
                                            <th>Submission Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_gcr as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <td><?php echo $application['applicant_name']; ?></td>
                                            <td><?php echo $application['year']; ?></td>
                                            <td>
                                                <?php echo $application['days_requested']; ?>
                                                <?php if ($application['status'] === 'approved' && isset($application['gm_days_approved'])): ?>
                                                    <span class="text-success">
                                                        (<?php echo $application['gm_days_approved']; ?> approved)
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view_application.php?type=gcr&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No GCR applications found.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- My Evaluations Tab -->
                        <div class="tab-pane fade" id="my-evaluations" role="tabpanel" aria-labelledby="my-evaluations-tab">
                            <?php if (count($my_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Training Title</th>
                                            <th>Completion Date</th>
                                            <th>Submission Date</th>
                                            <th>Overall Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_evaluations as $evaluation): ?>
                                        <tr>
                                            <td><?php echo $evaluation['id']; ?></td>
                                            <td><?php echo htmlspecialchars($evaluation['training_title'] ?? 'Untitled Training'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($evaluation['completion_date'] ?? date('Y-m-d'))); ?></td>
                                            <td><?php echo isset($evaluation['submitted_date']) ? date('d M Y', strtotime($evaluation['submitted_date'])) : 'Not submitted'; ?></td>
                                            <td>
                                                <?php if (isset($evaluation['overall_rating'])): ?>
                                                    <?php 
                                                        $rating = $evaluation['overall_rating'];
                                                        $stars = '';
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            $stars .= ($i <= $rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>';
                                                        }
                                                        echo $stars;
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not rated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($evaluation['status']); ?>">
                                                    <?php echo ucwords($evaluation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($evaluation['status'] === 'pending'): ?>
                                                    <a href="training_evaluation_form.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-clipboard-check me-1"></i> Complete
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_evaluation.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No training evaluations found.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>