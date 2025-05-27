<?php
// gcr_hr3_verification.php - HR3 Lampiran A verification page

require_once 'config.php';
require_once 'gcr_controller.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || (!hasRole('hr') && !hasRole('admin'))) {
    header('Location: login.php');
    exit;
}

$controller = new GCRController();
$error_message = '';
$success_message = '';
$application = null;

// Get application ID from URL
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'hr3_verify') {
    // Add debugging
    error_log("Processing HR3 Lampiran A verification: " . print_r($_POST, true));
    
    $result = $controller->processHR3LampiranA($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Debug log
        error_log("HR3 Lampiran A verification successful for ID: " . $application_id);
        
        // Redirect after successful processing
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
        error_log("HR3 Lampiran A verification failed: " . $error_message);
    }
}

// Get application details
if ($application_id > 0) {
    $application = $controller->getApplication($application_id);
    
    if (!$application || $application['status'] !== 'pending_hr3') {
        $error_message = "Application is not available for Lampiran A verification.";
    }
}

// Page title
$page_title = 'GCR Lampiran A Verification';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i> GCR Lampiran A Verification
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
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-1-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Staff Submission</h5>
                                    <p>Application submitted</p>
                                </div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-2-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Verification</h5>
                                    <p>Verified by HR</p>
                                </div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-3-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>GM Approval</h5>
                                    <p>Approved by GM</p>
                                </div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-4-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Recording</h5>
                                    <p>Recorded by HR</p>
                                </div>
                            </div>
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="bi bi-5-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Lampiran A</h5>
                                    <p>Pending verification</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-6-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Final Approval</h5>
                                    <p>GM final signature</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($application): ?>
                    <!-- Application Info -->
                    <div class="application-info mb-4">
                        <div class="section-header">
                            <i class="bi bi-info-circle me-2"></i> Application Details
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Applicant:</strong> <?php echo htmlspecialchars($application['applicant_name']); ?></p>
                                <p><strong>Position:</strong> <?php echo htmlspecialchars($application['applicant_position']); ?></p>
                                <p><strong>Department:</strong> <?php echo htmlspecialchars($application['applicant_department']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Year:</strong> <?php echo htmlspecialchars($application['year']); ?></p>
                                <p><strong>Days Requested:</strong> <?php echo htmlspecialchars($application['days_requested']); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-primary">Pending Lampiran A Verification</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lampiran A Form -->
                    <form id="hr3Form" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="form_action" value="hr3_verify">
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <div class="form-section" id="lampiranA">
                            <div class="section-header">
                                <i class="bi bi-file-earmark-text me-2"></i> Lampiran A
                            </div>
                            <div class="card-body">
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
                                                    <div class="col-md-8">
                                                        <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                                                    </div>
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
                                                <td>
                                                    <input type="number" class="form-control" id="total_days_balance" name="total_days_balance" min="0" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" id="gc_days_approved" name="gc_days_approved" min="0" value="<?php echo htmlspecialchars($application['days_requested']); ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control" id="remaining_days" name="remaining_days" min="0" required>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <p><strong>Disediakan Oleh:</strong></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="OINIE ZAPHIA ANAK SAMAT" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan:</label>
                                            <input type="text" class="form-control" value="HR OFFICER" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tarikh:</label>
                                            <input type="date" class="form-control" id="verified_date" name="verified_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tandatangan:</label>
                                            <div class="signature-area border rounded p-2">
                                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                                                <input type="hidden" id="hr3_signature" name="hr3_signature">
                                            </div>
                                            <div class="invalid-feedback">Please provide your signature.</div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                                    <i class="bi bi-eraser me-1"></i> Clear Signature
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <p><strong>Disahkan Oleh:</strong></p>
                                            <p class="text-muted">Akan ditandatangani oleh GM</p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="DATO DR. ANDERSON TIONG ING HENG" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan:</label>
                                            <input type="text" class="form-control" value="PENGURUS BESAR" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="Akan diisi oleh GM" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <div class="signature-box border rounded p-3 text-center">
                                                <p class="text-muted">GM will sign here</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Comments/Notes (Optional):</label>
                                    <textarea class="form-control" id="hr3_comments" name="hr3_comments" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 mb-5">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-success px-4 py-2" id="submitBtn">
                                <i class="bi bi-check-circle me-2"></i> Verify Lampiran A & Send to GM
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> No valid application found for Lampiran A verification.
                        </div>
                        <div class="text-center mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize signature pad
    const canvas = document.getElementById('signatureCanvas');
    if (canvas) {
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'black'
        });
        
        // Clear signature button
        document.getElementById('clearSignature').addEventListener('click', function() {
            signaturePad.clear();
        });
        
        // Form validation
        const hr3Form = document.getElementById('hr3Form');
        
        if (hr3Form) {
            hr3Form.addEventListener('submit', function(event) {
                // Check if signature is empty
                if (signaturePad.isEmpty()) {
                    event.preventDefault();
                    alert('Please provide your signature');
                    return;
                }
                
                // Save signature data to hidden field
                document.getElementById('hr3_signature').value = signaturePad.toDataURL();
                
                // Form validation
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                this.classList.add('was-validated');
            });
        }
        
        // Resize canvas when window resizes
        window.addEventListener('resize', resizeCanvas);
        
        function resizeCanvas() {
            if (!canvas) return;
            
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // clear canvas
        }
        
        // Set canvas size on load
        resizeCanvas();
    }
    
    // Auto-calculate remaining days
    const totalDaysBalance = document.getElementById('total_days_balance');
    const gcDaysApproved = document.getElementById('gc_days_approved');
    const remainingDays = document.getElementById('remaining_days');
    
    function calculateRemainingDays() {
        if (totalDaysBalance && gcDaysApproved && remainingDays) {
            const total = parseInt(totalDaysBalance.value) || 0;
            const approved = parseInt(gcDaysApproved.value) || 0;
            remainingDays.value = Math.max(0, total - approved);
        }
    }
    
    if (totalDaysBalance && gcDaysApproved) {
        totalDaysBalance.addEventListener('input', calculateRemainingDays);
        gcDaysApproved.addEventListener('input', calculateRemainingDays);
    }
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
    border-left: 4px solid #198754; /* Green color for GCR forms */
}

.form-section {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.required-field::after {
    content: " *";
    color: #dc3545;
}

/* Signature area */
.signature-area {
    background-color: #fff;
    width: 100%;
    height: 150px;
}

.signature-box {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

/* Application info styling */
.application-info {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
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
    border-color: #198754;
}

.step.active .step-icon i {
    color: #198754;
}

.step.completed .step-icon {
    border-color: #28a745;
    background-color: #28a745;
}

.step.completed .step-icon i {
    color: #fff;
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
@media (max-width: 992px) {
    .process-steps {
        flex-wrap: wrap;
    }
    
    .step {
        flex: 0 0 33.333%;
        margin-bottom: 20px;
    }
    
    .process-steps:before {
        display: none;
    }
}

@media (max-width: 768px) {
    .process-steps {
        flex-direction: column;
    }
    
    .step {
        margin-bottom: 20px;
        display: flex;
        text-align: left;
        flex: 0 0 100%;
    }
    
    .step-icon {
        margin: 0 15px 0 0;
    }
    
    .step-content {
        flex: 1;
    }
}
</style>

<?php include 'footer.php'; ?>