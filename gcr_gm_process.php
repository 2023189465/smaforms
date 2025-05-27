<?php
// Process GM approval for GCR application

require_once 'config.php';
require_once 'gcr_controller.php';

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
$controller = new GCRController();
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

// Page title
$page_title = 'GM Approval - GCR Application';

// Function to get badge class for status
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
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-clipboard-check me-2"></i> GM Approval - GCR Application
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
                                    <p>Application submitted by staff</p>
                                </div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-2-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Verification</h5>
                                    <p>Verified by Human Resource</p>
                                </div>
                            </div>
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="bi bi-3-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>GM Approval</h5>
                                    <p>Pending your approval</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-4-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Recording</h5>
                                    <p>Final recording by HR</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form id="gmApprovalForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <!-- Staff Section (Bahagian A) - Display only -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-person-badge me-2"></i> Bahagian A (Diisi oleh Pemohon)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="kepada" class="form-label">Kepada:</label>
                                    <input type="text" class="form-control" value="DATO DR. ANDERSON TIONG ING HENG (Ketua Jabatan)" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <p><strong>Tuan,</strong></p>
                                    <h5>PERMOHONAN PENGUMPULAN BAKI CUTI BAGI FAEDAH GANTIAN CUTI REHAT (GCR)- TAHUN <?php echo htmlspecialchars($application['year']); ?></h5>
                                    <p>Dengan hormatnya saya merujuk kepada perkara dia atas dan ingin memohon kelulusan tuan/puan untuk saya mengumpul baki cuti yang tidak dapat dihabiskan pada tahun <?php echo htmlspecialchars($application['year']); ?> bagi tujuan faedah gentian cuti rehat (GCR). Disertakan bersama ini Lampiran A, Penyata Cuti Rehat, Kenyataan Cuti Rehat dan Senarai Semak Pemohon saya yang telah dikemaskini untuk tindakan tuan selanjutnya.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_name']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_position']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pejabat:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_department']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah Hari:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['days_requested']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <?php if (!empty($application['signature_data'])): ?>
                                                <div class="signature-display border rounded p-2 bg-light">
                                                    <img src="<?php echo $application['signature_data']; ?>" class="img-fluid" style="max-height: 150px;" alt="Applicant Signature">
                                                </div>
                                            <?php else: ?>
                                                <div class="signature-box border rounded p-3 text-center">
                                                    <p class="text-muted">No signature available</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HR Section B (Display only) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> Bahagian B (Diisi oleh Pegawai Yang Diberi Kuasa)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN/KEBENARAN KETUA JABATAN/BAHAGIAN</strong></p>
                                    <p>Saya mengesahkan bahawa <?php echo htmlspecialchars($application['applicant_name']); ?> dibenarkan untuk mengumpul baki cuti sebanyak <?php echo htmlspecialchars($application['days_requested']); ?> hari dari tahun <?php echo htmlspecialchars($application['year']); ?> yang tidak dapat dihabiskan atas kepentingan perkhidmatan bagi faedah gantian cuti rehat (GCR) iaitu mengikut kelayakan beliau.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['hr1_name'] ?? 'SOPHIA BINTI IDRIS'); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="PEGAWAI TADBIR, N41" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pejabat:</label>
                                            <input type="text" class="form-control" value="SUMBER MANUSIA" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="<?php echo !empty($application['hr1_date']) ? date('d/m/Y', strtotime($application['hr1_date'])) : '................'; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <?php if (!empty($application['hr1_signature'])): ?>
                                                <div class="signature-display border rounded p-2 bg-light">
                                                    <img src="<?php echo $application['hr1_signature']; ?>" class="img-fluid" style="max-height: 150px;" alt="HR Signature">
                                                </div>
                                            <?php else: ?>
                                                <div class="signature-box border rounded p-3 text-center">
                                                    <p class="text-muted">Signature will appear here</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GM Section C (Active) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> Bahagian C (Diisi oleh Ketua Jabatan/Bahagian)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>KEPUTUSAN PERMOHONAN GCR</strong></p>
                                    <p>
                                        Permohonan GCR 
                                        <?php echo htmlspecialchars($application['applicant_name']); ?> 
                                        
                                        <div class="form-check form-check-inline ms-3">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_approved" value="approved" required>
                                            <label class="form-check-label" for="gm_approved">diluluskan</label>
                                        </div>
                                        
                                        <span class="approval-section" style="display: none;">
                                            sebanyak <input type="number" class="form-control d-inline-block mx-2" style="width: 80px;" id="days_approved" name="days_approved" min="1" max="<?php echo $application['days_requested']; ?>" value="<?php echo $application['days_requested']; ?>"> hari
                                        </span>
                                        
                                        <div class="form-check form-check-inline ms-3">
                                            <input class="form-check-input" type="radio" name="gm_decision" id="gm_rejected" value="rejected" required>
                                            <label class="form-check-label" for="gm_rejected">ditolak</label>
                                        </div>
                                        
                                        <span class="rejection-section" style="display: none;">
                                            kerana <input type="text" class="form-control mt-2" id="gm_comments" name="gm_comments">
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="DATO DR. ANDERSON TIONG ING HENG" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="PENGURUS BESAR, VU7" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pejabat:</label>
                                            <input type="text" class="form-control" value="SARAWAK MULTIMEDIA AUTHORITY" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tandatangan:</label>
                                            <div class="signature-area border rounded p-2">
                                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                                                <input type="hidden" id="signature_data" name="signature_data">
                                            </div>
                                            <div class="invalid-feedback">Please provide your signature.</div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                                    <i class="bi bi-eraser me-1"></i> Clear Signature
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 mt-2">
                                    <p class="small">s.k. Setiausaha Kerajaan Negeri [UPSM (u.p. Seksyen Kemudahan)] & Pemohon</p>
                                    <p class="small">Nota: *mana yang berkenaan</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- HR2 Section D (Inactive) -->
                        <div class="form-section disabled-section" id="sectionD">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> Bahagian D (Diisi oleh Pegawai Yang Mengurus Cuti & GCR)
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <p><strong>REKOD KELULUSAN GCR</strong></p>
                                    <p>Telah direkodkan dalam Penyata Cuti Rehat Pegawai.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="HAMISIAH BINTI USUP" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="PENOLONG PEGAWAI TADBIR, N32" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="................" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tandatangan:</label>
                                            <div class="signature-area border rounded p-2">
                                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                                                <input type="hidden" id="signature_data" name="signature_data" required>
                                            </div>
                                            <div class="invalid-feedback">Sila berikan tandatangan anda.</div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                                    <i class="bi bi-eraser me-1"></i> Clear Signature
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Application History -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clock-history me-2"></i> Application History
                            </div>
                            <div class="card-body">
                                <?php if (!empty($application['history'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Action</th>
                                                    <th>Status</th>
                                                    <th>By</th>
                                                    <th>Comments</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($application['history'] as $history): ?>
                                                <tr>
                                                    <td><?php echo date('d M Y H:i', strtotime($history['timestamp'])); ?></td>
                                                    <td><?php echo htmlspecialchars($history['action']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo getStatusBadgeClass($history['status']); ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $history['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($history['user_name']); ?> (<?php echo ucfirst($history['user_role']); ?>)</td>
                                                    <td>
                                                        <?php if (!empty($history['comments'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="popover" data-bs-placement="left" data-bs-html="true" data-bs-content="<?php echo htmlspecialchars(nl2br($history['comments'])); ?>">
                                                                <i class="bi bi-chat-text me-1"></i> View
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">No history records found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-success px-4 py-2">
                                <i class="bi bi-check-circle me-2"></i> Submit Decision
                            </button>
                        </div>
                    </form>
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
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'black'
    });
    
    // Clear signature button
    document.getElementById('clearSignature').addEventListener('click', function() {
        signaturePad.clear();
    });
    
    // Form validation
    const form = document.getElementById('gmApprovalForm');
    const approvalSection = document.querySelector('.approval-section');
    const rejectionSection = document.querySelector('.rejection-section');
    const daysApprovedInput = document.getElementById('days_approved');
    const gmCommentsInput = document.getElementById('gm_comments');
    const maxDays = <?php echo $application['days_requested']; ?>;
    
    // Function to toggle approval/rejection specific fields
    function toggleDecisionFields() {
        const isApproved = document.getElementById('gm_approved').checked;
        const isRejected = document.getElementById('gm_rejected').checked;
        
        if (isApproved) {
            approvalSection.style.display = 'inline';
            rejectionSection.style.display = 'none';
            daysApprovedInput.required = true;
            gmCommentsInput.required = false;
        } else if (isRejected) {
            approvalSection.style.display = 'none';
            rejectionSection.style.display = 'block';
            daysApprovedInput.required = false;
            gmCommentsInput.required = true;
        } else {
            approvalSection.style.display = 'none';
            rejectionSection.style.display = 'none';
            daysApprovedInput.required = false;
            gmCommentsInput.required = false;
        }
    }
    
    // Initialize visibility
    toggleDecisionFields();
    
    // Add event listeners to radio buttons
    document.querySelectorAll('input[name="gm_decision"]').forEach(function(radio) {
        radio.addEventListener('change', toggleDecisionFields);
    });
    
    // Validate days approved doesn't exceed days requested
    daysApprovedInput.addEventListener('input', function() {
        if (parseInt(this.value) > maxDays) {
            this.value = maxDays;
        }
    });
    
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
    if (canvas) {
        resizeCanvas();
    }
    
    // Add process steps styling
    const steps = document.querySelectorAll('.step');
    if (steps.length > 0) {
        // These should already be applied in the HTML, but adding for safety
        steps[0].classList.add('completed'); // Step 1 (Staff Submission)
        steps[1].classList.add('completed'); // Step 2 (HR Verification)
        steps[2].classList.add('active');    // Step 3 (GM Approval)
    }
    
    // Initialize popovers for comments
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Form submission validation
    form.addEventListener('submit', function(event) {
        // Check if signature is empty
        if (signaturePad.isEmpty()) {
            event.preventDefault();
            alert('Please provide your signature');
            return;
        }
        
        // Save signature data to hidden field
        document.getElementById('signature_data').value = signaturePad.toDataURL();
        
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
    border-left: 4px solid #198754; /* Green color for GCR forms */
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

/* Signature display */
.signature-display {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 150px;
    background-color: #f8f9fa;
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
}
</style>

<?php include 'footer.php'; ?>