<?php
// gcr_gm_final.php - GM final signature page for Lampiran A

require_once 'config.php';
require_once 'gcr_controller.php';

// Check if user is logged in and has GM role
if (!isLoggedIn() || $_SESSION['user_role'] != 'gm') {
    header('Location: login.php');
    exit;
}

$controller = new GCRController();
$error_message = '';
$success_message = '';
$application = null;
$hr3_data = null;

// Get application ID from URL
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'gm_final_signature') {
    // Add debugging
    error_log("Processing GM final signature: " . print_r($_POST, true));
    
    $result = $controller->processGMFinalSignature($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Debug log
        error_log("GM final signature successful for ID: " . $application_id);
        
        // Redirect after successful processing
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
        error_log("GM final signature failed: " . $error_message);
    }
}

// Get application details
if ($application_id > 0) {
    $application = $controller->getApplication($application_id);
    
    if (!$application || $application['status'] !== 'pending_gm_final') {
        $error_message = "Application is not available for final signature.";
    } else {
        // Get HR3 Lampiran A data
        // This would need to be implemented in the controller
        $hr3_data = $controller->getApplicationProcessingData($application_id, 'hr3');
    }
}

// Page title
$page_title = 'GCR Final Approval';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i> GCR Final Approval - Lampiran A
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
                    
                    <?php if ($application && $hr3_data): ?>
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
                                <p><strong>Status:</strong> <span class="badge bg-info">Pending GM Final Signature</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lampiran A Display -->
                    <div class="form-section" id="lampiranADisplay">
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
                                                <div class="col-md-8"><?php echo htmlspecialchars($hr3_data['lampiran_a']['employee_id'] ?? ''); ?></div>
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
                                            <td><?php echo htmlspecialchars($hr3_data['lampiran_a']['total_days_balance'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($hr3_data['lampiran_a']['gc_days_approved'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($hr3_data['lampiran_a']['remaining_days'] ?? 0); ?></td>
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
                                        <label class="form-label">Tarikh:</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($hr3_data['lampiran_a']['verified_date'] ?? date('Y-m-d')); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tandatangan:</label>
                                        <div class="signature-display border rounded p-2 text-center">
                                            <?php if (!empty($hr3_data['signature'])): ?>
                                                <img src="<?php echo $hr3_data['signature']; ?>" class="img-fluid" style="max-height: 150px;">
                                            <?php else: ?>
                                                <p class="text-muted">No signature available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- GM Final Signature Form -->
                                <div class="col-md-6">
                                    <form id="gmFinalForm" method="POST" action="" class="needs-validation" novalidate>
                                        <input type="hidden" name="form_action" value="gm_final_signature">
                                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                                        
                                        <div class="mb-3">
                                            <p><strong>Disahkan Oleh:</strong></p>
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
                                            <label class="form-label required-field">Tarikh:</label>
                                            <input type="date" class="form-control" id="finalized_date" name="finalized_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tandatangan:</label>
                                            <div class="signature-area border rounded p-2">
                                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                                                <input type="hidden" id="gm_final_signature" name="gm_final_signature">
                                            </div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                                    <i class="bi bi-eraser me-1"></i> Clear Signature
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 mb-5">
                                    <a href="gm_applications.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                        <i class="bi bi-arrow-left me-2"></i> Back to Applications
                                    </a>
                                    <button type="submit" class="btn btn-success px-4 py-2" id="submitBtn">
                                        <i class="bi bi-check-circle me-2"></i> Approve & Finalize
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> No valid application found for final approval.
                        </div>
                        <div class="text-center mt-4">
                            <a href="gm_applications.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left me-2"></i> Back to Applications
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
        const gmFinalForm = document.getElementById('gmFinalForm');
        
        if (gmFinalForm) {
            gmFinalForm.addEventListener('submit', function(event) {
                // Check if signature is empty
                if (signaturePad.isEmpty()) {
                    event.preventDefault();
                    alert('Please provide your signature');
                    return;
                }
                
                // Save signature data to hidden field
                document.getElementById('gm_final_signature').value = signaturePad.toDataURL();
                
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

.signature-display {
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
</style>

<?php include 'footer.php'; ?>