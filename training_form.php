<?php
// training_form.php - Training application form page

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$controller = new TrainingController();
$error_message = '';
$success_message = '';
$application_id = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $controller->submitApplication($_POST, $_FILES);
    
    if ($result['success']) {
        $success_message = $result['message'];
        $application_id = $result['application_id'];
        
        // Redirect to application list after successful submission
        header('Location: my_applications.php?type=training&success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Get user information
$conn = connectDB();
$user_id = $_SESSION['user_id'];

$sql = "SELECT name, position, department FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fix: Add null checks using the null coalescing operator
$user_name = $user['name'] ?? '';
$user_position = $user['position'] ?? '';
$user_department = $user['department'] ?? '';

// Get HOD users for the dropdown
$sql = "SELECT id, name, position FROM users WHERE role = 'hod'";
$result = $conn->query($sql);
$hod_users = $result->fetch_all(MYSQLI_ASSOC);

// Get HR users for the dropdown
$sql = "SELECT id, name, position FROM users WHERE role = 'hr'";
$result = $conn->query($sql);
$hr_users = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Page title
$page_title = 'Training Application Form';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-journal-text me-2"></i> Training Application Form
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
                    
                    <!-- Application Process Section -->
                    <div class="process-container mb-4">
                        <div class="section-header">
                            <i class="bi bi-info-circle me-2"></i> Application Process
                        </div>
                        <div class="process-steps">
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="bi bi-1-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Staff Submission</h5>
                                    <p>Complete and submit the training application form with required attachments</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-2-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HOD Approval</h5>
                                    <p>Head of Department reviews and recommends the application</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-3-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Review</h5>
                                    <p>Human Resource verifies budget availability and processes the application</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-4-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>GM Approval</h5>
                                    <p>General Manager makes the final decision on the application</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="trainingForm" method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Section A: Programme Details -->
                        <div class="form-section" id="sectionA">
                            <div class="section-header">
                                <i class="bi bi-journal-text me-2"></i> SECTION A: PROGRAMME DETAILS
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="programmeTitle" class="form-label required-field">Title</label>
                                        <input type="text" class="form-control" id="programmeTitle" name="programmeTitle" required>
                                        <div class="invalid-feedback">Please enter the program title.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="venue" class="form-label required-field">Venue</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" class="form-control" id="venue" name="venue" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter the venue.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="organiser" class="form-label required-field">Organiser</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                                            <input type="text" class="form-control" id="organiser" name="organiser" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter the organiser.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="dateTime" class="form-label required-field">Date & Time</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                            <input type="datetime-local" class="form-control" id="dateTime" name="dateTime" required>
                                        </div>
                                        <div class="invalid-feedback">Please select the date and time.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fee" class="form-label required-field">Fee (per pax) in MYR</label>
                                        <div class="input-group">
                                            <span class="input-group-text">MYR</span>
                                            <input type="number" class="form-control" id="fee" name="fee" min="0" step="0.01" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter the fee amount.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section B: Requestor Details -->
                        <div class="form-section" id="sectionB">
                            <div class="section-header">
                                <i class="bi bi-person-badge me-2"></i> SECTION B: REQUESTOR DETAILS
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="requestorName" class="form-label required-field">Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="requestorName" name="requestorName" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter your name.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="postGrade" class="form-label required-field">Post / Grade</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                                            <input type="text" class="form-control" id="postGrade" name="postGrade" value="<?php echo htmlspecialchars($user_position); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter your position or grade.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="unitDivision" class="form-label required-field">Unit / Division</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                                            <input type="text" class="form-control" id="unitDivision" name="unitDivision" value="<?php echo htmlspecialchars($user_department); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter your unit or division.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="requestorDate" class="form-label required-field">Date</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                            <input type="date" class="form-control" id="requestorDate" name="requestorDate" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="invalid-feedback">Please select the submission date.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section C: Justifications -->
                        <div class="form-section" id="sectionC">
                            <div class="section-header">
                                <i class="bi bi-chat-left-text me-2"></i> SECTION C: JUSTIFICATION(S) FOR ATTENDING
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="justification" class="form-label required-field">Justification</label>
                                    <textarea class="form-control" id="justification" name="justification" rows="5" required></textarea>
                                    <div class="form-text">Explain how this training will benefit your role and the organization</div>
                                    <div class="invalid-feedback">Please provide a justification for attending this training.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required-field">Training Brochure (Max 5 files)</label>
                                    
                                    <!-- Enhanced File Upload Area -->
                                    <div class="file-drop-zone" id="fileDropZone">
                                        <i class="bi bi-cloud-arrow-up drop-zone-icon"></i>
                                        <p class="mb-1">Drag and drop files here or click to browse</p>
                                        <small class="text-muted d-block">Accepted formats: PDF, DOC, DOCX, JPG, PNG</small>
                                        <!-- IMPORTANT CHANGE: Remove the d-none class for proper functionality -->
                                        <input type="file" id="brochure" name="brochure[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple required>
                                        <div class="file-limit-indicator">
                                            <span id="fileCount">0</span>/5 files selected
                                        </div>
                                    </div>
                                    <div class="form-text mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Attach training brochure or related documents (max 5 files, 5MB each)
                                    </div>
                                    <div class="invalid-feedback">Please attach at least one training brochure.</div>
                                </div>

                                <!-- File Preview Container -->
                                <div id="filePreviewContainer" class="mt-4">
                                    <!-- File previews will be dynamically inserted here -->
                                    <div class="no-file-message" id="noFileMessage">
                                        <i class="bi bi-file-earmark-x no-file-icon"></i>
                                        <h5>No Files Selected</h5>
                                        <p class="text-muted">Selected files will appear here</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section D: HOD Recommendation (Visible but locked) -->
                        <div class="form-section disabled-section" id="sectionD">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> SECTION D: RECOMMENDATION BY HEAD OF DIVISION / DEPUTY GENERAL MANAGER
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Decision</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="hod_decision" id="hod_recommended" value="recommended" disabled>
                                                <label class="form-check-label" for="hod_recommended">Recommended</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="hod_decision" id="hod_not_recommended" value="not_recommended" disabled>
                                                <label class="form-check-label" for="hod_not_recommended">Not Recommended</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="hod_comments" class="form-label">Comments</label>
                                    <textarea class="form-control" id="hod_comments" name="hod_comments" rows="3" disabled></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="hod_name" class="form-label">HOD Name</label>
                                        <select class="form-select" id="hod_name" name="hod_name" disabled>
                                            <option value="">Select HOD</option>
                                            <?php foreach($hod_users as $hod): ?>
                                                <option value="<?php echo $hod['id']; ?>"><?php echo $hod['name']; ?> (<?php echo $hod['position']; ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hod_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="hod_date" name="hod_date" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section E: HR Comments (Visible but locked) -->
                        <div class="form-section disabled-section" id="sectionE">
                            <div class="section-header">
                                <i class="bi bi-briefcase me-2"></i> SECTION E: COMMENTS BY HUMAN RESOURCE UNIT
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                
                                <!-- Reference Number (Visible but locked) -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reference_number" class="form-label">Reference Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                                placeholder="SMA/500-XXXX" disabled>
                                        </div>
                                        <div class="form-text">Format: SMA/500-XXXX (where XXXX is a 4-digit number)</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hr_comments" class="form-label">Comments</label>
                                    <textarea class="form-control" id="hr_comments" name="hr_comments" rows="3" disabled></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Budget</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="budget_status" id="budget_yes" value="yes" disabled>
                                                <label class="form-check-label" for="budget_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="budget_status" id="budget_no" value="no" disabled>
                                                <label class="form-check-label" for="budget_no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="credit_hours" class="form-label">Credit Hours</label>
                                        <input type="number" class="form-control" id="credit_hours" name="credit_hours" disabled>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="budget_comments" class="form-label">Budget Comments</label>
                                    <textarea class="form-control" id="budget_comments" name="budget_comments" rows="2" disabled></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="hr_name" class="form-label">HR Officer</label>
                                        <select class="form-select" id="hr_name" name="hr_name" disabled>
                                            <option value="">Select HR Officer</option>
                                            <?php foreach($hr_users as $hr): ?>
                                                <option value="<?php echo $hr['id']; ?>"><?php echo $hr['name']; ?> (<?php echo $hr['position']; ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="hr_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="hr_date" name="hr_date" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section F: GM Approval (always locked) -->
                        <div class="form-section disabled-section" id="sectionF">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> SECTION F: APPROVAL BY GENERAL MANAGER
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <label class="form-label">Decision</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_approved" value="approved" disabled>
                                            <label class="form-check-label" for="gm_approved">Approved</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_rejected" value="rejected" disabled>
                                            <label class="form-check-label" for="gm_rejected">Rejected</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="gm_name" class="form-label">General Manager</label>
                                    <input type="text" class="form-control" id="gm_name" name="gm_name" value="Dato Dr. Anderson Tiong Ing Heng" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="gm_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="gm_date" name="gm_date" disabled>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                                <div>
                                    <strong>Important Notice:</strong> Please ensure that your training brochure is attached when submitting to Human Resource Unit. Applications without proper documentation may be delayed or rejected.
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 mb-5">
                            <button type="button" class="btn btn-outline-secondary me-md-2 px-4 py-2" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise me-2"></i> Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                                <i class="bi bi-send me-2"></i> Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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

.required-field::after {
    content: " *";
    color: #dc3545;
}

/* File Drop Zone */
.file-drop-zone {
    border: 2px dashed #adb5bd;
    border-radius: 5px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.file-drop-zone:hover, .file-drop-zone.highlight {
    background-color: #f8f9fa;
    border-color: #0d6efd;
}

.file-drop-zone.disabled {
    opacity: 0.6;
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.drop-zone-icon {
    font-size: 2rem;
    color: #6c757d;
    margin-bottom: 10px;
}

/* File limit indicator */
.file-limit-indicator {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 10px;
}

.file-limit-indicator.full {
    color: #dc3545;
    font-weight: bold;
}

/* No File Message */
.no-file-message {
    text-align: center;
    padding: 20px;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
}

.no-file-icon {
    font-size: 2.5rem;
    color: #6c757d;
    margin-bottom: 10px;
    display: block;
}

/* File preview section */
#filePreviewContainer {
    max-height: 300px;
    overflow-y: auto;
}

/* File preview card styling */
.card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* File type icons */
.text-danger {
    color: #dc3545;
}

.text-primary {
    color: #0d6efd;
}

.text-success {
    color: #198754;
}

.text-secondary {
    color: #6c757d;
}

/* Modal adjustments for file preview */
.modal-body embed {
    border: none;
    border-radius: 0.25rem;
}

/* Process Steps Styling */
.process-container {
    margin-bottom: 30px;
}

.process-steps {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    position: relative;
    margin-top: 20px;
}

.process-steps:before {
    content: '';
    position: absolute;
    top: 25px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
    z-index: 2;
    padding: 0 10px;
    min-width: 120px;
    margin-bottom: 10px;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #fff;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #dee2e6;
}

.step-icon i {
    font-size: 24px;
    color: #6c757d;
}

.step.active .step-icon {
    border-color: #0d6efd;
}

.step.active .step-icon i {
    color: #0d6efd;
}

.step-content h5 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.step-content p {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .process-steps {
        flex-direction: column;
    }
    
    .process-steps:before {
        display: none;
    }
    
    .step {
        margin-bottom: 20px;
        display: flex;
        text-align: left;
    }
    
    .step-icon {
        margin: 0 15px 0 0;
    }
    
    .step-content {
        flex: 1;
    }
    
    .file-drop-zone {
        padding: 15px;
    }
    
}
/* File Input Style - Make it have proper dimensions but invisible */
#brochure {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 10;
}

/* Make sure the drop zone has position relative for proper file input positioning */
.file-drop-zone {
    position: relative;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Ensure disabled state is properly styled */
.file-drop-zone.disabled {
    opacity: 0.6;
    background-color: #f8f9fa;
    pointer-events: none;
}

/* Ensure preview container has proper spacing */
#filePreviewContainer {
    margin-top: 15px;
    min-height: 100px;
}

/* Improve the file preview card display */
#filePreviewContainer .card {
    transition: all 0.2s;
    border-left: 3px solid #0d6efd;
}

#filePreviewContainer .card:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
}

/* File type icons highlighting */
.text-danger { color: #dc3545 !important; }
.text-primary { color: #0d6efd !important; }
.text-success { color: #198754 !important; }
.text-secondary { color: #6c757d !important; }

/* Adjust no file message for better visibility */
.no-file-message {
    text-align: center;
    padding: 20px;
    border: 1px dashed #dee2e6;
    border-radius: 5px;
    background-color: #f8f9fa;
}

/* Animation for alert messages */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('trainingForm');
    const fileInput = document.getElementById('brochure');
    const dropZone = document.getElementById('fileDropZone');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    const noFileMessage = document.getElementById('noFileMessage');
    const fileCountElement = document.getElementById('fileCount');
    const resetBtn = document.getElementById('resetBtn');
    
    // Map to store file icons based on file type
    const fileIcons = {
        'pdf': { icon: 'bi-file-earmark-pdf', class: 'text-danger' },
        'doc': { icon: 'bi-file-earmark-word', class: 'text-primary' },
        'docx': { icon: 'bi-file-earmark-word', class: 'text-primary' },
        'jpg': { icon: 'bi-file-earmark-image', class: 'text-success' },
        'jpeg': { icon: 'bi-file-earmark-image', class: 'text-success' },
        'png': { icon: 'bi-file-earmark-image', class: 'text-success' },
        'default': { icon: 'bi-file-earmark', class: 'text-secondary' }
    };
    
    // Function to format file size - defined early so it can be used by previewFile
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Function to preview a file - defined outside updateFilePreview
    function previewFile(file) {
        console.log("Preview file triggered for: " + file.name);
        
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.error('Bootstrap Modal is not available');
            alert('Preview functionality requires Bootstrap. Please refresh the page.');
            return;
        }
        
        // Create a URL for the file
        const fileURL = URL.createObjectURL(file);
        
        // Determine file type
        const fileType = file.type;
        const extension = file.name.split('.').pop().toLowerCase();
        
        // Create a modal for preview
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'filePreviewModal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'filePreviewModalLabel');
        modal.setAttribute('aria-hidden', 'true');
        
        // Modal content based on file type
        let previewContent;
        
        if (fileType.startsWith('image/')) {
            // Image preview
            previewContent = `<img src="${fileURL}" class="img-fluid" alt="${file.name}">`;
        } else if (extension === 'pdf') {
            // PDF preview
            previewContent = `<embed src="${fileURL}" type="application/pdf" width="100%" height="500px">`;
        } else {
            // For other file types, just show info
            previewContent = `
                <div class="text-center p-5">
                    <i class="bi bi-file-earmark-text display-1 mb-3 text-primary"></i>
                    <h4>${file.name}</h4>
                    <p class="text-muted">File type: ${fileType}</p>
                    <p class="text-muted">Size: ${formatFileSize(file.size)}</p>
                    <div class="mt-3">
                        <a href="${fileURL}" class="btn btn-primary" download="${file.name}">
                            <i class="bi bi-download me-2"></i> Download
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Build modal HTML
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filePreviewModalLabel">${file.name}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ${previewContent}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to document
        document.body.appendChild(modal);
        
        // Initialize and show modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Clean up when modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            URL.revokeObjectURL(fileURL);
            document.body.removeChild(modal);
        });
    }
    
    // Initialize form validation
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // File validation - make sure we have at least one file
            if (fileInput.files.length === 0 && fileInput.required) {
                showAlertMessage('Please attach at least one training brochure', 'danger');
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    }
    
    // Initialize click event on drop zone
    if (dropZone) {
        dropZone.addEventListener('click', function() {
            if (fileInput.files.length >= 5) {
                showFileLimitMessage();
                return;
            }
            fileInput.click();
        });
    }
    
    // Prevent default behavior for drag events
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        
        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.add('highlight');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.remove('highlight');
            });
        });
        
        // Handle dropped files
        dropZone.addEventListener('drop', function(e) {
            if (fileInput.files.length >= 5) {
                showFileLimitMessage();
                return;
            }
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            // Check file limit
            if (fileInput.files.length + files.length > 5) {
                showFileLimitMessage();
                return;
            }
            
            // Create a new FileList with both existing and new files
            handleNewFiles(files);
        });
    }
    
    let selectedFiles = [];

    fileInput.addEventListener('change', function(e) {
        const MAX_FILES = 5;
        const MAX_SIZE = 5 * 1024 * 1024; // 5MB

        let newFiles = Array.from(e.target.files);
        let errorMsg = '';

        // Try to add new files to selectedFiles
        for (let file of newFiles) {
            if (selectedFiles.length >= MAX_FILES) {
                errorMsg = `You can upload a maximum of ${MAX_FILES} files.`;
                break;
            }
            if (file.size > MAX_SIZE) {
                errorMsg = `File "${file.name}" exceeds the 5MB size limit.`;
                break;
            }
            // Prevent duplicates by name and size
            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                selectedFiles.push(file);
            }
        }

        if (errorMsg) {
            showAlertMessage(errorMsg, 'danger');
        }

        // Update the file input with the selectedFiles array
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;

        updateFilePreview();
    });

    // Update removeFile to also update selectedFiles
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        updateFilePreview();
    }

    // On reset, clear selectedFiles
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (form) {
                form.reset();
                selectedFiles = [];
                fileInput.value = '';
                updateFilePreview();
                form.classList.remove('was-validated');
            }
        });
    }
    
    function handleNewFiles(newFiles) {
        // Validate files and show warnings for invalid ones
        for (let i = 0; i < newFiles.length; i++) {
            // Validate file size (5MB max)
            if (newFiles[i].size > 5 * 1024 * 1024) {
                showAlertMessage(`File ${newFiles[i].name} is larger than 5MB. Please select a smaller file.`, 'warning');
                continue;
            }
            
            // Add file to the input
            addFileToInput(newFiles[i]);
        }
        
        updateFilePreview();
    }
    
    function addFileToInput(file) {
        // Create a new input to store the new files
        const newInput = document.createElement('input');
        newInput.type = 'file';
        newInput.multiple = true;
        
        // Create a DataTransfer object
        const dataTransfer = new DataTransfer();
        
        // Add existing files
        if (fileInput.files) {
            Array.from(fileInput.files).forEach(existingFile => {
                dataTransfer.items.add(existingFile);
            });
        }
        
        // Add new file if we haven't reached the limit
        if (dataTransfer.files.length < 5) {
            dataTransfer.items.add(file);
        }
        
        // Set the files to the original input
        fileInput.files = dataTransfer.files;
    }
    
    // Create a file preview card
    function createFilePreviewCard(file, index) {
        const card = document.createElement('div');
        card.className = 'card mb-2';
        
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body p-2';
        
        const row = document.createElement('div');
        row.className = 'row align-items-center';
        
        // File icon
        const iconCol = document.createElement('div');
        iconCol.className = 'col-auto';
        
        const icon = document.createElement('i');
        const extension = file.name.split('.').pop().toLowerCase();
        const fileType = fileIcons[extension] || fileIcons['default'];
        
        icon.className = `bi ${fileType.icon} display-6 ${fileType.class}`;
        iconCol.appendChild(icon);
        
        // File details
        const detailsCol = document.createElement('div');
        detailsCol.className = 'col';
        
        const fileName = document.createElement('h6');
        fileName.className = 'mb-0';
        fileName.textContent = file.name;
        
        const fileSize = document.createElement('small');
        fileSize.className = 'text-muted';
        fileSize.textContent = formatFileSize(file.size);
        
        detailsCol.appendChild(fileName);
        detailsCol.appendChild(fileSize);
        
        // Actions column with View and Remove buttons
        const actionsCol = document.createElement('div');
        actionsCol.className = 'col-auto';

        // View button
        const viewBtn = document.createElement('button');
        viewBtn.type = 'button';
        viewBtn.className = 'btn btn-sm btn-outline-primary me-2';
        viewBtn.innerHTML = '<i class="bi bi-eye"></i>';
        viewBtn.title = 'View File';
        viewBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default behavior
            e.stopPropagation(); // Stop event propagation
            console.log("View button clicked for:", file.name); // Debug log
            previewFile(file); // This calls the function defined at the top level
        });

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
        removeBtn.title = 'Remove File';
        removeBtn.addEventListener('click', function() {
            removeFile(index);
        });

        actionsCol.appendChild(viewBtn);
        actionsCol.appendChild(removeBtn);
        
        // Assemble the card
        row.appendChild(iconCol);
        row.appendChild(detailsCol);
        row.appendChild(actionsCol);
        
        cardBody.appendChild(row);
        card.appendChild(cardBody);
        
        return card;
    }
    
    // Function to update the file preview area
    function updateFilePreview() {
        if (!filePreviewContainer || !fileInput || !noFileMessage || !fileCountElement) return;
        
        const files = fileInput.files;
        
        // Update file count in the UI
        fileCountElement.textContent = files.length;
        
        // Show/hide no file message
        if (files.length === 0) {
            noFileMessage.style.display = 'block';
            filePreviewContainer.innerHTML = '';
            filePreviewContainer.appendChild(noFileMessage);
            
            // Reset drop zone
            if (dropZone) {
                dropZone.classList.remove('disabled');
                const dropText = dropZone.querySelector('p');
                if (dropText) dropText.textContent = 'Drag and drop files here or click to browse';
                
                const fileIndicator = document.querySelector('.file-limit-indicator');
                if (fileIndicator) fileIndicator.classList.remove('full');
            }
            return;
        }
        
        noFileMessage.style.display = 'none';
        
        // Update drop zone message if max files reached
        if (files.length >= 5 && dropZone) {
            dropZone.classList.add('disabled');
            const dropText = dropZone.querySelector('p');
            if (dropText) dropText.textContent = 'Maximum number of files reached';
            
            const fileIndicator = document.querySelector('.file-limit-indicator');
            if (fileIndicator) fileIndicator.classList.add('full');
        } else if (dropZone) {
            dropZone.classList.remove('disabled');
            const dropText = dropZone.querySelector('p');
            if (dropText) dropText.textContent = 'Drag and drop files here or click to browse';
            
            const fileIndicator = document.querySelector('.file-limit-indicator');
            if (fileIndicator) fileIndicator.classList.remove('full');
        }
        
        // Clear previous previews
        filePreviewContainer.innerHTML = '';
        
        // Create a preview for each file
        Array.from(files).forEach((file, index) => {
            const card = createFilePreviewCard(file, index);
            filePreviewContainer.appendChild(card);
        });
    }
    
    // Show file limit message
    function showFileLimitMessage() {
        showAlertMessage('You can upload a maximum of 5 files', 'warning');
    }
    
    // Show alert message above the file drop zone
    function showAlertMessage(message, type = 'warning') {
        if (!dropZone) return;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert before file drop zone
        dropZone.parentNode.insertBefore(alertDiv, dropZone);
        
        // Remove alert after 3 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
    
    // Initialize file preview on page load
    updateFilePreview();
});
</script>

<?php include 'footer.php'; ?>