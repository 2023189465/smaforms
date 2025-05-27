<?php
// Process HR2 final recording for GCR application

require_once 'config.php';
require_once 'gcr_controller.php';

// Check if user is logged in and has HR or admin role
if (!isLoggedIn() || (!hasRole('hr') && !hasRole('admin'))) {
    header('Location: login.php');
    exit;
}

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=no_application_id');
    exit;
}

$application_id = $_GET['id'];

// Add diagnostic output at the beginning of the file
error_log("HR2 Process started for application ID: " . $application_id);

$controller = new GCRController();
$application = $controller->getApplication($application_id);

// After retrieving the application
error_log("Application data: " . print_r($application, true));

// Check if application exists
if (!$application) {
    error_log("Application not found with ID: " . $application_id);
    header('Location: dashboard.php?error=application_not_found');
    exit;
}

// Log the application status to see what's happening
error_log("Application status: " . $application['status']);

// Check if the application is in the correct state for HR2 recording
if ($application['status'] !== 'pending_hr2') {
    error_log("Application status incorrect. Expected 'pending_hr2', got: " . $application['status']);
    header('Location: dashboard.php?error=invalid_application_status&status=' . $application['status']);
    exit;
}

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add debugging
    error_log("Processing GCR HR2 recording: " . print_r($_POST, true));
    
    // Make sure the signature data is properly passed to the controller
    if (isset($_POST['hr2_signature']) && !empty($_POST['hr2_signature'])) {
        // Signature data exists, proceed with processing
        $result = $controller->processHR2Recording($_POST);
        
        if ($result['success']) {
            $success_message = $result['message'];
            
            // Redirect after successful processing
            header('Location: dashboard.php?success=' . urlencode($success_message));
            exit;
        } else {
            $error_message = $result['message'];
            error_log("GCR HR2 recording failed: " . $error_message);
        }
    } else {
        $error_message = "Signature is required. Please provide your signature.";
        error_log("GCR HR2 recording failed: Signature is missing");
    }
}

// Page title
$page_title = 'HR Final Recording - GCR Application';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i> HR Final Recording - GCR Application
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
                    
                    <!-- Process Tracker -->
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
                                    <p>Initial HR verification</p>
                                </div>
                            </div>
                            <div class="step completed">
                                <div class="step-icon">
                                    <i class="bi bi-3-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>GM Approval</h5>
                                    <p>General Manager review complete</p>
                                </div>
                            </div>
                            <div class="step active">
                                <div class="step-icon">
                                    <i class="bi bi-4-circle-fill"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Recording</h5>
                                    <p>Current step - Final recording by HR</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form id="hr2RecordingForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <!-- Display Application Details (Read-only) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i> Bahagian A (Maklumat Pemohon)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="kepada" class="form-label">Kepada:</label>
                                    <input type="text" class="form-control" value="DATO DR. ANDERSON TIONG ING HENG (Ketua Jabatan)" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <p><strong>Tuan,</strong></p>
                                    <h5>PERMOHONAN PENGUMPULAN BAKI CUTI BAGI FAEDAH GANTIAN CUTI REHAT (GCR)- TAHUN <?php echo $application['year']; ?></h5>
                                    <p>Dengan hormatnya saya merujuk kepada perkara dia atas dan ingin memohon kelulusan tuan/puan untuk saya mengumpul baki cuti yang tidak dapat dihabiskan pada tahun <?php echo $application['year']; ?> bagi tujuan faedah gentian cuti rehat (GCR). Disertakan bersama ini Lampiran A, Penyata Cuti Rehat, Kenyataan Cuti Rehat dan Senarai Semak Pemohon saya yang telah dikemaskini untuk tindakan tuan selanjutnya.</p>
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
                                            <input type="text" class="form-control" value="<?php echo $application['days_requested']; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah Hari Diluluskan:</label>
                                            <input type="text" class="form-control" value="<?php echo $application['gm_days_approved']; ?>" readonly>
                                        </div>
                                        
                                        <?php if (!empty($application['signature_data'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan Pemohon:</label>
                                            <div class="border rounded p-2 bg-light">
                                                <img src="<?php echo $application['signature_data']; ?>" alt="Applicant Signature" class="img-fluid" style="max-height: 150px;">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- HR1 Verification Section (Readonly) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> Bahagian B (Pengesahan HR)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN/KEBENARAN KETUA JABATAN/BAHAGIAN</strong></p>
                                    <p class="mb-4">Saya mengesahkan bahawa <strong><?php echo htmlspecialchars($application['applicant_name']); ?></strong> dibenarkan untuk mengumpul baki cuti sebanyak <strong><?php echo $application['days_requested']; ?></strong> hari dari tahun <strong><?php echo $application['year']; ?></strong> yang tidak dapat dihabiskan atas kepentingan perkhidmatan bagi faedah gantian cuti rehat (GCR) iaitu mengikut kelayakan beliau.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="SOPHIA BINTI DRIS" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="PEGAWAI TADBIR, N41" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pejabat:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['hr1_department'] ?? 'SUMBER MANUSIA'); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="<?php echo isset($application['hr1_date']) ? date('d-m-Y', strtotime($application['hr1_date'])) : ''; ?>" readonly>
                                        </div>
                                        
                                        <?php if (!empty($application['hr1_signature'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <div class="border rounded p-2 bg-light">
                                                <img src="<?php echo $application['hr1_signature']; ?>" alt="HR1 Signature" class="img-fluid" style="max-height: 150px;">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GM Section C (Readonly) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> Bahagian C (Diisi oleh Ketua Jabatan/Bahagian)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>KEPUTUSAN PERMOHONAN GCR</strong></p>
                                    <p>Permohonan GCR 
                                        <?php if ($application['gm_decision'] === 'approved'): ?>
                                            <strong>diluluskan</strong> ✓ sebanyak <strong><?php echo $application['gm_days_approved']; ?></strong> hari
                                        <?php elseif ($application['gm_decision'] === 'rejected'): ?>
                                            <strong>ditolak</strong> ✓ kerana <?php echo htmlspecialchars($application['gm_comments'] ?? ''); ?>
                                        <?php else: ?>
                                            Belum diputuskan
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="DATO DR. ANDERSON TIONG ING HENG" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan:</label>
                                            <input type="text" class="form-control" value="PENGURUS BESAR, VU7" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="<?php echo isset($application['gm_date']) ? date('d-m-Y', strtotime($application['gm_date'])) : ''; ?>" readonly>
                                        </div>
                                        
                                        <?php if (!empty($application['gm_signature'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <div class="border rounded p-2 bg-light">
                                                <img src="<?php echo $application['gm_signature']; ?>" alt="GM Signature" class="img-fluid" style="max-height: 150px;">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- HR2 Section D (Active) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> Bahagian D (Diisi oleh Pegawai Yang Mengurus Cuti & GCR)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <p><strong>REKOD KELULUSAN GCR</strong></p>
                                    <p>Telah direkodkan dalam Penyata Cuti Rehat Pegawai.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" value="<?php echo $_SESSION['user_role'] === 'hr' ? 'PEGAWAI TADBIR, N41' : 'ADMINISTRATOR'; ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Pejabat:</label>
                                            <input type="text" class="form-control" value="SUMBER MANUSIA" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tarikh:</label>
                                            <input type="text" class="form-control" value="<?php echo date('d-m-Y'); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label required-field">Tandatangan:</label>
                                            <div class="signature-area border rounded p-2">
                                                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                                                <input type="hidden" id="signature_data" name="hr2_signature" required>
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
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-success px-4 py-2">
                                <i class="bi bi-check-circle me-2"></i> Selesaikan Rekod
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
    const hr2Form = document.getElementById('hr2RecordingForm');
    
    if (hr2Form) {
        hr2Form.addEventListener('submit', function(event) {
            // Check if signature is empty
            if (signaturePad.isEmpty()) {
                event.preventDefault();
                alert('Please provide your signature');
                return;
            }
            
            // Save signature data to hidden field
            document.getElementById('signature_data').value = signaturePad.toDataURL();
            
            console.log("Form submitted with signature data length: " + signaturePad.toDataURL().length);
            
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
    if (canvas) {
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
    border-color: #198754;
    background-color: #198754;
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