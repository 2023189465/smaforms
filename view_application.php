<?php
// view_application.php - View application details

require_once 'config.php';
require_once 'training_controller.php';
require_once 'gcr_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check required parameters
if (!isset($_GET['type']) || !isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=invalid_parameters');
    exit;
}

$type = $_GET['type'];
$application_id = $_GET['id'];
$application = null;
$controller = null;

// Determine controller based on application type
if ($type === 'training') {
    $controller = new TrainingController();
    $application = $controller->getApplication($application_id);
    $page_title = 'View Training Application';
} elseif ($type === 'gcr') {
    $controller = new GCRController();
    $application = $controller->getApplication($application_id);
    $page_title = 'View GCR Application';
} else {
    header('Location: dashboard.php?error=invalid_application_type');
    exit;
}

// Check if application exists
if (!$application) {
    header('Location: dashboard.php?error=application_not_found');
    exit;
}

// Get HOD and HR users for displaying names
$conn = connectDB();

// Get HOD users
$sql = "SELECT id, name, position FROM users WHERE role = 'hod'";
$result = $conn->query($sql);
$hod_users = $result->fetch_all(MYSQLI_ASSOC);
$hod_map = [];
foreach ($hod_users as $hod) {
    $hod_map[$hod['id']] = $hod['name'] . ' (' . $hod['position'] . ')';
}

// Get HR users
$sql = "SELECT id, name, position FROM users WHERE role = 'admin'";
$result = $conn->query($sql);
$hr_users = $result->fetch_all(MYSQLI_ASSOC);
$hr_map = [];
foreach ($hr_users as $hr) {
    $hr_map[$hr['id']] = $hr['name'] . ' (' . $hr['position'] . ')';
}

$conn->close();

// Common function to display status badge
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
        default:
            return 'bg-secondary';
    }
}
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header <?php echo $type === 'training' ? 'bg-primary' : 'bg-success'; ?> text-white">
                    <h3 class="mb-0">
                        <?php if ($type === 'training'): ?>
                            <i class="bi bi-journal-text me-2"></i> Training Application Details
                        <?php else: ?>
                            <i class="bi bi-file-earmark-text me-2"></i> GCR Application Details
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Status and Reference -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5>
                            <?php if (isset($application['reference_number']) && !empty($application['reference_number'])): ?>
                                <span class="badge bg-dark"><?php echo htmlspecialchars($application['reference_number']); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No Reference Number</span>
                            <?php endif; ?>
                        </h5>
                        <h5>
                            <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                            </span>
                        </h5>
                    </div>
                    
                    <!-- Training Application Specific View -->
                    <?php if ($type === 'training'): ?>
                        <!-- Application Process Visualization -->
                        <div class="process-container mb-4">
                            <div class="section-header">
                                <i class="bi bi-diagram-3 me-2"></i> Application Process
                            </div>
                            <div class="process-steps">
                                <div class="step <?php echo in_array($application['status'], ['pending_submission', 'pending_hod', 'pending_hr', 'pending_gm', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-1-circle<?php echo in_array($application['status'], ['pending_submission', 'pending_hod', 'pending_hr', 'pending_gm', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Staff Submission</h5>
                                        <p>Application submitted</p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_hod', 'pending_hr', 'pending_gm', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-2-circle<?php echo in_array($application['status'], ['pending_hod', 'pending_hr', 'pending_gm', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>HOD Approval</h5>
                                        <p><?php echo in_array($application['status'], ['pending_hod']) ? 'In progress' : (in_array($application['status'], ['pending_hr', 'pending_gm', 'approved']) ? 'Approved' : (in_array($application['status'], ['rejected']) && isset($application['hod_decision']) && $application['hod_decision'] == 'not_recommended' ? 'Rejected' : 'Pending')); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_hr', 'pending_gm', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-3-circle<?php echo in_array($application['status'], ['pending_hr', 'pending_gm', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>HR Review</h5>
                                        <p><?php echo in_array($application['status'], ['pending_hr']) ? 'In progress' : (in_array($application['status'], ['pending_gm', 'approved']) ? 'Processed' : (in_array($application['status'], ['rejected']) && isset($application['hod_decision']) && $application['hod_decision'] != 'not_recommended' ? 'Rejected' : 'Pending')); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_gm', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-4-circle<?php echo in_array($application['status'], ['pending_gm', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>GM Approval</h5>
                                        <p><?php echo $application['status'] === 'pending_gm' ? 'In progress' : ($application['status'] === 'approved' ? 'Approved' : ($application['status'] === 'rejected' && isset($application['gm_decision']) && $application['gm_decision'] === 'rejected' ? 'Rejected' : 'Pending')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section A: Programme Details -->
                        <div class="form-section mb-4" id="sectionA">
                            <div class="section-header">
                                <i class="bi bi-journal-text me-2"></i> SECTION A: PROGRAMME DETAILS
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Title</label>
                                        <p><?php echo htmlspecialchars($application['programme_title']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Venue</label>
                                        <p><i class="bi bi-geo-alt text-muted me-1"></i> <?php echo htmlspecialchars($application['venue']); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Organiser</label>
                                        <p><i class="bi bi-building text-muted me-1"></i> <?php echo htmlspecialchars($application['organiser']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Date & Time</label>
                                        <p><i class="bi bi-calendar-event text-muted me-1"></i> <?php echo date('d M Y, h:i A', strtotime($application['date_time'])); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Fee (MYR)</label>
                                        <p><i class="bi bi-currency-dollar text-muted me-1"></i> RM <?php echo number_format($application['fee'], 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section B: Requestor Details -->
                        <div class="form-section mb-4" id="sectionB">
                            <div class="section-header">
                                <i class="bi bi-person-badge me-2"></i> SECTION B: REQUESTOR DETAILS
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Name</label>
                                        <p><i class="bi bi-person text-muted me-1"></i> <?php echo htmlspecialchars($application['requestor_name']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Position/Grade</label>
                                        <p><i class="bi bi-briefcase text-muted me-1"></i> <?php echo htmlspecialchars($application['post_grade']); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Unit/Division</label>
                                        <p><i class="bi bi-diagram-3 text-muted me-1"></i> <?php echo htmlspecialchars($application['unit_division']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Submission Date</label>
                                        <p><i class="bi bi-calendar text-muted me-1"></i> <?php echo date('d M Y', strtotime($application['requestor_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section C: Justifications -->
                        <div class="form-section mb-4" id="sectionC">
                            <div class="section-header">
                                <i class="bi bi-chat-left-text me-2"></i> SECTION C: JUSTIFICATION(S) FOR ATTENDING
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Justification</label>
                                    <div class="p-3 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($application['justification'])); ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($application['documents'])): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Training Brochure</label>
                                    <div class="list-group">
                                        <?php foreach ($application['documents'] as $document): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php 
                                                    $ext = pathinfo($document['file_name'], PATHINFO_EXTENSION);
                                                    $icon_class = 'bi-file-earmark';
                                                    $text_class = '';
                                                    
                                                    switch(strtolower($ext)) {
                                                        case 'pdf':
                                                            $icon_class = 'bi-file-earmark-pdf';
                                                            $text_class = 'text-danger';
                                                            break;
                                                        case 'doc':
                                                        case 'docx':
                                                            $icon_class = 'bi-file-earmark-word';
                                                            $text_class = 'text-primary';
                                                            break;
                                                        case 'jpg':
                                                        case 'jpeg':
                                                        case 'png':
                                                            $icon_class = 'bi-file-earmark-image';
                                                            $text_class = 'text-success';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="bi <?php echo $icon_class; ?> me-2 <?php echo $text_class; ?>"></i> 
                                                    <?php echo htmlspecialchars($document['file_name']); ?>
                                                    <span class="text-muted ms-2">(<?php echo round($document['file_size']/1024, 2); ?> KB)</span>
                                                </span>
                                                <div>
                                                    <button type="button" class="btn btn-info btn-sm me-2" onclick="previewFile(<?php echo $document['id']; ?>, '<?php echo htmlspecialchars(addslashes($document['file_name'])); ?>', '<?php echo strtolower($ext); ?>')">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="download_document.php?id=<?php echo $document['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Modal for file preview -->
                            <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="filePreviewModalLabel">File Preview</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" id="filePreviewContent">
                                            <!-- Preview content will be loaded here -->
                                            <div class="text-center p-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-3">Loading preview...</p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <a href="#" class="btn btn-primary" id="previewDownloadBtn">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <!-- Section D: HOD Recommendation -->
                        <div class="form-section mb-4" id="sectionD">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> SECTION D: RECOMMENDATION BY HEAD OF DIVISION / DEPUTY GENERAL MANAGER
                                <?php if ($application['status'] == 'pending_submission'): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($application['status'] == 'pending_submission'): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['hod_decision']) && !empty($application['hod_decision'])): ?>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label fw-bold">Decision</label>
                                            <p>
                                                <?php if ($application['hod_decision'] == 'recommended'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Recommended</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Not Recommended</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($application['hod_comments']) && !empty($application['hod_comments'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Comments</label>
                                            <div class="p-3 bg-light rounded">
                                                <?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">HOD Name</label>
                                            <p><?php echo htmlspecialchars($application['hod_name'] ?? 'Not specified'); ?></p>
                                        </div>
                                        
                                        <?php if (isset($application['hod_date']) && !empty($application['hod_date'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Date</label>
                                                <p><?php echo date('d M Y', strtotime($application['hod_date'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> This section will be completed by the Head of Division.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Section E: HR Comments -->
                        <div class="form-section mb-4" id="sectionE">
                            <div class="section-header">
                                <i class="bi bi-briefcase me-2"></i> SECTION E: COMMENTS BY HUMAN RESOURCE UNIT
                                <?php if (in_array($application['status'], ['pending_submission', 'pending_hod'])): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (in_array($application['status'], ['pending_submission', 'pending_hod'])): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['reference_number']) && !empty($application['reference_number'])): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Reference Number</label>
                                            <p><i class="bi bi-hash text-muted me-1"></i> <?php echo htmlspecialchars($application['reference_number']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['hr_comments']) && !empty($application['hr_comments'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Comments</label>
                                        <div class="p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($application['hr_comments'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['budget_status']) && !empty($application['budget_status'])): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Budget</label>
                                            <p>
                                                <?php if ($application['budget_status'] == 'yes'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Not Available</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        
                                        <?php if (isset($application['credit_hours']) && !empty($application['credit_hours'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Credit Hours</label>
                                                <p><?php echo htmlspecialchars($application['credit_hours']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['budget_comments']) && !empty($application['budget_comments'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Budget Comments</label>
                                        <div class="p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($application['budget_comments'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['hr_name']) && !empty($application['hr_name'])): ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">HR Officer</label>
                                            <p><?php echo htmlspecialchars($application['hr_name']); ?></p>
                                        </div>
                                        <?php if (isset($application['hr_date']) && !empty($application['hr_date'])): ?>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Date</label>
                                                <p><?php echo date('d M Y', strtotime($application['hr_date'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($application['signature_data'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Signature</label>
                                            <div class="signature-display border rounded p-2">
                                                <img src="<?php echo $application['signature_data']; ?>" alt="HR Signature" class="img-fluid" style="max-height: 150px;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($_SESSION['user_role'] === 'hr' && $application['status'] === 'pending_hr'): ?>
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i> You need to process this application.
                                        </div>
                                        <div class="mt-3">
                                            <a href="training_hr_process.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i> Process HR Review
                                            </a>
                                        </div>
                                    <?php elseif (in_array($application['status'], ['pending_hr', 'pending_gm', 'approved', 'rejected'])): ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This section is being processed by the Human Resources unit.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This section will be completed by the Human Resources unit.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Section F: GM Approval -->
                        <div class="form-section mb-4" id="sectionF">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> SECTION F: APPROVAL BY GENERAL MANAGER
                                <?php if (!in_array($application['status'], ['pending_gm', 'approved', 'rejected'])): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!in_array($application['status'], ['pending_gm', 'approved', 'rejected'])): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['gm_decision']) && !empty($application['gm_decision'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Decision</label>
                                        <p>
                                            <?php if ($application['gm_decision'] == 'approved'): ?>
                                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">General Manager</label>
                                        <p>Dato Dr. Anderson Tiong Ing Heng</p>
                                    </div>
                                    
                                    <?php if (isset($application['gm_date']) && !empty($application['gm_date'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Date</label>
                                            <p><?php echo date('d M Y', strtotime($application['gm_date'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($application['status'] === 'pending_gm'): ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This application is pending approval from the General Manager.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This section will be completed by the General Manager.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Approval History -->
                        <div class="form-section mb-4" id="historySection">
                            <div class="section-header">
                                <i class="bi bi-clock-history me-2"></i> APPLICATION HISTORY
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php if (!empty($application['history'])): ?>
                                        <?php foreach ($application['history'] as $history): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-date">
                                                    <?php echo date('d M Y, h:i A', strtotime($history['timestamp'])); ?>
                                                </div>
                                                <div class="timeline-content">
                                                    <h6><?php echo htmlspecialchars($history['action']); ?></h6>
                                                    <p class="text-muted">
                                                        <small>
                                                            By: <?php echo htmlspecialchars($history['user_name']); ?> (<?php echo ucfirst($history['user_role']); ?>)
                                                        </small>
                                                    </p>
                                                    <?php if (!empty($history['comments'])): ?>
                                                        <p><?php echo nl2br(htmlspecialchars($history['comments'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No history available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    
                    <!-- GCR Application Specific View Section for view_application.php -->
                    <?php else: ?>

                        <!-- Application Process Visualization -->
                        <div class="process-container mb-4">
                            <div class="section-header">
                                <i class="bi bi-diagram-3 me-2"></i> Application Process
                            </div>
                            <div class="process-steps">
                                <div class="step <?php echo in_array($application['status'], ['pending_submission', 'pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-1-circle<?php echo in_array($application['status'], ['pending_submission', 'pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Staff Submission</h5>
                                        <p>Application submitted</p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-2-circle<?php echo in_array($application['status'], ['pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>HR Verification</h5>
                                        <p><?php echo in_array($application['status'], ['pending_hr1']) ? 'In progress' : (in_array($application['status'], ['pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved']) ? 'Verified' : 'Pending'); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-3-circle<?php echo in_array($application['status'], ['pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>GM Approval</h5>
                                        <p><?php echo in_array($application['status'], ['pending_gm']) ? 'In progress' : (in_array($application['status'], ['pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved']) ? 'Approved' : (in_array($application['status'], ['rejected']) ? 'Rejected' : 'Pending')); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-4-circle<?php echo in_array($application['status'], ['pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>HR Recording</h5>
                                        <p><?php echo $application['status'] === 'pending_hr2' ? 'In progress' : (in_array($application['status'], ['pending_hr3', 'pending_gm_final', 'approved']) ? 'Completed' : 'Pending'); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-5-circle<?php echo in_array($application['status'], ['pending_hr3', 'pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Lampiran A</h5>
                                        <p><?php echo $application['status'] === 'pending_hr3' ? 'In progress' : (in_array($application['status'], ['pending_gm_final', 'approved']) ? 'Verified' : 'Pending'); ?></p>
                                    </div>
                                </div>
                                <div class="step <?php echo in_array($application['status'], ['pending_gm_final', 'approved', 'rejected']) ? 'active' : ''; ?>">
                                    <div class="step-icon">
                                        <i class="bi bi-6-circle<?php echo in_array($application['status'], ['pending_gm_final', 'approved', 'rejected']) ? '-fill' : ''; ?>"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Final Approval</h5>
                                        <p><?php echo $application['status'] === 'pending_gm_final' ? 'In progress' : ($application['status'] === 'approved' ? 'Completed' : 'Pending'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section A: Staff Application Details -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-person-badge me-2"></i> BAHAGIAN A: DIISI OLEH PEMOHON
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Kepada</label>
                                    <p>DATO DR. ANDERSON TIONG ING HENG (Ketua Jabatan)</p>
                                </div>
                                
                                <div class="mb-3">
                                    <p><strong>Tuan,</strong></p>
                                    <h5>PERMOHONAN PENGUMPULAN BAKI CUTI BAGI FAEDAH GANTIAN CUTI REHAT (GCR)- TAHUN</h5>
                                    <p>Dengan hormatnya saya merujuk kepada perkara dia atas dan ingin memohon kelulusan tuan/puan untuk saya mengumpul baki cuti yang tidak dapat dihabiskan pada tahun 
                                        <strong><?php echo htmlspecialchars($application['year']); ?></strong>
                                        bagi tujuan faedah gentian cuti rehat (GCR). Disertakan bersama ini Lampiran A, Penyata Cuti Rehat, Kenyataan Cuti Rehat dan Senarai Semak Pemohon saya yang telah dikemaskini untuk tindakan tuan selanjutnya.
                                    </p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nama</label>
                                        <p><?php echo htmlspecialchars($application['applicant_name']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jawatan/Gred</label>
                                        <p><?php echo htmlspecialchars($application['applicant_position']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Pejabat</label>
                                        <p><?php echo htmlspecialchars($application['applicant_department']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jumlah Hari</label>
                                        <p><?php echo htmlspecialchars($application['days_requested']); ?> hari</p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tandatangan Pemohon</label>
                                    <?php if (!empty($application['signature_data'])): ?>
                                        <div class="signature-display border rounded p-2">
                                            <img src="<?php echo $application['signature_data']; ?>" alt="Applicant Signature" class="img-fluid" style="max-height: 150px;">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No signature available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section B: HR Officer Verification -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> BAHAGIAN B: DIISI OLEH PEGAWAI YANG DIBERI KUASA
                                <?php if (!in_array($application['status'], ['pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) && !(hasRole('hr') || hasRole('admin'))): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                            <?php if (!in_array($application['status'], ['pending_hr1', 'pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) && !(hasRole('hr') || hasRole('hr3') || hasRole('admin'))): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN/KEBENARAN KETUA JABATAN/BAHAGIAN</strong></p>
                                    <p>Saya mengesahkan bahawa <?php echo htmlspecialchars($application['applicant_name']); ?> dibenarkan untuk mengumpul baki cuti sebanyak <?php echo htmlspecialchars($application['days_requested']); ?> hari dari tahun <?php echo htmlspecialchars($application['year']); ?> yang tidak dapat dihabiskan atas kepentingan perkhidmatan bagi faedah gantian cuti rehat (GCR) iaitu mengikut kelayakan beliau.</p>
                                </div>
                                
                                <?php if (isset($application['hr1_comments']) && !empty($application['hr1_comments'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Comments</label>
                                        <div class="p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($application['hr1_comments'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nama</label>
                                        <p><?php echo htmlspecialchars($application['hr1_name'] ?? 'SOPHIA BINTI IDRIS'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jawatan/Gred</label>
                                        <p>PEGAWAI TADBIR, N41</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Pejabat</label>
                                        <p>SUMBER MANUSIA</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tarikh</label>
                                        <p><?php echo isset($application['hr1_date']) ? date('d M Y', strtotime($application['hr1_date'])) : 'Not set'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tandatangan HR</label>
                                    <?php if (!empty($application['hr1_signature'])): ?>
                                        <div class="signature-display border rounded p-2">
                                            <img src="<?php echo $application['hr1_signature']; ?>" alt="HR Signature" class="img-fluid" style="max-height: 150px;">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No signature available</p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($application['status'] === 'pending_hr1' && (hasRole('hr') || hasRole('admin'))): ?>
                                    <div class="mt-3">
                                        <a href="gcr_hr1_process.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Process HR Verification
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Section C: GM Decision -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> BAHAGIAN C: DIISI OLEH KETUA JABATAN/BAHAGIAN
                                <?php if (!in_array($application['status'], ['pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) && !hasRole('gm')): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!in_array($application['status'], ['pending_gm', 'pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved', 'rejected']) && !hasRole('gm')): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <p><strong>KEPUTUSAN PERMOHONAN GCR</strong></p>
                                    <p>Permohonan GCR <?php echo htmlspecialchars($application['applicant_name']); ?> 
                                        <?php if (isset($application['gm_decision']) && $application['gm_decision'] === 'approved'): ?>
                                            diluluskan <span class="badge bg-success"><i class="bi bi-check-square"></i></span> sebanyak <?php echo htmlspecialchars($application['gm_days_approved']); ?> hari
                                        <?php elseif (isset($application['gm_decision']) && $application['gm_decision'] === 'rejected'): ?>
                                            ditolak <span class="badge bg-danger"><i class="bi bi-x-square"></i></span>
                                        <?php else: ?>
                                            <span class="text-muted">Belum diputuskan</span>
                                        <?php endif; ?>
                                        <?php if (isset($application['gm_comments']) && !empty($application['gm_comments'])): ?>
                                            kerana <?php echo htmlspecialchars($application['gm_comments']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nama</label>
                                        <p>DATO DR. ANDERSON TIONG ING HENG</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jawatan/Gred</label>
                                        <p>PENGURUS BESAR, VU7</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Pejabat</label>
                                        <p>SARAWAK MULTIMEDIA AUTHORITY</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tarikh</label>
                                        <p><?php echo isset($application['gm_date']) ? date('d M Y', strtotime($application['gm_date'])) : 'Not set'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tandatangan GM</label>
                                    <?php if (!empty($application['gm_signature'])): ?>
                                        <div class="signature-display border rounded p-2">
                                            <img src="<?php echo $application['gm_signature']; ?>" alt="GM Signature" class="img-fluid" style="max-height: 150px;">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No signature available</p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($application['status'] === 'pending_gm' && hasRole('gm')): ?>
                                    <div class="mt-3">
                                        <a href="gcr_gm_process.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-2"></i> Process GM Approval
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Section D: HR Final Recording -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> BAHAGIAN D: DIISI OLEH PEGAWAI YANG MENGURUS CUTI & GCR
                                <?php if (!in_array($application['status'], ['pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved']) && !(hasRole('hr') || hasRole('admin'))): ?>
                                    <i class="bi bi-lock-fill lock-icon float-end"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!in_array($application['status'], ['pending_hr2', 'pending_hr3', 'pending_gm_final', 'approved']) && !(hasRole('hr') || hasRole('admin'))): ?>
                                    <div class="section-overlay"></div>
                                <?php endif; ?>
                                
                                <?php if (isset($application['hr2_id']) && !empty($application['hr2_id'])): ?>
                                    <div class="mb-3">
                                        <p><strong>REKOD KELULUSAN GCR</strong></p>
                                        <p>Telah direkodkan dalam Penyata Cuti Rehat Pegawai.</p>
                                    </div>
                                    
                                    <?php if (isset($application['hr2_comments']) && !empty($application['hr2_comments'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Comments</label>
                                            <div class="p-3 bg-light rounded">
                                                <?php echo nl2br(htmlspecialchars($application['hr2_comments'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Nama</label>
                                            <p><?php echo htmlspecialchars($application['hr2_name'] ?? 'HAMISIAH BINTI USUP'); ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Jawatan/Gred</label>
                                            <p>PENOLONG PEGAWAI TADBIR, N32</p>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Tarikh</label>
                                            <p><?php echo isset($application['hr2_date']) ? date('d M Y', strtotime($application['hr2_date'])) : 'Not set'; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tandatangan HR (Rekod)</label>
                                        <?php if (!empty($application['hr2_signature'])): ?>
                                            <div class="signature-display border rounded p-2">
                                                <img src="<?php echo $application['hr2_signature']; ?>" alt="HR2 Signature" class="img-fluid" style="max-height: 150px;">
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No signature available</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3 mt-2">
                                        <p class="small">s.k. Setiausaha Kerajaan Negeri [UPSM (u.p. Seksyen Kemudahan)] & Pemohon</p>
                                        <p class="small">Nota: *mana yang berkenaan</p>
                                    </div>
                                <?php else: ?>
                                    <?php if ($application['status'] === 'pending_hr2'): ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This application is pending final recording by HR.
                                        </div>
                                        <?php if (hasRole('hr') || hasRole('admin')): ?>
                                            <div class="mt-3">
                                                <a href="gcr_hr2_process.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                                    <i class="bi bi-check-circle me-2"></i> Process HR Final Recording
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i> This section will be completed by HR after GM approval.
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Section E: HR3 Lampiran A Verification (New) -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <i class="bi bi-file-earmark-text me-2"></i> BAHAGIAN E: LAMPIRAN A (DIISI OLEH HR3)
                            <?php if (!in_array($application['status'], ['pending_hr3', 'pending_gm_final', 'approved']) && !(hasRole('hr3') || hasRole('admin'))): ?>
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Get Lampiran A data if available
                            $lampiran_a = null;
                            if (method_exists($controller, 'getLampiranA')) {
                                $lampiran_a = $controller->getLampiranA($application_id);
                            }
                            ?>
                            
                            <?php if ($lampiran_a && !empty($lampiran_a)): ?>
                                <div class="mb-3">
                                    <p class="fw-bold text-center">PENYATA PENGUMPULAN CUTI REHAT BAGI FAEDAH GANTIAN CUTI REHAT (GCR)</p>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td colspan="2">
                                                <div class="row">
                                                    <div class="col-md-3"><strong>Nama:</strong></div>
                                                    <div class="col-md-9"><?php echo htmlspecialchars($application['applicant_name']); ?></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="row">
                                                    <div class="col-md-4"><strong>No. Pekerja:</strong></div>
                                                    <div class="col-md-8"><?php echo htmlspecialchars($lampiran_a['employee_id']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-md-4"><strong>Jawatan/Gred:</strong></div>
                                                    <div class="col-md-8"><?php echo htmlspecialchars($application['applicant_position']); ?></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <div class="row">
                                                    <div class="col-md-3"><strong>Pejabat:</strong></div>
                                                    <div class="col-md-9"><?php echo htmlspecialchars($application['applicant_department']); ?></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>BIL</th>
                                                <th>TAHUN</th>
                                                <th>JUMLAH HARI BAKI</th>
                                                <th>JUMLAH HARI DILULUSKAN UNTUK GCR</th>
                                                <th>JUMLAH HARI BAKI SELEPAS GCR</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td><?php echo htmlspecialchars($application['year']); ?></td>
                                                <td><?php echo htmlspecialchars($lampiran_a['total_days_balance']); ?></td>
                                                <td><?php echo htmlspecialchars($lampiran_a['gc_days_approved']); ?></td>
                                                <td><?php echo htmlspecialchars($lampiran_a['remaining_days']); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Disediakan Oleh:</label>
                                            <p>OINIE ZAPHIA ANAK SAMAT (HR OFFICER)</p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Tarikh:</label>
                                            <p><?php echo isset($lampiran_a['verified_date']) ? date('d M Y', strtotime($lampiran_a['verified_date'])) : 'Not set'; ?></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Tandatangan HR3:</label>
                                            <?php if (!empty($lampiran_a['hr3_signature'])): ?>
                                                <div class="signature-display border rounded p-2">
                                                    <img src="<?php echo $lampiran_a['hr3_signature']; ?>" alt="HR3 Signature" class="img-fluid" style="max-height: 150px;">
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted">No signature available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if ($application['status'] === 'pending_hr3'): ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> This application is pending Lampiran A verification by HR.
                                    </div>
                                    <?php if (hasRole('hr3') || hasRole('admin')): ?>
                                        <div class="mt-3">
                                            <a href="gcr_hr3_verification.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i> Process Lampiran A Verification
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> Lampiran A will be processed by HR after HR2 recording.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section F: GM Final Signature (New) -->
                    <div class="form-section mb-4">
                        <div class="section-header">
                            <i class="bi bi-check-circle me-2"></i> BAHAGIAN F: PENGESAHAN AKHIR (DIISI OLEH GM)
                            <?php if (!in_array($application['status'], ['pending_gm_final', 'approved']) && !hasRole('gm')): ?>
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!in_array($application['status'], ['pending_gm_final', 'approved']) && !hasRole('gm')): ?>
                                <div class="section-overlay"></div>
                            <?php endif; ?>
                            
                            <?php if (isset($application['gm_final_signature']) && !empty($application['gm_final_signature'])): ?>
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN AKHIR UNTUK LAMPIRAN A</strong></p>
                                    <p>Pengesahan akhir telah dibuat untuk Lampiran A GCR ini.</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nama:</label>
                                        <p>DATO DR. ANDERSON TIONG ING HENG</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Jawatan:</label>
                                        <p>PENGURUS BESAR</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tarikh:</label>
                                        <p><?php echo isset($application['gm_final_date']) ? date('d M Y', strtotime($application['gm_final_date'])) : 'Not set'; ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tandatangan GM (Pengesahan Akhir):</label>
                                    <?php if (!empty($application['gm_final_signature'])): ?>
                                        <div class="signature-display border rounded p-2">
                                            <img src="<?php echo $application['gm_final_signature']; ?>" alt="GM Final Signature" class="img-fluid" style="max-height: 150px;">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No signature available</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php if ($application['status'] === 'pending_gm_final'): ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> This application is pending final signature from the General Manager.
                                    </div>
                                    <?php if (hasRole('gm')): ?>
                                        <div class="mt-3">
                                            <a href="gcr_gm_final.php?id=<?php echo $application_id; ?>" class="btn btn-primary">
                                                <i class="bi bi-check-circle me-2"></i> Process Final Signature
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i> This section will be completed by the General Manager after Lampiran A verification.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                        </a>
                        <?php if ($application['status'] === 'pending_submission' && $application['user_id'] == $_SESSION['user_id']): ?>
                            <a href="edit_application.php?type=<?php echo $type; ?>&id=<?php echo $application_id; ?>" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-pencil me-2"></i> Edit Application
                            </a>
                        <?php endif; ?>
                        
                        <!-- Print Button for both application types -->
                        <?php if ($type === 'training'): ?>
                            <a href="print_training.php?id=<?php echo $application_id; ?>" target="_blank" class="btn btn-info px-4 py-2">
                                <i class="bi bi-printer me-2"></i> Print Application
                            </a>
                        <?php elseif ($type === 'gcr'): ?>
                            <a href="print_gcr.php?id=<?php echo $application_id; ?>" target="_blank" class="btn btn-info px-4 py-2">
                                <i class="bi bi-printer me-2"></i> Print Application
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Container for generated print content -->
<div id="print-container" style="display: none;">
    <!-- Print content will be generated here -->
</div>

<style>
/* Timeline styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.timeline-content {
    padding-left: 15px;
}

.timeline-content h6 {
    margin-bottom: 5px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -36px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 2px solid #fff;
}

/* Process steps */
.process-steps {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    position: relative;
}

.process-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
    padding: 0 10px;
}

.step-icon {
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    background-color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.step-icon i {
    font-size: 1.5rem;
    color: #adb5bd;
}

.step.active .step-icon i {
    color: #0d6efd;
}

.step-content h5 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.step-content p {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Section styles */
.section-header {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-weight: 600;
    border-left: 4px solid #0d6efd;
}

.form-section {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.disabled-section {
    opacity: 0.8;
}

.section-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.4);
    z-index: 10;
}

.lock-icon {
    color: #dc3545;
}

/* Signature display */
.signature-display {
    background-color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 150px;
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    #print-container, #print-container * {
        visibility: visible;
    }
    
    #print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
    
    .no-print {
        display: none !important;
    }
    
    .print-only {
        display: block !important;
    }
    
    .print-header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #c82333;
        padding-bottom: 10px;
    }

    .print-header img {
        max-height: 80px;
        margin-bottom: 5px;
    }

    .print-header h2 {
        color: #c82333;
        margin: 5px 0;
    }
    
    .print-section {
        margin-bottom: 20px;
        page-break-inside: avoid;
    }
    
    .print-section-header {
        font-weight: bold;
        background-color: #f1f1f1;
        padding: 8px;
        border-left: 4px solid #c82333;
        margin-bottom: 15px;
    }
    
    .print-field {
        margin-bottom: 15px;
    }
    
    .print-field-label {
        font-weight: bold;
        margin-right: 5px;
    }
    
    .print-signature {
        max-height: 80px;
        margin-top: 10px;
    }
    
    .print-status {
        font-weight: bold;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #c82333;
    }
    
    .print-footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
        font-size: 12px;
    }
    
    table.print-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    table.print-table th, table.print-table td {
        border: 1px solid #dee2e6;
        padding: 8px;
    }
    
    table.print-table th {
        background-color: #f8f9fa;
    }
    
    .page-break {
        page-break-after: always;
    }
    
    /* Decision badges */
    .print-decision {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 4px;
        margin-left: 5px;
    }
    
    .print-decision-approved {
        background-color: #28a745;
        color: white;
    }
    
    .print-decision-rejected {
        background-color: #dc3545;
        color: white;
    }
    
    /* Custom padding */
    .print-container {
        padding: 20px;
    }
}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function previewFile(fileId, fileName, fileType) {
    console.log('Preview function called for:', fileId, fileName, fileType);
    
    // Get modal elements
    const previewModal = document.getElementById('filePreviewModal');
    const previewModalContent = document.getElementById('filePreviewContent');
    const previewModalTitle = document.getElementById('filePreviewModalLabel');
    const previewDownloadBtn = document.getElementById('previewDownloadBtn');
    
    if (!previewModal || !previewModalContent || !previewModalTitle || !previewDownloadBtn) {
        console.error('Missing required modal elements:', {
            modal: previewModal,
            content: previewModalContent,
            title: previewModalTitle,
            downloadBtn: previewDownloadBtn
        });
        alert('Preview functionality is not available. Please try downloading the file instead.');
        return;
    }
    
    // Update modal title and download button
    previewModalTitle.textContent = fileName;
    previewDownloadBtn.href = 'download_document.php?id=' + fileId;
    
    // Show loading state
    previewModalContent.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading preview...</p>
        </div>
    `;
    
    // Set preview URL
    const previewUrl = 'download_document.php?id=' + fileId + '&preview=true';
    console.log('Preview URL:', previewUrl);
    
    // Handle different file types
    const fileTypeLower = fileType.toLowerCase();
    
    if (fileTypeLower === 'pdf') {
        // For PDFs, use iframe for better compatibility
        previewModalContent.innerHTML = `
            <iframe src="${previewUrl}" width="100%" height="500px" style="border: none;">
                Your browser doesn't support iframe previews. Please 
                <a href="download_document.php?id=${fileId}" target="_blank">download the file</a> to view it.
            </iframe>
        `;
    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileTypeLower)) {
        // For images, create a new image and set onload/onerror handlers
        const img = new Image();
        img.className = 'img-fluid';
        img.alt = fileName;
        
        img.onload = function() {
            console.log('Image loaded successfully');
            previewModalContent.innerHTML = '';
            previewModalContent.appendChild(img);
        };
        
        img.onerror = function(e) {
            console.error('Error loading image:', e);
            previewModalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error loading image. Please try downloading the file instead.
                </div>
            `;
        };
        
        img.src = previewUrl;
    } else {
        previewModalContent.innerHTML = `
            <div class="text-center p-5">
                <i class="bi bi-file-earmark-text display-1 mb-3 text-primary"></i>
                <h4>${fileName}</h4>
                <p class="text-muted">This file type (${fileTypeLower}) cannot be previewed directly in the browser.</p>
                <p class="mt-3">Please download the file to view its contents.</p>
            </div>
        `;
    }
    
    try {
        console.log('Attempting to show modal');
        const bsModal = new bootstrap.Modal(previewModal);
        bsModal.show();
        console.log('Modal shown successfully');
    } catch (error) {
        console.error('Error showing modal:', error);
        alert('Error showing preview. Please try downloading the file instead.');
    }
}
</script>

<?php include 'footer.php'; ?>