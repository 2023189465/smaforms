<?php
// Enhanced my_applications.php with status tracking and evaluation support

require_once 'config.php';
require_once 'training_controller.php';
require_once 'gcr_controller.php';
require_once 'TrainingEvaluationController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get type from query string (training, gcr, or evaluation)
$type = isset($_GET['type']) ? $_GET['type'] : 'training';

// Validate and sanitize type
if (!in_array($type, ['training', 'gcr', 'evaluation'])) {
    $type = 'training'; // Default to training if invalid type
}

// Get success/error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

// Initialize appropriate controller based on type
if ($type === 'training') {
    $controller = new TrainingController();
    $workflow_steps = [
        'pending_submission' => ['name' => 'Draft', 'position' => 0, 'color' => 'secondary'],
        'pending_hod' => ['name' => 'HOD Review', 'position' => 1, 'color' => 'info'],
        'pending_hr' => ['name' => 'HR Review', 'position' => 2, 'color' => 'primary'],
        'pending_gm' => ['name' => 'GM Approval', 'position' => 3, 'color' => 'warning'],
        'approved' => ['name' => 'Approved', 'position' => 4, 'color' => 'success'],
        'rejected' => ['name' => 'Rejected', 'position' => 4, 'color' => 'danger']
    ];
} elseif ($type === 'gcr') {
    $controller = new GCRController();
    $workflow_steps = [
        'pending_submission' => ['name' => 'Draft', 'position' => 0, 'color' => 'secondary'],
        'pending_hr1' => ['name' => 'HR Verification', 'position' => 1, 'color' => 'primary'],
        'pending_gm' => ['name' => 'GM Approval', 'position' => 2, 'color' => 'warning'],
        'pending_hr2' => ['name' => 'HR Recording', 'position' => 3, 'color' => 'primary'],
        'approved' => ['name' => 'Approved', 'position' => 4, 'color' => 'success'],
        'rejected' => ['name' => 'Rejected', 'position' => 4, 'color' => 'danger']
    ];
} else { // evaluation
    $controller = new TrainingEvaluationController();
    $workflow_steps = [
        'draft' => ['name' => 'Draft', 'position' => 0, 'color' => 'secondary'],
        'submitted' => ['name' => 'Submitted', 'position' => 1, 'color' => 'success']
    ];
}

// Get user's applications/evaluations
if ($type === 'training') {
    $user_applications = $controller->getUserApplications();
} elseif ($type === 'gcr') {
    $user_applications = $controller->getUserApplications();
} else { // evaluation
    $user_applications = $controller->getUserEvaluations($_SESSION['user_id']);
}

// Get role-based applications (all applications the user can see based on their role)
$all_applications = [];
if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'hr' || $_SESSION['user_role'] === 'gm') {
    if ($type === 'training') {
        $training_controller = new TrainingController();
        $all_applications = $training_controller->getUserApplications();
    } elseif ($type === 'gcr') {
        $gcr_controller = new GCRController();
        $all_applications = $gcr_controller->getUserApplications();
    } elseif ($type === 'evaluation' && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'hr')) {
        $evaluation_controller = new TrainingEvaluationController();
        $all_applications = $evaluation_controller->getAllEvaluations();
    }
}

// Debug information
error_log("User ID: " . $_SESSION['user_id']);
error_log("User Role: " . $_SESSION['user_role']);
error_log("Type: $type");
error_log("User Applications count: " . count($user_applications));
error_log("All Applications count: " . count($all_applications));

// Get database direct query for verification
$conn = connectDB();
$user_id = $_SESSION['user_id'];

// Direct SQL query to verify database records
if ($type === 'gcr') {
    $sql = "SELECT * FROM gcr_applications WHERE user_id = ?";
} elseif ($type === 'training') {
    $sql = "SELECT * FROM training_applications WHERE user_id = ?";
} else { // evaluation
    $sql = "SELECT * FROM training_evaluations WHERE user_id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$direct_applications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

error_log("Direct DB query applications count: " . count($direct_applications));
if (count($direct_applications) > 0) {
    error_log("First direct DB app: " . print_r($direct_applications[0], true));
}

// Also check for all applications in the system (admin only)
if ($_SESSION['user_role'] === 'admin') {
    if ($type === 'gcr') {
        $sql = "SELECT * FROM gcr_applications ORDER BY created_at DESC LIMIT 10";
    } elseif ($type === 'training') {
        $sql = "SELECT * FROM training_applications ORDER BY created_at DESC LIMIT 10";
    } else { // evaluation
        $sql = "SELECT * FROM training_evaluations ORDER BY created_at DESC LIMIT 10";
    }
    
    $result = $conn->query($sql);
    $admin_all_apps = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Admin all apps count: " . count($admin_all_apps));
}

$conn->close();

// Page title based on type
if ($type === 'training') {
    $page_title = 'Training Applications';
    $icon_class = 'bi-journal-text text-primary';
    $new_button_class = 'btn-primary';
} elseif ($type === 'gcr') {
    $page_title = 'GCR Applications';
    $icon_class = 'bi-file-earmark-text text-success';
    $new_button_class = 'btn-success';
} else { // evaluation
    $page_title = 'Training Evaluations';
    $icon_class = 'bi-clipboard-check text-info';
    $new_button_class = 'btn-info';
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending_submission':
            return 'bg-secondary';
        case 'pending_hod':
            return 'bg-info';
        case 'pending_hr':
        case 'pending_hr1':
        case 'pending_hr2':
            return 'bg-primary';
        case 'pending_gm':
            return 'bg-warning';
        case 'approved':
        case 'submitted':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        case 'reviewed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Helper function to get progress percentage based on status
function getProgressPercentage($status, $workflow_steps) {
    if (!isset($workflow_steps[$status])) {
        return 0;
    }
    
    $position = $workflow_steps[$status]['position'];
    $max_position = max(array_column($workflow_steps, 'position'));
    
    if ($max_position === 0) {
        return 0;
    }
    
    return ($position / $max_position) * 100;
}

// Helper function to render progress bar
function renderProgressBar($status, $workflow_steps) {
    if (!isset($workflow_steps[$status])) {
        return '<div class="progress-bar bg-secondary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">Unknown</div>';
    }
    
    $percentage = getProgressPercentage($status, $workflow_steps);
    $color = $workflow_steps[$status]['color'];
    $name = $workflow_steps[$status]['name'];
    
    return '<div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . $percentage . '%" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">' . $name . '</div>';
}
?>

<?php include 'header.php'; ?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="bi <?php echo $icon_class; ?> me-2"></i> 
                                My <?php echo ucfirst($type); ?> <?php echo ($type === 'evaluation') ? 'Forms' : 'Applications'; ?>
                            </h4>
                            <p class="text-muted mb-0">View and manage your <?php echo ($type === 'evaluation') ? 'evaluation forms' : 'applications'; ?></p>
                        </div>
                        <div>
                            <!-- Tabs for different form types -->
                            <div class="btn-group me-2" role="group" aria-label="Form types">
                                <a href="my_applications.php?type=training" class="btn <?php echo $type === 'training' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Training
                                </a>
                                <a href="my_applications.php?type=gcr" class="btn <?php echo $type === 'gcr' ? 'btn-success' : 'btn-outline-success'; ?>">
                                    GCR
                                </a>
                                <a href="my_applications.php?type=evaluation" class="btn <?php echo $type === 'evaluation' ? 'btn-info' : 'btn-outline-info'; ?>">
                                    Evaluations
                                </a>
                            </div>
                            
                            <!-- New form button -->
                            <?php if ($type === 'training'): ?>
                                <a href="training_form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> New Training Application
                                </a>
                            <?php elseif ($type === 'gcr'): ?>
                                <a href="gcr_form.php" class="btn btn-success">
                                    <i class="bi bi-plus-circle me-1"></i> New GCR Application
                                </a>
                            <?php else: ?>
                                <a href="training_evaluation_form.php" class="btn btn-info">
                                    <i class="bi bi-plus-circle me-1"></i> New Evaluation Form
                                </a>
                            <?php endif; ?>
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
    
    <!-- System Status Section (for troubleshooting) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i> System Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">User Information</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>User ID:</span> <strong><?php echo $_SESSION['user_id']; ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Role:</span> <strong><?php echo ucfirst($_SESSION['user_role']); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Name:</span> <strong><?php echo $_SESSION['user_name'] ?? 'Not set'; ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Application Counts</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Controller Applications:</span> <strong><?php echo count($user_applications); ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Direct DB Applications:</span> <strong><?php echo count($direct_applications); ?></strong>
                                        </li>
                                        <?php if ($_SESSION['user_role'] !== 'staff'): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Pending Actions:</span> <strong><?php echo count($all_applications); ?></strong>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Workflow Information</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <span>Type: <strong><?php echo ucfirst($type); ?></strong></span>
                                        </li>
                                        <li class="list-group-item">
                                            <?php if ($type === 'gcr'): ?>
                                                <span>Flow: Staff → HR → GM → HR → Complete</span>
                                            <?php elseif ($type === 'training'): ?>
                                                <span>Flow: Staff → HOD → HR → GM → Complete</span>
                                            <?php else: ?>
                                                <span>Flow: Staff → Submit → Reviewed</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Direct DB Query Results -->
    <?php if (count($direct_applications) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-database-check me-2"></i> Database Records (Direct Query)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <?php if ($type === 'gcr'): ?>
                                        <th>Year</th>
                                        <th>Days</th>
                                    <?php elseif ($type === 'training'): ?>
                                        <th>Title</th>
                                    <?php else: ?>
                                        <th>Training</th>
                                        <th>Organiser</th>
                                    <?php endif; ?>
                                    <th>Location in Workflow</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($direct_applications as $app): ?>
                                <tr>
                                    <td><?php echo $app['id']; ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($app['created_at'])): ?>
                                            <?php echo date('d M Y H:i', strtotime($app['created_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($type === 'gcr'): ?>
                                        <td><?php echo $app['year']; ?></td>
                                        <td><?php echo $app['days_requested']; ?></td>
                                    <?php elseif ($type === 'training'): ?>
                                        <td><?php echo $app['programme_title']; ?></td>
                                    <?php else: ?>
                                        <td><?php echo $app['training_title']; ?></td>
                                        <td><?php echo $app['organiser']; ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="progress">
                                            <?php echo renderProgressBar($app['status'], $workflow_steps); ?>
                                        </div>
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
    
    <!-- My Applications/Evaluations (from Controller) -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i> 
                        My <?php echo ucfirst($type); ?> <?php echo ($type === 'evaluation') ? 'Forms' : 'Applications'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($user_applications) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <?php if ($type === 'training'): ?>
                                            <th>Reference</th>
                                            <th>Title</th>
                                            <th>Organiser</th>
                                        <?php elseif ($type === 'gcr'): ?>
                                            <th>Applicant Name</th>
                                            <th>Year</th>
                                            <th>Days Requested</th>
                                        <?php else: ?>
                                            <th>Training Title</th>
                                            <th>Training Date</th>
                                            <th>Organiser</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_applications as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <?php if ($type === 'training'): ?>
                                                <td><?php echo $application['reference_number'] ?? '<span class="text-muted">Pending</span>'; ?></td>
                                                <td><?php echo htmlspecialchars($application['programme_title']); ?></td>
                                                <td><?php echo htmlspecialchars($application['organiser']); ?></td>
                                            <?php elseif ($type === 'gcr'): ?>
                                                <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                                <td><?php echo $application['year']; ?></td>
                                                <td>
                                                    <?php echo $application['days_requested']; ?>
                                                    <?php if ($application['status'] === 'approved' && isset($application['gm_days_approved'])): ?>
                                                        <span class="text-success">
                                                            (<?php echo $application['gm_days_approved']; ?> approved)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php else: ?>
                                                <td><?php echo htmlspecialchars($application['training_title']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($application['training_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($application['organiser']); ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td style="width: 20%;">
                                                <div class="progress">
                                                    <?php echo renderProgressBar($application['status'], $workflow_steps); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="view_<?php echo ($type === 'evaluation') ? 'evaluation' : 'application'; ?>.php?type=<?php echo $type; ?>&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                                <?php if ($application['status'] === 'pending_submission'): ?>
                                                    <a href="<?php echo $type === 'evaluation' ? 'training_evaluation_form' : $type . '_form'; ?>.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-warning ms-1">
                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="assets/images/empty-<?php echo $type; ?>.svg" alt="No applications" class="img-fluid mb-3" style="max-width: 200px; opacity: 0.5;">
                            <h4>You don't have any <?php echo $type; ?> <?php echo ($type === 'evaluation') ? 'forms' : 'applications'; ?> yet.</h4>
                            <p class="text-muted">Create a new <?php echo strtoupper($type); ?> <?php echo ($type === 'evaluation') ? 'form' : 'application'; ?> to get started.</p>
                            <?php if ($type === 'training'): ?>
                                <a href="training_form.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle me-2"></i> Create a New Training Application
                                </a>
                            <?php elseif ($type === 'gcr'): ?>
                                <a href="gcr_form.php" class="btn btn-success mt-2">
                                    <i class="bi bi-plus-circle me-2"></i> Create a New GCR Application
                                </a>
                            <?php else: ?>
                                <a href="training_evaluation_form.php" class="btn btn-info mt-2">
                                    <i class="bi bi-plus-circle me-2"></i> Create a New Evaluation Form
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin/HR/GM Section: Applications Requiring Action -->
    <?php if (($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'hr' || $_SESSION['user_role'] === 'gm') && count($all_applications) > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check me-2"></i> 
                        <?php echo ucfirst($type); ?> <?php echo ($type === 'evaluation') ? 'Forms' : 'Applications'; ?> Requiring Your Action
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Applicant</th>
                                    <th>Department</th>
                                    <?php if ($type === 'training'): ?>
                                        <th>Title</th>
                                    <?php elseif ($type === 'gcr'): ?>
                                        <th>Year</th>
                                        <th>Days</th>
                                    <?php else: ?>
                                        <th>Training Title</th>
                                        <th>Training Date</th>
                                    <?php endif; ?>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_applications as $application): ?>
                                    <!-- Skip applications not requiring action from this role -->
                                    <?php 
                                    $skip = false;
                                    if ($_SESSION['user_role'] === 'hr' && !in_array($application['status'], ['pending_hr', 'pending_hr1', 'pending_hr2', 'submitted'])) {
                                        $skip = true;
                                    } elseif ($_SESSION['user_role'] === 'gm' && $application['status'] !== 'pending_gm') {
                                        $skip = true;
                                    }
                                    if ($skip) continue;
                                    ?>
                                    
                                    <tr>
                                        <td><?php echo $application['id']; ?></td>
                                        <td><?php echo htmlspecialchars($application['applicant_name'] ?? $application['participant_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($application['applicant_department'] ?? $application['division'] ?? ''); ?></td>
                                        <?php if ($type === 'training'): ?>
                                            <td><?php echo htmlspecialchars($application['programme_title']); ?></td>
                                        <?php elseif ($type === 'gcr'): ?>
                                            <td><?php echo $application['year']; ?></td>
                                            <td><?php echo $application['days_requested']; ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($application['training_title']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($application['training_date'])); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($type === 'training'): ?>
                                                <?php if ($application['status'] === 'pending_hr' && $_SESSION['user_role'] === 'hr'): ?>
                                                    <a href="training_hr_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Review
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_gm' && $_SESSION['user_role'] === 'gm'): ?>
                                                    <a href="training_gm_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_application.php?type=training&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($type === 'gcr'): ?>
                                                <?php if ($application['status'] === 'pending_hr1' && $_SESSION['user_role'] === 'hr'): ?>
                                                    <a href="gcr_hr1_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Verify
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_gm' && $_SESSION['user_role'] === 'gm'): ?>
                                                    <a href="gcr_gm_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </a>
                                                <?php elseif ($application['status'] === 'pending_hr2' && $_SESSION['user_role'] === 'hr'): ?>
                                                    <a href="gcr_hr2_process.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Record
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_application.php?type=gcr&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Evaluation review button for HR/Admin -->
                                                <?php if ($application['status'] === 'submitted' && ($_SESSION['user_role'] === 'hr' || $_SESSION['user_role'] === 'admin')): ?>
                                                    <a href="evaluation_review.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i> Review
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_evaluation.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
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
</div>

<style>
/* Enhanced progress bars */
.progress {
    height: 25px;
    margin-bottom: 0;
}

.progress-bar {
    line-height: 25px;
    font-size: 0.85rem;
}

/* Style for tabs */
.btn-group .btn {
    min-width: 120px;
}

/* Tab button styling */
.btn-outline-primary:hover, .btn-outline-success:hover, .btn-outline-info:hover {
    color: white;
}

/* Status badges */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}
</style>

<?php include 'footer.php'; ?>