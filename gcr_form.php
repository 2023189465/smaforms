<?php
// gcr_form.php - GCR application form page

require_once 'config.php';
require_once 'gcr_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$controller = new GCRController();
$error_message = '';
$success_message = '';
$application_id = null;
$edit_mode = false;
$current_status = 'pending_submission';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'submit_staff') {
    // Add debugging
    error_log("Processing GCR form submission: " . print_r($_POST, true));
    
    $result = $controller->submitApplication($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        $application_id = $result['application_id'];
        
        // Debug log
        error_log("GCR form submitted successfully. ID: " . $application_id);
        
        // Redirect to application list after successful submission
        header('Location: my_applications.php?type=gcr&success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
        error_log("GCR form submission failed: " . $error_message);
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

$user_name = $user['name'] ?? '';
$user_position = $user['position'] ?? '';
$user_department = $user['department'] ?? '';

$conn->close();

// Page title
$page_title = 'GCR Application Form';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i> GCR Application Form
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
                                    <p>Complete and submit the GCR application form</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-2-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Verification</h5>
                                    <p>Human Resource verifies your application details</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-3-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>GM Approval</h5>
                                    <p>General Manager reviews and approves the application</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-4-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>HR Recording</h5>
                                    <p>Recording by HR</p>
                                </div>
                            </div>
                            <div class="step">
                                <div class="step-icon">
                                    <i class="bi bi-5-circle"></i>
                                </div>
                                <div class="step-content">
                                    <h5>Lampiran A</h5>
                                    <p>HR3 verifies leave details</p>
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
                    
                    <!-- Staff Section (Bahagian A) -->
                    <?php if ($current_status == 'pending_submission'): ?>
                    <form id="staffForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="form_action" value="submit_staff">
                        
                        <div class="form-section" id="sectionA">
                            <div class="section-header">
                                <i class="bi bi-person-badge me-2"></i> Bahagian A (Diisi oleh Pemohon)
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="kepada" class="form-label">Kepada:</label>
                                    <input type="text" class="form-control" id="kepada" name="kepada" value="DATO DR. ANDERSON TIONG ING HENG (Ketua Jabatan)" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <p><strong>Tuan,</strong></p>
                                    <h5>PERMOHONAN PENGUMPULAN BAKI CUTI BAGI FAEDAH GANTIAN CUTI REHAT (GCR)- TAHUN</h5>
                                    <p>Dengan hormatnya saya merujuk kepada perkara dia atas dan ingin memohon kelulusan tuan/puan untuk saya mengumpul baki cuti yang tidak dapat dihabiskan pada tahun 
                                        <select class="form-select d-inline-block" style="width: auto;" id="year" name="year" required>
                                            <option value="">Select Year</option>
                                            <?php 
                                            $current_year = date('Y');
                                            for($i = $current_year; $i >= $current_year - 5; $i--) {
                                                echo "<option value=\"$i\">$i</option>";
                                            }
                                            ?>
                                        </select>
                                        bagi tujuan faedah gentian cuti rehat (GCR). Disertakan bersama ini Lampiran A, Penyata Cuti Rehat, Kenyataan Cuti Rehat dan Senarai Semak Pemohon saya yang telah dikemaskini untuk tindakan tuan selanjutnya.
                                    </p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="applicant_name" class="form-label required-field">Nama:</label>
                                            <input type="text" class="form-control" id="applicant_name" name="applicant_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                            <div class="invalid-feedback">Please enter your name.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="applicant_position" class="form-label required-field">Jawatan/Gred:</label>
                                            <input type="text" class="form-control" id="applicant_position" name="applicant_position" value="<?php echo htmlspecialchars($user_position); ?>" required>
                                            <div class="invalid-feedback">Please enter your position/grade.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="applicant_department" class="form-label required-field">Pejabat:</label>
                                            <input type="text" class="form-control" id="applicant_department" name="applicant_department" value="<?php echo htmlspecialchars($user_department); ?>" required>
                                            <div class="invalid-feedback">Please enter your department.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="days_requested" class="form-label required-field">Jumlah Hari:</label>
                                            <input type="number" class="form-control" id="days_requested" name="days_requested" min="1" max="365" required>
                                            <div class="invalid-feedback">Please enter the number of days (1-365).</div>
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
                            </div>
                        </div>

                        <!-- Display inactive sections -->
                        <!-- HR Section B (Inactive) -->
                        <div class="form-section disabled-section" id="sectionB">
                            <div class="section-header">
                                <i class="bi bi-people me-2"></i> Bahagian B (Diisi oleh Pegawai Yang Diberi Kuasa)
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN/KEBENARAN KETUA JABATAN/BAHAGIAN</strong></p>
                                    <p>Saya mengesahkan bahawa ......................... dibenarkan untuk mengumpul baki cuti sebanyak ............ hari dari tahun ............ yang tidak dapat dihabiskan atas kepentingan perkhidmatan bagi faedah gantian cuti rehat (GCR) iaitu mengikut kelayakan beliau.</p>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama:</label>
                                            <input type="text" class="form-control" value="SOPHIA BINTI IDRIS" readonly>
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
                                            <input type="text" class="form-control" value="................" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tandatangan:</label>
                                            <div class="signature-box border rounded p-3 text-center">
                                                <p class="text-muted">Signature will appear here</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GM Section C (Inactive) -->
                        <div class="form-section disabled-section" id="sectionC">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> Bahagian C (Diisi oleh Ketua Jabatan/Bahagian)
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <p><strong>KEPUTUSAN PERMOHONAN GCR</strong></p>
                                    <p>Permohonan GCR ............. diluluskan ☐ sebanyak ..... hari / ditolak* ☐ kerana .............</p>
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
                                           <input type="text" class="form-control" value="................" readonly>
                                       </div>
                                       
                                       <div class="mb-3">
                                           <label class="form-label">Tandatangan:</label>
                                           <div class="signature-box border rounded p-3 text-center">
                                               <p class="text-muted">Signature will appear here</p>
                                           </div>
                                       </div>
                                   </div>
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
                                           <label class="form-label">Tandatangan:</label>
                                           <div class="signature-box border rounded p-3 text-center">
                                               <p class="text-muted">Signature will appear here</p>
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

                       <!-- HR3 Section E (Inactive) - Lampiran A -->
                        <div class="form-section disabled-section" id="sectionE">
                            <div class="section-header">
                                <i class="bi bi-file-earmark-text me-2"></i> Bahagian E (Lampiran A - Diisi oleh HR3)
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <p><strong>LAMPIRAN A</strong></p>
                                    <p class="fw-bold text-center">PENYATA PENGUMPULAN CUTI REHAT BAGI FAEDAH GANTIAN CUTI REHAT (GCR)</p>
                                    <p>Disediakan oleh Pegawai HR3 (OINIE ZAPHIA ANAK SAMAT) untuk mengesahkan baki cuti yang diluluskan.</p>
                                </div>
                            </div>
                        </div>

                        <!-- GM Final Section F (Inactive) -->
                        <div class="form-section disabled-section" id="sectionF">
                            <div class="section-header">
                                <i class="bi bi-check-circle me-2"></i> Bahagian F (Pengesahan Akhir oleh GM)
                                <i class="bi bi-lock-fill lock-icon float-end"></i>
                            </div>
                            <div class="card-body">
                                <div class="section-overlay"></div>
                                <div class="mb-3">
                                    <p><strong>PENGESAHAN AKHIR GM</strong></p>
                                    <p>Pengesahan akhir Lampiran A oleh General Manager untuk menyelesaikan proses permohonan GCR.</p>
                                </div>
                            </div>
                        </div>

                       <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4 mb-5">
                           <button type="button" class="btn btn-outline-secondary me-md-2 px-4 py-2" id="resetBtn">
                               <i class="bi bi-arrow-counterclockwise me-2"></i> Reset Form
                           </button>
                           <button type="submit" class="btn btn-success px-4 py-2" id="submitBtn">
                               <i class="bi bi-send me-2"></i> Submit Application
                           </button>
                       </div>
                    </form>
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
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'black'
    });
    
    // Clear signature button
    document.getElementById('clearSignature').addEventListener('click', function() {
        signaturePad.clear();
    });
    
    // Form validation
    const staffForm = document.getElementById('staffForm');
    
    if (staffForm) {
        staffForm.addEventListener('submit', function(event) {
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
    
    // Reset button functionality
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (staffForm) {
                staffForm.reset();
                signaturePad.clear();
                staffForm.classList.remove('was-validated');
            }
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
    
    // Add process steps styling
    const steps = document.querySelectorAll('.step');
    if (steps.length > 0) {
        steps[0].classList.add('active');
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