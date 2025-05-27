<?php
// Process HOD approval for training application

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in and has HOD role
if (!isLoggedIn() || !hasRole('hod')) {
    header('Location: login.php');
    exit;
}

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=no_application_id');
    exit;
}

$application_id = $_GET['id'];
$controller = new TrainingController();
$application = $controller->getApplication($application_id);

// Check if application exists and is pending HOD approval
if (!$application || $application['status'] !== 'pending_hod') {
    header('Location: dashboard.php?error=invalid_application');
    exit;
}

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->processHODDecision($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Redirect after successful processing
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Page title
$page_title = 'HOD Approval - Training Application';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-people me-2"></i> HOD Approval - Training Application
                    </h3>
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
                    
                    <form id="hodApprovalForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <!-- Display Application Details (Read-only) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i> Application Details
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference</label>
                                        <input type="text" class="form-control" value="<?php echo $application_id; ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Submission Date</label>
                                        <input type="text" class="form-control" value="<?php echo date('d M Y', strtotime($application['created_at'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Training Title</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['programme_title']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Organiser</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['organiser']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Venue</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['venue']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date & Time</label>
                                        <input type="text" class="form-control" value="<?php echo date('d M Y, h:i A', strtotime($application['date_time'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fee (MYR)</label>
                                        <input type="text" class="form-control" value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label class="form-label">Justification</label>
                                    <div class="form-control bg-light" style="min-height: 100px; max-height: 200px; overflow-y: auto;">
                                        <?php echo nl2br(htmlspecialchars($application['justification'])); ?>
                                    </div>
                                </div>
                                
                                <!-- Attachments -->
                                <?php if (!empty($application['documents'])): ?>
                                    <div class="mt-3">
                                        <label class="form-label">Attachments</label>
                                        <div class="list-group">
                                            <?php foreach ($application['documents'] as $document): ?>
                                                <a href="download_document.php?id=<?php echo $document['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="bi bi-file-earmark me-2"></i> 
                                                        <?php echo htmlspecialchars($document['file_name']); ?>
                                                    </span>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <i class="bi bi-download"></i>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- HOD Decision Section -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> HOD Decision
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label required-field">Decision</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="hod_decision" id="hod_recommended" value="recommended" required>
                                            <label class="form-check-label" for="hod_recommended">Recommended</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="hod_decision" id="hod_not_recommended" value="not_recommended" required>
                                            <label class="form-check-label" for="hod_not_recommended">Not Recommended</label>
                                        </div>
                                        <div class="invalid-feedback">Please select your decision.</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hod_comments" class="form-label required-field">Comments</label>
                                    <textarea class="form-control" id="hod_comments" name="hod_comments" rows="3" required></textarea>
                                    <div class="invalid-feedback">Please provide your comments.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-check-circle me-2"></i> Submit Decision
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    var form = document.getElementById('hodApprovalForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include 'footer.php'; ?>