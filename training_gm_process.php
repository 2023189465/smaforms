<?php
// Process GM approval for training application

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in and has GM role
if (!isLoggedIn() || !hasRole('gm')) {
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

// Check if application exists and is pending GM approval
if (!$application || $application['status'] !== 'pending_gm') {
    header('Location: dashboard.php?error=invalid_application');
    exit;
}

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->processGMDecision($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Redirect after successful processing
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Get GM information
$conn = connectDB();
$user_id = $_SESSION['user_id'];
$sql = "SELECT name, position FROM users WHERE id = ? AND role = 'gm'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$gm_info = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Page title
$page_title = 'GM Approval - Training Application';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i> GM Approval - Training Application
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
                    
                    <form id="gmApprovalForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <!-- Display Application Details (Read-only) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i> Application Details
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Number</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['reference_number']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Submission Date</label>
                                        <input type="text" class="form-control" value="<?php echo date('d M Y', strtotime($application['created_at'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Applicant</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_name']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_department']); ?>" readonly>
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
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Credit Hours</label>
                                        <input type="text" class="form-control" value="<?php echo $application['credit_hours']; ?>" readonly>
                                    </div>
                                </div>
                                
                                <!-- Previous Approvals (Read-only) -->
                                <div class="border-top pt-3 mt-4">
                                    <h5>Previous Approvals</h5>
                                    
                                    <!-- HOD Approval -->
                                    <div class="mb-3">
                                        <h6>HOD Decision</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Decision</label>
                                                <input type="text" class="form-control" value="<?php echo $application['hod_decision'] == 'recommended' ? 'Recommended' : 'Not Recommended'; ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">HOD</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['hod_name'] ?? ''); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="form-label">Comments</label>
                                            <div class="form-control bg-light" style="min-height: 60px; max-height: 120px; overflow-y: auto;">
                                                <?php echo nl2br(htmlspecialchars($application['hod_comments'] ?? '')); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- HR Review -->
                                    <div class="mt-3">
                                        <h6>HR Review</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Budget Status</label>
                                                <input type="text" class="form-control" value="<?php echo ucfirst($application['budget_status'] ?? ''); ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">HR Officer</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['hr_name'] ?? ''); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="form-label">Comments</label>
                                            <div class="form-control bg-light" style="min-height: 60px; max-height: 120px; overflow-y: auto;">
                                                <?php echo nl2br(htmlspecialchars($application['hr_comments'] ?? '')); ?>
                                            </div>
                                        </div>
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
                        
                        <!-- GM Decision Section -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> GM Decision
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label required-field">Decision</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_approved" value="approved" required>
                                            <label class="form-check-label" for="gm_approved">Approved</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_rejected" value="rejected" required>
                                            <label class="form-check-label" for="gm_rejected">Rejected</label>
                                        </div>
                                        <div class="invalid-feedback">Please select your decision.</div>
                                    </div>
                                </div>
                                
                                <!-- GM Comments Section -->
                                <div class="mb-3">
                                    <label for="gm_comments" class="form-label">Comments</label>
                                    <textarea class="form-control" id="gm_comments" name="gm_comments" rows="3"></textarea>
                                </div>
                                
                                <!-- Display GM Information -->
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($gm_info['name'] ?? 'DATO DR. ANDERSON TIONG ING HENG'); ?>" readonly>
                                        <input type="hidden" name="gm_name" value="<?php echo htmlspecialchars($gm_info['name'] ?? 'DATO DR. ANDERSON TIONG ING HENG'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Position/Jawatan</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($gm_info['position'] ?? 'PENGURUS BESAR, VU7'); ?>" readonly>
                                        <input type="hidden" name="gm_position" value="<?php echo htmlspecialchars($gm_info['position'] ?? 'PENGURUS BESAR, VU7'); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="gm_date" class="form-label required-field">Date</label>
                                    <input type="date" class="form-control" id="gm_date" name="gm_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">Please select a date.</div>
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
    var form = document.getElementById('gmApprovalForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
});
</script>

<style>
/* Custom styles for the form */
.section-header {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-weight: 600;
    border-left: 4px solid #0d6efd; /* Blue color for Training forms */
}

.form-section {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-bottom: 20px;
}

.required-field::after {
    content: " *";
    color: #dc3545;
}
</style>

<?php include 'footer.php'; ?>